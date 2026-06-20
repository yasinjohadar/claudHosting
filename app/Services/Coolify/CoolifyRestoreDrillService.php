<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshot;
use App\Models\CoolifyRestoreDrill;
class CoolifyRestoreDrillService
{
    public function __construct(
        protected CoolifySnapshotStorageService $storage,
        protected CoolifyDatabaseBackupRestoreService $databaseRestore,
        protected CoolifyBackupAuditService $audit
    ) {}

    public function runForSnapshot(CoolifyProjectSnapshot $snapshot): CoolifyRestoreDrill
    {
        $drill = CoolifyRestoreDrill::create([
            'snapshot_id' => $snapshot->id,
            'status' => 'running',
            'started_at' => now(),
            'items_total' => $snapshot->items()->where('status', 'completed')->count(),
        ]);

        $verified = 0;
        $failed = 0;
        $results = [];

        foreach ($snapshot->items()->where('status', 'completed')->get() as $item) {
            $row = [
                'item_id' => $item->id,
                'resource_name' => $item->resource_name,
                'strategy' => $item->strategy,
                'ok' => false,
                'message' => '',
            ];

            try {
                match ($item->strategy) {
                    'ssh_volume' => $this->verifyVolumeItem($item),
                    'coolify_api' => $this->verifyDatabaseItem($item),
                    default => $this->verifyManifestItem($item),
                };
                $row['ok'] = true;
                $row['message'] = 'تم التحقق';
                $verified++;
            } catch (\Throwable $e) {
                $row['message'] = $e->getMessage();
                $failed++;
            }

            $results[] = $row;
        }

        $status = $failed === 0 ? 'completed' : ($verified > 0 ? 'partial' : 'failed');
        $summary = "تحقق: {$verified} ناجح، {$failed} فاشل من {$drill->items_total}";

        $drill->update([
            'status' => $status,
            'items_verified' => $verified,
            'items_failed' => $failed,
            'summary' => $summary,
            'results' => $results,
            'completed_at' => now(),
        ]);

        $this->audit->log(
            'restore_drill',
            'restore_drill',
            $drill->uuid,
            null,
            null,
            $status === 'completed' ? 'completed' : 'failed',
            $summary,
            ['snapshot_uuid' => $snapshot->uuid]
        );

        return $drill;
    }

    protected function verifyVolumeItem($item): void
    {
        if (! $this->storage->isConfigured()) {
            throw new \RuntimeException('S3 غير مضبوط');
        }

        $volumes = $item->metadata['volumes'] ?? [];
        $checked = 0;
        foreach ($volumes as $vol) {
            if (! is_array($vol)) {
                continue;
            }
            $key = (string) ($vol['s3_key'] ?? '');
            if ($key === '') {
                continue;
            }
            if (! $this->storage->disk()->exists($key)) {
                throw new \RuntimeException('ملف volume غير موجود على S3: '.$key);
            }
            $size = $this->storage->disk()->size($key);
            if ($size <= 0) {
                throw new \RuntimeException('حجم volume صفر على S3');
            }
            $checked++;
        }

        if ($checked === 0) {
            throw new \RuntimeException('لا توجد volumes قابلة للتحقق');
        }
    }

    protected function verifyDatabaseItem($item): void
    {
        $configUuid = trim((string) $item->coolify_backup_config_uuid);
        if ($configUuid === '') {
            throw new \RuntimeException('لا يوجد معرّف جدولة نسخ Coolify');
        }

        $execution = $item->metadata['execution'] ?? null;
        if (! is_array($execution)) {
            $this->databaseRestore->waitForSuccessfulExecution(
                (string) $item->resource_uuid,
                $configUuid,
                60
            );
        }

        $host = trim((string) $item->server_host);
        if ($host !== '' && ! empty($item->metadata['backup_filename'])) {
            $this->databaseRestore->locateBackupFileOnServer(
                $host,
                (string) $item->resource_uuid,
                (string) $item->metadata['backup_filename']
            );
        }
    }

    protected function verifyManifestItem($item): void
    {
        $path = (string) ($item->backup_path ?? '');
        if ($path === '') {
            throw new \RuntimeException('لا يوجد مسار نسخ');
        }

        if (str_starts_with($path, 's3:')) {
            $key = $this->storage->parseBackupPath($path);
            if ($key === null || ! $this->storage->disk()->exists($key)) {
                throw new \RuntimeException('manifest غير موجود على S3');
            }
        }
    }
}
