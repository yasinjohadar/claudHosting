<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyServiceComposeService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyServiceController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyServiceComposeService $compose
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listServices();
        $services = array_map(function (array $row): array {
            $links = $this->coolify->collectResourceAccessLinks($row, 'service');
            $row['_primary_url'] = $this->coolify->primaryResourceAccessLink($links, (string) ($row['name'] ?? null));

            return $row;
        }, $this->coolifyList($response));
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب الخدمات');

        return view('admin.coolify.services.index', compact('services', 'error'));
    }

    public function create()
    {
        $projects = $this->coolifyList($this->coolify->listProjects());
        $servers = $this->coolifyList($this->coolify->listServers());
        $typesResponse = $this->coolify->getServiceTypes();
        $serviceTypes = $this->coolifyList($typesResponse);
        if (empty($serviceTypes) && isset($typesResponse['data']) && is_array($typesResponse['data'])) {
            $serviceTypes = $typesResponse['data'];
        }

        return view('admin.coolify.services.create', compact('projects', 'servers', 'serviceTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'project_uuid' => 'required|string',
            'server_uuid' => 'required|string',
            'environment_name' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $response = $this->coolify->createService($validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء الخدمة');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            $this->logCoolify('create', 'service', $uuid, $validated['name'] ?? null);

            return $this->coolifyRedirectSuccess('تم إنشاء الخدمة', 'admin.coolify.services.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('create', 'service', null, $validated['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم الإنشاء', 'admin.coolify.services.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getService($uuid);
        $service = $this->coolifyItem($response);

        if (! $service) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.services.index');
        }

        $envs = $this->coolifyList($this->coolify->listServiceEnvs($uuid));
        $serverUuid = $this->coolify->extractResourceServerUuid($service);
        $serverResolved = $serverUuid !== '' ? $this->coolify->resolveResourceServer($service) : null;
        $accessLinks = $this->coolify->collectResourceAccessLinks($service, 'service');
        $primaryUrl = $this->coolify->primaryResourceAccessLink($accessLinks, (string) ($service['name'] ?? null));
        $coolifyPanelUrl = $this->coolify->coolifyPanelBaseUrl() ?: null;

        return view('admin.coolify.services.show', compact(
            'service',
            'uuid',
            'envs',
            'serverUuid',
            'serverResolved',
            'accessLinks',
            'primaryUrl',
            'coolifyPanelUrl'
        ));
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getService($uuid);
        $service = $this->coolifyItem($response);

        if (! $service) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.services.index');
        }

        return view('admin.coolify.services.edit', compact('service', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'public_url' => 'nullable|url|max:500',
        ]);

        $payload = array_filter([
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($validated['public_url'])) {
            $serviceResponse = $this->coolify->getService($uuid);
            $service = $this->coolifyItem($serviceResponse) ?? [];
            $payload['urls'] = $this->coolify->buildServiceUrlsForService($service, $validated['public_url']);
            $payload['force_domain_override'] = true;
        }

        $response = $this->coolify->updateService($uuid, $payload);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم التحديث', 'admin.coolify.services.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $response = $this->coolify->deleteService($uuid);
        $this->coolify->clearDashboardCache();

        if ($response['success'] ?? false) {
            \App\Models\CoolifyWordpressSite::query()
                ->where('service_uuid', $uuid)
                ->each(fn (\App\Models\CoolifyWordpressSite $site) => $site->delete());
            $this->logCoolify('delete', 'service', $uuid);
        }

        return $this->redirectAfterResourceDestroy(
            $response['success'] ?? false,
            ($response['success'] ?? false) ? 'تم حذف الخدمة' : ($response['message'] ?? 'فشل الحذف'),
            'admin.coolify.services.show',
            ['uuid' => $uuid],
            'admin.coolify.services.index'
        );
    }

    protected function lifecycle(string $uuid, string $action, ?string $returnRoute = null)
    {
        $response = match ($action) {
            'start' => $this->coolify->startService($uuid),
            'stop' => $this->coolify->stopService($uuid),
            default => $this->coolify->restartService($uuid),
        };

        $route = $returnRoute ?? 'admin.coolify.services.show';

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الإجراء', $route, ['uuid' => $uuid]);
        }

        return $this->coolifyRedirectSuccess('تم تنفيذ الإجراء', $route, ['uuid' => $uuid]);
    }

    public function start(string $uuid)
    {
        return $this->lifecycle($uuid, 'start', request()->input('_return') ? 'admin.coolify.services.index' : null);
    }

    public function stop(string $uuid)
    {
        return $this->lifecycle($uuid, 'stop', request()->input('_return') ? 'admin.coolify.services.index' : null);
    }

    public function restart(string $uuid)
    {
        return $this->lifecycle($uuid, 'restart', request()->input('_return') ? 'admin.coolify.services.index' : null);
    }

    public function redeploy(string $uuid)
    {
        $result = $this->compose->redeploy($uuid);
        $route = request()->input('_return') ? 'admin.coolify.services.index' : 'admin.coolify.services.show';

        if (! $result['success']) {
            return $this->coolifyRedirectError($result['message'], $route, ['uuid' => $uuid]);
        }

        return $this->coolifyRedirectSuccess($result['message'], $route, ['uuid' => $uuid]);
    }

    public function logs(string $uuid)
    {
        return view('admin.coolify.services.logs', ['uuid' => $uuid]);
    }

    public function logsFetch(string $uuid)
    {
        $response = $this->coolify->getService($uuid);
        $service = $this->coolifyItem($response);
        $logs = [];

        if ($service) {
            $logs = $this->coolify->fetchServiceApplicationLogs($service, (int) request('lines', 120));
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => (bool) $service, 'logs' => $logs]);
        }

        return view('admin.coolify.services.logs', compact('uuid', 'logs', 'service'));
    }

    public function storeEnv(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $response = $this->coolify->createServiceEnv($uuid, $validated);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل الإضافة');
        }

        return $this->coolifyRedirectSuccess('تم إضافة المتغير', 'admin.coolify.services.show', ['uuid' => $uuid]);
    }

    public function destroyEnv(string $uuid, string $envUuid)
    {
        $response = $this->coolify->deleteServiceEnv($uuid, $envUuid);

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الحذف', 'admin.coolify.services.show');
        }

        return $this->coolifyRedirectSuccess('تم حذف المتغير', 'admin.coolify.services.show', ['uuid' => $uuid]);
    }

    public function updateEnv(Request $request, string $uuid, string $envUuid)
    {
        $validated = $request->validate([
            'key' => 'sometimes|string',
            'value' => 'sometimes|string',
        ]);

        $response = $this->coolify->updateServiceEnv($uuid, $envUuid, $validated);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم تحديث المتغير', 'admin.coolify.services.show', ['uuid' => $uuid]);
    }

    public function bulkEnvs(Request $request, string $uuid)
    {
        $request->validate(['env_bulk' => 'required|string']);
        $envs = CoolifyApiService::parseEnvBulkText($request->input('env_bulk'));
        $response = $this->coolify->bulkUpdateServiceEnvs($uuid, ['data' => $envs]);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل bulk');
        }

        return $this->coolifyRedirectSuccess('تم تحديث المتغيرات', 'admin.coolify.services.show', ['uuid' => $uuid]);
    }
}
