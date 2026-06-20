<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Jobs\RestoreProjectSnapshotJob;
use App\Jobs\RunProjectSnapshotItemJob;
use App\Jobs\RunProjectSnapshotJob;
use App\Jobs\RunRestoreDrillJob;
use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifySnapshotSchedule;
use App\Services\Coolify\CoolifyBackupService;
use App\Services\Coolify\CoolifyProjectBackupPlanner;
use App\Services\Coolify\CoolifyProjectSnapshotService;
use App\Services\Coolify\CoolifySnapshotStorageService;
use App\Services\Coolify\CoolifyScheduledSnapshotService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifyProjectRestoreService;
use App\Services\Coolify\CoolifySnapshotCancellationService;
use App\Services\Coolify\CoolifySnapshotStuckRecoveryService;
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
            'preselectedServer' => $request->query('server_uuid'),
        ]);
    }

    public function plan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope' => 'required|in:all_projects,single_project,custom,server',
            'project_uuid' => 'nullable|string',
            'server_uuid' => 'nullable|string',
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
            'scope' => 'required|in:all_projects,single_project,custom,server',
            'project_uuid' => 'nullable|string',
            'server_uuid' => 'nullable|string',
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

        if (($validated['scope'] ?? '') === 'server' && empty($validated['server_uuid'])) {
            return $this->coolifyRedirectError(
                'أدخل UUID سيرفر Coolify للقطة الكاملة.',
                'admin.coolify.backups.projects.wizard'
            );
        }

        if ($plan === []) {
            return $this->coolifyRedirectError(
                'لم تُحدَّد أي موارد في الخطة. ارجع للمعالج وفعّل مورداً واحداً على الأقل.',
                'admin.coolify.backups.projects.wizard',
                array_filter([
                    'project_uuid' => $validated['project_uuid'] ?? null,
                    'server_uuid' => $validated['server_uuid'] ?? null,
                ])
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
            'server_uuid' => $validated['server_uuid'] ?? null,
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

    public function status(string $uuid, CoolifySnapshotStuckRecoveryService $stuckRecovery): JsonResponse
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with('items')->first();

        if (! $snapshot) {
            return response()->json(['success' => false, 'message' => 'غير موجود'], 404);
        }

        $autoRecover = request()->boolean('recover_stale', true) && ! $snapshot->isCancelled();
        $staleDetected = ! $snapshot->isCancelled() && $stuckRecovery->hasStaleItems($snapshot);
        $recovery = ['recovered' => 0, 'actions' => []];

        if ($autoRecover && $staleDetected) {
            $recovery = $stuckRecovery->recoverStaleRunningOnly($snapshot->fresh('items'));
            $snapshot->refresh()->load('items');
        }

        $items = $snapshot->items->map(function ($i) {
            $volumes = [];
            foreach ($i->metadata['volumes'] ?? [] as $v) {
                if (is_array($v) && ! empty($v['volume_name'])) {
                    $volumes[] = $v['volume_name'];
                }
            }

            return [
                'id' => $i->id,
                'resource_name' => $i->resource_name,
                'resource_type' => $i->resource_type,
                'status' => $i->status,
                'strategy' => $i->strategy,
                'strategy_label' => \App\Models\CoolifyProjectSnapshotItem::STRATEGIES[$i->strategy] ?? $i->strategy,
                'error_message' => $i->error_message,
                'backup_path' => $i->backup_path ? \Illuminate\Support\Str::limit($i->backup_path, 56) : null,
                'volumes' => $volumes,
            ];
        });

        $completed = $items->where('status', 'completed')->count();
        $failed = $items->where('status', 'failed')->count();
        $active = $items->whereIn('status', ['pending', 'running'])->count();
        $total = max(1, $items->count());

        $queuePending = 0;
        if (config('queue.default') === 'database') {
            $queueName = app(CoolifySettingsService::class)->getBackupQueue();
            $queuePending = (int) \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', $queueName)
                ->count();
        }

        return response()->json([
            'success' => true,
            'stale_detected' => $staleDetected,
            'recovery' => $recovery,
            'queue_pending_jobs' => $queuePending,
            'queue_hint' => $queuePending > 0
                ? 'يوجد '.$queuePending.' مهمة في الطابور — شغّل queue:work'
                : null,
            'snapshot' => [
                'uuid' => $snapshot->uuid,
                'status' => $snapshot->status,
                'status_label' => \App\Models\CoolifyProjectSnapshot::STATUSES[$snapshot->status] ?? $snapshot->status,
                'name' => $snapshot->name,
            ],
            'items' => $items->values(),
            'stats' => [
                'total' => $items->count(),
                'completed' => $completed,
                'failed' => $failed,
                'running' => $active,
                'percent' => (int) round((($completed + $failed) / $total) * 100),
            ],
        ]);
    }

    public function restore(Request $request, string $uuid, CoolifyProjectRestoreService $restoreService)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->first();

        if (! $snapshot) {
            return $this->respondRestore($request, false, 'اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        if ($snapshot->isRestoreRunning()) {
            return $this->respondRestore($request, false, 'استعادة قيد التنفيذ بالفعل', 'admin.coolify.backups.snapshots.show', ['uuid' => $uuid]);
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

        $items = $restoreService->resolveItemsForRestore($snapshot, $itemIds, $options);
        if ($items->isEmpty()) {
            return $this->respondRestore($request, false, 'لا توجد موارد مكتملة النسخ للاستعادة', 'admin.coolify.backups.snapshots.show', ['uuid' => $uuid]);
        }

        $restoreService->beginRestore($snapshot, $itemIds);
        RestoreProjectSnapshotJob::dispatch($snapshot->id, $itemIds, $options);

        return $this->respondRestore(
            $request,
            true,
            'بدأت عملية الاستعادة — '.$items->count().' مورد',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $uuid],
            ['restore_status' => 'running', 'items_count' => $items->count()]
        );
    }

    public function restoreStatus(string $uuid): JsonResponse
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with('items')->first();

        if (! $snapshot) {
            return response()->json(['success' => false, 'message' => 'غير موجود'], 404);
        }

        $items = $snapshot->items->map(fn ($i) => [
            'id' => $i->id,
            'resource_name' => $i->resource_name,
            'resource_type' => $i->resource_type,
            'backup_status' => $i->status,
            'restore_status' => $i->restore_status,
            'restore_error' => $i->restore_error,
            'strategy' => $i->strategy,
        ]);

        $total = max(1, $items->filter(fn ($i) => $i['restore_status'] !== null)->count());
        $completed = $items->where('restore_status', 'completed')->count();
        $failed = $items->where('restore_status', 'failed')->count();
        $skipped = $items->where('restore_status', 'skipped')->count();
        $active = $items->whereIn('restore_status', ['pending', 'running'])->count();

        return response()->json([
            'success' => true,
            'snapshot' => [
                'uuid' => $snapshot->uuid,
                'restore_status' => $snapshot->restore_status,
                'restore_status_label' => CoolifyProjectSnapshot::RESTORE_STATUSES[$snapshot->restore_status ?? ''] ?? $snapshot->restore_status,
            ],
            'items' => $items->values(),
            'stats' => [
                'total' => $items->count(),
                'completed' => $completed,
                'failed' => $failed,
                'skipped' => $skipped,
                'running' => $active,
                'percent' => (int) round((($completed + $failed + $skipped) / $total) * 100),
            ],
            'failed_items' => $items->filter(fn ($i) => ($i['restore_status'] ?? '') === 'failed')
                ->map(fn ($i) => [
                    'id' => $i['id'],
                    'resource_name' => $i['resource_name'],
                    'restore_error' => $i['restore_error'],
                ])->values(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $routeParams
     * @param  array<string, mixed>  $extra
     */
    protected function respondRestore(
        Request $request,
        bool $success,
        string $message,
        string $route,
        array $routeParams = [],
        array $extra = []
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $success ? 200 : 422);
        }

        return $success
            ? $this->coolifyRedirectSuccess($message, $route, $routeParams)
            : $this->coolifyRedirectError($message, $route, $routeParams);
    }

    public function cancel(string $uuid, CoolifySnapshotCancellationService $cancellation)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with('items')->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        if ($snapshot->isCancelled()) {
            return $this->coolifyRedirectError(
                'اللقطة ملغاة مسبقاً',
                'admin.coolify.backups.snapshots.show',
                ['uuid' => $uuid]
            );
        }

        if (! in_array($snapshot->status, ['pending', 'running'], true)
            && $snapshot->items->whereIn('status', ['pending', 'running'])->isEmpty()) {
            return $this->coolifyRedirectError(
                'لا يمكن إيقاف لقطة مكتملة',
                'admin.coolify.backups.snapshots.show',
                ['uuid' => $uuid]
            );
        }

        $result = $cancellation->cancel($snapshot);

        return $this->coolifyRedirectSuccess(
            'تم إيقاف اللقطة ('.$result['cancelled_items'].' عنصر، حُذف '.$result['removed_jobs'].' من الطابور)',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $uuid]
        );
    }

    public function resume(string $uuid, CoolifySnapshotStuckRecoveryService $stuckRecovery)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->with('items')->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        $pending = $snapshot->items->whereIn('status', ['pending', 'running'])->count();
        if ($pending === 0) {
            return $this->coolifyRedirectError(
                'لا توجد عناصر معلّقة لإعادة التشغيل',
                'admin.coolify.backups.snapshots.show',
                ['uuid' => $uuid]
            );
        }

        $result = $stuckRecovery->recoverAllIncomplete($snapshot);

        return $this->coolifyRedirectSuccess(
            'تم إرسال '.$result['recovered'].' عنصراً إلى الطابور. شغّل: php artisan queue:work --queue=coolify-backups',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $uuid]
        );
    }

    public function restoreDrill(string $uuid)
    {
        $snapshot = CoolifyProjectSnapshot::where('uuid', $uuid)->first();

        if (! $snapshot) {
            return $this->coolifyRedirectError('اللقطة غير موجودة', 'admin.coolify.backups.snapshots.index');
        }

        if ($snapshot->status !== 'completed') {
            return $this->coolifyRedirectError(
                'يمكن تشغيل restore drill على لقطة مكتملة فقط',
                'admin.coolify.backups.snapshots.show',
                ['uuid' => $uuid]
            );
        }

        RunRestoreDrillJob::dispatch($snapshot->id);

        return $this->coolifyRedirectSuccess(
            'بدأ اختبار قابلية الاستعادة (restore drill)',
            'admin.coolify.backups.snapshots.show',
            ['uuid' => $uuid]
        );
    }
}
