<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Jobs\RunProjectSnapshotJob;
use App\Services\Coolify\CoolifyProjectBackupPlanner;
use App\Services\Coolify\CoolifyProjectSnapshotService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\CoolifyApiService;
use Illuminate\Http\Request;

class CoolifyResourceSnapshotController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyProjectBackupPlanner $planner,
        protected CoolifyProjectSnapshotService $snapshots,
        protected CoolifySettingsService $coolifySettings
    ) {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.');
        }

        $this->coolifySettings->syncSnapshotStorageFromCoolify($this->coolify);

        $validated = $request->validate([
            'resource_uuid' => 'required|string',
            'resource_type' => 'required|string|in:database,application,service',
            'resource_name' => 'nullable|string|max:255',
            'project_uuid' => 'nullable|string',
            'server_uuid' => 'nullable|string',
        ]);

        $readiness = $this->coolifySettings->getSnapshotReadiness();
        if (! $readiness['ready']) {
            return $this->coolifyRedirectError(
                'اختر تخزين S3 في إعدادات Coolify قبل النسخ.',
                'admin.coolify.settings.index'
            );
        }

        $plan = $this->planner->buildPlan([
            'scope' => 'custom',
            'resource_uuids' => [$validated['resource_uuid']],
            'include_databases' => $validated['resource_type'] === 'database',
            'include_applications' => $validated['resource_type'] === 'application',
            'include_services' => $validated['resource_type'] === 'service',
            'project_uuid' => $validated['project_uuid'] ?? null,
        ]);

        if ($plan === []) {
            return back()->with('error', 'تعذر بناء خطة النسخ لهذا المورد.');
        }

        if ($this->coolifySettings->planRequiresCoolifyS3($plan) && ! $readiness['ready_with_db']) {
            return $this->coolifyRedirectError(
                'نسخ قاعدة البيانات يتطلب UUID تخزين S3 في Coolify — بدونه يُسجَّل manifest فقط بدون بيانات.',
                'admin.coolify.settings.index'
            );
        }

        $name = 'نسخ مورد — '.($validated['resource_name'] ?? $validated['resource_uuid']);

        $snapshot = $this->snapshots->createFromPlan($plan, [
            'scope' => 'custom',
            'project_uuid' => $validated['project_uuid'] ?? null,
            'name' => $name,
            'options' => [
                'frequency' => 'daily',
                'save_s3' => true,
                's3_storage_uuid' => $this->coolifySettings->getCoolifyS3StorageUuid(),
                'storage_config_id' => $this->coolifySettings->getSnapshotStorageConfigId(),
                's3_prefix' => $this->coolifySettings->getS3Prefix(),
            ],
        ]);

        RunProjectSnapshotJob::dispatch($snapshot->id);

        $redirect = match ($validated['resource_type']) {
            'database' => route('admin.coolify.databases.show', $validated['resource_uuid']),
            'application' => route('admin.coolify.applications.show', $validated['resource_uuid']),
            'service' => route('admin.coolify.services.show', $validated['resource_uuid']),
            default => route('admin.coolify.backups.snapshots.show', $snapshot->uuid),
        };

        return redirect($redirect)->with('success', 'بدأت عملية نسخ المورد');
    }
}
