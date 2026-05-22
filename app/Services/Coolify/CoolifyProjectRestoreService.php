<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyProjectSnapshotItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Str;

class CoolifyProjectRestoreService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySnapshotStorageService $storage
    ) {}

    /**
     * @param  array<int>|null  $itemIds
     */
    public function restore(CoolifyProjectSnapshot $snapshot, ?array $itemIds = null, array $options = []): void
    {
        if (! $this->storage->isConfigured()) {
            throw new \RuntimeException('تخزين S3 غير مضبوط في إعدادات Coolify');
        }

        $query = $snapshot->items()->where('status', 'completed');

        if ($itemIds !== null && $itemIds !== []) {
            $query->whereIn('id', $itemIds);
        } elseif (($options['scope'] ?? '') === 'project' && $snapshot->project_uuid) {
            $query->where('project_uuid', $snapshot->project_uuid);
        }

        $items = $query->get();
        $stopBefore = (bool) ($options['stop_before_restore'] ?? true);

        foreach ($items as $item) {
            $this->restoreItem($item, $stopBefore, $options);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function restoreItem(CoolifyProjectSnapshotItem $item, bool $stopBefore, array $options = []): void
    {
        $item->update(['status' => 'running', 'started_at' => now()]);

        try {
            match ($item->strategy) {
                'ssh_volume' => $this->restoreVolumes($item, $stopBefore),
                'coolify_api' => $this->restoreDatabaseHint($item),
                default => true,
            };

            if (($options['redeploy'] ?? false) && in_array($item->resource_type, ['application', 'service'], true)) {
                if ($item->resource_type === 'service') {
                    $this->coolify->restartService($item->resource_uuid);
                } else {
                    $this->coolify->restartApplication($item->resource_uuid);
                }
            }

            $item->update(['status' => 'completed', 'completed_at' => now()]);
        } catch (\Throwable $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
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

    protected function restoreDatabaseHint(CoolifyProjectSnapshotItem $item): void
    {
        $meta = $item->metadata ?? [];
        $item->update([
            'metadata' => array_merge($meta, [
                'restore_note' => 'استعادة DB: الملف على S3 في Coolify (save_s3). استخدم تنفيذ النسخ من لوحة Coolify أو pg_restore/mysql من نسخة S3.',
            ]),
        ]);
    }
}
