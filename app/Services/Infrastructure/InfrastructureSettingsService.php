<?php

namespace App\Services\Infrastructure;

use App\Models\SystemSetting;
use App\Services\Infrastructure\Netcup\NetcupScpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class InfrastructureSettingsService
{
    protected const GROUP = 'infrastructure';

    protected const CACHE_KEY = 'infrastructure_settings';

    protected const SECRET_KEYS = [
        'contabo_client_secret',
        'contabo_api_password',
        'hetzner_api_token',
        'digitalocean_api_token',
        'ovh_application_secret',
        'ovh_consumer_key',
        'netcup_api_password',
        'netcup_refresh_token',
        'netcup_client_secret',
    ];

    /**
     * @return array<string, string>
     */
    public function getCredentials(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $keys = config('infrastructure.keys');
            $defaults = config('infrastructure.defaults');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $out = [];
            foreach ($keys as $formKey => $dbKey) {
                $raw = $stored[$dbKey] ?? $defaults[$formKey] ?? '';
                $out[$formKey] = in_array($formKey, self::SECRET_KEYS, true)
                    ? $this->decryptIfEncrypted($raw)
                    : (string) $raw;
            }

            return $out;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormSettings(): array
    {
        $creds = $this->getCredentials();

        return [
            'contabo_client_id' => $creds['contabo_client_id'] ?? '',
            'contabo_api_user' => $creds['contabo_api_user'] ?? '',
            'has_contabo_secret' => ($creds['contabo_client_secret'] ?? '') !== '',
            'has_contabo_password' => ($creds['contabo_api_password'] ?? '') !== '',
            'has_hetzner_token' => ($creds['hetzner_api_token'] ?? '') !== '',
            'has_digitalocean_token' => ($creds['digitalocean_api_token'] ?? '') !== '',
            'ovh_application_key' => $creds['ovh_application_key'] ?? '',
            'ovh_endpoint' => $creds['ovh_endpoint'] ?? 'ovh-eu',
            'has_ovh_application_secret' => ($creds['ovh_application_secret'] ?? '') !== '',
            'has_ovh_consumer_key' => ($creds['ovh_consumer_key'] ?? '') !== '',
            'netcup_customer_number' => $creds['netcup_customer_number'] ?? $creds['netcup_client_id'] ?? '',
            'has_netcup_api_password' => ($creds['netcup_api_password'] ?? $creds['netcup_client_secret'] ?? '') !== '',
            'has_netcup_refresh_token' => ($creds['netcup_refresh_token'] ?? '') !== '',
            'netcup_scp_user_id' => $creds['netcup_scp_user_id'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $keys = config('infrastructure.keys');

        foreach ($keys as $formKey => $dbKey) {
            if (! array_key_exists($formKey, $data)) {
                continue;
            }

            $value = trim((string) $data[$formKey]);
            if ($value === '' && in_array($formKey, self::SECRET_KEYS, true)) {
                continue;
            }

            if (in_array($formKey, self::SECRET_KEYS, true) && $value !== '') {
                $value = Crypt::encryptString($value);
            }

            SystemSetting::set($dbKey, $value, 'string', self::GROUP);
        }

        $this->clearCache();
    }

    public function clearCredential(string $formKey): void
    {
        $dbKey = config('infrastructure.keys')[$formKey] ?? null;
        if ($dbKey === null) {
            return;
        }

        SystemSetting::query()
            ->where('group', self::GROUP)
            ->where('key', $dbKey)
            ->delete();

        $this->clearCache();
    }

    public function isProviderConfigured(string $provider): bool
    {
        $creds = $this->getCredentials();

        return match ($provider) {
            'contabo' => ($creds['contabo_client_id'] ?? '') !== ''
                && ($creds['contabo_client_secret'] ?? '') !== ''
                && ($creds['contabo_api_user'] ?? '') !== ''
                && ($creds['contabo_api_password'] ?? '') !== '',
            'hetzner' => ($creds['hetzner_api_token'] ?? '') !== '',
            'digitalocean' => ($creds['digitalocean_api_token'] ?? '') !== '',
            'ovh' => ($creds['ovh_application_key'] ?? '') !== ''
                && ($creds['ovh_application_secret'] ?? '') !== ''
                && ($creds['ovh_consumer_key'] ?? '') !== '',
            'netcup' => ($creds['netcup_refresh_token'] ?? '') !== ''
                || (
                    ($creds['netcup_customer_number'] ?? $creds['netcup_client_id'] ?? '') !== ''
                    && ($creds['netcup_api_password'] ?? $creds['netcup_client_secret'] ?? '') !== ''
                ),
            default => false,
        };
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(NetcupScpClient::TOKEN_CACHE_KEY);
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
