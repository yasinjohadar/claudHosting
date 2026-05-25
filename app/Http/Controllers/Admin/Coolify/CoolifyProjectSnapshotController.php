<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Jobs\RestoreProjectSnapshotJob;
use App\Jobs\RunProjectSnapshotJob;
use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifySnapshotSchedule;
use App\Services\Coolify\CoolifyBackupService;
use App\Services\Coolify\CoolifyProjectBackupPlanner;
use App\Services\Coolify\CoolifyProjectSnapshotService;
use App\Services\Coolify\CoolifySnapshotStorageService;
use App\Services\Coolify\CoolifyScheduledSnapshotService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoolifyProjectSnapshotController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyBackupService $backups,
        protected CoolifyProjectBackupPlanner $planner,
        protected CoolifyProjectSnapshotService $snapshots,
        protected CoolifySnapshotStorageService $snapshotStorage,
        protected CoolifySettingsService $coolifySettings,
        protected CoolifyScheduledSnapshotService $scheduledSnapshots
    ) {
        $this->middleware('auth');
    }

    public function projectsIndex()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $projects = $this->coolify->normalizeList($this->coolify->listProjects()['data'] ?? []);
        $recentSnapshots = CoolifyProjectSnapshot::with('creator')->latest()->limit(10)->get();

        return view('admin.coolify.backups.projects.index', compact('projects', 'recentSnapshots'));
    }

    public function wizard(Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $projects = $this->coolify->normalizeList($this->coolify->listProjects()['data'] ?? []);

        if ($this->coolify->isConfigured()) {
            $this->coolifySettings->syncSnapshotStorageFromCoolify($this->coolify);
        }

        $readiness = $this->coolifySettings->getSnapshotReadiness();
        if (! $readiness['ready']) {
            return $this->coolifyRedirectError(
                'اتصال API جاهز. لإنشاء لقطة اختر سجل S3 من «ربط الأقراص» في إعدادات Coolify.',
                'admin.coolify.settings.index'
            );
        }

        return view('admin.coolify.backups.projects.wizard', [
            'projects' => $projects,
            'frequencies' => CoolifyBackupService::FREQUENCIES,
            'preselectedProject' => $request->query('project_uuid'),
        ]);
    }

    public function plan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => 'required|in:all_projects,single_project,custom',
            'project_uuid' => 'nullable|string',
            'resource_uuids' => 'nullable|array',
            'include_databases' => 'nullable|boolean',
            'include_applications' => 'nullable|boolean',
            'include_services' => 'nullable|boolean',
        ]);

        $validated['include_databases'] = $request->boolean('include_databases', true);
        $validated['include_applications'] = $request->boolean('include_applications', true);
        $validated['include_services'] = $request->boolean('include_services', true);

        $plan = $this->planner->buildPlan($validated);

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'message' => $plan === []
                ? 'لم يُعثر على موارد مطابقة للخيارات المحددة.'
                : null,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->coolify->isConfigured()) {
            $this->coolifySettings->syncSnapshotStorageFromCoolify($this->coolify);
        }

        $validated = $request->validate([
            'scope' => 'required|in:all_projects,single_project,custom',
            'project_uuid' => 'nullable|string',
            'project_name' => 'nullable|string',
            'name' => 'required|string|max:255',
            'frequency' => 'nullable|string',
            'create_schedule' => 'nullable|boolean',
            'save_s3' => 'nullable|boolean',
            'plan' => 'required|array',
            'plan.*.resource_uuid' => 'required|string',
            'plan.*.resource_type' => 'required|string|in:database,application,service,resource',
            'plan.*.resource_name' => 'nullable|string|max:255',
            'plan.*.project_uuid' => 'nullable|string',
            'plan.*.server_uuid' => 'nullable|string',
            'plan.*.server_host' => 'nullable|string|max:255',
            'plan.*.strategy' => 'required|string|in:coolify_api,ssh_volume,manifest_only',
            'plan.*.enabled' => 'nullable|boolean',
        ]);

        $plan = array_values(array_filter(
            $validated['plan'],
            fn (array $row) => filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)
        ));

        if ($plan === []) {
            return $this->coolifyRedirectError(
                'لم تُحدَّد أي موارد في الخطة. ارجع للمعالج وفعّل مورداً واحداً على الأقل.',
                'admin.coolify.backups.projects.wizard',
                array_filter(['project_uuid' => $validated['project_uuid'] ?? null])
            );
        }

        $readiness = $this->coolifySettings->getSnapshotReadiness();
        if (! $readiness['ready']) {
            return $this->coolifyRedirectError(
                'اختر سجل تخزين S3 (App Storage) في إعدادات Coolify.',
                'admin.coolify.settings.index'
            );
        }

        if ($this->coolifySettings->planRequiresCoolifyS3($plan) && ! $readiness['ready_with_db']) {
            return $this->coolifyRedirectError(
                'الخطة تتضمن قواعد بيانات: أدخل UUID تخزين S3 في Coolify (زر «جلب من Coolify» في الإعدادات) أو ألغِ تضمين قواعد البيانات في المعالج.',
                'admin.coolify.settings.index'
            );
        }

        $snapshot = $this->snapshots->createFromPlan($plan, [
            'scope' => $validated['scope'],
            'project_uuid' => $validated['project_uuid'] ?? null,
            'project_name' => $validated['project_name'] ?? null,
            'name' => $validated['name'],
            'options' => [
                'frequency' => $validated['frequency'] ?? 'daily',
                'save_s3' => true,
                's3_storage_uuid' => $this->coolifySettings->getCoolifyS3StorageUuid(),
                'storage_config_id' => $this->coolifySettings->getSnapshotStorageConfigId(),
                's3_prefix' => $this->coolifySettings->getS3Prefix(),
            ],
        ]);

        RunProjectSnapshotJob::dispatch($snapshot->id);

        if ($request->boolean('create_schedule')
            && ($validated['scope'] ?? '') === 'single_project'
            && ! empty($validated['project_uuid'])) {
            $frequency = in_array($validated['frequency'] ?? '', ['hourly', 'daily', 'weekly', 'monthly'], true)
                ? $validated['frequency']
                : 'daily';

            CoolifySnapshotSchedule::create([
                'project_uuid' => $validated['project_uuid'],
                'project_name' => $validated['project_name'] ?? null,
                'name' => ($validated['name'] ?? 'لقطة').' — مجدول',
                'frequency' => $frequency,
                'enabled' => true,
                'options' => [
                    'include_databases' => collect($plan)->contains(fn ($row) => ($row['resource_type'] ?? '') === 'database'),
                    'include_applications' => collect($plan)->contains(fn ($row) => ($row['resource_type'] ?? '') === 'application'),
                    'include_services' => collect($plan)->contains(fn ($row) => ($row['resource_type'] ?? '') === 'service'),
                ],
                'next_run_at' => $this->scheduledSnapshots->calculateNextRunAt($frequency),
                'created_by' => auth()->id(),
            ]);
        }

        $this->logCoolify('snapshot_create', 'project_snapshot', $snapshot->uuid, $validated['name']);

        return $this->coolifyRedirectSuccess(
            'بدأت عملية إنشاء اللقطة',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $snapshot->uuid]
        );
    }

    public function snapshotsIndex()
    {
        $snapshots = CoolifyProjectSnapshot::with('creator')->withCount('items')->latest()->paginate(20);

        return view('admin.coolify.backups.snapshots.index', compact('snapshots'));
    }

    public function show(string $uuid)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with(['items', 'creator'])->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        return view('admin.coolify.backups.snapshots.show', compact('snapshot'));
    }

    public function status(string $uuid): JsonResponse
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with('items')->first();

        if (! $snapshot) {
            return response()->json(['success' => false, 'message' => 'غير موجود'], 404);
        }

        $items = $snapshot->items->map(fn ($i) => [
            'id' => $i->id,
            'resource_name' => $i->resource_name,
            'resource_type' => $i->resource_type,
            'status' => $i->status,
            'strategy' => $i->strategy,
            'error_message' => $i->error_message,
        ]);

        return response()->json([
            'success' => true,
            'snapshot' => [
                'uuid' => $snapshot->uuid,
                'status' => $snapshot->status,
                'name' => $snapshot->name,
            ],
            'items' => $items,
            'stats' => [
                'total' => $items->count(),
                'completed' => $items->where('status', 'completed')->count(),
                'failed' => $items->where('status', 'failed')->count(),
                'running' => $items->whereIn('status', ['pending', 'running'])->count(),
            ],
        ]);
    }

    public function restore(Request $request, string $uuid)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        $validated = $request->validate([
            'restore_scope' => 'required|in:all,project,selected',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer',
            'stop_before_restore' => 'nullable|boolean',
            'redeploy' => 'nullable|boolean',
        ]);

        $itemIds = null;
        $options = [
            'stop_before_restore' => $request->boolean('stop_before_restore', true),
            'redeploy' => $request->boolean('redeploy'),
        ];

        if ($validated['restore_scope'] === 'project') {
            $options['scope'] = 'project';
        } elseif ($validated['restore_scope'] === 'selected') {
            $itemIds = $validated['item_ids'] ?? [];
        }

        RestoreProjectSnapshotJob::dispatch($snapshot->id, $itemIds, $options);

        $this->logCoolify('snapshot_restore', 'project_snapshot', $snapshot->uuid);

        return $this->coolifyRedirectSuccess(
            'بدأت عملية الاستعادة',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $uuid]
        );
    }
}
