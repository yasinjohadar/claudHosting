<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoolifyProjectRestoreService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySnapshotStorageService $storage,
        protected CoolifyDatabaseBackupRestoreService $databaseRestore,
        protected CoolifyBackupAuditService $audit
    ) {}

    /**
     * @param  array<int>|null  $itemIds
     * @return \Illuminate\Support\Collection<int, CoolifyProjectSnapshotItem>
     */
    public function resolveItemsForRestore(CoolifyProjectSnapshot $snapshot, ?array $itemIds = null, array $options = []): \Illuminate\Support\Collection
    {
        $query = $snapshot->items()->where('status', 'completed');

        if ($itemIds !== null && $itemIds !== []) {
            $query->whereIn('id', $itemIds);
        } elseif (($options['scope'] ?? '') === 'project' && $snapshot->project_uuid) {
            $query->where('project_uuid', $snapshot->project_uuid);
        }

        return $query->get();
    }

    public function beginRestore(CoolifyProjectSnapshot $snapshot, ?array $itemIds = null): void
    {
        $snapshot->resetRestoreStateForItems($itemIds);

        $snapshot->update([
            'restore_status' => 'running',
            'restore_started_at' => now(),
            'restore_completed_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function processRestoreItem(CoolifyProjectSnapshotItem $item, array $options = []): void
    {
        if (! $this->storage->isConfigured()) {
            throw new \RuntimeException('تخزين S3 غير مضبوط في إعدادات Coolify');
        }

        $stopBefore = (bool) ($options['stop_before_restore'] ?? true);

        $item->update([
            'restore_status' => 'running',
            'restore_error' => null,
        ]);

        try {
            if ($item->strategy === 'manifest_only') {
                $item->update([
                    'restore_status' => 'skipped',
                    'restore_error' => 'بيانات وصفية فقط — لا يوجد محتوى لاستعادته',
                ]);

                return;
            }

            match ($item->strategy) {
                'ssh_volume' => $this->restoreVolumes($item, $stopBefore),
                'coolify_api' => $this->databaseRestore->restoreSnapshotItem($item, $stopBefore),
                default => null,
            };

            if (($options['redeploy'] ?? false) && in_array($item->resource_type, ['application', 'service'], true)) {
                if ($item->resource_type === 'service') {
                    $this->coolify->restartService($item->resource_uuid);
                } else {
                    $this->coolify->restartApplication($item->resource_uuid);
                }
            }

            $item->update(['restore_status' => 'completed', 'restore_error' => null]);
        } catch (\Throwable $e) {
            $item->update([
                'restore_status' => 'failed',
                'restore_error' => $e->getMessage(),
            ]);

            $this->audit->log(
                'snapshot_restore_item_failed',
                'snapshot_item',
                (string) $item->id,
                $item->resource_type,
                $item->resource_uuid,
                'failed',
                $e->getMessage()
            );
        }
    }

    public function finalizeRestoreIfDone(CoolifyProjectSnapshot $snapshot): void
    {
        $snapshot->refreshRestoreStatusFromItems();

        if (! $snapshot->isRestoreFinished()) {
            return;
        }

        $lockKey = 'coolify.snapshot.restore_audit.'.$snapshot->id;
        if (! Cache::add($lockKey, 1, now()->addHour())) {
            return;
        }

        $items = $snapshot->items()->whereNotNull('restore_status')->get();
        $failed = $items->where('restore_status', 'failed')->count();

        $this->audit->log(
            'snapshot_restore',
            'project_snapshot',
            $snapshot->uuid,
            null,
            null,
            $failed > 0 ? 'partial' : 'completed',
            'استعادة لقطة: '.$items->where('restore_status', 'completed')->count().' ناجح، '.$failed.' فاشل',
            ['restore_status' => $snapshot->restore_status]
        );
    }

    protected function restoreVolumes(CoolifyProjectSnapshotItem $item, bool $stopBefore): void
    {
        $host = trim((string) $item->server_host);
        if ($host === '') {
            throw new \RuntimeException('لا يوجد سيرفر للاستعادة');
        }

        $volumes = $item->metadata['volumes'] ?? [];
        $tag = 'restore-'.$item->id;

        foreach ($volumes as $vol) {
            if (is_string($vol)) {
                continue;
            }

            $s3Key = (string) ($vol['s3_key'] ?? '');
            $volumeName = (string) ($vol['volume_name'] ?? '');
            if ($s3Key === '' || $volumeName === '') {
                continue;
            }

            $localPath = $this->storage->laravelTempDir().'/'.Str::random(16).'.tar.gz';
            $remotePath = $this->ssh->remoteTempArchivePath($tag, $volumeName);

            try {
                $this->storage->downloadToLocalFile($s3Key, $localPath);

                $up = $this->ssh->uploadFile($host, $localPath, $remotePath);
                if (! ($up['success'] ?? false)) {
                    throw new \RuntimeException('فشل رفع الأرشيف إلى السيرفر: '.($up['output'] ?? ''));
                }

                $result = $this->ssh->restoreVolume($host, $volumeName, $remotePath, $stopBefore);
                $this->ssh->removeRemoteFile($host, $remotePath);

                if (! ($result['success'] ?? false)) {
                    throw new \RuntimeException('فشل استعادة '.$volumeName.': '.$result['output']);
                }
            } finally {
                if (is_file($localPath)) {
                    @unlink($localPath);
                }
            }
        }
    }
}
