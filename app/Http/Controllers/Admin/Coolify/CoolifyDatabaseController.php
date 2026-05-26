<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Services\Coolify\CoolifyBackupService;
use App\Services\Coolify\CoolifyDatabaseRedeployService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyDatabaseController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyBackupService $backupService,
        protected CoolifyDatabaseRedeployService $redeployService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $response = $this->coolify->listDatabases();
        $databases = $this->coolifyList($response);
        $error = $response['success'] ? null : ($response['message'] ?? 'فشل جلب قواعد البيانات');

        return view('admin.coolify.databases.index', compact('databases', 'error'));
    }

    public function create(Request $request)
    {
        $projects = $this->coolifyList($this->coolify->listProjects());
        $servers = $this->coolifyList($this->coolify->listServers());
        $types = CoolifyApiService::databaseTypes();
        $type = $request->get('type', 'postgresql');

        return view('admin.coolify.databases.create', compact('projects', 'servers', 'types', 'type'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(CoolifyApiService::databaseTypes())),
            'project_uuid' => 'required|string',
            'server_uuid' => 'required|string',
            'environment_name' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type = $validated['type'];
        unset($validated['type']);

        $response = $this->coolify->createDatabase($type, $validated);
        $this->coolify->clearDashboardCache();

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء قاعدة البيانات');
        }

        $item = $this->coolifyItem($response);
        $uuid = $item ? $this->resourceUuid($item) : null;

        if ($uuid) {
            $this->logCoolify('create', 'database', $uuid, $validated['name'] ?? null);

            return $this->coolifyRedirectSuccess('تم إنشاء قاعدة البيانات', 'admin.coolify.databases.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('create', 'database', null, $validated['name'] ?? null);

        return $this->coolifyRedirectSuccess('تم الإنشاء', 'admin.coolify.databases.index');
    }

    public function show(string $uuid)
    {
        $response = $this->coolify->getDatabase($uuid);
        $database = $this->coolifyItem($response);

        if (! $database) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.databases.index');
        }

        $backupRows = $this->backupService->listConfigsForDatabase($uuid);
        $catalogInstallUrl = $this->redeployService->catalogInstallUrl($database);
        $accessLinks = $this->coolify->collectResourceAccessLinks($database, 'database');
        $primaryUrl = $this->coolify->primaryResourceAccessLink($accessLinks, (string) ($database['name'] ?? null));
        $coolifyPanelUrl = $this->coolify->coolifyPanelBaseUrl() ?: null;

        return view('admin.coolify.databases.show', compact(
            'database',
            'uuid',
            'backupRows',
            'catalogInstallUrl',
            'accessLinks',
            'primaryUrl',
            'coolifyPanelUrl'
        ));
    }

    public function edit(string $uuid)
    {
        $response = $this->coolify->getDatabase($uuid);
        $database = $this->coolifyItem($response);

        if (! $database) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.databases.index');
        }

        return view('admin.coolify.databases.edit', compact('database', 'uuid'));
    }

    public function update(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $response = $this->coolify->updateDatabase($uuid, $validated);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل التحديث');
        }

        return $this->coolifyRedirectSuccess('تم التحديث', 'admin.coolify.databases.show', ['uuid' => $uuid]);
    }

    public function destroy(string $uuid)
    {
        $response = $this->coolify->deleteDatabase($uuid);
        $this->coolify->clearDashboardCache();

        if ($response['success'] ?? false) {
            $this->logCoolify('delete', 'database', $uuid);
        }

        return $this->redirectAfterResourceDestroy(
            $response['success'] ?? false,
            $response['success'] ?? false ? 'تم حذف قاعدة البيانات' : ($response['message'] ?? 'فشل الحذف'),
            'admin.coolify.databases.show',
            ['uuid' => $uuid],
            'admin.coolify.databases.index'
        );
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

    public function redeploy(string $uuid)
    {
        $result = $this->redeployService->redeploy($uuid);

        if (! ($result['success'] ?? false)) {
            return $this->coolifyRedirectError($result['message'] ?? 'فشل إعادة النشر', 'admin.coolify.databases.show', ['uuid' => $uuid]);
        }

        $this->coolify->clearDashboardCache();
        $this->logCoolify('redeploy', 'database', $uuid);

        return $this->coolifyRedirectSuccess($result['message'] ?? 'تمت إعادة النشر', 'admin.coolify.databases.show', ['uuid' => $uuid]);
    }

    public function reinstall(Request $request, string $uuid)
    {
        $response = $this->coolify->getDatabase($uuid);
        $database = $this->coolifyItem($response);

        if (! $database) {
            return $this->coolifyRedirectError($response['message'] ?? 'غير موجود', 'admin.coolify.databases.index');
        }

        $validated = $request->validate([
            'confirm_name' => 'required|string',
        ]);

        $expected = trim((string) ($database['name'] ?? ''));
        if ($expected === '' || $validated['confirm_name'] !== $expected) {
            return back()->with('error', 'اكتب اسم قاعدة البيانات بالضبط للتأكيد: '.$expected);
        }

        $result = $this->redeployService->reinstall($database);

        if (! ($result['success'] ?? false)) {
            return $this->coolifyRedirectError($result['message'] ?? 'فشل إعادة التثبيت', 'admin.coolify.databases.show', ['uuid' => $uuid]);
        }

        $this->logCoolify('reinstall', 'database', $uuid, $expected);

        $newUuid = $result['uuid'] ?? null;
        if ($newUuid) {
            return $this->coolifyRedirectSuccess(
                'تم حذف المورد القديم وإنشاء نسخة جديدة. قد تستغرق الدقائق الأولى حتى تصبح running.',
                'admin.coolify.databases.show',
                ['uuid' => $newUuid]
            );
        }

        return $this->coolifyRedirectSuccess($result['message'] ?? 'تمت إعادة التثبيت', 'admin.coolify.databases.index');
    }

    protected function lifecycle(string $uuid, string $action)
    {
        $response = match ($action) {
            'start' => $this->coolify->startDatabase($uuid),
            'stop' => $this->coolify->stopDatabase($uuid),
            default => $this->coolify->restartDatabase($uuid),
        };

        if (! $response['success']) {
            return $this->coolifyRedirectError($response['message'] ?? 'فشل الإجراء', 'admin.coolify.databases.show');
        }

        return $this->coolifyRedirectSuccess('تم تنفيذ الإجراء', 'admin.coolify.databases.show', ['uuid' => $uuid]);
    }

    public function storeBackup(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'save_s3' => 'nullable|boolean',
            'frequency' => 'nullable|string|max:255',
            'backup_now' => 'nullable|boolean',
        ]);

        $payload = $this->backupService->backupPayloadFromRequest(array_merge($validated, [
            'enabled' => $request->boolean('enabled', true),
            'save_s3' => $request->boolean('save_s3'),
            'backup_now' => $request->boolean('backup_now'),
            'frequency' => $request->input('frequency', 'daily'),
        ]));

        $response = $this->coolify->createDatabaseBackup($uuid, $payload);

        if (! $response['success']) {
            return back()->with('error', $response['message'] ?? 'فشل إنشاء النسخ الاحتياطي');
        }

        $this->backupService->clearCache($uuid);
        $this->logCoolify('backup', 'database', $uuid, null, $request->boolean('backup_now') ? 'نسخ الآن' : 'جدولة');

        return $this->coolifyRedirectSuccess('تم جدولة النسخ الاحتياطي', 'admin.coolify.databases.show', ['uuid' => $uuid]);
    }
}
