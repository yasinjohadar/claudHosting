<?php

namespace App\Services\Whm;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class WhmSettingsService
{
    protected const GROUP = 'whm';

    protected const CACHE_KEY = 'whm_connection_config';

    protected const BILLING_CACHE_KEY = 'whm_billing_config';

    /**
     * @return array{
     *   host: string,
     *   username: string,
     *   api_token: string,
     *   verify_ssl: bool,
     *   default_package: string,
     *   default_domain_suffix: string,
     *   timeout: int,
     *   token_configured: bool
     * }
     */
    public function getConnectionConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('whm.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $host = trim((string) ($stored[$keys['host']] ?? ''));
            $username = trim((string) ($stored[$keys['username']] ?? config('whm.defaults.username')));
            $tokenRaw = $stored[$keys['api_token']] ?? '';
            $verifySsl = ($stored[$keys['verify_ssl']] ?? config('whm.defaults.verify_ssl')) !== '0';
            $defaultPackage = trim((string) ($stored[$keys['default_package']] ?? config('whm.defaults.default_package')));
            $domainSuffix = trim((string) ($stored[$keys['default_domain_suffix']] ?? ''));
            $timeout = (int) ($stored[$keys['timeout']] ?? config('whm.defaults.timeout', 60));

            $token = $this->decryptIfEncrypted($tokenRaw);

            return [
                'host' => rtrim($host, '/'),
                'username' => $username,
                'api_token' => $token,
                'verify_ssl' => $verifySsl,
                'default_package' => $defaultPackage !== '' ? $defaultPackage : 'default',
                'default_domain_suffix' => $domainSuffix,
                'timeout' => max(10, min(180, $timeout)),
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

        return [
            'host' => $config['host'],
            'username' => $config['username'],
            'verify_ssl' => $config['verify_ssl'],
            'default_package' => $config['default_package'],
            'default_domain_suffix' => $config['default_domain_suffix'],
            'timeout' => $config['timeout'],
            'has_token' => $config['token_configured'],
            'billing' => $this->getBillingConfig(),
            'ssh' => $this->getSshFormSettings(),
        ];
    }

    /**
     * @return array{ssh_host: string, ssh_user: string, ssh_port: int, has_ssh_key: bool, ssh_private_key_path: string, using_coolify_key: bool}
     */
    public function getSshFormSettings(): array
    {
        $ssh = app(WhmSshExecutor::class)->getSshConfig();

        return [
            'ssh_host' => $ssh['ssh_host'] ?? '',
            'ssh_user' => $ssh['ssh_user'] ?? 'root',
            'ssh_port' => $ssh['ssh_port'] ?? 22,
            'has_ssh_key' => (bool) ($ssh['ssh_key_configured'] ?? false),
            'ssh_private_key_path' => ($ssh['using_coolify_key'] ?? false) ? '' : ($ssh['ssh_private_key_path'] ?? ''),
            'using_coolify_key' => (bool) ($ssh['using_coolify_key'] ?? false),
        ];
    }

    /**
     * @return array{renewal_amount: float, invoice_due_days: int, subscription_years: int}
     */
    public function getBillingConfig(): array
    {
        return Cache::remember(self::BILLING_CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('whm.keys');
            $defaults = config('whm.defaults');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            return [
                'renewal_amount' => max(0, (float) ($stored[$keys['renewal_amount']] ?? $defaults['renewal_amount'])),
                'invoice_due_days' => max(1, (int) ($stored[$keys['invoice_due_days']] ?? $defaults['invoice_due_days'])),
                'subscription_years' => max(1, (int) ($stored[$keys['subscription_years']] ?? $defaults['subscription_years'])),
            ];
        });
    }

    public function updateSettings(array $data): void
    {
        $keys = config('whm.keys');

        if (array_key_exists('host', $data)) {
            SystemSetting::set($keys['host'], rtrim(trim((string) $data['host']), '/'), 'string', self::GROUP);
        }

        if (array_key_exists('username', $data)) {
            SystemSetting::set($keys['username'], trim((string) $data['username']), 'string', self::GROUP);
        }

        if (array_key_exists('verify_ssl', $data)) {
            SystemSetting::set($keys['verify_ssl'], ! empty($data['verify_ssl']) ? '1' : '0', 'string', self::GROUP);
        }

        if (array_key_exists('default_package', $data)) {
            SystemSetting::set($keys['default_package'], trim((string) $data['default_package']), 'string', self::GROUP);
        }

        if (array_key_exists('default_domain_suffix', $data)) {
            SystemSetting::set($keys['default_domain_suffix'], trim((string) $data['default_domain_suffix']), 'string', self::GROUP);
        }

        if (isset($data['timeout'])) {
            SystemSetting::set($keys['timeout'], (string) max(10, min(180, (int) $data['timeout'])), 'integer', self::GROUP);
        }

        if (! empty($data['api_token'])) {
            SystemSetting::set(
                $keys['api_token'],
                Crypt::encryptString((string) $data['api_token']),
                'string',
                self::GROUP
            );
        }

        if (array_key_exists('renewal_amount', $data)) {
            SystemSetting::set($keys['renewal_amount'], (string) max(0, (float) $data['renewal_amount']), 'string', self::GROUP);
        }

        if (array_key_exists('invoice_due_days', $data)) {
            SystemSetting::set($keys['invoice_due_days'], (string) max(1, (int) $data['invoice_due_days']), 'integer', self::GROUP);
        }

        if (array_key_exists('subscription_years', $data)) {
            SystemSetting::set($keys['subscription_years'], (string) max(1, (int) $data['subscription_years']), 'integer', self::GROUP);
        }

        if (array_key_exists('ssh_host', $data)) {
            SystemSetting::set($keys['ssh_host'], trim((string) $data['ssh_host']), 'string', self::GROUP);
        }

        if (array_key_exists('ssh_user', $data)) {
            SystemSetting::set($keys['ssh_user'], trim((string) $data['ssh_user']) ?: 'root', 'string', self::GROUP);
        }

        if (array_key_exists('ssh_port', $data)) {
            $port = (int) $data['ssh_port'];
            SystemSetting::set($keys['ssh_port'], (string) ($port > 0 && $port <= 65535 ? $port : 22), 'integer', self::GROUP);
        }

        if (array_key_exists('ssh_private_key_path', $data)) {
            SystemSetting::set($keys['ssh_private_key_path'], trim((string) $data['ssh_private_key_path']), 'string', self::GROUP);
        }

        if (! empty($data['ssh_private_key'])) {
            SystemSetting::set(
                $keys['ssh_private_key'],
                Crypt::encryptString((string) $data['ssh_private_key']),
                'string',
                self::GROUP
            );
        }

        $this->clearCache();
        app(WhmSshExecutor::class)->clearSshCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::BILLING_CACHE_KEY);
    }

    public function initializeDefaults(): void
    {
        $keys = config('whm.keys');
        $defaults = config('whm.defaults');

        foreach ($keys as $settingKey => $dbKey) {
            if (! SystemSetting::query()->where('group', self::GROUP)->where('key', $dbKey)->exists()) {
                SystemSetting::set($dbKey, (string) ($defaults[$settingKey] ?? ''), 'string', self::GROUP);
            }
        }
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
