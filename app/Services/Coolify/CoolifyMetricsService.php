<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use App\Services\Monitoring\HostMetricsCollector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoolifyMetricsService
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings,
        protected HostMetricsCollector $collector
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
            $endpoint = $this->coolify->resolveServerSshEndpoint($serverUuid);
            if (! ($endpoint['success'] ?? false)) {
                return $this->failure(
                    $endpoint['message'] ?? 'لا يوجد عنوان SSH للسيرفر. اضبط IP السيرفر في إعدادات Coolify → SSH.'
                );
            }

            $host = (string) ($endpoint['host'] ?? '');
            $port = (int) ($endpoint['port'] ?? 22);
            $hostMetrics = $this->collector->collectHostMetrics($host, $port);
            if (! ($hostMetrics['success'] ?? false)) {
                return $this->failure($hostMetrics['message'] ?? 'فشل جلب مقاييس السيرفر');
            }

            $containers = $this->collector->collectContainerMetrics($host, $port);

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
