<?php

namespace App\Services\Coolify;

use App\Models\AppStorageConfig;
use App\Models\SystemSetting;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class CoolifySettingsService
{
    protected const GROUP = 'coolify';

    protected const CACHE_KEY = 'coolify_connection_config';

    protected const SSH_CACHE_KEY = 'coolify_ssh_config';

    protected const TERMINAL_CACHE_KEY = 'coolify_terminal_bridge_config';

    /**
     * @return array{api_url: string, api_token: string, timeout: int, token_configured: bool}
     */
    public function getConnectionConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('coolify.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $defaults = config('coolify.defaults');
            $url = trim((string) ($stored[$keys['api_url']] ?? $defaults['api_url'] ?? ''));
            $tokenRaw = $stored[$keys['api_token']] ?? '';
            $timeout = (int) ($stored[$keys['timeout']] ?? $defaults['timeout'] ?? 30);

            $token = $this->decryptIfEncrypted($tokenRaw);

            if ($timeout <= 0) {
                $timeout = (int) ($defaults['timeout'] ?? 30);
            }

            return [
                'api_url' => rtrim($url, '/'),
                'api_token' => $token,
                'timeout' => max(5, min(120, $timeout)),
                'token_configured' => $token !== '' || $tokenRaw !== '',
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormSettings(): array
    {
        $config = $this->getConnectionConfig();
        $ssh = $this->getSshConfig();

        return [
            'api_url' => $config['api_url'],
            'timeout' => $config['timeout'],
            'has_token' => $config['token_configured'],
            'ssh_user' => $ssh['ssh_user'],
            'ssh_private_key_path' => $ssh['ssh_private_key_path'],
            'has_ssh_key' => $ssh['ssh_key_configured'],
            'backup_queue' => $this->getBackupQueue(),
            'snapshot_storage_config_id' => $this->getSnapshotStorageConfigId(),
            'coolify_s3_storage_uuid' => $this->getCoolifyS3StorageUuid(),
            's3_prefix' => $this->getS3Prefix(),
            'wordpress_base_domain' => $this->getWordpressBaseDomain(),
            'wordpress_default_server_uuid' => $this->getWordpressDefaultServerUuid(),
            'wordpress_shared_project_uuid' => $this->getWordpressSharedProjectUuid(),
            'wordpress_default_environment' => $this->getWordpressDefaultEnvironment(),
            'wordpress_instant_deploy' => $this->getWordpressInstantDeploy(),
            'wordpress_provision_queue' => $this->getWordpressProvisionQueue(),
            'wordpress_default_destination_uuid' => $this->getWordpressDefaultDestinationUuid(),
            'wordpress_service_type' => $this->getWordpressServiceType(),
            'wordpress_cloudflare_zone_id' => $this->getWordpressCloudflareZoneId(),
            'wordpress_cloudflare_proxied' => $this->getWordpressCloudflareProxied(),
            'wordpress_cloudflare_ssl_mode' => $this->getWordpressCloudflareSslMode(),
            'wordpress_security_preset' => $this->getWordpressSecurityPreset(),
            'wordpress_cloudflare_enabled' => $this->getWordpressCloudflareEnabled(),
            'wordpress_docker_tag' => $this->getWordpressDockerTag(),
            'wordpress_filebrowser_enabled' => $this->getWordpressFilebrowserEnabled(),
            'wordpress_filebrowser_subdomain_prefix' => $this->getWordpressFilebrowserSubdomainPrefix(),
            'wordpress_management_queue' => $this->getWordpressManagementQueue(),
            'wordpress_redis_enabled' => $this->getWordpressRedisEnabled(),
            'wordpress_redis_host' => $this->getWordpressRedisHost(),
            'wordpress_redis_port' => $this->getWordpressRedisPort(),
            'ssh_host_fallback' => $this->getSshHostFallback(),
            'ssh_port' => $this->getSshPort(),
            'terminal_bridge_enabled' => $this->getTerminalBridgeConfig()['enabled'],
            'terminal_bridge_url' => $this->getTerminalBridgeConfig()['url'],
            'terminal_bridge_port' => $this->getTerminalBridgeConfig()['port'],
            'terminal_bridge_token_ttl' => $this->getTerminalBridgeConfig()['token_ttl_seconds'],
            'has_terminal_bridge_secret' => $this->getTerminalBridgeConfig()['secret_configured'],
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     url: string,
     *     secret: string,
     *     port: int,
     *     token_ttl_seconds: int,
     *     secret_configured: bool
     * }
     */
    public function getTerminalBridgeConfig(): array
    {
        return Cache::remember(self::TERMINAL_CACHE_KEY, 300, function () {
            $this->initializeDefaults();
            $keys = config('coolify.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $defaults = config('coolify.defaults');
            $env = config('terminal_bridge.env_fallback', []);

            $enabledRaw = $stored[$keys['terminal_bridge_enabled']] ?? null;
            if ($enabledRaw === null || $enabledRaw === '') {
                $enabled = (bool) ($env['enabled'] ?? false);
            } else {
                $enabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);
            }

            $url = trim((string) ($stored[$keys['terminal_bridge_url']] ?? $defaults['terminal_bridge_url'] ?? ''));
            if ($url === '') {
                $url = (string) ($env['url'] ?? 'http://127.0.0.1:3099');
            }
            $url = rtrim($url, '/');

            $secretRaw = $stored[$keys['terminal_bridge_secret']] ?? '';
            $secret = $this->decryptIfEncrypted($secretRaw);
            if ($secret === '') {
                $secret = trim((string) ($env['secret'] ?? ''));
            }

            $port = (int) ($stored[$keys['terminal_bridge_port']] ?? $defaults['terminal_bridge_port'] ?? 0);
            if ($port <= 0 || $port > 65535) {
                $port = (int) ($env['port'] ?? 3099);
            }

            $ttl = (int) ($stored[$keys['terminal_bridge_token_ttl']] ?? $defaults['terminal_bridge_token_ttl'] ?? 0);
            if ($ttl < 60 || $ttl > 86400) {
                $ttl = (int) ($env['token_ttl_seconds'] ?? 900);
            }
            $ttl = max(60, min(86400, $ttl));

            return [
                'enabled' => $enabled,
                'url' => $url,
                'secret' => $secret,
                'port' => $port,
                'token_ttl_seconds' => $ttl,
                'secret_configured' => $secret !== '' || $secretRaw !== '',
            ];
        });
    }

    public function getSshHostFallback(): string
    {
        $value = trim((string) $this->getSettingValue(
            'ssh_host_fallback',
            (string) config('coolify.defaults.ssh_host_fallback', '')
        ));

        return $value;
    }

    public function getSshPort(): int
    {
        $port = (int) $this->getSettingValue(
            'ssh_port',
            (string) config('coolify.defaults.ssh_port', 22)
        );

        if ($port <= 0 || $port > 65535) {
            return 22;
        }

        return $port;
    }

    /**
     * @return array<string, string>
     */
    public function getWordpressSecurityPresetOptions(): array
    {
        return config('coolify.wordpress_security_presets', [
            'basic' => 'أساسي',
            'performance' => 'أداء',
            'strict' => 'صارم',
        ]);
    }

    public function getWordpressCloudflareZoneId(): string
    {
        return trim((string) $this->getSettingValue('wordpress_cloudflare_zone_id', ''));
    }

    public function persistWordpressCloudflareZoneIdIfEmpty(string $zoneId): void
    {
        $zoneId = trim($zoneId);
        if ($zoneId === '' || $this->getWordpressCloudflareZoneId() !== '') {
            return;
        }

        $keys = config('coolify.keys');
        SystemSetting::set($keys['wordpress_cloudflare_zone_id'], $zoneId, 'string', self::GROUP);
    }

    public function getWordpressCloudflareProxied(): bool
    {
        return filter_var(
            $this->getSettingValue('wordpress_cloudflare_proxied', config('coolify.defaults.wordpress_cloudflare_proxied', '1')),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function getWordpressCloudflareSslMode(): string
    {
        $mode = strtolower(trim((string) $this->getSettingValue(
            'wordpress_cloudflare_ssl_mode',
            config('coolify.defaults.wordpress_cloudflare_ssl_mode', 'full')
        )));
        $allowed = ['off', 'flexible', 'full', 'strict'];

        return in_array($mode, $allowed, true) ? $mode : 'full';
    }

    public function getWordpressSecurityPreset(): string
    {
        $preset = trim((string) $this->getSettingValue(
            'wordpress_security_preset',
            config('coolify.defaults.wordpress_security_preset', 'basic')
        ));
        $allowed = array_keys($this->getWordpressSecurityPresetOptions());

        return in_array($preset, $allowed, true) ? $preset : 'basic';
    }

    public function getWordpressCloudflareEnabled(): bool
    {
        return filter_var(
            $this->getSettingValue('wordpress_cloudflare_enabled', config('coolify.defaults.wordpress_cloudflare_enabled', '1')),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return array<string, string>
     */
    public function getWordpressServiceTypeOptions(): array
    {
        return config('coolify.wordpress_service_types', [
            'wordpress-with-mariadb' => 'WordPress + MariaDB',
            'wordpress-with-mysql' => 'WordPress + MySQL',
            'wordpress-without-database' => 'WordPress بدون قاعدة',
        ]);
    }

    public function getWordpressServiceType(): string
    {
        $default = (string) config('coolify.defaults.wordpress_service_type', 'wordpress-with-mariadb');
        $type = trim((string) $this->getSettingValue('wordpress_service_type', $default));
        if ($type === 'wordpress') {
            $type = 'wordpress-with-mariadb';
        }
        $allowed = array_keys($this->getWordpressServiceTypeOptions());

        if ($type !== '' && in_array($type, $allowed, true)) {
            return $type;
        }

        return in_array($default, $allowed, true) ? $default : 'wordpress-with-mariadb';
    }

    public function getWordpressDefaultDestinationUuid(): string
    {
        return trim((string) $this->getSettingValue('wordpress_default_destination_uuid', ''));
    }

    public function getWordpressBaseDomain(): string
    {
        return strtolower(trim((string) $this->getSettingValue('wordpress_base_domain', '')));
    }

    public function getWordpressDefaultServerUuid(): string
    {
        return trim((string) $this->getSettingValue('wordpress_default_server_uuid', ''));
    }

    public function getWordpressSharedProjectUuid(): string
    {
        return trim((string) $this->getSettingValue('wordpress_shared_project_uuid', ''));
    }

    public function getWordpressDefaultEnvironment(): string
    {
        $env = trim((string) $this->getSettingValue('wordpress_default_environment', config('coolify.defaults.wordpress_default_environment', 'production')));

        return $env !== '' ? $env : 'production';
    }

    public function getWordpressInstantDeploy(): bool
    {
        return filter_var(
            $this->getSettingValue('wordpress_instant_deploy', config('coolify.defaults.wordpress_instant_deploy', '1')),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function getWordpressProvisionQueue(): string
    {
        $queue = trim((string) $this->getSettingValue('wordpress_provision_queue', config('coolify.defaults.wordpress_provision_queue', 'coolify-provision')));

        return $queue !== '' ? $queue : 'coolify-provision';
    }

    public function getWordpressManagementQueue(): string
    {
        $queue = trim((string) $this->getSettingValue(
            'wordpress_management_queue',
            config('coolify.defaults.wordpress_management_queue', 'coolify-provision')
        ));

        return $queue !== '' ? $queue : $this->getWordpressProvisionQueue();
    }

    public function getWordpressDockerTag(): string
    {
        $tag = trim((string) $this->getSettingValue(
            'wordpress_docker_tag',
            config('coolify.defaults.wordpress_docker_tag', 'latest')
        ));

        return $tag !== '' ? $tag : 'latest';
    }

    public function getWordpressFilebrowserEnabled(): bool
    {
        return filter_var(
            $this->getSettingValue(
                'wordpress_filebrowser_enabled',
                config('coolify.defaults.wordpress_filebrowser_enabled', '1')
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function getWordpressFilebrowserSubdomainPrefix(): string
    {
        $prefix = strtolower(trim((string) $this->getSettingValue(
            'wordpress_filebrowser_subdomain_prefix',
            config('coolify.defaults.wordpress_filebrowser_subdomain_prefix', 'files')
        )));
        $prefix = preg_replace('/[^a-z0-9-]/', '', $prefix ?? '') ?? '';

        return $prefix !== '' ? $prefix : 'files';
    }

    public function buildWordpressFilebrowserPublicUrl(string $slug): string
    {
        $base = $this->getWordpressBaseDomain();
        $slug = strtolower(trim($slug));
        $prefix = $this->getWordpressFilebrowserSubdomainPrefix();

        return 'https://'.$prefix.'.'.$slug.'.'.$base;
    }

    public function buildWordpressFilebrowserDnsName(string $slug): string
    {
        $slug = strtolower(trim($slug));

        return $this->getWordpressFilebrowserSubdomainPrefix().'.'.$slug;
    }

    public function getWordpressRedisEnabled(): bool
    {
        return filter_var(
            $this->getSettingValue('wordpress_redis_enabled', config('coolify.defaults.wordpress_redis_enabled', '0')),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function getWordpressRedisHost(): string
    {
        return trim((string) $this->getSettingValue('wordpress_redis_host', ''));
    }

    public function getWordpressRedisPort(): int
    {
        return max(1, min(65535, (int) $this->getSettingValue(
            'wordpress_redis_port',
            config('coolify.defaults.wordpress_redis_port', '6379')
        )));
    }

    /**
     * @return array{ready: bool, missing: array<int, string>}
     */
    public function getWordpressReadiness(): array
    {
        $missing = [];

        if (! $this->isApiConfigured()) {
            $missing[] = 'api';
        }
        if ($this->getWordpressBaseDomain() === '') {
            $missing[] = 'base_domain';
        }
        if ($this->getWordpressDefaultServerUuid() === '') {
            $missing[] = 'default_server';
        }

        return [
            'ready' => $missing === [],
            'missing' => $missing,
        ];
    }

    public function buildWordpressPublicUrl(string $slug): string
    {
        $base = $this->getWordpressBaseDomain();
        $slug = strtolower(trim($slug));

        return 'https://'.$slug.'.'.$base;
    }

    public function getBackupQueue(): string
    {
        return trim((string) $this->getSettingValue('backup_queue', config('coolify.defaults.backup_queue', 'default'))) ?: 'default';
    }

    public function getSnapshotStorageConfigId(): int
    {
        return max(0, (int) $this->getSettingValue('snapshot_storage_config_id', ''));
    }

    public function getCoolifyS3StorageUuid(): string
    {
        return trim((string) $this->getSettingValue('coolify_s3_storage_uuid', ''));
    }

    public function getS3Prefix(): string
    {
        $prefix = trim((string) $this->getSettingValue('s3_prefix', config('coolify.defaults.s3_prefix', 'coolify-snapshots')));

        return $prefix !== '' ? trim($prefix, '/') : 'coolify-snapshots';
    }

    public function getSshKeyCachePath(): string
    {
        $path = trim((string) $this->getSettingValue('ssh_key_cache_path', config('coolify.defaults.ssh_key_cache_path', 'coolify-keys')));

        return $path !== '' ? $path : 'coolify-keys';
    }

    public function isSnapshotStorageConfigured(): bool
    {
        return $this->getSnapshotStorageConfigId() > 0;
    }

    public function isApiConfigured(): bool
    {
        $config = $this->getConnectionConfig();

        return $config['api_url'] !== '' && $config['token_configured'];
    }

    /**
     * جاهزية اللقطات (منفصلة عن اتصال API الأساسي).
     *
     * @return array{api: bool, app_storage: bool, coolify_s3: bool, ready: bool, ready_with_db: bool}
     */
    public function getSnapshotReadiness(): array
    {
        $appStorage = $this->isSnapshotStorageConfigured();
        $coolifyS3 = $this->getCoolifyS3StorageUuid() !== '';

        return [
            'api' => $this->isApiConfigured(),
            'app_storage' => $appStorage,
            'coolify_s3' => $coolifyS3,
            'ready' => $appStorage,
            'ready_with_db' => $appStorage && $coolifyS3,
        ];
    }

    public function planRequiresCoolifyS3(array $plan): bool
    {
        foreach ($plan as $row) {
            if (! filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            if ((string) ($row['strategy'] ?? '') === 'coolify_api') {
                return true;
            }
        }

        return false;
    }

    /**
     * مزامنة إعدادات S3 من Coolify / App Storage عند توفر الاتصال.
     *
     * @return array<int, string> ما تم ضبطه تلقائياً
     */
    public function syncSnapshotStorageFromCoolify(CoolifyApiService $api): array
    {
        $synced = [];

        if ($this->getCoolifyS3StorageUuid() === '' && $api->isConfigured()) {
            $discovered = $api->discoverS3StorageUuidFromBackups();
            if ($discovered !== null && $discovered !== '') {
                $this->updateSettings(['coolify_s3_storage_uuid' => $discovered]);
                $synced[] = 'coolify_s3_storage_uuid';
            }
        }

        if ($this->getSnapshotStorageConfigId() <= 0) {
            $storage = AppStorageConfig::query()
                ->where('is_active', true)
                ->whereIn('driver', config('coolify.snapshot_storage_drivers', ['s3']))
                ->orderBy('priority')
                ->orderBy('id')
                ->first();

            if ($storage) {
                $this->updateSettings(['snapshot_storage_config_id' => $storage->id]);
                $synced[] = 'snapshot_storage_config_id';
            }
        }

        return $synced;
    }

    /**
     * @return array{
     *     ssh_user: string,
     *     ssh_private_key: string,
     *     ssh_private_key_path: string,
     *     ssh_key_cache_path: string,
     *     ssh_key_configured: bool
     * }
     */
    public function getSshConfig(): array
    {
        return Cache::remember(self::SSH_CACHE_KEY, 300, function () {
            $this->initializeDefaults();
            $keys = config('coolify.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $keyRaw = $stored[$keys['ssh_private_key']] ?? '';
            $privateKey = $this->decryptIfEncrypted($keyRaw);

            $defaults = config('coolify.defaults');
            $path = trim((string) ($stored[$keys['ssh_private_key_path']] ?? $defaults['ssh_private_key_path'] ?? ''));
            $user = trim((string) ($stored[$keys['ssh_user']] ?? $defaults['ssh_user'] ?? 'root'));
            $cachePath = trim((string) ($stored[$keys['ssh_key_cache_path']] ?? $defaults['ssh_key_cache_path'] ?? 'coolify-keys'));

            return [
                'ssh_user' => $user,
                'ssh_private_key' => $privateKey,
                'ssh_private_key_path' => $path,
                'ssh_key_cache_path' => $cachePath !== '' ? $cachePath : 'coolify-keys',
                'ssh_key_configured' => $privateKey !== '' || ($path !== '' && is_file($path)),
            ];
        });
    }

    public function updateSettings(array $data): void
    {
        $keys = config('coolify.keys');

        if (isset($data['api_url'])) {
            SystemSetting::set($keys['api_url'], rtrim(trim((string) $data['api_url']), '/'), 'string', self::GROUP);
        }

        if (isset($data['timeout'])) {
            SystemSetting::set($keys['timeout'], (string) max(5, min(120, (int) $data['timeout'])), 'integer', self::GROUP);
        }

        if (! empty($data['api_token'])) {
            SystemSetting::set(
                $keys['api_token'],
                Crypt::encryptString((string) $data['api_token']),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('ssh_user', $data)) {
            SystemSetting::set($keys['ssh_user'], trim((string) $data['ssh_user']), 'string', self::GROUP);
        }

        if (array_key_exists('ssh_private_key_path', $data)) {
            $path = trim((string) $data['ssh_private_key_path']);
            if ($this->looksLikePemInPathField($path)) {
                if (empty($data['ssh_private_key'])) {
                    $data['ssh_private_key'] = $path;
                }
                $path = '';
            }
            SystemSetting::set($keys['ssh_private_key_path'], $path, 'string', self::GROUP);
        }

        if (array_key_exists('ssh_host_fallback', $data)) {
            SystemSetting::set($keys['ssh_host_fallback'], trim((string) $data['ssh_host_fallback']), 'string', self::GROUP);
        }

        if (array_key_exists('ssh_port', $data)) {
            $port = max(1, min(65535, (int) $data['ssh_port']));
            SystemSetting::set($keys['ssh_port'], (string) $port, 'integer', self::GROUP);
        }

        if (! empty($data['ssh_private_key'])) {
            SystemSetting::set(
                $keys['ssh_private_key'],
                Crypt::encryptString((string) $data['ssh_private_key']),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('backup_queue', $data)) {
            $queue = trim((string) $data['backup_queue']);
            SystemSetting::set(
                $keys['backup_queue'],
                $queue !== '' ? $queue : (string) config('coolify.defaults.backup_queue', 'default'),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('snapshot_storage_config_id', $data) && (int) $data['snapshot_storage_config_id'] > 0) {
            SystemSetting::set(
                $keys['snapshot_storage_config_id'],
                (string) (int) $data['snapshot_storage_config_id'],
                'integer',
                self::GROUP
            );
        }

        if (array_key_exists('coolify_s3_storage_uuid', $data)) {
            $s3Uuid = trim((string) $data['coolify_s3_storage_uuid']);
            if ($s3Uuid !== '') {
                SystemSetting::set($keys['coolify_s3_storage_uuid'], $s3Uuid, 'string', self::GROUP);
            }
        }

        if (array_key_exists('s3_prefix', $data)) {
            $prefix = trim(trim((string) $data['s3_prefix']), '/');
            SystemSetting::set(
                $keys['s3_prefix'],
                $prefix !== '' ? $prefix : (string) config('coolify.defaults.s3_prefix', 'coolify-snapshots'),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_base_domain', $data)) {
            $domain = strtolower(trim((string) $data['wordpress_base_domain']));
            $domain = preg_replace('#^https?://#', '', $domain ?? '');
            $domain = rtrim($domain ?? '', '/');
            SystemSetting::set($keys['wordpress_base_domain'], $domain, 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_default_server_uuid', $data)) {
            SystemSetting::set($keys['wordpress_default_server_uuid'], trim((string) $data['wordpress_default_server_uuid']), 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_shared_project_uuid', $data)) {
            SystemSetting::set($keys['wordpress_shared_project_uuid'], trim((string) $data['wordpress_shared_project_uuid']), 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_default_environment', $data)) {
            $env = trim((string) $data['wordpress_default_environment']);
            SystemSetting::set(
                $keys['wordpress_default_environment'],
                $env !== '' ? $env : 'production',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_instant_deploy', $data)) {
            SystemSetting::set(
                $keys['wordpress_instant_deploy'],
                filter_var($data['wordpress_instant_deploy'] ?? true, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_provision_queue', $data)) {
            $queue = trim((string) $data['wordpress_provision_queue']);
            SystemSetting::set(
                $keys['wordpress_provision_queue'],
                $queue !== '' ? $queue : (string) config('coolify.defaults.wordpress_provision_queue', 'coolify-provision'),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_default_destination_uuid', $data)) {
            SystemSetting::set(
                $keys['wordpress_default_destination_uuid'],
                trim((string) $data['wordpress_default_destination_uuid']),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_service_type', $data)) {
            $type = trim((string) $data['wordpress_service_type']);
            $allowed = array_keys($this->getWordpressServiceTypeOptions());
            if (! in_array($type, $allowed, true)) {
                $type = (string) config('coolify.defaults.wordpress_service_type', 'wordpress-with-mariadb');
            }
            SystemSetting::set($keys['wordpress_service_type'], $type, 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_cloudflare_zone_id', $data)) {
            SystemSetting::set($keys['wordpress_cloudflare_zone_id'], trim((string) $data['wordpress_cloudflare_zone_id']), 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_cloudflare_proxied', $data)) {
            SystemSetting::set(
                $keys['wordpress_cloudflare_proxied'],
                filter_var($data['wordpress_cloudflare_proxied'] ?? true, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_cloudflare_ssl_mode', $data)) {
            $mode = strtolower(trim((string) $data['wordpress_cloudflare_ssl_mode']));
            if (! in_array($mode, ['off', 'flexible', 'full', 'strict'], true)) {
                $mode = 'full';
            }
            SystemSetting::set($keys['wordpress_cloudflare_ssl_mode'], $mode, 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_security_preset', $data)) {
            $preset = trim((string) $data['wordpress_security_preset']);
            $allowed = array_keys($this->getWordpressSecurityPresetOptions());
            if (! in_array($preset, $allowed, true)) {
                $preset = 'basic';
            }
            SystemSetting::set($keys['wordpress_security_preset'], $preset, 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_cloudflare_enabled', $data)) {
            SystemSetting::set(
                $keys['wordpress_cloudflare_enabled'],
                filter_var($data['wordpress_cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_docker_tag', $data)) {
            SystemSetting::set($keys['wordpress_docker_tag'], trim((string) $data['wordpress_docker_tag']), 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_filebrowser_enabled', $data)) {
            SystemSetting::set(
                $keys['wordpress_filebrowser_enabled'],
                filter_var($data['wordpress_filebrowser_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_filebrowser_subdomain_prefix', $data)) {
            $prefix = strtolower(trim((string) $data['wordpress_filebrowser_subdomain_prefix']));
            $prefix = preg_replace('/[^a-z0-9-]/', '', $prefix ?? '') ?? '';
            SystemSetting::set(
                $keys['wordpress_filebrowser_subdomain_prefix'],
                $prefix !== '' ? $prefix : 'files',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_management_queue', $data)) {
            $queue = trim((string) $data['wordpress_management_queue']);
            SystemSetting::set(
                $keys['wordpress_management_queue'],
                $queue !== '' ? $queue : (string) config('coolify.defaults.wordpress_management_queue', 'coolify-provision'),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_redis_enabled', $data)) {
            SystemSetting::set(
                $keys['wordpress_redis_enabled'],
                filter_var($data['wordpress_redis_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('wordpress_redis_host', $data)) {
            SystemSetting::set($keys['wordpress_redis_host'], trim((string) $data['wordpress_redis_host']), 'string', self::GROUP);
        }

        if (array_key_exists('wordpress_redis_port', $data)) {
            SystemSetting::set(
                $keys['wordpress_redis_port'],
                (string) max(1, min(65535, (int) $data['wordpress_redis_port'])),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('terminal_bridge_enabled', $data)) {
            SystemSetting::set(
                $keys['terminal_bridge_enabled'],
                filter_var($data['terminal_bridge_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('terminal_bridge_url', $data)) {
            SystemSetting::set(
                $keys['terminal_bridge_url'],
                rtrim(trim((string) $data['terminal_bridge_url']), '/'),
                'string',
                self::GROUP
            );
        }

        if (! empty($data['terminal_bridge_secret'])) {
            SystemSetting::set(
                $keys['terminal_bridge_secret'],
                Crypt::encryptString((string) $data['terminal_bridge_secret']),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('terminal_bridge_port', $data)) {
            SystemSetting::set(
                $keys['terminal_bridge_port'],
                (string) max(1, min(65535, (int) $data['terminal_bridge_port'])),
                'integer',
                self::GROUP
            );
        }

        if (array_key_exists('terminal_bridge_token_ttl', $data)) {
            SystemSetting::set(
                $keys['terminal_bridge_token_ttl'],
                (string) max(60, min(86400, (int) $data['terminal_bridge_token_ttl'])),
                'integer',
                self::GROUP
            );
        }

        if (array_key_exists('ssh_private_key', $data) || array_key_exists('ssh_private_key_path', $data) || array_key_exists('ssh_host_fallback', $data)) {
            $this->purgeSshKeyCacheFiles();
        }

        $this->clearCache();
    }

    protected function looksLikePemInPathField(string $path): bool
    {
        return str_contains($path, '-----BEGIN') && str_contains($path, 'PRIVATE KEY');
    }

    public function purgeSshKeyCacheFiles(): void
    {
        $dir = storage_path('app/'.trim((string) config('coolify.defaults.ssh_key_cache_path', 'coolify-keys'), '/'));
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.'/ssh_key_*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::SSH_CACHE_KEY);
        Cache::forget(self::TERMINAL_CACHE_KEY);
        Cache::forget('coolify_dashboard_stats');
    }

    public function initializeDefaults(): void
    {
        $keys = config('coolify.keys');
        $defaults = config('coolify.defaults');

        foreach ([
            $keys['api_url'] => $defaults['api_url'],
            $keys['api_token'] => $defaults['api_token'],
            $keys['timeout'] => (string) $defaults['timeout'],
            $keys['ssh_user'] => $defaults['ssh_user'],
            $keys['ssh_private_key'] => $defaults['ssh_private_key'],
            $keys['ssh_private_key_path'] => $defaults['ssh_private_key_path'],
            $keys['ssh_key_cache_path'] => $defaults['ssh_key_cache_path'],
            $keys['ssh_host_fallback'] => $defaults['ssh_host_fallback'],
            $keys['ssh_port'] => (string) $defaults['ssh_port'],
            $keys['backup_queue'] => $defaults['backup_queue'],
            $keys['snapshot_storage_config_id'] => (string) $defaults['snapshot_storage_config_id'],
            $keys['coolify_s3_storage_uuid'] => $defaults['coolify_s3_storage_uuid'],
            $keys['s3_prefix'] => $defaults['s3_prefix'],
            $keys['wordpress_base_domain'] => $defaults['wordpress_base_domain'],
            $keys['wordpress_default_server_uuid'] => $defaults['wordpress_default_server_uuid'],
            $keys['wordpress_shared_project_uuid'] => $defaults['wordpress_shared_project_uuid'],
            $keys['wordpress_default_environment'] => $defaults['wordpress_default_environment'],
            $keys['wordpress_instant_deploy'] => $defaults['wordpress_instant_deploy'],
            $keys['wordpress_provision_queue'] => $defaults['wordpress_provision_queue'],
            $keys['wordpress_default_destination_uuid'] => $defaults['wordpress_default_destination_uuid'],
            $keys['wordpress_service_type'] => $defaults['wordpress_service_type'],
            $keys['wordpress_cloudflare_zone_id'] => $defaults['wordpress_cloudflare_zone_id'],
            $keys['wordpress_cloudflare_proxied'] => $defaults['wordpress_cloudflare_proxied'],
            $keys['wordpress_cloudflare_ssl_mode'] => $defaults['wordpress_cloudflare_ssl_mode'],
            $keys['wordpress_security_preset'] => $defaults['wordpress_security_preset'],
            $keys['wordpress_cloudflare_enabled'] => $defaults['wordpress_cloudflare_enabled'],
            $keys['terminal_bridge_enabled'] => $defaults['terminal_bridge_enabled'],
            $keys['terminal_bridge_url'] => $defaults['terminal_bridge_url'],
            $keys['terminal_bridge_secret'] => $defaults['terminal_bridge_secret'],
            $keys['terminal_bridge_port'] => (string) $defaults['terminal_bridge_port'],
            $keys['terminal_bridge_token_ttl'] => (string) $defaults['terminal_bridge_token_ttl'],
        ] as $key => $value) {
            if (! SystemSetting::query()->where('group', self::GROUP)->where('key', $key)->exists()) {
                SystemSetting::set($key, (string) $value, 'string', self::GROUP);
            }
        }
    }

    protected function getSettingValue(string $configKey, string $default = ''): string
    {
        $this->initializeDefaults();
        $keys = config('coolify.keys');
        $dbKey = $keys[$configKey] ?? null;
        if ($dbKey === null) {
            return $default;
        }

        $value = SystemSetting::query()
            ->where('group', self::GROUP)
            ->where('key', $dbKey)
            ->value('value');

        return $value !== null && $value !== '' ? (string) $value : $default;
    }

    protected function decryptIfEncrypted(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
}
