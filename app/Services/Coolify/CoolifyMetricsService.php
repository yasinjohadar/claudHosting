<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoolifyMetricsService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings
    ) {}

    public function refreshSeconds(): int
    {
        return max(5, min(60, (int) config('coolify.metrics_refresh_seconds', 10)));
    }

    public function cacheTtl(): int
    {
        return max(3, min(30, (int) config('coolify.metrics_cache_seconds', 8)));
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewMetrics(bool $refresh = false): array
    {
        if (! $this->coolify->isConfigured()) {
            return ['success' => false, 'message' => 'Coolify غير مضبوط', 'servers' => []];
        }

        $servers = $this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []);
        $out = [];

        foreach ($servers as $server) {
            if (! is_array($server)) {
                continue;
            }
            $uuid = (string) ($server['uuid'] ?? '');
            if ($uuid === '') {
                continue;
            }
            $metrics = $this->getServerMetrics($uuid, $refresh);
            $out[] = [
                'uuid' => $uuid,
                'name' => $server['name'] ?? $uuid,
                'metrics' => $metrics,
            ];
        }

        return ['success' => true, 'servers' => $out, 'fetched_at' => now()->toIso8601String()];
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerMetrics(string $serverUuid, bool $refresh = false): array
    {
        $cacheKey = 'coolify_metrics_server_'.$serverUuid;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($serverUuid) {
            $host = $this->resolveServerHost($serverUuid);
            if ($host === '') {
                return $this->failure('لا يوجد عنوان IP للسيرفر');
            }

            $hostMetrics = $this->collectHostMetrics($host);
            if (! ($hostMetrics['success'] ?? false)) {
                return $hostMetrics;
            }

            $containers = $this->collectContainerMetrics($host);

            return [
                'success' => true,
                'scope' => 'server',
                'server_uuid' => $serverUuid,
                'host' => $host,
                'server' => $hostMetrics['server'],
                'containers' => $containers['containers'] ?? [],
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getProjectMetrics(string $projectUuid, bool $refresh = false): array
    {
        $cacheKey = 'coolify_metrics_project_'.$projectUuid;
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($projectUuid) {
            $resources = $this->coolify->normalizeList(
                $this->coolify->projectResources($projectUuid)['data'] ?? []
            );

            if ($resources === []) {
                return $this->failure('لا موارد في المشروع أو فشل جلبها');
            }

            $serverUuid = $this->pickServerUuidFromResources($resources);
            if ($serverUuid === '') {
                return $this->failure('لم يُحدد سيرفر للمشروع');
            }

            $hints = $this->buildMatchHintsFromResources($resources);
            $serverData = $this->getServerMetrics($serverUuid, true);
            if (! ($serverData['success'] ?? false)) {
                return $serverData;
            }

            $matched = $this->filterContainersByHints($serverData['containers'] ?? [], $hints);
            $aggregated = $this->aggregateContainers($matched);

            return [
                'success' => true,
                'scope' => 'project',
                'project_uuid' => $projectUuid,
                'server_uuid' => $serverUuid,
                'host' => $serverData['host'] ?? '',
                'server' => $serverData['server'] ?? [],
                'containers' => $matched,
                'aggregated' => $aggregated,
                'match_count' => count($matched),
                'total_containers' => count($serverData['containers'] ?? []),
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getResourceMetrics(string $type, string $uuid, bool $refresh = false): array
    {
        $cacheKey = 'coolify_metrics_resource_'.$type.'_'.$uuid;
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($type, $uuid) {
            $resource = $this->fetchResource($type, $uuid);
            if ($resource === null) {
                return $this->failure('المورد غير موجود');
            }

            $serverUuid = $this->coolify->extractResourceServerUuid($resource);

            if ($serverUuid === '') {
                return $this->failure(
                    'تعذّر تحديد السيرفر لهذا المورد. في Coolify تأكد من ربط الخدمة بمشروع وسيرفر، أو عيّن «السيرفر الافتراضي» في إعدادات Coolify → مواقع WordPress.'
                );
            }

            $hints = $this->buildMatchHintsFromResource($resource, $type, $uuid);
            $serverData = $this->getServerMetrics($serverUuid, true);
            if (! ($serverData['success'] ?? false)) {
                return $serverData;
            }

            $matched = $this->filterContainersByHints($serverData['containers'] ?? [], $hints);
            if ($matched === [] && $type === 'service') {
                $serviceName = Str::slug((string) ($resource['name'] ?? ''), '');
                $matched = $this->filterContainersByServiceName($serverData['containers'] ?? [], (string) ($resource['name'] ?? ''));
            }

            return [
                'success' => true,
                'scope' => 'resource',
                'resource_type' => $type,
                'resource_uuid' => $uuid,
                'resource_name' => $resource['name'] ?? $uuid,
                'server_uuid' => $serverUuid,
                'host' => $serverData['host'] ?? '',
                'server' => $serverData['server'] ?? [],
                'containers' => $matched,
                'aggregated' => $this->aggregateContainers($matched),
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchResource(string $type, string $uuid): ?array
    {
        $response = match ($type) {
            'application' => $this->coolify->getApplication($uuid),
            'service' => $this->coolify->getService($uuid),
            'database' => $this->coolify->getDatabase($uuid),
            default => ['success' => false],
        };

        if (! ($response['success'] ?? false)) {
            return null;
        }

        $data = $response['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    protected function resolveServerHost(string $serverUuid): string
    {
        if ($serverUuid !== '') {
            $res = $this->coolify->getServer($serverUuid);
            if ($res['success'] ?? false) {
                $server = is_array($res['data'] ?? null) ? $res['data'] : [];
                $ip = trim((string) ($server['ip'] ?? $server['host'] ?? ''));
                if ($ip !== '') {
                    return $ip;
                }
            }
        }

        return trim($this->settings->getSshHostFallback());
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectHostMetrics(string $host): array
    {
        $script = <<<'SH'
free -b 2>/dev/null | awk '/^Mem:/ {t=$2; u=$3; print "MEM_TOTAL=" t; print "MEM_USED=" u}'
grep '^cpu ' /proc/stat 2>/dev/null | awk '{idle=$5+$6; total=0; for(i=2;i<=NF;i++) total+=$i; print "CPU_IDLE=" idle; print "CPU_TOTAL=" total}'
sleep 1
grep '^cpu ' /proc/stat 2>/dev/null | awk '{idle=$5+$6; total=0; for(i=2;i<=NF;i++) total+=$i; print "CPU_IDLE2=" idle; print "CPU_TOTAL2=" total}'
df -B1 --output=source,size,used,avail,pcent,target 2>/dev/null | tail -n +2
SH;

        $result = $this->ssh->run($host, $script, 30);
        if (! ($result['success'] ?? false)) {
            return $this->failure('فشل SSH: '.($result['output'] ?? ''));
        }

        return $this->parseHostOutput($result['output'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseHostOutput(string $output): array
    {
        $memTotal = 0;
        $memUsed = 0;
        $cpuIdle1 = $cpuTotal1 = $cpuIdle2 = $cpuTotal2 = 0;
        $disks = [];

        foreach (preg_split('/\r\n|\r|\n/', $output) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'MEM_TOTAL=')) {
                $memTotal = (int) substr($line, 10);
            } elseif (str_starts_with($line, 'MEM_USED=')) {
                $memUsed = (int) substr($line, 9);
            } elseif (str_starts_with($line, 'CPU_IDLE=')) {
                $cpuIdle1 = (float) substr($line, 9);
            } elseif (str_starts_with($line, 'CPU_TOTAL=')) {
                $cpuTotal1 = (float) substr($line, 10);
            } elseif (str_starts_with($line, 'CPU_IDLE2=')) {
                $cpuIdle2 = (float) substr($line, 10);
            } elseif (str_starts_with($line, 'CPU_TOTAL2=')) {
                $cpuTotal2 = (float) substr($line, 11);
            } elseif ($line !== '' && ! str_starts_with($line, 'CPU_') && ! str_starts_with($line, 'MEM_')) {
                $parts = preg_split('/\s+/', $line, 6);
                if (count($parts) >= 5) {
                    $disks[] = [
                        'source' => $parts[0],
                        'size_bytes' => (int) $parts[1],
                        'used_bytes' => (int) $parts[2],
                        'avail_bytes' => (int) $parts[3],
                        'percent' => $this->parsePercent($parts[4]),
                        'mount' => $parts[5] ?? '',
                    ];
                }
            }
        }

        $cpuPercent = $this->calcCpuPercent($cpuIdle1, $cpuTotal1, $cpuIdle2, $cpuTotal2);
        $ramPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0;

        $rootDisk = collect($disks)->first(fn ($d) => ($d['mount'] ?? '') === '/')
            ?? ($disks[0] ?? null);

        return [
            'success' => true,
            'server' => [
                'cpu_percent' => $cpuPercent,
                'ram_percent' => $ramPercent,
                'ram_used_bytes' => $memUsed,
                'ram_total_bytes' => $memTotal,
                'disk_percent' => $rootDisk['percent'] ?? 0,
                'disk_used_bytes' => $rootDisk['used_bytes'] ?? 0,
                'disk_total_bytes' => $rootDisk['size_bytes'] ?? 0,
                'disks' => $disks,
            ],
        ];
    }

    protected function calcCpuPercent(float $idle1, float $total1, float $idle2, float $total2): float
    {
        $idleDelta = $idle2 - $idle1;
        $totalDelta = $total2 - $total1;
        if ($totalDelta <= 0) {
            return 0;
        }

        return round(max(0, min(100, (1 - ($idleDelta / $totalDelta)) * 100)), 1);
    }

    /**
     * @return array{containers: array<int, array<string, mixed>>}
     */
    protected function collectContainerMetrics(string $host): array
    {
        $cmd = 'docker stats --no-stream --format "{{json .}}" 2>/dev/null';
        $result = $this->ssh->run($host, $cmd, 60);
        if (! ($result['success'] ?? false)) {
            return ['containers' => []];
        }

        $containers = [];
        foreach (preg_split('/\r\n|\r|\n/', $result['output'] ?? '') ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (! is_array($row)) {
                continue;
            }
            $containers[] = [
                'name' => $row['Name'] ?? $row['name'] ?? '',
                'id' => $row['ID'] ?? $row['id'] ?? '',
                'cpu_percent' => $this->parsePercent($row['CPUPerc'] ?? $row['cpu'] ?? '0'),
                'mem_percent' => $this->parsePercent($row['MemPerc'] ?? $row['mem'] ?? '0'),
                'mem_usage' => $row['MemUsage'] ?? '',
                'net_io' => $row['NetIO'] ?? '',
                'block_io' => $row['BlockIO'] ?? '',
            ];
        }

        usort($containers, fn ($a, $b) => ($b['cpu_percent'] ?? 0) <=> ($a['cpu_percent'] ?? 0));

        return ['containers' => $containers];
    }

    protected function parsePercent(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 1);
        }

        return round((float) preg_replace('/[^0-9.]/', '', (string) $value), 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $resources
     */
    protected function pickServerUuidFromResources(array $resources): string
    {
        foreach ($resources as $r) {
            $uuid = (string) ($r['server_uuid'] ?? '');
            if ($uuid !== '') {
                return $uuid;
            }
            $resolved = $this->coolify->resolveResourceServer($r);
            if (! empty($resolved['server_uuid'])) {
                return (string) $resolved['server_uuid'];
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $resources
     * @return array<int, string>
     */
    protected function buildMatchHintsFromResources(array $resources): array
    {
        $hints = [];
        foreach ($resources as $r) {
            $hints = array_merge($hints, $this->buildMatchHintsFromResource($r, (string) ($r['type'] ?? ''), (string) ($r['uuid'] ?? '')));
        }

        return array_values(array_unique(array_filter($hints)));
    }

    /**
     * @return array<int, string>
     */
    protected function buildMatchHintsFromResource(array $resource, string $type, string $uuid): array
    {
        $hints = [];
        $name = strtolower((string) ($resource['name'] ?? ''));
        if ($name !== '') {
            $hints[] = $name;
            $hints[] = Str::slug($name, '');
        }
        if ($uuid !== '') {
            $hints[] = strtolower($uuid);
            $hints[] = substr(str_replace('-', '', $uuid), 0, 12);
        }

        return array_values(array_unique(array_filter($hints, fn ($h) => strlen($h) >= 3)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $containers
     * @param  array<int, string>  $hints
     * @return array<int, array<string, mixed>>
     */
    protected function filterContainersByHints(array $containers, array $hints): array
    {
        if ($hints === []) {
            return [];
        }

        return array_values(array_filter($containers, function (array $c) use ($hints) {
            $name = strtolower((string) ($c['name'] ?? ''));

            foreach ($hints as $hint) {
                if ($hint !== '' && str_contains($name, strtolower($hint))) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $containers
     * @return array<int, array<string, mixed>>
     */
    protected function filterContainersByServiceName(array $containers, string $serviceName): array
    {
        $slug = Str::slug($serviceName, '');
        $normalized = strtolower(str_replace(['_', ' '], '-', $serviceName));

        return array_values(array_filter($containers, function (array $c) use ($slug, $normalized) {
            $name = strtolower((string) ($c['name'] ?? ''));

            return ($slug !== '' && str_contains($name, $slug))
                || ($normalized !== '' && str_contains($name, $normalized));
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $containers
     * @return array{cpu_percent: float, mem_percent: float, container_count: int}
     */
    protected function aggregateContainers(array $containers): array
    {
        if ($containers === []) {
            return ['cpu_percent' => 0, 'mem_percent' => 0, 'container_count' => 0];
        }

        $cpu = 0;
        $mem = 0;
        foreach ($containers as $c) {
            $cpu += (float) ($c['cpu_percent'] ?? 0);
            $mem += (float) ($c['mem_percent'] ?? 0);
        }

        return [
            'cpu_percent' => round($cpu, 1),
            'mem_percent' => round($mem / max(1, count($containers)), 1),
            'container_count' => count($containers),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'server' => [
                'cpu_percent' => 0,
                'ram_percent' => 0,
                'disk_percent' => 0,
            ],
            'containers' => [],
            'fetched_at' => now()->toIso8601String(),
        ];
    }
}
