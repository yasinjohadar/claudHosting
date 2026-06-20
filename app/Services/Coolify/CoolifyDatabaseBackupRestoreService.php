<?php

namespace App\Services\Coolify;

use App\Models\CoolifyProjectSnapshotItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Str;

class CoolifyDatabaseBackupRestoreService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifyBackupService $backupService,
        protected CoolifySshExecutor $ssh
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function waitForSuccessfulExecution(
        string $databaseUuid,
        string $configUuid,
        int $maxSeconds = 900,
        int $pollSeconds = 5
    ): array {
        $deadline = time() + $maxSeconds;

        while (time() < $deadline) {
            $executions = $this->backupService->listExecutions($databaseUuid, $configUuid, true);

            foreach ($executions as $execution) {
                $status = strtolower((string) ($execution['status'] ?? ''));
                if (in_array($status, ['success', 'completed', 'finished'], true)) {
                    return $execution;
                }
                if (in_array($status, ['failed', 'error'], true)) {
                    throw new \RuntimeException(
                        'فشل تنفيذ النسخ: '.($execution['message'] ?? $status)
                    );
                }
            }

            sleep($pollSeconds);
        }

        throw new \RuntimeException('انتهت المهلة في انتظار اكتمال نسخ قاعدة البيانات عبر Coolify');
    }

    public function restoreSnapshotItem(CoolifyProjectSnapshotItem $item, bool $stopBefore = true): void
    {
        $databaseUuid = (string) $item->resource_uuid;
        $configUuid = trim((string) $item->coolify_backup_config_uuid);
        $host = trim((string) $item->server_host);

        if ($databaseUuid === '' || $configUuid === '') {
            throw new \RuntimeException('بيانات النسخ غير مكتملة (معرّف DB أو جدولة النسخ)');
        }

        if ($host === '') {
            $server = $this->coolify->resolveServerSshHost(
                (string) ($item->server_uuid ?? '')
            );
            $host = trim((string) ($server['host'] ?? ''));
        }

        if ($host === '') {
            throw new \RuntimeException('لا يوجد عنوان SSH للسيرفر لاستعادة قاعدة البيانات');
        }

        $execution = $item->metadata['execution'] ?? null;
        if (! is_array($execution) || empty($execution['filename'])) {
            $execution = $this->waitForSuccessfulExecution($databaseUuid, $configUuid, 120);
        }

        $filename = (string) ($execution['filename'] ?? '');
        if ($filename === '' || $filename === '—') {
            throw new \RuntimeException('لم يُعثر على اسم ملف النسخة في تنفيذ Coolify');
        }

        $remotePath = $this->locateBackupFileOnServer($host, $databaseUuid, $filename);
        $this->restoreFileOnServer($host, $databaseUuid, $remotePath, $stopBefore);
    }

    /**
     * @param  array<string, mixed>  $execution
     */
    public function attachExecutionToItemMetadata(CoolifyProjectSnapshotItem $item, array $execution): void
    {
        $meta = $item->metadata ?? [];
        $item->update([
            'metadata' => array_merge($meta, [
                'execution' => $execution,
                'execution_uuid' => $execution['uuid'] ?? null,
                'backup_filename' => $execution['filename'] ?? null,
            ]),
        ]);
    }

    public function locateBackupFileOnServer(string $host, string $databaseUuid, string $filename): string
    {
        $safeName = basename($filename);
        $safeUuid = preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseUuid) ?? $databaseUuid;

        $commands = [
            'find /data/coolify/backups -type f -name '.escapeshellarg($safeName).' 2>/dev/null | head -1',
            'find /var/lib/coolify/backups -type f -name '.escapeshellarg($safeName).' 2>/dev/null | head -1',
            'find /data/coolify/backups -type f -path '.escapeshellarg('*'.$safeUuid.'*').' 2>/dev/null | head -1',
        ];

        foreach ($commands as $cmd) {
            $result = $this->ssh->run($host, $cmd, 60);
            $path = trim($result['output'] ?? '');
            if (($result['success'] ?? false) && $path !== '' && ! str_contains($path, 'find:')) {
                return $path;
            }
        }

        throw new \RuntimeException('لم يُعثر على ملف النسخة على السيرفر: '.$safeName);
    }

    protected function restoreFileOnServer(
        string $host,
        string $databaseUuid,
        string $remotePath,
        bool $stopBefore
    ): void {
        $details = $this->coolify->getDatabase($databaseUuid);
        if (! ($details['success'] ?? false)) {
            throw new \RuntimeException($details['message'] ?? 'تعذر جلب تفاصيل قاعدة البيانات');
        }

        $data = is_array($details['data'] ?? null) ? $details['data'] : [];
        $type = strtolower((string) ($data['type'] ?? $data['database_type'] ?? 'mysql'));
        $containerId = $this->resolveDatabaseContainerId($host, $databaseUuid, $data);

        if ($containerId === '') {
            throw new \RuntimeException('تعذر تحديد حاوية قاعدة البيانات على السيرفر');
        }

        if ($stopBefore) {
            $this->ssh->run($host, 'docker stop '.escapeshellarg($containerId).' 2>/dev/null; true', 30);
            $this->ssh->run($host, 'docker start '.escapeshellarg($containerId).' 2>/dev/null', 60);
            sleep(3);
        }

        $id = escapeshellarg($containerId);
        $path = escapeshellarg($remotePath);

        $this->ssh->run($host, 'docker cp '.$path.' '.$id.':/tmp/coolify-restore.sql.gz', 300);

        if (str_contains($type, 'postgres') || str_contains($type, 'pgsql')) {
            $inner = 'gunzip -c /tmp/coolify-restore.sql.gz | psql -U "$POSTGRES_USER" "$POSTGRES_DB"';
        } else {
            $inner = 'gunzip -c /tmp/coolify-restore.sql.gz | mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"';
        }

        $cmd = 'docker exec -i '.$id.' sh -lc '.escapeshellarg($inner);

        $result = $this->ssh->run($host, $cmd, 1200);
        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException(
                'فشل استعادة قاعدة البيانات: '.Str::limit(trim($result['output'] ?? ''), 500)
            );
        }
    }

    /**
     * @param  array<string, mixed>  $databaseData
     */
    protected function resolveDatabaseContainerId(string $host, string $databaseUuid, array $databaseData): string
    {
        $short = substr($databaseUuid, 0, 8);
        $name = (string) ($databaseData['name'] ?? '');
        $filters = array_filter([
            $short !== '' ? 'docker ps -q --filter name='.escapeshellarg($short).' 2>/dev/null | head -1' : null,
            $name !== '' ? 'docker ps -q --filter name='.escapeshellarg($name).' 2>/dev/null | head -1' : null,
            'docker ps -q --filter label=coolify.databaseId='.escapeshellarg($databaseUuid).' 2>/dev/null | head -1',
        ]);

        foreach ($filters as $cmd) {
            $result = $this->ssh->run($host, $cmd, 30);
            $id = trim($result['output'] ?? '');
            if ($id !== '') {
                return $id;
            }
        }

        $composeDirs = [
            '/data/coolify/databases/'.preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseUuid),
            '/var/lib/coolify/databases/'.preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseUuid),
        ];

        foreach ($composeDirs as $dir) {
            $ps = $this->ssh->run(
                $host,
                'test -d '.escapeshellarg($dir).' && cd '.escapeshellarg($dir).' && docker compose ps -q 2>/dev/null | head -1',
                30
            );
            $id = trim($ps['output'] ?? '');
            if ($id !== '') {
                return $id;
            }
        }

        return '';
    }
}
