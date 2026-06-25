<?php

namespace App\Services\CyberPanel;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class CyberPanelSettingsService
{
    protected const GROUP = 'cyberpanel';

    protected const CACHE_KEY = 'cyberpanel_connection_config';

    protected const BILLING_CACHE_KEY = 'cyberpanel_billing_config';

    /**
     * @return array{
     *   host: string,
     *   port: int,
     *   admin_user: string,
     *   admin_password: string,
     *   api_token: string,
     *   api_style: string,
     *   verify_ssl: bool,
     *   default_package: string,
     *   default_php_version: string,
     *   default_owner: string,
     *   default_domain_suffix: string,
     *   timeout: int,
     *   password_configured: bool,
     *   token_configured: bool
     * }
     */
    public function getConnectionConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('cyberpanel.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $host = trim((string) ($stored[$keys['host']] ?? ''));
            $port = (int) ($stored[$keys['port']] ?? config('cyberpanel.defaults.port', 8090));
            $adminUser = trim((string) ($stored[$keys['admin_user']] ?? config('cyberpanel.defaults.admin_user')));
            $passwordRaw = $stored[$keys['admin_password']] ?? '';
            $tokenRaw = $stored[$keys['api_token']] ?? '';
            $apiStyle = trim((string) ($stored[$keys['api_style']] ?? config('cyberpanel.defaults.api_style', 'cloud')));
            $verifySsl = ($stored[$keys['verify_ssl']] ?? config('cyberpanel.defaults.verify_ssl')) !== '0';
            $defaultPackage = trim((string) ($stored[$keys['default_package']] ?? config('cyberpanel.defaults.default_package')));
            $phpVersion = trim((string) ($stored[$keys['default_php_version']] ?? config('cyberpanel.defaults.default_php_version')));
            $defaultOwner = trim((string) ($stored[$keys['default_owner']] ?? config('cyberpanel.defaults.default_owner')));
            $domainSuffix = trim((string) ($stored[$keys['default_domain_suffix']] ?? ''));
            $timeout = (int) ($stored[$keys['timeout']] ?? config('cyberpanel.defaults.timeout', 60));

            $password = $this->decryptIfEncrypted($passwordRaw);
            $token = $this->decryptIfEncrypted($tokenRaw);

            if (! in_array($apiStyle, config('cyberpanel.api_styles', ['cloud', 'legacy']), true)) {
                $apiStyle = 'cloud';
            }

            return [
                'host' => rtrim($host, '/'),
                'port' => max(1, min(65535, $port)),
                'admin_user' => $adminUser,
                'admin_password' => $password,
                'api_token' => $token,
                'api_style' => $apiStyle,
                'verify_ssl' => $verifySsl,
                'default_package' => $defaultPackage !== '' ? $defaultPackage : 'Default',
                'default_php_version' => $phpVersion !== '' ? $phpVersion : 'PHP 8.3',
                'default_owner' => $defaultOwner !== '' ? $defaultOwner : 'admin',
                'default_domain_suffix' => $domainSuffix,
                'timeout' => max(10, min(180, $timeout)),
                'password_configured' => $password !== '' || $passwordRaw !== '',
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
            'port' => $config['port'],
            'admin_user' => $config['admin_user'],
            'api_style' => $config['api_style'],
            'verify_ssl' => $config['verify_ssl'],
            'default_package' => $config['default_package'],
            'default_php_version' => $config['default_php_version'],
            'default_owner' => $config['default_owner'],
            'default_domain_suffix' => $config['default_domain_suffix'],
            'timeout' => $config['timeout'],
            'has_password' => $config['password_configured'],
            'has_token' => $config['token_configured'],
            'billing' => $this->getBillingConfig(),
        ];
    }

    /**
     * @return array{renewal_amount: float, invoice_due_days: int, subscription_years: int}
     */
    public function getBillingConfig(): array
    {
        return Cache::remember(self::BILLING_CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('cyberpanel.keys');
            $defaults = config('cyberpanel.defaults');
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
        $keys = config('cyberpanel.keys');

        if (array_key_exists('host', $data)) {
            SystemSetting::set($keys['host'], rtrim(trim((string) $data['host']), '/'), 'string', self::GROUP);
        }

        if (array_key_exists('port', $data)) {
            SystemSetting::set($keys['port'], (string) max(1, min(65535, (int) $data['port'])), 'integer', self::GROUP);
        }

        if (array_key_exists('admin_user', $data)) {
            SystemSetting::set($keys['admin_user'], trim((string) $data['admin_user']), 'string', self::GROUP);
        }

        if (array_key_exists('api_style', $data)) {
            $style = trim((string) $data['api_style']);
            if (! in_array($style, config('cyberpanel.api_styles', ['cloud', 'legacy']), true)) {
                $style = 'cloud';
            }
            SystemSetting::set($keys['api_style'], $style, 'string', self::GROUP);
        }

        if (array_key_exists('verify_ssl', $data)) {
            SystemSetting::set($keys['verify_ssl'], ! empty($data['verify_ssl']) ? '1' : '0', 'string', self::GROUP);
        }

        if (array_key_exists('default_package', $data)) {
            SystemSetting::set($keys['default_package'], trim((string) $data['default_package']), 'string', self::GROUP);
        }

        if (array_key_exists('default_php_version', $data)) {
            SystemSetting::set($keys['default_php_version'], trim((string) $data['default_php_version']), 'string', self::GROUP);
        }

        if (array_key_exists('default_owner', $data)) {
            SystemSetting::set($keys['default_owner'], trim((string) $data['default_owner']), 'string', self::GROUP);
        }

        if (array_key_exists('default_domain_suffix', $data)) {
            SystemSetting::set($keys['default_domain_suffix'], trim((string) $data['default_domain_suffix']), 'string', self::GROUP);
        }

        if (isset($data['timeout'])) {
            SystemSetting::set($keys['timeout'], (string) max(10, min(180, (int) $data['timeout'])), 'integer', self::GROUP);
        }

        if (! empty($data['admin_password'])) {
            SystemSetting::set(
                $keys['admin_password'],
                Crypt::encryptString((string) $data['admin_password']),
                'string',
                self::GROUP
            );
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

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::BILLING_CACHE_KEY);
    }

    public function initializeDefaults(): void
    {
        $keys = config('cyberpanel.keys');
        $defaults = config('cyberpanel.defaults');

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

    public function getPanelBaseUrl(): string
    {
        $config = $this->getConnectionConfig();
        $host = $config['host'];
        if ($host === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $host)) {
            return rtrim($host, '/');
        }

        $scheme = $config['verify_ssl'] ? 'https' : 'http';

        return $scheme.'://'.$host.':'.$config['port'];
    }

    /**
     * @return array{panel: string, file_manager: string, wp_manager: string, websites: string}
     */
    public function buildCyberPanelDeepLinks(string $domain): array
    {
        $base = $this->getPanelBaseUrl();
        $domain = trim($domain);

        if ($base === '') {
            return [
                'panel' => '#',
                'file_manager' => '#',
                'wp_manager' => '#',
                'websites' => '#',
            ];
        }

        return [
            'panel' => $base.'/',
            'file_manager' => $base.'/filemanager/'.$domain,
            'wp_manager' => $base.'/websites/'.$domain.'/wordpress',
            'websites' => $base.'/websites/listWebsites',
        ];
    }
}
