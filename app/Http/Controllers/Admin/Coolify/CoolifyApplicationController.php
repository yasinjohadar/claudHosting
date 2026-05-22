<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyApplicationController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(protected CoolifyApiService $coolify)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listApplications();
        $applications = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب التطبيقات');

        return view('admin.coolify.applications.index', compact('applications', 'error'));
    }

    public function create(Request $request)
    {
        $projects = $this->coolifyList($this->coolify->listProjects());
        $servers = $this->coolifyList($this->coolify->listServers());
        $githubApps = $this->coolifyList($this->coolify->listGithubApps());
        $type = $request->get('type', 'public');
        $prefill = [
            'project_uuid' => $request->get('project_uuid'),
            'server_uuid' => $request->get('server_uuid'),
            'environment_name' => $request->get('environment_name', 'production'),
        ];

        return view('admin.coolify.applications.create', compact('projects', 'servers', 'githubApps', 'type', 'prefill'));
    }

    public function store(Request $request)
    {
        $type = $request->input('create_type', 'public');

        $base = $request->validate([
            'project_uuid' => 'required|string',
            'server_uuid' => 'required|string',
            'environment_name' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domains' => 'nullable|string',
            'git_repository' => 'nullable|string',
            'git_branch' => 'nullable|string',
            'build_pack' => 'nullable|string',
            'docker_registry_image_name' => 'nullable|string',
            'docker_registry_image_tag' => 'nullable|string',
            'dockerfile' => 'nullable|string',
            'docker_compose_raw' => 'nullable|string',
            'private_key_uuid' => 'nullable|string',
            'github_app_uuid' => 'nullable|string',
            'ports' => 'nullable|string',
            'instant_deploy' => 'nullable|boolean',
            'watch_paths' => 'nullable|string',
        ]);

        if (! empty($base['domains']) && is_string($base['domains'])) {
            $base['domains'] = array_map('trim', explode(',', $base['domains']));
        }

        $response = match ($type) {
            'private-github' => $this->coolify->createApplicationPrivateGithub($base),
            'private-key' => $this->coolify->createApplicationPrivateDeployKey($base),
            'dockerfile' => $this->coolify->createApplicationDockerfile($base),
            'docker-image' => $this->coolify->createApplicationDockerImage($base),
            'docker-compose' => $this->coolify->createApplicationDockerCompose($base),
            default => $this->coolify->createApplicationPublic($base),
        };

        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء التطبيق');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            $this->logCoolify('create', 'application', $uuid, $base['name'] ?? null);

            return $this->coolifyRedirectSuccess('تم إنشاء التطبيق', 'admin.coolify.applications.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('create', 'application', null, $base['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم إنشاء التطبيق', 'admin.coolify.applications.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getApplication($uuid);
        $application = $this->coolifyItem($response);

        if (! $application) {
            return $this->coolifyRedirectError($response['message'] ?? 'التطبيق غير موجود', 'admin.coolify.applications.index');
        }

        $envs = $this->coolifyList($this->coolify->listApplicationEnvs($uuid));
        $deployments = $this->coolifyList($this->coolify->listDeploymentsByApplication($uuid));

        return view('admin.coolify.applications.show', compact('application', 'uuid', 'envs', 'deployments'));
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getApplication($uuid);
        $application = $this->coolifyItem($response);

        if (! $application) {
            return $this->coolifyRedirectError($response['message'] ?? 'التطبيق غير موجود', 'admin.coolify.applications.index');
        }

        return view('admin.coolify.applications.edit', compact('application', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $response = $this->coolify->updateApplication($uuid, $validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم تحديث التطبيق', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $response = $this->coolify->deleteApplication($uuid);
        $this->coolify->clearDashboardCache();

        if ($response['success'] ?? false) {
            $this->logCoolify('delete', 'application', $uuid);
        }

        return $this->redirectAfterResourceDestroy(
            $response['success'] ?? false,
            $response['success'] ?? false ? 'تم حذف التطبيق' : ($response['message'] ?? 'فشل الحذف'),
            'admin.coolify.applications.show',
            ['uuid' => $uuid],
            'admin.coolify.applications.index'
        );
    }

    public function logs(string $uuid)
    {
        return view('admin.coolify.applications.logs', ['uuid' => $uuid]);
    }

    public function logsFetch(string $uuid)
    {
        $response = $this->coolify->applicationLogs($uuid, (int) request('lines', 200));

        if (request()->wantsJson()) {
            return response()->json($response);
        }

        return view('admin.coolify.applications.logs', [
            'uuid' => $uuid,
            'logs' => $response['data'] ?? $response['message'] ?? '',
            'success' => $response['success'] ?? false,
        ]);
    }

    public function start(string $uuid)
    {
        return $this->lifecycle($uuid, 'start');
    }

    public function stop(string $uuid)
    {
        return $this->lifecycle($uuid, 'stop');
    }

    public function restart(string $uuid)
    {
        return $this->lifecycle($uuid, 'restart');
    }

    public function deploy(Request $request, string $uuid)
    {
        $query = array_filter([
            'force' => $request->boolean('force') ? 'true' : null,
            'tag' => $request->input('tag'),
            'pr' => $request->input('pr'),
        ]);

        $response = $this->coolify->deploy($uuid, $query);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل النشر', 'admin.coolify.applications.show');
        }

        $this->logCoolify('deploy', 'application', $uuid, $request->input('name'));

        return $this->coolifyRedirectSuccess('تم بدء النشر', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    protected function lifecycle(string $uuid, string $action)
    {
        $response = match ($action) {
            'start' => $this->coolify->startApplication($uuid),
            'stop' => $this->coolify->stopApplication($uuid),
            default => $this->coolify->restartApplication($uuid),
        };

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الإجراء', 'admin.coolify.applications.show');
        }

        return $this->coolifyRedirectSuccess('تم تنفيذ الإجراء بنجاح', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    public function storeEnv(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
            'is_preview' => 'nullable|boolean',
            'is_literal' => 'nullable|boolean',
            'is_multiline' => 'nullable|boolean',
        ]);

        $response = $this->coolify->createApplicationEnv($uuid, $validated);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل إضافة المتغير');
        }

        return $this->coolifyRedirectSuccess('تم إضافة متغير البيئة', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    public function updateEnv(Request $request, string $uuid, string $envUuid)
    {
        $validated = $request->validate([
            'key' => 'sometimes|string',
            'value' => 'sometimes|string',
        ]);

        $response = $this->coolify->updateApplicationEnv($uuid, $envUuid, $validated);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم تحديث المتغير', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    public function destroyEnv(string $uuid, string $envUuid)
    {
        $response = $this->coolify->deleteApplicationEnv($uuid, $envUuid);

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الحذف', 'admin.coolify.applications.show');
        }

        return $this->coolifyRedirectSuccess('تم حذف المتغير', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }

    public function bulkEnvs(Request $request, string $uuid)
    {
        $request->validate(['env_bulk' => 'required|string']);
        $envs = CoolifyApiService::parseEnvBulkText($request->input('env_bulk'));
        $response = $this->coolify->bulkUpdateApplicationEnvs($uuid, ['data' => $envs]);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل bulk');
        }

        return $this->coolifyRedirectSuccess('تم تحديث المتغيرات', 'admin.coolify.applications.show', ['uuid' => $uuid]);
    }
}
