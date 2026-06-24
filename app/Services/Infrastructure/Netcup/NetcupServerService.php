<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupServerService
{
    use NetcupScpHelpers;

    public function __construct(protected NetcupScpClient $client) {}

    /**
     * @return array{success: bool, message: string, instances?: list<array<string, mixed>>}
     */
    public function listInstances(): array
    {
        $instances = [];
        $offset = 0;
        $limit = 100;

        do {
            $res = $this->client->request('GET', '/servers', ['limit' => $limit, 'offset' => $offset]);
            if (! ($res['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $res['message'] ?? 'فشل',
                    'instances' => $instances,
                ];
            }

            $rows = $this->extractList($res['body'] ?? [], 'data', 'servers');
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $id = (string) ($row['id'] ?? $row['serverId'] ?? '');
                if ($id === '') {
                    continue;
                }

                $detail = $this->getInstance('scp:'.$id, true);
                if (($detail['success'] ?? false) && isset($detail['instance']) && is_array($detail['instance'])) {
                    $instances[] = $detail['instance'];

                    continue;
                }

                $mapped = $this->mapServerRow($row);
                if ($mapped !== null) {
                    $instances[] = $mapped;
                }
            }

            $offset += $limit;
        } while (count($rows) >= $limit);

        return ['success' => true, 'message' => 'OK', 'instances' => $instances];
    }

    /**
     * @return array{success: bool, message: string, instance?: array<string, mixed>}
     */
    public function getInstance(string $externalId, bool $live = true): array
    {
        $serverId = $this->parseServerId($externalId);
        $query = $live ? ['loadServerLiveInfo' => 'true'] : [];
        $res = $this->client->request('GET', '/servers/'.$serverId, $query);

        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل'];
        }

        $row = is_array($res['body']) ? ($res['body']['data'] ?? $res['body']) : null;
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'السيرفر غير موجود'];
        }

        $mapped = $this->mapServerRow($row);

        return $mapped
            ? ['success' => true, 'message' => 'OK', 'instance' => $mapped]
            : ['success' => false, 'message' => 'تعذّر قراءة السيرفر'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    public function update(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('PATCH', '/servers/'.$serverId, [], $payload),
            'تم تحديث السيرفر'
        );
    }

    public function guestAgent(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/guest-agent'));
    }

    public function guestAgentStatus(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/guest-agent/status'));
    }

    public function logs(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/logs'));
    }

    public function gpuDriver(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/gpu-driver'));
    }

    public function getRescueSystem(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/rescuesystem'));
    }

    public function activateRescueSystem(string $externalId, array $payload = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/rescuesystem', [], $payload),
            'تم تفعيل نظام الإنقاذ'
        );
    }

    public function deactivateRescueSystem(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('DELETE', '/servers/'.$serverId.'/rescuesystem'),
            'تم إلغاء نظام الإنقاذ'
        );
    }

    public function optimizeStorage(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/storageoptimization'),
            'تم طلب تحسين التخزين'
        );
    }
}
