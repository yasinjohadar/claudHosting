<?php

namespace App\Services\Coolify;

use App\Jobs\RunProjectSnapshotItemJob;
use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use Illuminate\Support\Facades\Cache;

class CoolifySnapshotStuckRecoveryService
{
    public function staleMinutes(): int
    {
        return (int) config('coolify.snapshot_item_stale_minutes', 8);
    }

    public function isStaleRunning(CoolifyProjectSnapshotItem $item): bool
    {
        if ($item->status !== 'running') {
            return false;
        }

        $cutoff = now()->subMinutes($this->staleMinutes());

        return $item->started_at === null || $item->started_at->lt($cutoff);
    }

    public function hasStaleItems(CoolifyProjectSnapshot $snapshot): bool
    {
        return $snapshot->items->contains(fn (CoolifyProjectSnapshotItem $item) => $this->isStaleRunning($item));
    }

    /**
     * للاستدعاء التلقائي من polling — يعيد جدولة العناصر العالقة في running فقط.
     *
     * @return array{recovered: int, actions: array<int, string>}
     */
    public function recoverStaleRunningOnly(CoolifyProjectSnapshot $snapshot): array
    {
        if ($snapshot->isCancelled()) {
            return ['recovered' => 0, 'actions' => []];
        }

        $actions = [];
        $recovered = 0;

        foreach ($snapshot->items as $item) {
            if (! $this->isStaleRunning($item)) {
                continue;
            }

            $guardKey = 'coolify.snapshot.auto_recover.'.$item->id;
            if (! Cache::add($guardKey, 1, now()->addMinutes(15))) {
                continue;
            }

            $item->update([
                'status' => 'pending',
                'error_message' => 'أُعيدت الجدولة تلقائياً بعد توقّف ('.$this->staleMinutes().'+ دقائق)',
                'started_at' => null,
            ]);

            RunProjectSnapshotItemJob::dispatch($snapshot->id, $item->id, true);
            $actions[] = "إعادة: {$item->resource_name}";
            $recovered++;
        }

        if ($recovered > 0) {
            $snapshot->update(['status' => 'running']);
            $snapshot->refreshStatusFromItems();
        }

        return ['recovered' => $recovered, 'actions' => $actions];
    }

    /**
     * زر «متابعة» — كل العناصر غير المكتملة.
     *
     * @return array{recovered: int, actions: array<int, string>}
     */
    public function recoverAllIncomplete(CoolifyProjectSnapshot $snapshot): array
    {
        if ($snapshot->isCancelled()) {
            return ['recovered' => 0, 'actions' => []];
        }

        $actions = [];
        $recovered = 0;

        foreach ($snapshot->items as $item) {
            if ($item->status === 'completed') {
                continue;
            }

            if ($item->status === 'running') {
                $item->update([
                    'status' => 'pending',
                    'error_message' => 'أُعيدت الجدولة يدوياً',
                    'started_at' => null,
                ]);
            }

            RunProjectSnapshotItemJob::dispatch($snapshot->id, $item->id, true);
            $actions[] = $item->resource_name;
            $recovered++;
        }

        if ($recovered > 0) {
            $snapshot->update(['status' => 'running']);
            $snapshot->refreshStatusFromItems();
        }

        return ['recovered' => $recovered, 'actions' => $actions];
    }
}
