<?php

namespace App\Services\Namecom;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class NamecomSettingsService
{
    protected const GROUP = 'namecom';

    protected const CACHE_KEY = 'namecom_connection_config';

    /**
     * @return array{username: string, api_token: string, api_base: string, timeout: int, cache_ttl: int, token_configured: bool}
     */
    public function getConnectionConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('namecom.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $username = trim((string) ($stored[$keys['username']] ?? ''));
            $tokenRaw = $stored[$keys['api_token']] ?? '';
            $apiBase = trim((string) ($stored[$keys['api_base']] ?? config('namecom.defaults.api_base')));
            $timeout = (int) ($stored[$keys['timeout']] ?? config('namecom.defaults.timeout', 30));
            $cacheTtl = (int) ($stored[$keys['cache_ttl']] ?? config('namecom.defaults.cache_ttl', 600));

            $token = $this->decryptIfEncrypted($tokenRaw);

            if ($username === '') {
                $username = trim((string) config('namecom.env_fallback.username', ''));
            }
            if ($token === '') {
                $token = trim((string) config('namecom.env_fallback.api_token', ''));
            }
            if ($apiBase === '') {
                $apiBase = config('namecom.defaults.api_base');
            }

            return [
                'username' => $username,
                'api_token' => $token,
                'api_base' => rtrim($apiBase, '/'),
                'timeout' => max(5, min(120, $timeout)),
                'cache_ttl' => max(60, min(3600, $cacheTtl)),
                'token_configured' => $token !== '' || $tokenRaw !== '',
            ];
        });
    }

    /**
     * @return array{username: string, api_base: string, timeout: int, cache_ttl: int, has_token: bool}
     */
    public function getFormSettings(): array
    {
        $config = $this->getConnectionConfig();

        return [
            'username' => $config['username'],
            'api_base' => $config['api_base'],
            'timeout' => $config['timeout'],
            'cache_ttl' => $config['cache_ttl'],
            'has_token' => $config['token_configured'],
        ];
    }

    public function updateSettings(array $data): void
    {
        $keys = config('namecom.keys');

        if (array_key_exists('username', $data)) {
            SystemSetting::set($keys['username'], trim((string) $data['username']), 'string', self::GROUP);
        }

        if (array_key_exists('api_base', $data)) {
            $base = trim((string) $data['api_base']);
            if ($base === '') {
                $base = config('namecom.defaults.api_base');
            }
            SystemSetting::set($keys['api_base'], rtrim($base, '/'), 'string', self::GROUP);
        }

        if (isset($data['timeout'])) {
            SystemSetting::set($keys['timeout'], (string) max(5, min(120, (int) $data['timeout'])), 'integer', self::GROUP);
        }

        if (isset($data['cache_ttl'])) {
            SystemSetting::set($keys['cache_ttl'], (string) max(60, min(3600, (int) $data['cache_ttl'])), 'integer', self::GROUP);
        }

        if (! empty($data['api_token'])) {
            SystemSetting::set(
                $keys['api_token'],
                Crypt::encryptString((string) $data['api_token']),
                'string',
                self::GROUP
            );
        }

        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        $config = $this->getConnectionConfig();
        $username = $config['username'] ?? '';
        if ($username !== '') {
            Cache::forget('namecom_domains_v1_'.md5($username));
        }
    }

    public function initializeDefaults(): void
    {
        $keys = config('namecom.keys');
        $defaults = config('namecom.defaults');

        foreach ([
            $keys['username'] => $defaults['username'],
            $keys['api_token'] => $defaults['api_token'],
            $keys['api_base'] => $defaults['api_base'],
            $keys['timeout'] => (string) $defaults['timeout'],
            $keys['cache_ttl'] => (string) $defaults['cache_ttl'],
        ] as $key => $value) {
            if (! SystemSetting::query()->where('group', self::GROUP)->where('key', $key)->exists()) {
                SystemSetting::set($key, (string) $value, 'string', self::GROUP);
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
