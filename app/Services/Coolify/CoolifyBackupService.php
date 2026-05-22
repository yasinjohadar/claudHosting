<?php

namespace App\Services\Coolify;

use App\Services\CoolifyApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CoolifyBackupService
{
    public const FREQUENCIES = [
        'every_minute' => 'كل دقيقة',
        'hourly' => 'كل ساعة',
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'monthly' => 'شهري',
        'yearly' => 'سنوي',
    ];

    public const CACHE_TTL_SECONDS = 45;

    public function __construct(protected CoolifyApiService $coolify) {}

    public function isConfigured(): bool
    {
        return $this->coolify->isConfigured();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDatabases(): array
    {
        $response = $this->coolify->listDatabases();
        if (! ($response['success'] ?? false)) {
            return [];
        }

        return $this->coolify->normalizeList($response['data'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     stats: array<string, int>,
     *     error: string|null,
     *     databases_without_backup: int
     * }
     */
    public function aggregateDashboard(array $filters = [], bool $fresh = false): array
    {
        if (! $this->isConfigured()) {
            return [
                'rows' => [],
                'stats' => $this->emptyStats(),
                'error' => 'يرجى ضبط إعدادات Coolify أولاً.',
                'databases_without_backup' => 0,
            ];
        }

        if ($fresh) {
            $this->clearCache();
        }

        $cacheKey = $this->dashboardCacheKey($filters);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filters) {
            return $this->buildDashboard($filters);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listConfigsForDatabase(string $databaseUuid): array
    {
        $meta = $this->databaseMeta($databaseUuid);

        return $this->normalizeBackupConfigs(
            $this->coolify->listDatabaseBackups($databaseUuid),
            $databaseUuid,
            $meta
        );
    }

    public function findConfig(string $databaseUuid, string $configUuid): ?array
    {
        $configs = $this->normalizeBackupConfigs(
            $this->coolify->listDatabaseBackups($databaseUuid),
            $databaseUuid,
            $this->databaseMeta($databaseUuid)
        );

        foreach ($configs as $config) {
            if (($config['config_uuid'] ?? '') === $configUuid) {
                return $config;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listExecutions(string $databaseUuid, string $configUuid, bool $fresh = false): array
    {
        if ($fresh) {
            Cache::increment('coolify.backups.version');
        }

        $cacheKey = $this->executionsCacheKey($databaseUuid, $configUuid);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($databaseUuid, $configUuid) {
            $response = $this->coolify->listDatabaseBackupExecutions($databaseUuid, $configUuid);
            if (! ($response['success'] ?? false)) {
                return [];
            }

            return $this->normalizeExecutions($response['data'] ?? []);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function backupPayloadFromRequest(array $input, bool $forUpdate = false): array
    {
        $payload = [];

        $boolFields = ['enabled', 'save_s3', 'dump_all', 'backup_now'];
        foreach ($boolFields as $field) {
            if (array_key_exists($field, $input)) {
                $payload[$field] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        $stringFields = ['frequency', 's3_storage_uuid', 'databases_to_backup'];
        foreach ($stringFields as $field) {
            if (! empty($input[$field])) {
                $payload[$field] = $input[$field];
            }
        }

        $intFields = [
            'database_backup_retention_amount_locally',
            'database_backup_retention_days_locally',
            'database_backup_retention_max_storage_locally',
            'database_backup_retention_amount_s3',
            'database_backup_retention_days_s3',
            'database_backup_retention_max_storage_s3',
            'timeout',
        ];
        foreach ($intFields as $field) {
            if (isset($input[$field]) && $input[$field] !== '') {
                $payload[$field] = (int) $input[$field];
            }
        }

        if (! $forUpdate && ! isset($payload['enabled'])) {
            $payload['enabled'] = true;
        }

        return $payload;
    }

    public function clearCache(?string $databaseUuid = null, ?string $configUuid = null): void
    {
        Cache::increment('coolify.backups.version');

        if ($databaseUuid && $configUuid) {
            Cache::forget($this->executionsCacheKey($databaseUuid, $configUuid));
        }
    }

    protected function dashboardCacheKey(array $filters): string
    {
        $version = (int) Cache::get('coolify.backups.version', 0);

        return 'coolify.backups.dashboard.'.$version.'.'.md5(json_encode($filters));
    }

    protected function executionsCacheKey(string $databaseUuid, string $configUuid): string
    {
        $version = (int) Cache::get('coolify.backups.version', 0);

        return "coolify.backups.executions.{$version}.{$databaseUuid}.{$configUuid}";
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     stats: array<string, int>,
     *     error: string|null,
     *     databases_without_backup: int
     * }
     */
    protected function buildDashboard(array $filters): array
    {
        $databases = $this->listDatabases();
        $rows = [];
        $databasesWithBackup = [];
        $stats = $this->emptyStats();

        foreach ($databases as $database) {
            $dbUuid = (string) ($database['uuid'] ?? '');
            if ($dbUuid === '') {
                continue;
            }

            $meta = [
                'database_uuid' => $dbUuid,
                'database_name' => $database['name'] ?? $database['hostname'] ?? $dbUuid,
                'database_type' => $database['type'] ?? $database['database_type'] ?? '—',
            ];

            $response = $this->coolify->listDatabaseBackups($dbUuid);
            $configs = $this->normalizeBackupConfigs($response, $dbUuid, $meta);

            if ($configs !== []) {
                $databasesWithBackup[$dbUuid] = true;
            }

            foreach ($configs as $config) {
                $rows[] = $config;
                $stats['total_configs']++;

                if ($config['enabled'] ?? false) {
                    $stats['enabled_configs']++;
                }

                if ($config['save_s3'] ?? false) {
                    $stats['s3_configs']++;
                }

                $latest = $config['latest_execution'] ?? null;
                if ($latest) {
                    $stats['total_executions']++;
                    $status = strtolower((string) ($latest['status'] ?? ''));
                    if (in_array($status, ['success', 'completed', 'finished'], true)) {
                        $stats['successful_executions']++;
                    } elseif (in_array($status, ['failed', 'error'], true)) {
                        $stats['failed_executions']++;
                    } elseif (in_array($status, ['running', 'in_progress', 'pending'], true)) {
                        $stats['running_executions']++;
                    }

                    if ($this->isWithinLastDay($latest['created_at'] ?? null)) {
                        $stats['executions_24h']++;
                    }
                }
            }
        }

        $rows = $this->applyFilters($rows, $filters);
        $stats['databases_total'] = count($databases);
        $stats['databases_with_backup'] = count($databasesWithBackup);
        $stats['databases_without_backup'] = max(0, $stats['databases_total'] - $stats['databases_with_backup']);

        return [
            'rows' => $rows,
            'stats' => $stats,
            'error' => null,
            'databases_without_backup' => $stats['databases_without_backup'],
        ];
    }

    /**
     * @param  array{success?: bool, data?: mixed}  $response
     * @param  array<string, mixed>  $meta
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeBackupConfigs(array $response, string $databaseUuid, array $meta): array
    {
        if (! ($response['success'] ?? false)) {
            return [];
        }

        $items = $this->coolify->normalizeList($response['data'] ?? []);
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $executions = $this->normalizeExecutions($item['executions'] ?? $item['latest_log'] ?? []);
            $latest = $executions[0] ?? null;

            $normalized[] = array_merge($meta, [
                'config_uuid' => (string) ($item['uuid'] ?? $item['id'] ?? ''),
                'frequency' => $item['frequency'] ?? '—',
                'frequency_label' => self::FREQUENCIES[$item['frequency'] ?? ''] ?? ($item['frequency'] ?? '—'),
                'enabled' => (bool) ($item['enabled'] ?? false),
                'save_s3' => (bool) ($item['save_s3'] ?? false),
                's3_storage_uuid' => $item['s3_storage_uuid'] ?? null,
                'databases_to_backup' => $item['databases_to_backup'] ?? null,
                'dump_all' => (bool) ($item['dump_all'] ?? false),
                'timeout' => $item['timeout'] ?? null,
                'retention_local_amount' => $item['database_backup_retention_amount_locally'] ?? null,
                'retention_local_days' => $item['database_backup_retention_days_locally'] ?? null,
                'retention_s3_amount' => $item['database_backup_retention_amount_s3'] ?? null,
                'retention_s3_days' => $item['database_backup_retention_days_s3'] ?? null,
                'executions_count' => count($executions),
                'latest_execution' => $latest,
                'executions' => $executions,
                'raw' => $item,
            ]);
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeExecutions(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $list = $this->coolify->normalizeList($data);
        $normalized = [];

        foreach ($list as $execution) {
            if (! is_array($execution)) {
                continue;
            }

            $normalized[] = [
                'uuid' => (string) ($execution['uuid'] ?? $execution['id'] ?? ''),
                'filename' => $execution['filename'] ?? $execution['name'] ?? '—',
                'size' => isset($execution['size']) ? (int) $execution['size'] : null,
                'size_human' => $this->formatBytes(isset($execution['size']) ? (int) $execution['size'] : null),
                'created_at' => $execution['created_at'] ?? $execution['createdAt'] ?? null,
                'status' => $execution['status'] ?? 'unknown',
                'message' => $execution['message'] ?? null,
            ];
        }

        usort($normalized, function (array $a, array $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    protected function applyFilters(array $rows, array $filters): array
    {
        if (! empty($filters['database_uuid'])) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => ($row['database_uuid'] ?? '') === $filters['database_uuid']
            ));
        }

        if (! empty($filters['status'])) {
            $want = strtolower((string) $filters['status']);
            $rows = array_values(array_filter($rows, function (array $row) use ($want) {
                $status = strtolower((string) ($row['latest_execution']['status'] ?? 'none'));

                return match ($want) {
                    'success' => in_array($status, ['success', 'completed', 'finished'], true),
                    'failed' => in_array($status, ['failed', 'error'], true),
                    'running' => in_array($status, ['running', 'in_progress', 'pending'], true),
                    'none' => $status === 'none' || $status === 'unknown' || ($row['latest_execution'] ?? null) === null,
                    default => true,
                };
            }));
        }

        if (! empty($filters['enabled_only'])) {
            $rows = array_values(array_filter($rows, fn (array $row) => (bool) ($row['enabled'] ?? false)));
        }

        if (! empty($filters['s3_only'])) {
            $rows = array_values(array_filter($rows, fn (array $row) => (bool) ($row['save_s3'] ?? false)));
        }

        if (! empty($filters['q'])) {
            $q = mb_strtolower((string) $filters['q']);
            $rows = array_values(array_filter($rows, function (array $row) use ($q) {
                $haystack = mb_strtolower(implode(' ', [
                    $row['database_name'] ?? '',
                    $row['database_uuid'] ?? '',
                    $row['config_uuid'] ?? '',
                    $row['frequency'] ?? '',
                ]));

                return str_contains($haystack, $q);
            }));
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function databaseMeta(string $databaseUuid): array
    {
        foreach ($this->listDatabases() as $database) {
            if (($database['uuid'] ?? '') === $databaseUuid) {
                return [
                    'database_uuid' => $databaseUuid,
                    'database_name' => $database['name'] ?? $database['hostname'] ?? $databaseUuid,
                    'database_type' => $database['type'] ?? $database['database_type'] ?? '—',
                ];
            }
        }

        return [
            'database_uuid' => $databaseUuid,
            'database_name' => $databaseUuid,
            'database_type' => '—',
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function emptyStats(): array
    {
        return [
            'total_configs' => 0,
            'enabled_configs' => 0,
            's3_configs' => 0,
            'total_executions' => 0,
            'successful_executions' => 0,
            'failed_executions' => 0,
            'running_executions' => 0,
            'executions_24h' => 0,
            'databases_total' => 0,
            'databases_with_backup' => 0,
            'databases_without_backup' => 0,
        ];
    }

    protected function isWithinLastDay(?string $timestamp): bool
    {
        if (! $timestamp) {
            return false;
        }

        try {
            return Carbon::parse($timestamp)->greaterThan(now()->subDay());
        } catch (\Throwable) {
            return false;
        }
    }

    protected function formatBytes(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
