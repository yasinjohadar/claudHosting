<?php

namespace App\Services\Coolify;

use App\Jobs\RunProjectSnapshotJob;
use App\Models\CoolifyProjectSnapshot;
use Illuminate\Support\Str;

class CoolifyPreRestoreSnapshotService
{
    public function __construct(
        protected CoolifyProjectSnapshotService $snapshots,
        protected CoolifyProjectBackupPlanner $planner,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * Creates a quick pre-restore snapshot (synchronous dispatch) when storage is ready.
     */
    public function createPreRestoreSnapshot(CoolifyProjectSnapshot $source, ?array $itemIds = null): ?CoolifyProjectSnapshot
    {
        if (! $this->settings->isSnapshotStorageConfigured()) {
            return null;
        }

        $items = $source->items()
            ->where('status', 'completed')
            ->when($itemIds !== null && $itemIds !== [], fn ($q) => $q->whereIn('id', $itemIds))
            ->get();

        if ($items->isEmpty()) {
            return null;
        }

        $plan = $items->map(fn ($item) => [
            'resource_type' => $item->resource_type,
            'resource_uuid' => $item->resource_uuid,
            'resource_name' => ($item->resource_name ?? '').' (قبل الاستعادة)',
            'project_uuid' => $item->project_uuid,
            'server_uuid' => $item->server_uuid,
            'server_host' => $item->server_host,
            'strategy' => $item->strategy,
            'enabled' => true,
        ])->all();

        $snapshot = $this->snapshots->createFromPlan($plan, [
            'scope' => 'custom',
            'project_uuid' => $source->project_uuid,
            'project_name' => $source->project_name,
            'name' => 'قبل الاستعادة — '.($source->name ?? Str::limit($source->uuid, 8)),
            'options' => array_merge($source->options ?? [], [
                'pre_restore_for' => $source->uuid,
            ]),
        ]);

        RunProjectSnapshotJob::dispatchSync($snapshot->id);

        return $snapshot->fresh();
    }
}
