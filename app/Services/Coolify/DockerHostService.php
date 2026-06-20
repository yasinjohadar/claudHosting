<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DockerHostService
{
    public function __construct(
        protected CoolifySshExecutor $ssh,
        protected CoolifyApiService $coolify,
        protected WordpressContainerResolver $containerResolver
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSiteContainerStats(CoolifyWordpressSite $site): array
    {
        $resolved = $this->containerResolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'تعذّر تحديد الحاوية'];
        }

        return $this->getContainerStats(
            (string) $resolved['host'],
            (string) $resolved['container_id']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getContainerStats(string $host, string $containerId): array
    {
        $id = escapeshellarg($containerId);
        $cmd = 'docker stats --no-stream --format "{{json .}}" '.$id.' 2>/dev/null';
        $result = $this->ssh->run($host, $cmd, 45);
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => trim($result['output'] ?? '') ?: 'فشل جلب الإحصائيات'];
        }

        $line = trim($result['output'] ?? '');
        $row = json_decode($line, true);
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'استجابة غير متوقعة من docker stats'];
        }

        return [
            'success' => true,
            'stats' => [
                'name' => $row['Name'] ?? $row['name'] ?? '',
                'id' => $row['ID'] ?? $row['id'] ?? $containerId,
                'cpu_percent' => $this->parsePercent($row['CPUPerc'] ?? '0'),
                'mem_percent' => $this->parsePercent($row['MemPerc'] ?? '0'),
                'mem_usage' => $row['MemUsage'] ?? '',
                'net_io' => $row['NetIO'] ?? '',
                'block_io' => $row['BlockIO'] ?? '',
                'pids' => $row['PIDs'] ?? null,
            ],
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSiteHealth(CoolifyWordpressSite $site): array
    {
        $resolved = $this->containerResolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'تعذّر تحديد الحاوية', 'healthy' => false];
        }

        $host = (string) $resolved['host'];
        $wpHealth = $this->inspectContainerHealth($host, (string) $resolved['container_id']);

        $db = $this->resolveDatabaseContainer($site, $host);
        $dbHealth = $db !== null
            ? $this->inspectContainerHealth($host, $db['id'])
            : ['status' => 'unknown', 'healthy' => null, 'message' => 'لم تُعثر على حاوية قاعدة البيانات'];

        $healthy = ($wpHealth['healthy'] ?? false) && ($dbHealth['healthy'] !== false);

        return [
            'success' => true,
            'healthy' => $healthy,
            'wordpress' => array_merge($wpHealth, [
                'container_id' => $resolved['container_id'],
                'container_name' => $resolved['container_name'] ?? '',
            ]),
            'database' => $dbHealth,
            'database_container' => $db,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createDatabaseBackup(CoolifyWordpressSite $site): array
    {
        $compose = $this->resolveComposeContext($site);
        if (! ($compose['success'] ?? false)) {
            return $compose;
        }

        $host = (string) $compose['host'];
        $dir = (string) $compose['dir'];
        $dbService = (string) $compose['database_service'];

        $filename = $site->slug.'-'.now()->format('Y-m-d_His').'.sql.gz';
        $innerDump = 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" 2>/dev/null | gzip -c';
        $dumpCmd = 'cd '.escapeshellarg($dir).' && docker compose exec -T '
            .escapeshellarg($dbService).' sh -lc '.escapeshellarg($innerDump)
            .' | base64 -w0 2>/dev/null || cd '.escapeshellarg($dir).' && docker compose exec -T '
            .escapeshellarg($dbService).' sh -lc '.escapeshellarg('mariadb-dump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" 2>/dev/null | gzip -c')
            .' | base64 -w0 2>/dev/null';

        $dump = $this->ssh->run($host, $dumpCmd, 600);
        if (! ($dump['success'] ?? false)) {
            return ['success' => false, 'message' => 'فشل إنشاء النسخة: '.trim($dump['output'] ?? '')];
        }

        $localDir = 'wordpress-db-backups/'.$site->uuid;
        Storage::disk('local')->makeDirectory($localDir);
        $localPath = $localDir.'/'.$filename;

        $binary = base64_decode(preg_replace('/\s+/', '', $dump['output'] ?? ''), true);
        if ($binary === false || $binary === '') {
            return ['success' => false, 'message' => 'ملف النسخة فارغ أو تالف'];
        }

        Storage::disk('local')->put($localPath, $binary);

        $meta = $site->metadata ?? [];
        $backups = $meta['db_backups'] ?? [];
        $backups[] = [
            'path' => $localPath,
            'filename' => $filename,
            'size_bytes' => strlen($binary),
            'created_at' => now()->toIso8601String(),
        ];
        $site->update(['metadata' => array_merge($meta, ['db_backups' => array_slice($backups, -20)])]);

        return [
            'success' => true,
            'message' => 'تم إنشاء النسخة الاحتياطية',
            'path' => $localPath,
            'filename' => $filename,
            'size_bytes' => strlen($binary),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restoreDatabaseBackup(CoolifyWordpressSite $site, string $backupPath): array
    {
        if (! Storage::disk('local')->exists($backupPath)) {
            return ['success' => false, 'message' => 'ملف النسخة غير موجود'];
        }

        $compose = $this->resolveComposeContext($site);
        if (! ($compose['success'] ?? false)) {
            return $compose;
        }

        $host = (string) $compose['host'];
        $dir = (string) $compose['dir'];
        $dbService = (string) $compose['database_service'];

        $binary = Storage::disk('local')->get($backupPath);
        $encoded = base64_encode($binary);
        $remoteTmp = '/tmp/wp-restore-'.Str::random(12).'.sql.gz';

        $uploadCmd = 'echo '.escapeshellarg($encoded).' | base64 -d > '.escapeshellarg($remoteTmp);
        $upload = $this->ssh->run($host, $uploadCmd, 300);
        if (! ($upload['success'] ?? false)) {
            return ['success' => false, 'message' => 'فشل رفع ملف النسخة إلى السيرفر'];
        }

        $restoreInner = 'gunzip -c '.escapeshellarg($remoteTmp).' | mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"';
        $restoreCmd = 'cd '.escapeshellarg($dir).' && docker compose exec -T '
            .escapeshellarg($dbService).' sh -lc '.escapeshellarg($restoreInner)
            .'; rm -f '.escapeshellarg($remoteTmp);

        $restore = $this->ssh->run($host, $restoreCmd, 900);
        $this->ssh->run($host, 'rm -f '.escapeshellarg($remoteTmp), 15);

        if (! ($restore['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'فشل الاستعادة: '.Str::limit(trim($restore['output'] ?? ''), 400),
            ];
        }

        return ['success' => true, 'message' => 'تمت استعادة قاعدة بيانات WordPress'];
    }

    /**
     * ملخص البنية التحتية Docker على سيرفر (للوحة العمليات).
     *
     * @return array<string, mixed>
     */
    public function collectInfrastructureSummary(string $serverUuid): array
    {
        $endpoint = $this->coolify->resolveServerSshEndpoint($serverUuid);
        if (! ($endpoint['success'] ?? false)) {
            return ['success' => false, 'message' => $endpoint['message'] ?? 'SSH غير متاح'];
        }

        $host = (string) ($endpoint['host'] ?? '');
        $port = (int) ($endpoint['port'] ?? 22);

        $df = $this->ssh->run($host, 'df -h / 2>/dev/null | tail -1', 30, $port);
        $dockerDf = $this->ssh->run($host, 'docker system df --format "{{.Type}}\t{{.Size}}\t{{.Reclaimable}}" 2>/dev/null', 45, $port);

        $diskWarning = false;
        $diskPercent = 0;
        if ($df['success'] ?? false) {
            if (preg_match('/(\d+)%/', $df['output'] ?? '', $m)) {
                $diskPercent = (int) $m[1];
                $diskWarning = $diskPercent >= 85;
            }
        }

        $dockerRows = [];
        foreach (preg_split('/\r\n|\r|\n/', $dockerDf['output'] ?? '') ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split("/\t+/", $line);
            $dockerRows[] = [
                'type' => $parts[0] ?? '',
                'size' => $parts[1] ?? '',
                'reclaimable' => $parts[2] ?? '',
            ];
        }

        return [
            'success' => true,
            'server_uuid' => $serverUuid,
            'host' => $host,
            'disk_percent' => $diskPercent,
            'disk_warning' => $diskWarning,
            'disk_line' => trim($df['output'] ?? ''),
            'docker_system_df' => $dockerRows,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summarizeWordpressSitesHealth(): array
    {
        $sites = CoolifyWordpressSite::query()
            ->where('status', 'running')
            ->whereNotNull('service_uuid')
            ->limit(25)
            ->get();

        $unhealthy = [];
        foreach ($sites as $site) {
            $health = $this->getSiteHealth($site);
            if (($health['success'] ?? false) && ($health['healthy'] ?? true)) {
                continue;
            }
            $unhealthy[] = [
                'uuid' => $site->uuid,
                'name' => $site->display_name,
                'slug' => $site->slug,
                'message' => $health['message'] ?? 'صحة غير سليمة',
                'url' => route('admin.coolify.wordpress-sites.show', $site->uuid),
            ];
        }

        return [
            'checked' => $sites->count(),
            'unhealthy_count' => count($unhealthy),
            'unhealthy' => $unhealthy,
        ];
    }

    /**
     * @return array{id: string, name: string, image: string}|null
     */
    protected function resolveDatabaseContainer(CoolifyWordpressSite $site, string $host): ?array
    {
        $compose = $this->resolveComposeContext($site);
        if (! ($compose['success'] ?? false)) {
            return null;
        }

        $dir = (string) $compose['dir'];
        $svcList = $this->ssh->run($host, 'cd '.escapeshellarg($dir).' && docker compose config --services 2>/dev/null', 30);
        $services = array_values(array_filter(array_map('trim', explode("\n", $svcList['output'] ?? ''))));

        foreach ($services as $serviceName) {
            $lower = strtolower($serviceName);
            if (! str_contains($lower, 'mariadb') && ! str_contains($lower, 'mysql')) {
                continue;
            }
            $ps = $this->ssh->run(
                $host,
                'cd '.escapeshellarg($dir).' && docker compose ps -q '.escapeshellarg($serviceName).' 2>/dev/null',
                30
            );
            foreach (preg_split('/\s+/', trim($ps['output'] ?? '')) as $id) {
                $id = trim($id);
                if ($id !== '') {
                    return ['id' => $id, 'name' => $serviceName, 'service' => $serviceName];
                }
            }
        }

        return null;
    }

    /**
     * @return array{success: bool, host?: string, dir?: string, database_service?: string, message?: string}
     */
    protected function resolveComposeContext(CoolifyWordpressSite $site): array
    {
        $ssh = $this->containerResolver->resolve($site);
        if (! ($ssh['success'] ?? false)) {
            return ['success' => false, 'message' => $ssh['message'] ?? 'SSH غير متاح'];
        }

        $host = (string) $ssh['host'];
        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $site->service_uuid);
        if ($uuid === '') {
            return ['success' => false, 'message' => 'معرّف الخدمة غير صالح'];
        }

        foreach ([
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
        ] as $dir) {
            $yes = $this->ssh->run($host, 'test -f '.escapeshellarg($dir.'/docker-compose.yml').' && echo yes', 15);
            if (! str_contains($yes['output'] ?? '', 'yes')) {
                continue;
            }

            $svcList = $this->ssh->run($host, 'cd '.escapeshellarg($dir).' && docker compose config --services 2>/dev/null', 30);
            $services = array_values(array_filter(array_map('trim', explode("\n", $svcList['output'] ?? ''))));
            $dbService = null;
            foreach ($services as $name) {
                $lower = strtolower($name);
                if (str_contains($lower, 'mariadb') || str_contains($lower, 'mysql')) {
                    $dbService = $name;
                    break;
                }
            }

            if ($dbService === null) {
                return ['success' => false, 'message' => 'لم تُعثر على خدمة قاعدة بيانات في docker-compose'];
            }

            return [
                'success' => true,
                'host' => $host,
                'dir' => $dir,
                'database_service' => $dbService,
            ];
        }

        return ['success' => false, 'message' => 'لم يُعثر على مجلد docker-compose للخدمة'];
    }

    /**
     * @return array{status: string, healthy: ?bool, message: string}
     */
    protected function inspectContainerHealth(string $host, string $containerId): array
    {
        $id = escapeshellarg($containerId);
        $state = $this->ssh->run($host, 'docker inspect -f {{.State.Status}} '.$id.' 2>/dev/null', 20);
        $status = trim($state['output'] ?? 'unknown');
        if ($status !== 'running') {
            return [
                'status' => $status,
                'healthy' => false,
                'message' => 'الحاوية ليست قيد التشغيل ('.$status.')',
            ];
        }

        $health = $this->ssh->run($host, 'docker inspect -f {{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}} '.$id.' 2>/dev/null', 20);
        $healthStatus = trim($health['output'] ?? 'none');
        if ($healthStatus === 'none') {
            return ['status' => $status, 'healthy' => true, 'message' => 'تعمل (بدون healthcheck)'];
        }

        return [
            'status' => $status,
            'healthy' => $healthStatus === 'healthy',
            'message' => 'health: '.$healthStatus,
        ];
    }

    protected function parsePercent(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 1);
        }

        return round((float) preg_replace('/[^0-9.]/', '', (string) $value), 1);
    }
}
