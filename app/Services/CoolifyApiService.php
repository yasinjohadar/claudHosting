<?php

namespace App\Services;

use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoolifyApiService
{
    protected string $baseUrl;

    protected string $token;

    protected int $timeout;

    public function __construct(protected CoolifySettingsService $settings)
    {
        $this->loadConnectionConfig();
    }

    protected function loadConnectionConfig(): void
    {
        $config = $this->settings->getConnectionConfig();
        $url = $config['api_url'] ?? '';
        $this->baseUrl = $url !== '' ? $url.'/api/v1' : '';
        $this->token = $config['api_token'] ?? '';
        $this->timeout = (int) ($config['timeout'] ?? 30);
    }

    public function isConfigured(): bool
    {
        return $this->token !== '' && str_starts_with($this->baseUrl, 'http');
    }

    /**
     * Use a different API token (e.g. per-client team token) while keeping URL and timeout.
     */
    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return array{success: bool, data?: mixed, message?: string, status?: int}
     */
    protected function request(string $method, string $path, array $data = [], array $query = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'يرجى ضبط عنوان API ورمز التوكن من صفحة إعدادات Coolify في لوحة التحكم',
            ];
        }

        $url = $this->baseUrl.'/'.ltrim($path, '/');

        try {
            $pending = Http::withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout);

            if (! empty($query)) {
                $pending = $pending->withQueryParameters($query);
            }

            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url),
                'POST' => $pending->post($url, $data),
                'PATCH' => $pending->patch($url, $data),
                'DELETE' => $pending->delete($url, $data),
                default => $pending->get($url),
            };

            $body = $response->json();
            if (! is_array($body) && $response->body() !== '') {
                $body = ['raw' => $response->body()];
            }
            if (! is_array($body)) {
                $body = [];
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $body,
                    'status' => $response->status(),
                ];
            }

            Log::warning('Coolify API error', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => $this->formatErrorMessage($body, $response->status()),
                'data' => $body,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Coolify API exception', [
                'method' => $method,
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize list responses (array root or keyed collection).
     *
     * @return array<int, array<string, mixed>>
     */
    public function normalizeList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }
        if (array_is_list($data)) {
            return $data;
        }
        foreach (['data', 'servers', 'projects', 'applications', 'databases', 'services', 'deployments', 'keys', 'resources', 'executions', 'postgresqls', 'mysqls', 'mariadbs', 'mongodbs', 'redis', 'environments'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_is_list($data[$key]) ? $data[$key] : array_values($data[$key]);
            }
        }

        return array_values($data);
    }

    public function ping(): bool
    {
        $health = $this->request('GET', 'health');
        if ($health['success']) {
            return true;
        }

        $version = $this->request('GET', 'version');

        return $version['success'];
    }

    public function getVersion(): array
    {
        return $this->request('GET', 'version');
    }

    public function getHealth(): array
    {
        return $this->request('GET', 'health');
    }

    public function getSystemVersion(): array
    {
        return $this->getVersion();
    }

    public function getSystemHealth(): array
    {
        return $this->getHealth();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function hetznerOptionValue(array $item): string
    {
        foreach (['name', 'id', 'location', 'slug'] as $key) {
            if (! empty($item[$key])) {
                return (string) $item[$key];
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function hetznerOptionLabel(array $item): string
    {
        $value = self::hetznerOptionValue($item);
        $extra = $item['description'] ?? $item['city'] ?? null;

        return $extra && $extra !== $value ? "{$value} — {$extra}" : ($value ?: json_encode($item));
    }

    // --- Servers ---

    public function listServers(): array
    {
        return $this->request('GET', 'servers');
    }

    public function getServer(string $uuid): array
    {
        return $this->request('GET', "servers/{$uuid}");
    }

    public function createServer(array $data): array
    {
        return $this->request('POST', 'servers', $data);
    }

    public function updateServer(string $uuid, array $data): array
    {
        return $this->request('PATCH', "servers/{$uuid}", $data);
    }

    public function deleteServer(string $uuid): array
    {
        return $this->request('DELETE', "servers/{$uuid}");
    }

    public function validateServer(string $uuid): array
    {
        return $this->request('GET', "servers/{$uuid}/validate");
    }

    public function serverResources(string $uuid): array
    {
        return $this->request('GET', "servers/{$uuid}/resources");
    }

    public function serverDomains(string $uuid): array
    {
        return $this->request('GET', "servers/{$uuid}/domains");
    }

    // --- Projects ---

    public function listProjects(): array
    {
        return $this->request('GET', 'projects');
    }

    public function getProject(string $uuid): array
    {
        return $this->request('GET', "projects/{$uuid}");
    }

    public function createProject(array $data): array
    {
        return $this->request('POST', 'projects', $data);
    }

    public function updateProject(string $uuid, array $data): array
    {
        return $this->request('PATCH', "projects/{$uuid}", $data);
    }

    public function deleteProject(string $uuid): array
    {
        return $this->request('DELETE', "projects/{$uuid}");
    }

    public function projectEnvironment(string $uuid, string $environment): array
    {
        return $this->request('GET', "projects/{$uuid}/{$environment}");
    }

    public function projectResources(string $uuid): array
    {
        $legacy = $this->request('GET', "projects/{$uuid}/resources");
        $legacyItems = ($legacy['success'] ?? false)
            ? $this->normalizeList($legacy['data'] ?? [])
            : [];

        if ($legacyItems !== []) {
            return $legacy;
        }

        $items = $this->collectProjectResourcesFromEnvironments($uuid);

        if ($items !== []) {
            return ['success' => true, 'data' => $items];
        }

        if ($legacy['success'] ?? false) {
            return $legacy;
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }

    /**
     * Coolify v4 lists resources per environment (not projects/{uuid}/resources).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function collectProjectResourcesFromEnvironments(string $projectUuid): array
    {
        $items = [];

        foreach ($this->listProjectEnvironments($projectUuid) as $env) {
            $envKey = (string) ($env['name'] ?? $env['uuid'] ?? '');
            if ($envKey === '') {
                continue;
            }

            $response = $this->projectEnvironment($projectUuid, $envKey);
            if (! ($response['success'] ?? false)) {
                continue;
            }

            $envData = $response['data'] ?? [];
            if (! is_array($envData)) {
                continue;
            }

            foreach ($this->flattenEnvironmentResources($envData, $projectUuid) as $resource) {
                $items[] = $resource;
            }
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listProjectEnvironments(string $projectUuid): array
    {
        $envResponse = $this->request('GET', "projects/{$projectUuid}/environments");
        if ($envResponse['success'] ?? false) {
            $envs = $this->normalizeList($envResponse['data'] ?? []);
            if ($envs !== []) {
                return $envs;
            }
        }

        $projectResponse = $this->getProject($projectUuid);
        if (! ($projectResponse['success'] ?? false)) {
            return [];
        }

        $project = $projectResponse['data'] ?? [];
        if (! is_array($project)) {
            return [];
        }

        return $this->normalizeList($project['environments'] ?? []);
    }

    public function createProjectEnvironment(string $projectUuid, string $name): array
    {
        return $this->request('POST', "projects/{$projectUuid}/environments", ['name' => $name]);
    }

    /**
     * @return array{environment_name: string, environment_uuid: string|null}
     */
    public function resolveProjectEnvironment(string $projectUuid, string $preferredName): array
    {
        $preferredName = trim($preferredName) !== '' ? trim($preferredName) : 'production';

        foreach ($this->listProjectEnvironments($projectUuid) as $env) {
            $name = (string) ($env['name'] ?? '');
            if (strtolower($name) === strtolower($preferredName)) {
                return [
                    'environment_name' => $name,
                    'environment_uuid' => isset($env['uuid']) ? (string) $env['uuid'] : null,
                ];
            }
        }

        $create = $this->createProjectEnvironment($projectUuid, $preferredName);
        if ($create['success'] ?? false) {
            $data = $create['data'] ?? [];

            return [
                'environment_name' => $preferredName,
                'environment_uuid' => (string) ($data['uuid'] ?? ''),
            ];
        }

        foreach ($this->listProjectEnvironments($projectUuid) as $env) {
            $name = (string) ($env['name'] ?? '');
            if ($name !== '') {
                return [
                    'environment_name' => $name,
                    'environment_uuid' => isset($env['uuid']) ? (string) $env['uuid'] : null,
                ];
            }
        }

        return [
            'environment_name' => $preferredName,
            'environment_uuid' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractServerDestinations(array $server): array
    {
        $destinations = $this->normalizeList($server['destinations'] ?? []);

        if ($destinations !== []) {
            return $destinations;
        }

        if (isset($server['destination']) && is_array($server['destination'])) {
            return [$server['destination']];
        }

        return [];
    }

    public function resolveServerDestinationUuid(string $serverUuid, ?string $configuredUuid = null): ?string
    {
        if ($configuredUuid !== null && trim($configuredUuid) !== '') {
            return trim($configuredUuid);
        }

        $response = $this->getServer($serverUuid);
        if (! ($response['success'] ?? false)) {
            return null;
        }

        $server = $response['data'] ?? [];
        if (! is_array($server)) {
            return null;
        }

        $destinations = $this->extractServerDestinations($server);
        if ($destinations === []) {
            $resourcesResponse = $this->request('GET', "servers/{$serverUuid}/resources");
            if ($resourcesResponse['success'] ?? false) {
                $resources = $resourcesResponse['data'] ?? [];
                if (is_array($resources)) {
                    $destinations = $this->normalizeList($resources['destinations'] ?? $resources);
                }
            }
        }

        if ($destinations === []) {
            return null;
        }

        $first = $destinations[0];

        return (string) ($first['uuid'] ?? $first['id'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function formatApiErrorMessage(array $body, int $status): string
    {
        return $this->formatErrorMessage($body, $status);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function flattenEnvironmentResources(array $environment, string $projectUuid): array
    {
        $collections = [
            'applications' => 'application',
            'services' => 'service',
            'postgresqls' => 'database',
            'mysqls' => 'database',
            'mariadbs' => 'database',
            'mongodbs' => 'database',
            'redis' => 'database',
        ];

        $engines = [
            'postgresqls' => 'postgresql',
            'mysqls' => 'mysql',
            'mariadbs' => 'mariadb',
            'mongodbs' => 'mongodb',
            'redis' => 'redis',
        ];

        $out = [];

        foreach ($collections as $key => $type) {
            $collection = $environment[$key] ?? null;
            if (! is_array($collection)) {
                continue;
            }

            foreach ($this->normalizeList($collection) as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $uuid = (string) ($item['uuid'] ?? $item['id'] ?? '');
                if ($uuid === '') {
                    continue;
                }

                $out[] = array_merge($item, [
                    'uuid' => $uuid,
                    'type' => $type,
                    'resource_type' => $item['type'] ?? ($engines[$key] ?? $type),
                    'project_uuid' => $projectUuid,
                ]);
            }
        }

        return $out;
    }

    public function listResources(): array
    {
        return $this->request('GET', 'resources');
    }

    // --- Applications ---

    public function listApplications(): array
    {
        return $this->request('GET', 'applications');
    }

    public function getApplication(string $uuid): array
    {
        return $this->request('GET', "applications/{$uuid}");
    }

    public function createApplicationPublic(array $data): array
    {
        return $this->request('POST', 'applications/public', $data);
    }

    public function createApplicationPrivateGithub(array $data): array
    {
        return $this->request('POST', 'applications/private-github-app', $data);
    }

    public function createApplicationPrivateDeployKey(array $data): array
    {
        return $this->request('POST', 'applications/private-deploy-key', $data);
    }

    public function createApplicationDockerfile(array $data): array
    {
        return $this->request('POST', 'applications/dockerfile', $data);
    }

    public function createApplicationDockerImage(array $data): array
    {
        return $this->request('POST', 'applications/dockerimage', $data);
    }

    public function createApplicationDockerCompose(array $data): array
    {
        return $this->request('POST', 'applications/dockercompose', $data);
    }

    public function updateApplication(string $uuid, array $data): array
    {
        return $this->request('PATCH', "applications/{$uuid}", $data);
    }

    public function deleteApplication(string $uuid): array
    {
        return $this->request('DELETE', "applications/{$uuid}");
    }

    public function applicationLogs(string $uuid, int $lines = 100): array
    {
        return $this->request('GET', "applications/{$uuid}/logs", [], ['lines' => $lines]);
    }

    public function startApplication(string $uuid): array
    {
        return $this->request('GET', "applications/{$uuid}/start");
    }

    public function stopApplication(string $uuid): array
    {
        return $this->request('GET', "applications/{$uuid}/stop");
    }

    public function restartApplication(string $uuid): array
    {
        return $this->request('GET', "applications/{$uuid}/restart");
    }

    public function listApplicationEnvs(string $uuid): array
    {
        return $this->request('GET', "applications/{$uuid}/envs");
    }

    public function createApplicationEnv(string $uuid, array $data): array
    {
        return $this->request('POST', "applications/{$uuid}/envs", $data);
    }

    public function updateApplicationEnv(string $uuid, string $envUuid, array $data): array
    {
        return $this->request('PATCH', "applications/{$uuid}/envs/{$envUuid}", $data);
    }

    public function bulkUpdateApplicationEnvs(string $uuid, array $data): array
    {
        return $this->request('PATCH', "applications/{$uuid}/envs/bulk", $data);
    }

    public function deleteApplicationEnv(string $uuid, string $envUuid): array
    {
        return $this->request('DELETE', "applications/{$uuid}/envs/{$envUuid}");
    }

    public function listStorages(): array
    {
        return $this->request('GET', 'storages');
    }

    public function getS3Storage(string $uuid): array
    {
        foreach (['s3-storages/'.$uuid, 's3-storages/{uuid}', 'storages/'.$uuid] as $path) {
            $path = str_replace('{uuid}', $uuid, $path);
            $response = $this->request('GET', $path);
            if ($response['success'] ?? false) {
                return $response;
            }
        }

        return ['success' => false, 'message' => 'تعذر جلب تفاصيل S3'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listS3Storages(): array
    {
        foreach (['s3-storages', 'storages', 'storage/s3', 'destinations', 's3'] as $path) {
            $response = $this->request('GET', $path);
            if (! ($response['success'] ?? false)) {
                continue;
            }

            $list = $this->normalizeList($response['data'] ?? []);
            $filtered = array_values(array_filter($list, function (array $row) {
                $type = strtolower((string) ($row['type'] ?? $row['driver'] ?? $row['resource_type'] ?? ''));

                return str_contains($type, 's3')
                    || ($row['is_s3'] ?? false)
                    || isset($row['bucket'], $row['key']);
            }));

            if ($filtered !== []) {
                return $filtered;
            }

            if ($list !== []) {
                return $list;
            }
        }

        return [];
    }

    /**
     * استخراج UUID تخزين S3 من جداول النسخ الموجودة في Coolify (بعد ضبط S3 في لوحة Coolify).
     */
    public function discoverS3StorageUuidFromBackups(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $databases = $this->normalizeList($this->listDatabases()['data'] ?? []);

        foreach ($databases as $database) {
            $dbUuid = (string) ($database['uuid'] ?? '');
            if ($dbUuid === '') {
                continue;
            }

            $backups = $this->listDatabaseBackups($dbUuid);
            if (! ($backups['success'] ?? false)) {
                continue;
            }

            foreach ($this->normalizeList($backups['data'] ?? []) as $config) {
                $s3Uuid = trim((string) ($config['s3_storage_uuid'] ?? ''));
                if ($s3Uuid !== '') {
                    return $s3Uuid;
                }
            }
        }

        foreach ($this->listS3Storages() as $storage) {
            $uuid = trim((string) ($storage['uuid'] ?? $storage['id'] ?? ''));
            if ($uuid !== '') {
                return $uuid;
            }
        }

        $team = $this->request('GET', 'teams/current');
        if ($team['success'] ?? false) {
            $uuid = $this->extractS3UuidFromPayload($team['data'] ?? []);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        return null;
    }

    /**
     * @param  mixed  $payload
     */
    protected function extractS3UuidFromPayload(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        foreach ($payload as $key => $value) {
            if ($key === 's3_storage_uuid' && is_string($value) && strlen($value) > 10) {
                return trim($value);
            }
            if (is_array($value)) {
                if (isset($value['bucket'], $value['uuid']) && is_string($value['uuid'])) {
                    return trim($value['uuid']);
                }
                $nested = $this->extractS3UuidFromPayload($value);
                if ($nested !== '') {
                    return $nested;
                }
            }
        }

        return '';
    }

    public function listApplicationStorages(string $applicationUuid): array
    {
        return $this->request('GET', "applications/{$applicationUuid}/storages");
    }

    public function createApplicationStorage(string $applicationUuid, array $data): array
    {
        return $this->request('POST', "applications/{$applicationUuid}/storages", $data);
    }

    public function updateApplicationStorage(string $applicationUuid, string $storageId, array $data): array
    {
        return $this->request('PATCH', "applications/{$applicationUuid}/storages/{$storageId}", $data);
    }

    public function deleteApplicationStorage(string $applicationUuid, string $storageId): array
    {
        return $this->request('DELETE', "applications/{$applicationUuid}/storages/{$storageId}");
    }

    /**
     * مرشّحو SSH مرتّبون: إعدادات يدوية → مصادر الموقع → IP من Coolify → نطاق اللوحة (IP فقط أو مع fallback).
     *
     * @param  array<int, array{host: string, source: string, priority: int}>  $extra
     * @return array<int, array{host: string, source: string, priority: int}>
     */
    public function listServerSshHostCandidates(string $serverUuid, array $extra = []): array
    {
        $serverUuid = trim($serverUuid);
        if ($serverUuid === '') {
            return [];
        }

        $raw = array_merge($extra, []);
        $hasFallback = $this->settings->getSshHostFallback() !== '';

        $fallback = $this->settings->getSshHostFallback();
        if ($fallback !== '') {
            $raw[] = ['host' => $fallback, 'source' => 'settings_fallback', 'priority' => 1];
        }

        $response = $this->getServer($serverUuid);
        if ($response['success'] ?? false) {
            foreach ($this->collectServerHostCandidates($response['data'] ?? []) as $host) {
                $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
                $raw[] = [
                    'host' => $host,
                    'source' => 'coolify_server',
                    'priority' => $isIp ? 10 : 25,
                ];
            }
        }

        $apiHost = $this->hostFromConnectionUrl();
        if ($apiHost !== '') {
            $isIp = filter_var($apiHost, FILTER_VALIDATE_IP) !== false;
            if ($isIp || $hasFallback) {
                $raw[] = [
                    'host' => $apiHost,
                    'source' => 'api_url',
                    'priority' => $isIp ? 15 : 100,
                ];
            }
        }

        $seen = [];
        $out = [];
        usort($raw, fn ($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        foreach ($raw as $item) {
            $host = strtolower(trim((string) ($item['host'] ?? '')));
            if ($host === '' || isset($seen[$host])) {
                continue;
            }
            if ($this->isUnusableSshHost($host)) {
                $rejected[] = $host;
                continue;
            }
            $seen[$host] = true;
            $out[] = [
                'host' => $host,
                'source' => (string) ($item['source'] ?? 'unknown'),
                'priority' => (int) ($item['priority'] ?? 99),
            ];
        }

        return $out;
    }

    /**
     * @return array{success: bool, host: string, message?: string, source?: string, rejected?: array<int, string>, candidates?: array<int, array{host: string, source: string}>}
     */
    public function resolveServerSshHost(string $serverUuid): array
    {
        $candidates = $this->listServerSshHostCandidates($serverUuid);
        if ($candidates === []) {
            return [
                'success' => false,
                'host' => '',
                'message' => 'لم يُعثر على عنوان SSH. اضبط «عنوان SSH للسيرفر» بـ IP الحقيقي (ليس نطاق لوحة Coolify).',
            ];
        }

        $first = $candidates[0];

        return [
            'success' => true,
            'host' => $first['host'],
            'source' => $first['source'],
            'candidates' => $candidates,
        ];
    }

    public function isUnusableSshHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return true;
        }

        $blocked = [
            'host.docker.internal',
            'gateway.docker.internal',
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
        ];

        if (in_array($host, $blocked, true)) {
            return true;
        }

        if (str_ends_with($host, '.docker.internal')) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $server
     * @return array<int, string>
     */
    protected function collectServerHostCandidates(array $server): array
    {
        $out = $this->extractIpAddressesFromArray($server);
        $keys = ['ip', 'public_ip', 'private_ip', 'hostname', 'host', 'address', 'fqdn'];

        foreach ($keys as $key) {
            $value = trim((string) ($server[$key] ?? ''));
            if ($value !== '') {
                $out[] = $value;
            }
        }

        foreach (['settings', 'proxy', 'swarm'] as $nested) {
            $block = $server[$nested] ?? null;
            if (! is_array($block)) {
                continue;
            }
            foreach (['ip', 'public_ip', 'private_ip', 'host', 'hostname', 'address'] as $key) {
                $value = trim((string) ($block[$key] ?? ''));
                if ($value !== '') {
                    $out[] = $value;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<int, string>
     */
    public function extractIpAddressesFromArray(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        $ips = [];
        foreach ($data as $value) {
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '' && filter_var($value, FILTER_VALIDATE_IP) !== false && ! $this->isUnusableSshHost($value)) {
                    $ips[] = $value;
                }
            } elseif (is_array($value)) {
                $ips = array_merge($ips, $this->extractIpAddressesFromArray($value, $depth + 1));
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * @return array{ip: string, port: int, name: string, raw_ip: string}
     */
    public function describeServerConnection(string $serverUuid): array
    {
        $response = $this->getServer($serverUuid);
        if (! ($response['success'] ?? false)) {
            return ['ip' => '', 'port' => 22, 'name' => '', 'raw_ip' => ''];
        }

        $server = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'ip' => trim((string) ($server['ip'] ?? '')),
            'port' => (int) ($server['port'] ?? 22),
            'name' => trim((string) ($server['name'] ?? '')),
            'raw_ip' => trim((string) ($server['ip'] ?? '')),
        ];
    }

    public function requiresSshHostFallback(): bool
    {
        return $this->settings->getSshHostFallback() === '';
    }

    protected function hostFromConnectionUrl(): string
    {
        $url = $this->settings->getConnectionConfig()['api_url'] ?? '';
        if ($url === '') {
            return '';
        }

        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST)));

        return $this->isUnusableSshHost($host) ? '' : $host;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array{host: string, server_uuid: string|null}|null
     */
    public function resolveResourceServer(array $resource): ?array
    {
        $serverUuid = (string) ($resource['server_uuid'] ?? $resource['destination_uuid'] ?? '');
        if ($serverUuid === '') {
            return null;
        }

        $resolved = $this->resolveServerSshHost($serverUuid);

        return [
            'host' => $resolved['host'] ?? '',
            'server_uuid' => $serverUuid,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResourceDetails(string $type, string $uuid): ?array
    {
        $response = match (true) {
            str_contains(strtolower($type), 'application') => $this->getApplication($uuid),
            str_contains(strtolower($type), 'database') => $this->getDatabase($uuid),
            str_contains(strtolower($type), 'service') => $this->getService($uuid),
            default => ['success' => false],
        };

        if (! ($response['success'] ?? false)) {
            return null;
        }

        return is_array($response['data'] ?? null) ? $response['data'] : null;
    }

    // --- Databases ---

    public function listDatabases(): array
    {
        return $this->request('GET', 'databases');
    }

    public function getDatabase(string $uuid): array
    {
        return $this->request('GET', "databases/{$uuid}");
    }

    public function createDatabase(string $type, array $data): array
    {
        return $this->request('POST', "databases/{$type}", $data);
    }

    public function updateDatabase(string $uuid, array $data): array
    {
        return $this->request('PATCH', "databases/{$uuid}", $data);
    }

    public function deleteDatabase(string $uuid): array
    {
        return $this->request('DELETE', "databases/{$uuid}");
    }

    public function startDatabase(string $uuid): array
    {
        return $this->request('GET', "databases/{$uuid}/start");
    }

    public function stopDatabase(string $uuid): array
    {
        return $this->request('GET', "databases/{$uuid}/stop");
    }

    public function restartDatabase(string $uuid): array
    {
        return $this->request('GET', "databases/{$uuid}/restart");
    }

    /** @return array<string, string> */
    public static function databaseTypes(): array
    {
        return [
            'postgresql' => 'PostgreSQL',
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'mongodb' => 'MongoDB',
            'redis' => 'Redis',
            'keydb' => 'KeyDB',
            'clickhouse' => 'ClickHouse',
            'dragonfly' => 'Dragonfly',
        ];
    }

    // --- Services ---

    public function listServices(): array
    {
        return $this->request('GET', 'services');
    }

    public function getService(string $uuid): array
    {
        return $this->request('GET', "services/{$uuid}");
    }

    public function getServiceTypes(): array
    {
        return $this->request('GET', 'services/types');
    }

    public function createService(array $data): array
    {
        return $this->request('POST', 'services', $this->filterServicePayload($data));
    }

    /**
     * Coolify يقبل حقولاً محددة فقط؛ لا تُرسل null أو حقول غير مدعومة في POST.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function filterServicePayload(array $data, bool $includeUrls = false): array
    {
        $allowed = [
            'type', 'name', 'description', 'project_uuid', 'environment_name', 'environment_uuid',
            'server_uuid', 'destination_uuid', 'instant_deploy', 'docker_compose_raw',
            'is_container_label_escape_enabled',
        ];
        if ($includeUrls) {
            $allowed[] = 'urls';
            $allowed[] = 'force_domain_override';
        }

        $filtered = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($value === null || $value === '') {
                continue;
            }
            $filtered[$key] = $value;
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function formatErrorMessage(array $body, int $status): string
    {
        $message = (string) ($body['message'] ?? $body['error'] ?? 'فشل الطلب: HTTP '.$status);

        if (isset($body['raw']) && is_string($body['raw']) && $body['raw'] !== '') {
            $message .= ' — '.$body['raw'];
        }

        $parts = $this->flattenValidationErrors($body['errors'] ?? null);

        if ($parts !== []) {
            return $message.' — '.implode(' | ', $parts);
        }

        return $message;
    }

    /**
     * @return array<int, string>
     */
    protected function flattenValidationErrors(mixed $errors): array
    {
        if ($errors === null) {
            return [];
        }

        if (is_object($errors)) {
            $errors = json_decode(json_encode($errors), true);
        }

        if (! is_array($errors)) {
            return is_string($errors) ? [$errors] : [];
        }

        $parts = [];
        foreach ($errors as $field => $msgs) {
            if (is_array($msgs)) {
                foreach ($msgs as $m) {
                    $parts[] = is_string($field) ? $field.': '.$m : (string) $m;
                }
            } elseif (is_string($msgs)) {
                $parts[] = is_string($field) && ! is_numeric($field) ? $field.': '.$msgs : $msgs;
            }
        }

        return $parts;
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    public function buildServiceUrls(string $publicUrl, string $containerName = 'wordpress'): array
    {
        $url = $this->normalizeServiceUrl($publicUrl);
        if ($url === '') {
            return [];
        }

        return [['name' => $containerName, 'url' => $url]];
    }

    /**
     * يبني مصفوفة urls حسب أسماء حاويات الخدمة على Coolify (مطلوب لـ Traefik).
     *
     * @param  array<string, mixed>  $service
     * @return array<int, array{name: string, url: string}>
     */
    public function buildServiceUrlsForService(array $service, string $publicUrl): array
    {
        $url = $this->normalizeServiceUrl($publicUrl);
        if ($url === '') {
            return [];
        }

        $names = [];
        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $name = trim((string) ($app['name'] ?? ''));
            $fqdn = trim((string) ($app['fqdn'] ?? ''));
            if ($name !== '' && $fqdn !== '') {
                $names[] = $name;
            }
        }

        if ($names === []) {
            $names = ['wordpress'];
        }

        return array_map(
            static fn (string $name): array => ['name' => $name, 'url' => $url],
            array_values(array_unique($names))
        );
    }

    protected function normalizeServiceUrl(string $publicUrl): string
    {
        $url = trim($publicUrl);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }

        return $url;
    }

    public function isCoolifyGeneratedHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }

        if (str_ends_with($host, '.sslip.io') || str_ends_with($host, '.sslip.dev')) {
            return true;
        }

        return (bool) preg_match('/\.(\d{1,3}(?:\.\d{1,3}){3})\.sslip\.io$/', $host);
    }

    /**
     * @return array<int, string>
     */
    public function collectServicePublicUrls(array $service): array
    {
        $seen = [];
        $ordered = [];

        $push = function (?string $raw) use (&$seen, &$ordered): void {
            if ($raw === null || trim($raw) === '') {
                return;
            }
            $url = $this->normalizePublicUrl($raw);
            if ($url === '' || isset($seen[$url])) {
                return;
            }
            $seen[$url] = true;
            $ordered[] = $url;
        };

        $wordpressApps = [];
        $otherApps = [];
        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $name = strtolower((string) ($app['name'] ?? ''));
            if (str_contains($name, 'wordpress')) {
                $wordpressApps[] = $app;
            } else {
                $otherApps[] = $app;
            }
        }

        foreach (array_merge($wordpressApps, $otherApps) as $app) {
            $push($app['fqdn'] ?? null);
            foreach ($this->normalizeList($app['urls'] ?? []) as $entry) {
                if (is_string($entry)) {
                    $push($entry);
                } elseif (is_array($entry)) {
                    $push($entry['url'] ?? $entry['name'] ?? $entry['fqdn'] ?? null);
                }
            }
        }

        foreach (['fqdn', 'domain', 'public_url'] as $key) {
            $value = $service[$key] ?? null;
            if (is_string($value)) {
                $push($value);
            }
        }

        foreach ($this->normalizeList($service['domains'] ?? []) as $domain) {
            if (is_string($domain)) {
                $push($domain);
            } elseif (is_array($domain)) {
                $push($domain['url'] ?? $domain['name'] ?? $domain['fqdn'] ?? null);
            }
        }

        foreach ($this->normalizeList($service['urls'] ?? []) as $entry) {
            if (is_string($entry)) {
                $push($entry);
            } elseif (is_array($entry)) {
                $push($entry['url'] ?? $entry['name'] ?? null);
            }
        }

        return $ordered;
    }

    public function extractCoolifyDefaultPublicUrl(array $service): ?string
    {
        $fromWordpress = $this->extractWordpressApplicationPublicUrl($service);
        if ($fromWordpress !== null) {
            $host = $this->hostnameFromUrl($fromWordpress);
            if ($this->isCoolifyGeneratedHost($host)) {
                return $fromWordpress;
            }
        }

        foreach ($this->collectServicePublicUrls($service) as $url) {
            if ($this->isCoolifyGeneratedHost($this->hostnameFromUrl($url))) {
                return $url;
            }
        }

        return null;
    }

    public function extractWordpressApplicationPublicUrl(array $service): ?string
    {
        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $name = strtolower((string) ($app['name'] ?? ''));
            if (! str_contains($name, 'wordpress')) {
                continue;
            }
            $fqdn = (string) ($app['fqdn'] ?? '');
            if ($fqdn !== '') {
                return $this->normalizePublicUrl($fqdn);
            }
        }

        return null;
    }

    /**
     * @return array{coolify_default_url: ?string, coolify_default_admin_url: ?string, coolify_urls: array<int, string>}
     */
    public function resolveCoolifyUrlMetadata(array $service): array
    {
        $default = $this->extractCoolifyDefaultPublicUrl($service);

        return [
            'coolify_default_url' => $default,
            'coolify_default_admin_url' => $default ? rtrim($default, '/').'/wp-admin' : null,
            'coolify_urls' => $this->collectServicePublicUrls($service),
        ];
    }

    public function extractServicePublicUrl(array $service): ?string
    {
        foreach (['fqdn', 'domain', 'public_url'] as $key) {
            $value = $service[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $this->normalizePublicUrl($value);
            }
        }

        $domains = $service['domains'] ?? null;
        if (is_array($domains)) {
            foreach ($domains as $domain) {
                if (is_string($domain) && $domain !== '') {
                    return $this->normalizePublicUrl($domain);
                }
                if (is_array($domain)) {
                    $candidate = $domain['url'] ?? $domain['name'] ?? $domain['fqdn'] ?? null;
                    if (is_string($candidate) && $candidate !== '') {
                        return $this->normalizePublicUrl($candidate);
                    }
                }
            }
        }

        $urls = $service['urls'] ?? null;
        if (is_array($urls)) {
            foreach ($urls as $entry) {
                if (is_string($entry) && $entry !== '') {
                    return $this->normalizePublicUrl($entry);
                }
                if (is_array($entry)) {
                    $candidate = $entry['url'] ?? $entry['name'] ?? null;
                    if (is_string($candidate) && $candidate !== '') {
                        return $this->normalizePublicUrl($candidate);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Hostname للـ CNAME (بدون scheme/path) من تطبيق wordpress داخل الخدمة.
     */
    public function extractCoolifyOriginHostname(array $service): ?string
    {
        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $fqdn = (string) ($app['fqdn'] ?? '');
            if ($fqdn !== '') {
                return $this->hostnameFromUrl($fqdn);
            }
        }

        $public = $this->extractServicePublicUrl($service);
        if ($public !== null) {
            return $this->hostnameFromUrl($public);
        }

        return null;
    }

    public function hostnameFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_contains($url, '://')) {
            $url = 'https://'.$url;
        }

        return strtolower((string) parse_url($url, PHP_URL_HOST));
    }

    public function normalizePublicUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return 'https://'.$url;
        }

        return rtrim($url, '/');
    }

    /**
     * @param  array<int, array<string, mixed>>  $envs
     * @return array<string, string>
     */
    public function extractDatabaseEnvFromServiceEnvs(array $envs): array
    {
        $keys = [
            'MYSQL_ROOT_PASSWORD', 'MYSQL_PASSWORD', 'MYSQL_USER', 'MYSQL_DATABASE',
            'MARIADB_ROOT_PASSWORD', 'MARIADB_PASSWORD', 'MARIADB_USER', 'MARIADB_DATABASE',
            'WORDPRESS_DB_HOST', 'WORDPRESS_DB_USER', 'WORDPRESS_DB_PASSWORD', 'WORDPRESS_DB_NAME',
        ];
        $out = [];

        foreach ($envs as $env) {
            $key = (string) ($env['key'] ?? $env['name'] ?? '');
            if ($key === '' || ! in_array($key, $keys, true)) {
                continue;
            }
            $value = (string) ($env['value'] ?? $env['real_value'] ?? '');
            if (str_contains(strtolower($key), 'password')) {
                $value = $value !== '' ? '••••••••' : '';
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @return array<int, array{name: string, role: string, status: string}>
     */
    public function extractServiceComponentStatuses(array $service): array
    {
        $components = [];

        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $components[] = [
                'name' => (string) ($app['name'] ?? 'application'),
                'role' => 'application',
                'status' => strtolower((string) ($app['status'] ?? '')),
            ];
        }

        foreach ($this->normalizeList($service['databases'] ?? []) as $db) {
            if (! is_array($db)) {
                continue;
            }
            $components[] = [
                'name' => (string) ($db['name'] ?? 'database'),
                'role' => 'database',
                'status' => strtolower((string) ($db['status'] ?? '')),
            ];
        }

        return $components;
    }

    public function isComponentStatusRunning(string $status): bool
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return false;
        }

        $running = ['running', 'healthy', 'started', 'active'];
        if (in_array($status, $running, true)) {
            return true;
        }

        foreach ($running as $needle) {
            if (str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function isServiceStackHealthy(array $service): bool
    {
        $components = $this->extractServiceComponentStatuses($service);

        if ($components === []) {
            return $this->isComponentStatusRunning((string) ($service['status'] ?? ''));
        }

        $dbOk = false;
        $appOk = false;

        foreach ($components as $component) {
            $ok = $this->isComponentStatusRunning((string) ($component['status'] ?? ''));
            if ($component['role'] === 'database') {
                $dbOk = $dbOk || $ok;
            }
            if ($component['role'] === 'application') {
                $appOk = $appOk || $ok;
            }
        }

        return $dbOk && $appOk;
    }

    /**
     * @return array<string, array{uuid: string, success: bool, lines: string}>
     */
    public function fetchServiceApplicationLogs(array $service, int $lines = 80): array
    {
        $out = [];

        foreach ($this->normalizeList($service['applications'] ?? []) as $app) {
            if (! is_array($app)) {
                continue;
            }
            $uuid = (string) ($app['uuid'] ?? '');
            $name = (string) ($app['name'] ?? 'application');
            if ($uuid === '') {
                continue;
            }
            $response = $this->applicationLogs($uuid, $lines);
            $out[$name] = [
                'uuid' => $uuid,
                'role' => 'application',
                'success' => (bool) ($response['success'] ?? false),
                'lines' => $this->extractLogText($response['data'] ?? null),
            ];
        }

        return $out;
    }

    protected function extractLogText(mixed $data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (! is_array($data)) {
            return '';
        }

        if (isset($data['logs']) && is_string($data['logs'])) {
            return $data['logs'];
        }

        if (isset($data['data']) && is_string($data['data'])) {
            return $data['data'];
        }

        $lines = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $lines[] = $item;
            } elseif (is_array($item)) {
                $lines[] = (string) ($item['message'] ?? $item['line'] ?? json_encode($item));
            }
        }

        return implode("\n", $lines);
    }

    public function extractWordpressAdminUrl(array $service, array $envs = []): ?string
    {
        $public = $this->extractServicePublicUrl($service);
        if ($public) {
            return rtrim($public, '/').'/wp-admin';
        }

        foreach ($envs as $env) {
            $key = strtoupper((string) ($env['key'] ?? $env['name'] ?? ''));
            if (in_array($key, ['WORDPRESS_URL', 'WP_HOME', 'WP_SITEURL'], true)) {
                $value = (string) ($env['value'] ?? $env['real_value'] ?? '');
                if ($value !== '') {
                    return rtrim($this->normalizePublicUrl($value), '/').'/wp-admin';
                }
            }
        }

        return null;
    }

    public function updateService(string $uuid, array $data): array
    {
        $payload = $this->filterServicePayload($data, includeUrls: true);

        return $this->request('PATCH', "services/{$uuid}", $payload);
    }

    public function deleteService(string $uuid): array
    {
        return $this->request('DELETE', "services/{$uuid}");
    }

    public function startService(string $uuid): array
    {
        return $this->request('GET', "services/{$uuid}/start");
    }

    public function stopService(string $uuid): array
    {
        return $this->request('GET', "services/{$uuid}/stop");
    }

    public function restartService(string $uuid): array
    {
        return $this->request('GET', "services/{$uuid}/restart");
    }

    public function listServiceEnvs(string $uuid): array
    {
        return $this->request('GET', "services/{$uuid}/envs");
    }

    public function createServiceEnv(string $uuid, array $data): array
    {
        return $this->request('POST', "services/{$uuid}/envs", $data);
    }

    public function updateServiceEnv(string $uuid, string $envUuid, array $data): array
    {
        return $this->request('PATCH', "services/{$uuid}/envs/{$envUuid}", $data);
    }

    public function deleteServiceEnv(string $uuid, string $envUuid): array
    {
        return $this->request('DELETE', "services/{$uuid}/envs/{$envUuid}");
    }

    // --- Deployments ---

    public function listDeployments(): array
    {
        return $this->request('GET', 'deployments');
    }

    public function getDeployment(string $uuid): array
    {
        return $this->request('GET', "deployments/{$uuid}");
    }

    public function listDeploymentsByApplication(string $applicationUuid): array
    {
        return $this->request('GET', "deployments/applications/{$applicationUuid}");
    }

    public function deploy(string $uuid, array $query = []): array
    {
        return $this->request('GET', 'deploy', [], array_merge(['uuid' => $uuid], $query));
    }

    public function cancelDeployment(string $uuid): array
    {
        return $this->request('POST', "deployments/{$uuid}/cancel");
    }

    // --- Private keys ---

    public function listPrivateKeys(): array
    {
        return $this->request('GET', 'security/keys');
    }

    public function getPrivateKey(string $uuid): array
    {
        return $this->request('GET', "security/keys/{$uuid}");
    }

    public function createPrivateKey(array $data): array
    {
        return $this->request('POST', 'security/keys', $data);
    }

    public function updatePrivateKey(string $uuid, array $data): array
    {
        return $this->request('PATCH', "security/keys/{$uuid}", $data);
    }

    public function deletePrivateKey(string $uuid): array
    {
        return $this->request('DELETE', "security/keys/{$uuid}");
    }

    public function bulkUpdateServiceEnvs(string $uuid, array $data): array
    {
        return $this->request('PATCH', "services/{$uuid}/envs/bulk", $data);
    }

    // --- System ---

    public function enableApi(): array
    {
        return $this->request('GET', 'enable');
    }

    public function disableApi(): array
    {
        return $this->request('GET', 'disable');
    }

    public function getSystemResources(): array
    {
        return $this->request('GET', 'resources');
    }

    // --- Teams ---

    public function listTeams(): array
    {
        return $this->request('GET', 'teams');
    }

    public function getTeam(int $id): array
    {
        return $this->request('GET', "teams/{$id}");
    }

    public function getTeamMembers(int $id): array
    {
        return $this->request('GET', "teams/{$id}/members");
    }

    public function getCurrentTeam(): array
    {
        return $this->request('GET', 'teams/current');
    }

    public function getCurrentTeamMembers(): array
    {
        return $this->request('GET', 'teams/current/members');
    }

    // --- GitHub Apps ---

    public function listGithubApps(): array
    {
        return $this->request('GET', 'github-apps');
    }

    public function createGithubApp(array $data): array
    {
        return $this->request('POST', 'github-apps', $data);
    }

    public function getGithubApp(string $uuid): array
    {
        return $this->request('GET', "github-apps/{$uuid}");
    }

    public function updateGithubApp(string $uuid, array $data): array
    {
        return $this->request('PATCH', "github-apps/{$uuid}", $data);
    }

    public function deleteGithubApp(string $uuid): array
    {
        return $this->request('DELETE', "github-apps/{$uuid}");
    }

    // --- Cloud Tokens ---

    public function listCloudTokens(): array
    {
        return $this->request('GET', 'cloud-tokens');
    }

    public function createCloudToken(array $data): array
    {
        return $this->request('POST', 'cloud-tokens', $data);
    }

    public function getCloudToken(string $uuid): array
    {
        return $this->request('GET', "cloud-tokens/{$uuid}");
    }

    public function updateCloudToken(string $uuid, array $data): array
    {
        return $this->request('PATCH', "cloud-tokens/{$uuid}", $data);
    }

    public function deleteCloudToken(string $uuid): array
    {
        return $this->request('DELETE', "cloud-tokens/{$uuid}");
    }

    public function validateCloudToken(string $uuid): array
    {
        return $this->request('GET', "cloud-tokens/{$uuid}/validate");
    }

    // --- Hetzner ---

    public function hetznerLocations(): array
    {
        return $this->request('GET', 'hetzner/locations');
    }

    public function hetznerServerTypes(): array
    {
        return $this->request('GET', 'hetzner/server-types');
    }

    public function hetznerImages(): array
    {
        return $this->request('GET', 'hetzner/images');
    }

    public function hetznerSshKeys(): array
    {
        return $this->request('GET', 'hetzner/ssh-keys');
    }

    public function createHetznerServer(array $data): array
    {
        return $this->request('POST', 'servers/hetzner', $data);
    }

    // --- Database Backups ---

    public function listDatabaseBackups(string $databaseUuid): array
    {
        return $this->request('GET', "databases/{$databaseUuid}/backups");
    }

    public function createDatabaseBackup(string $databaseUuid, array $data): array
    {
        return $this->request('POST', "databases/{$databaseUuid}/backups", $data);
    }

    public function updateDatabaseBackup(string $databaseUuid, string $scheduledBackupUuid, array $data): array
    {
        return $this->request('PATCH', "databases/{$databaseUuid}/backups/{$scheduledBackupUuid}", $data);
    }

    public function deleteDatabaseBackup(string $databaseUuid, string $scheduledBackupUuid, bool $deleteS3 = false): array
    {
        return $this->request('DELETE', "databases/{$databaseUuid}/backups/{$scheduledBackupUuid}", [], [
            'delete_s3' => $deleteS3 ? 'true' : 'false',
        ]);
    }

    public function listDatabaseBackupExecutions(string $databaseUuid, string $scheduledBackupUuid): array
    {
        return $this->request('GET', "databases/{$databaseUuid}/backups/{$scheduledBackupUuid}/executions");
    }

    public function deleteDatabaseBackupExecution(
        string $databaseUuid,
        string $scheduledBackupUuid,
        string $executionUuid,
        bool $deleteS3 = false
    ): array {
        return $this->request(
            'DELETE',
            "databases/{$databaseUuid}/backups/{$scheduledBackupUuid}/executions/{$executionUuid}",
            [],
            ['delete_s3' => $deleteS3 ? 'true' : 'false']
        );
    }

    /**
     * Parse .env style bulk text into array for bulk update API.
     *
     * @return array<int, array{key: string, value: string}>
     */
    public static function parseEnvBulkText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $envs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $envs[] = ['key' => trim($key), 'value' => trim($value)];
        }

        return $envs;
    }

    /**
     * Dashboard stats with short cache.
     *
     * @return array{connected: bool, servers: int, projects: int, applications: int, databases: int, services: int, deployments: int}
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('coolify_dashboard_stats', 60, function () {
            $stats = [
                'connected' => false,
                'servers' => 0,
                'projects' => 0,
                'applications' => 0,
                'databases' => 0,
                'services' => 0,
                'deployments' => 0,
            ];

            if (! $this->isConfigured()) {
                return $stats;
            }

            $stats['connected'] = $this->ping();

            if (! $stats['connected']) {
                return $stats;
            }

            foreach ([
                'servers' => fn () => $this->listServers(),
                'projects' => fn () => $this->listProjects(),
                'applications' => fn () => $this->listApplications(),
                'databases' => fn () => $this->listDatabases(),
                'services' => fn () => $this->listServices(),
                'deployments' => fn () => $this->listDeployments(),
            ] as $key => $fn) {
                $res = $fn();
                if ($res['success']) {
                    $stats[$key] = count($this->normalizeList($res['data'] ?? []));
                }
            }

            return $stats;
        });
    }

    public function clearDashboardCache(): void
    {
        Cache::forget('coolify_dashboard_stats');
    }

    /** إعادة تحميل الإعدادات بعد الحفظ من اللوحة */
    public function refreshConnection(): void
    {
        $this->settings->clearCache();
        $this->loadConnectionConfig();
    }
}
