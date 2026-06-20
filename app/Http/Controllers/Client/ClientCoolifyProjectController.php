<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientCoolifyProject;
use App\Services\Coolify\CoolifyTeamService;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientCoolifyProjectController extends Controller
{
    public function __construct(
        protected CoolifyTeamService $teamService,
        protected CoolifyApiService $coolify
    ) {
        $this->middleware('auth');
    }

    public function show(string $uuid): View|RedirectResponse
    {
        $user = auth()->user();
        $access = $this->assertAccess($user->id, $uuid);
        if (! $access['success']) {
            abort(403, $access['message']);
        }

        $api = $this->teamService->apiForUser($user->id) ?? $this->coolify;
        $projectRes = $api->getProject($uuid);
        $project = is_array($projectRes['data'] ?? null) ? $projectRes['data'] : null;

        if (! $project) {
            return redirect()->route('client.services')->with('error', 'المشروع غير موجود أو غير متاح');
        }

        $resources = $api->normalizeList($api->projectResources($uuid)['data'] ?? []);
        $applications = array_values(array_filter($resources, fn ($r) => in_array($r['type'] ?? '', ['application', 'applications'], true) || isset($r['git_repository'])));
        $services = array_values(array_filter($resources, fn ($r) => ($r['type'] ?? '') === 'service'));
        $databases = array_values(array_filter($resources, fn ($r) => ($r['type'] ?? '') === 'database'));

        $deploymentsByApp = [];
        if (config('coolify.client_portal.actions.view_deployments', true)) {
            foreach ($applications as $app) {
                $appUuid = (string) ($app['uuid'] ?? '');
                if ($appUuid === '') {
                    continue;
                }
                $depRes = $api->listDeploymentsByApplication($appUuid);
                $deploymentsByApp[$appUuid] = array_slice($api->normalizeList($depRes['data'] ?? []), 0, 5);
            }
        }

        $actions = config('coolify.client_portal.actions', []);

        return view('client.pages.coolify-project', compact(
            'project',
            'uuid',
            'applications',
            'services',
            'databases',
            'actions',
            'deploymentsByApp'
        ));
    }

    public function deployApplication(Request $request, string $uuid, string $appUuid): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.deploy', true)) {
            abort(403);
        }

        $api = $this->apiForProject($uuid);
        $response = $api->deploy($appUuid);

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم بدء النشر' : ($response['message'] ?? 'فشل النشر')
        );
    }

    public function restartApplication(string $uuid, string $appUuid): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.restart', false)) {
            abort(403);
        }

        $api = $this->apiForProject($uuid);
        $response = $api->restartApplication($appUuid);

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم إعادة التشغيل' : ($response['message'] ?? 'فشل الإجراء')
        );
    }

    public function applicationLogs(string $uuid, string $appUuid): JsonResponse
    {
        if (! config('coolify.client_portal.actions.view_logs', true)) {
            abort(403);
        }

        $api = $this->apiForProject($uuid);
        $response = $api->applicationLogs($appUuid, (int) request('lines', 150));

        return response()->json($response);
    }

    public function applicationDeployments(string $uuid, string $appUuid): JsonResponse
    {
        if (! config('coolify.client_portal.actions.view_deployments', true)) {
            abort(403);
        }

        $api = $this->apiForProject($uuid);
        $response = $api->listDeploymentsByApplication($appUuid);

        return response()->json($response);
    }

    public function serviceLifecycle(string $uuid, string $serviceUuid, string $action): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.service_lifecycle', true)) {
            abort(403);
        }

        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            abort(404);
        }

        $api = $this->apiForProject($uuid);
        $response = match ($action) {
            'start' => $api->startService($serviceUuid),
            'stop' => $api->stopService($serviceUuid),
            default => $api->restartService($serviceUuid),
        };

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم تنفيذ الإجراء على الخدمة' : ($response['message'] ?? 'فشل الإجراء')
        );
    }

    public function serviceLogs(string $uuid, string $serviceUuid): JsonResponse
    {
        if (! config('coolify.client_portal.actions.service_logs', true)) {
            abort(403);
        }

        $api = $this->apiForProject($uuid);
        $response = $api->getService($serviceUuid);
        $service = is_array($response['data'] ?? null) ? $response['data'] : null;
        if (! $service) {
            return response()->json(['success' => false, 'message' => 'الخدمة غير موجودة'], 404);
        }

        $logs = $api->fetchServiceApplicationLogs($service, (int) request('lines', 120));

        return response()->json(['success' => true, 'data' => $logs]);
    }

    public function databaseLifecycle(string $uuid, string $databaseUuid, string $action): RedirectResponse
    {
        if (! config('coolify.client_portal.actions.database_lifecycle', true)) {
            abort(403);
        }

        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            abort(404);
        }

        $api = $this->apiForProject($uuid);
        $response = match ($action) {
            'start' => $api->startDatabase($databaseUuid),
            'stop' => $api->stopDatabase($databaseUuid),
            default => $api->restartDatabase($databaseUuid),
        };

        return back()->with(
            ($response['success'] ?? false) ? 'success' : 'error',
            ($response['success'] ?? false) ? 'تم تنفيذ الإجراء على قاعدة البيانات' : ($response['message'] ?? 'فشل الإجراء')
        );
    }

    protected function apiForProject(string $projectUuid): CoolifyApiService
    {
        $user = auth()->user();
        $access = $this->assertAccess($user->id, $projectUuid);
        if (! $access['success']) {
            abort(403, $access['message']);
        }

        return $this->teamService->apiForUser($user->id) ?? $this->coolify;
    }

    /**
     * @return array{success: bool, message?: string}
     */
    protected function assertAccess(int $userId, string $projectUuid): array
    {
        $assigned = ClientCoolifyProject::query()
            ->where('user_id', $userId)
            ->where('project_uuid', $projectUuid)
            ->exists();

        if (! $assigned) {
            return ['success' => false, 'message' => 'المشروع غير مرتبط بحسابك'];
        }

        return $this->teamService->assertProjectInClientTeam($userId, $projectUuid);
    }
}
