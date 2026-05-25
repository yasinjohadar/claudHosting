<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifySnapshotSchedule;
use App\Services\Coolify\CoolifyBackupService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyBackupController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyBackupService $backups,
        protected CoolifySettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'hub');
        $configured = $this->backups->isConfigured();

        if ($tab === 'hub') {
            $readiness = $this->settings->getSnapshotReadiness();
            $hubStats = [
                'snapshots_total' => CoolifyProjectSnapshot::query()->count(),
                'snapshots_running' => CoolifyProjectSnapshot::query()->whereIn('status', ['pending', 'running'])->count(),
                'snapshots_failed' => CoolifyProjectSnapshot::query()->whereIn('status', ['failed', 'partial'])->count(),
                'schedules_total' => CoolifySnapshotSchedule::query()->count(),
                'schedules_enabled' => CoolifySnapshotSchedule::query()->where('enabled', true)->count(),
            ];

            return view('admin.coolify.backups.hub', compact('configured', 'readiness', 'hubStats'));
        }

        if (! $configured) {
            return $this->coolifyRedirectError(
                'يرجى ضبط إعدادات Coolify من لوحة التحكم → إعدادات Coolify.',
                'admin.coolify.settings.index'
            );
        }

        $filters = $request->only(['database_uuid', 'status', 'q', 'enabled_only', 's3_only']);
        $filters['enabled_only'] = $request->boolean('enabled_only');
        $filters['s3_only'] = $request->boolean('s3_only');

        $dashboard = $this->backups->aggregateDashboard(
            $filters,
            $request->boolean('refresh')
        );

        $databases = $this->backups->listDatabases();

        return view('admin.coolify.backups.index', [
            'tab' => 'databases',
            'backupConfigured' => $configured,
            'rows' => $dashboard['rows'],
            'stats' => $dashboard['stats'],
            'error' => $dashboard['error'],
            'filters' => $filters,
            'databases' => $databases,
            'frequencies' => CoolifyBackupService::FREQUENCIES,
        ]);
    }

    public function create(Request $request)
    {
        if (! $this->backups->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        return view('admin.coolify.backups.create', [
            'databases' => $this->backups->listDatabases(),
            'frequencies' => CoolifyBackupService::FREQUENCIES,
            'databaseUuid' => $request->query('database_uuid'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBackupConfig($request);
        $databaseUuid = $validated['database_uuid'];
        unset($validated['database_uuid']);
        $validated = $this->applyFrequencyCustom($request, $validated);

        $payload = $this->backups->backupPayloadFromRequest($validated);

        $response = $this->coolify->createDatabaseBackup($databaseUuid, $payload);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل إنشاء جدولة النسخ');
        }

        $this->backups->clearCache();
        $this->logCoolify('backup_create', 'database_backup', $databaseUuid, null, $payload['frequency'] ?? null);

        return $this->coolifyRedirectSuccess(
            'تم إنشاء جدولة النسخ الاحتياطي',
            'admin.coolify.backups.index'
        );
    }

    public function show(string $databaseUuid, string $configUuid)
    {
        if (! $this->backups->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $config = $this->backups->findConfig($databaseUuid, $configUuid);

        if (! $config) {
            return $this->coolifyRedirectError('جدولة النسخ غير موجودة', 'admin.coolify.backups.index');
        }

        $executions = $this->backups->listExecutions($databaseUuid, $configUuid, request()->boolean('refresh'));

        return view('admin.coolify.backups.show', [
            'config' => $config,
            'databaseUuid' => $databaseUuid,
            'configUuid' => $configUuid,
            'executions' => $executions,
            'frequencies' => CoolifyBackupService::FREQUENCIES,
        ]);
    }

    public function edit(string $databaseUuid, string $configUuid)
    {
        $config = $this->backups->findConfig($databaseUuid, $configUuid);

        if (! $config) {
            return $this->coolifyRedirectError('جدولة النسخ غير موجودة', 'admin.coolify.backups.index');
        }

        return view('admin.coolify.backups.edit', [
            'config' => $config,
            'databaseUuid' => $databaseUuid,
            'configUuid' => $configUuid,
            'frequencies' => CoolifyBackupService::FREQUENCIES,
        ]);
    }

    public function update(Request $request, string $databaseUuid, string $configUuid)
    {
        $validated = $this->validateBackupConfig($request, true);
        $validated = $this->applyFrequencyCustom($request, $validated);
        $payload = $this->backups->backupPayloadFromRequest($validated, true);

        $response = $this->coolify->updateDatabaseBackup($databaseUuid, $configUuid, $payload);

        if (! $response['success']) {
            return back()->withInput()->with('error', $response['message'] ?? 'فشل تحديث الجدولة');
        }

        $this->backups->clearCache($databaseUuid, $configUuid);
        $this->logCoolify('backup_update', 'database_backup', $configUuid, $databaseUuid);

        return $this->coolifyRedirectSuccess(
            'تم تحديث جدولة النسخ',
            'admin.coolify.backups.show',
            ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
        );
    }

    public function run(string $databaseUuid, string $configUuid)
    {
        $response = $this->coolify->updateDatabaseBackup($databaseUuid, $configUuid, ['backup_now' => true]);

        if (! $response['success']) {
            return $this->coolifyRedirectError(
                $response['message'] ?? 'فشل تشغيل النسخ',
                'admin.coolify.backups.show',
                ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
            );
        }

        $this->backups->clearCache($databaseUuid, $configUuid);
        $this->logCoolify('backup_run', 'database_backup', $configUuid, $databaseUuid, 'نسخ الآن');

        return $this->coolifyRedirectSuccess(
            'تم إرسال طلب النسخ الاحتياطي',
            'admin.coolify.backups.show',
            ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
        );
    }

    public function destroyConfig(Request $request, string $databaseUuid, string $configUuid)
    {
        $deleteS3 = $request->boolean('delete_s3');

        $response = $this->coolify->deleteDatabaseBackup($databaseUuid, $configUuid, $deleteS3);

        if (! $response['success']) {
            return $this->coolifyRedirectError(
                $response['message'] ?? 'فشل حذف الجدولة',
                'admin.coolify.backups.show',
                ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
            );
        }

        $this->backups->clearCache($databaseUuid, $configUuid);
        $this->logCoolify('backup_delete_config', 'database_backup', $configUuid, $databaseUuid);

        return $this->coolifyRedirectSuccess('تم حذف جدولة النسخ', 'admin.coolify.backups.index');
    }

    public function destroyExecution(Request $request, string $databaseUuid, string $configUuid, string $executionUuid)
    {
        $deleteS3 = $request->boolean('delete_s3');

        $response = $this->coolify->deleteDatabaseBackupExecution(
            $databaseUuid,
            $configUuid,
            $executionUuid,
            $deleteS3
        );

        if (! $response['success']) {
            return $this->coolifyRedirectError(
                $response['message'] ?? 'فشل حذف التنفيذ',
                'admin.coolify.backups.show',
                ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
            );
        }

        $this->backups->clearCache($databaseUuid, $configUuid);
        $this->logCoolify('backup_delete_execution', 'database_backup', $executionUuid, $databaseUuid);

        return $this->coolifyRedirectSuccess(
            'تم حذف تنفيذ النسخ',
            'admin.coolify.backups.show',
            ['databaseUuid' => $databaseUuid, 'configUuid' => $configUuid]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateBackupConfig(Request $request, bool $partial = false): array
    {
        $rules = [
            'database_uuid' => ($partial ? 'sometimes|' : 'required|').'string',
            'frequency' => ($partial ? 'sometimes|' : 'required|').'string|max:255',
            'enabled' => 'nullable|boolean',
            'save_s3' => 'nullable|boolean',
            's3_storage_uuid' => 'nullable|string|max:255',
            'databases_to_backup' => 'nullable|string|max:500',
            'dump_all' => 'nullable|boolean',
            'backup_now' => 'nullable|boolean',
            'database_backup_retention_amount_locally' => 'nullable|integer|min:0',
            'database_backup_retention_days_locally' => 'nullable|integer|min:0',
            'database_backup_retention_max_storage_locally' => 'nullable|integer|min:0',
            'database_backup_retention_amount_s3' => 'nullable|integer|min:0',
            'database_backup_retention_days_s3' => 'nullable|integer|min:0',
            'database_backup_retention_max_storage_s3' => 'nullable|integer|min:0',
            'timeout' => 'nullable|integer|min:60|max:86400',
        ];

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function applyFrequencyCustom(Request $request, array $validated): array
    {
        $custom = trim((string) $request->input('frequency_custom', ''));
        if ($custom !== '') {
            $validated['frequency'] = $custom;
        }

        return $validated;
    }
}
