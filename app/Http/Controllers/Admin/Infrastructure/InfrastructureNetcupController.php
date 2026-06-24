<?php

namespace App\Http\Controllers\Admin\Infrastructure;

use App\Http\Controllers\Controller;
use App\Jobs\NetcupScpActionJob;
use App\Models\VpsActionLog;
use App\Models\VpsServer;
use App\Services\Infrastructure\Netcup\NetcupDiskService;
use App\Services\Infrastructure\Netcup\NetcupFirewallService;
use App\Services\Infrastructure\Netcup\NetcupImageService;
use App\Services\Infrastructure\Netcup\NetcupIsoService;
use App\Services\Infrastructure\Netcup\NetcupMetricsApiService;
use App\Services\Infrastructure\Netcup\NetcupNetworkService;
use App\Services\Infrastructure\Netcup\NetcupScpClient;
use App\Services\Infrastructure\Netcup\NetcupServerService;
use App\Services\Infrastructure\Netcup\NetcupSnapshotService;
use App\Services\Infrastructure\Netcup\NetcupTaskService;
use App\Services\Infrastructure\Netcup\NetcupUserService;
use App\Services\Infrastructure\VpsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfrastructureNetcupController extends Controller
{
    public function __construct(
        protected NetcupServerService $servers,
        protected NetcupImageService $images,
        protected NetcupDiskService $disks,
        protected NetcupSnapshotService $snapshots,
        protected NetcupFirewallService $firewall,
        protected NetcupIsoService $iso,
        protected NetcupNetworkService $network,
        protected NetcupMetricsApiService $metrics,
        protected NetcupTaskService $tasks,
        protected NetcupUserService $users,
        protected NetcupScpClient $client,
        protected VpsSyncService $sync
    ) {
        $this->middleware('auth');
    }

    public function overview(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $live = $this->servers->getInstance($server->external_id, true);
        $guest = $this->servers->guestAgentStatus($server->external_id);

        return $this->json([
            'server' => $server->only(['uuid', 'name', 'ip', 'status', 'region', 'external_id', 'metadata']),
            'live' => $live['instance'] ?? null,
            'guest_agent' => $guest['data'] ?? null,
        ]);
    }

    public function updateServer(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $payload = $request->validate([
            'nickname' => 'nullable|string|max:255',
            'hostname' => 'nullable|string|max:255',
            'autostart' => 'nullable|boolean',
        ]);

        $res = $this->servers->update($server->external_id, array_filter($payload, fn ($v) => $v !== null));
        if ($res['success']) {
            $this->sync->refreshOne($server);
        }

        return $this->json($res, $res['success'] ? 200 : 422);
    }

    public function snapshots(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->snapshots->list($server->external_id));
    }

    public function storeSnapshot(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $payload = $request->validate(['name' => 'required|string|max:128']);

        return $this->json($this->snapshots->create($server->external_id, $payload));
    }

    public function destroySnapshot(Request $request, string $uuid, string $name): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->snapshots->delete($server->external_id, $name));
    }

    public function revertSnapshot(Request $request, string $uuid, string $name): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->snapshots->revert($server->external_id, $name));
    }

    public function disks(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->disks->list($server->external_id));
    }

    public function formatDisk(Request $request, string $uuid, string $diskName): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->disks->format($server->external_id, $diskName, $request->all()));
    }

    public function networkInterfaces(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->network->listInterfaces($server->external_id));
    }

    public function rdns(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $data = $request->validate([
            'ip' => 'required|string',
            'type' => 'required|in:ipv4,ipv6',
            'hostname' => 'nullable|string|max:255',
            'action' => 'required|in:get,set,delete',
        ]);

        $res = match ($data['action']) {
            'get' => $data['type'] === 'ipv4'
                ? $this->network->getRdnsIpv4($data['ip'])
                : $this->network->getRdnsIpv6($data['ip']),
            'set' => $data['type'] === 'ipv4'
                ? $this->network->setRdnsIpv4(['ip' => $data['ip'], 'hostname' => $data['hostname'] ?? ''])
                : $this->network->setRdnsIpv6(['ip' => $data['ip'], 'hostname' => $data['hostname'] ?? '']),
            'delete' => $data['type'] === 'ipv4'
                ? $this->network->deleteRdnsIpv4($data['ip'])
                : $this->network->deleteRdnsIpv6($data['ip']),
        };

        return $this->json($res);
    }

    public function firewall(Request $request, string $uuid, string $mac): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        if ($request->isMethod('GET')) {
            return $this->json($this->firewall->getInterfaceFirewall($server->external_id, $mac));
        }

        return $this->json($this->firewall->putInterfaceFirewall($server->external_id, $mac, $request->all()));
    }

    public function firewallReapply(Request $request, string $uuid, string $mac): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->firewall->reapplyInterfaceFirewall($server->external_id, $mac));
    }

    public function iso(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        if ($request->isMethod('GET')) {
            return $this->json($this->iso->getServerIso($server->external_id));
        }
        if ($request->isMethod('DELETE')) {
            return $this->json($this->iso->detachIso($server->external_id));
        }

        return $this->json($this->iso->attachIso($server->external_id, $request->all()));
    }

    public function isoImages(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->iso->listIsoImages($server->external_id));
    }

    public function rescue(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        if ($request->isMethod('GET')) {
            return $this->json($this->servers->getRescueSystem($server->external_id));
        }
        if ($request->isMethod('DELETE')) {
            return $this->json($this->servers->deactivateRescueSystem($server->external_id));
        }

        return $this->json($this->servers->activateRescueSystem($server->external_id, $request->all()));
    }

    public function scpMetrics(Request $request, string $uuid, string $type): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $validated = $request->validate([
            'hours' => 'nullable|integer|min:1|max:1440',
        ]);
        $query = $this->metrics->metricsQuery($validated);

        $res = match ($type) {
            'cpu' => $this->metrics->cpu($server->external_id, $query),
            'disk' => $this->metrics->disk($server->external_id, $query),
            'network' => $this->metrics->network($server->external_id, $query),
            'packets' => $this->metrics->networkPackets($server->external_id, $query),
            default => ['success' => false, 'message' => 'نوع مقياس غير معروف', 'data' => null, 'task_uuid' => null],
        };

        if (($res['success'] ?? false) && $this->metrics->isEmptyPayload($res['data'] ?? null)) {
            $guest = $this->servers->guestAgentStatus($server->external_id);
            $res['meta'] = [
                'hours' => $query['hours'],
                'empty' => true,
                'guest_agent' => $guest['data'] ?? null,
                'hint' => 'لا توجد نقاط في الفترة المحددة. تأكد أن الخادم يعمل وأن QEMU Guest Agent مفعّل.',
            ];
        } else {
            $res['meta'] = ['hours' => $query['hours'], 'empty' => false];
        }

        return $this->json($res);
    }

    public function taskList(Request $request, string $uuid): JsonResponse
    {
        $this->netcupServer($uuid);

        return $this->json($this->tasks->list($request->only(['limit', 'offset'])));
    }

    public function taskShow(Request $request, string $uuid, string $taskUuid): JsonResponse
    {
        $this->netcupServer($uuid);

        return $this->json($this->tasks->get($taskUuid));
    }

    public function taskCancel(Request $request, string $uuid, string $taskUuid): JsonResponse
    {
        $this->netcupServer($uuid);

        return $this->json($this->tasks->cancel($taskUuid));
    }

    public function logs(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->servers->logs($server->external_id));
    }

    public function imageFlavours(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);

        return $this->json($this->images->listFlavours($server->external_id));
    }

    public function setupImage(Request $request, string $uuid): JsonResponse
    {
        $server = $this->netcupServer($uuid);
        $data = $request->validate([
            'imageFlavourId' => 'required|string',
            'hostname' => 'nullable|string|max:255',
            'disk' => 'nullable|string|max:64',
        ]);

        $res = $this->images->setupImage($server->external_id, $data['imageFlavourId'], $data);
        if ($res['success'] && ($res['task_uuid'] ?? null)) {
            NetcupScpActionJob::dispatch($server->id, $res['task_uuid']);
        }
        if ($res['success']) {
            $this->sync->refreshOne($server);
        }

        return $this->json($res);
    }

    public function sshKeys(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            return $this->json($this->users->listSshKeys());
        }

        $payload = $request->validate([
            'name' => 'required|string|max:128',
            'publicKey' => 'required|string',
        ]);

        return $this->json($this->users->createSshKey($payload));
    }

    public function deleteSshKey(Request $request, string $id): JsonResponse
    {
        return $this->json($this->users->deleteSshKey($id));
    }

    public function maintenance(): JsonResponse
    {
        return $this->json($this->client->maintenance());
    }

    public function ping(): JsonResponse
    {
        return $this->json($this->client->ping());
    }

    protected function netcupServer(string $uuid): VpsServer
    {
        $server = VpsServer::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($server->provider === 'netcup', 404);

        return $server;
    }

    protected function json(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }
}
