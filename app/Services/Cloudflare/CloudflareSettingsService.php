<?php

namespace App\Services\Cloudflare;

use App\Models\SystemSetting;
use App\Services\CloudflareApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class CloudflareSettingsService
{
    protected const GROUP = 'cloudflare';

    protected const CACHE_KEY = 'cloudflare_connection_config';

    /**
     * @return array{api_token: string, account_id: string, timeout: int, cache_ttl: int, token_configured: bool}
     */
    public function getConnectionConfig(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $this->initializeDefaults();

            $keys = config('cloudflare.keys');
            $stored = SystemSetting::query()
                ->where('group', self::GROUP)
                ->pluck('value', 'key')
                ->toArray();

            $tokenRaw = $stored[$keys['api_token']] ?? '';
            $accountId = CloudflareApiService::sanitizeAccountId((string) ($stored[$keys['account_id']] ?? ''));
            $timeout = (int) ($stored[$keys['timeout']] ?? config('cloudflare.defaults.timeout', 30));
            $cacheTtl = (int) ($stored[$keys['cache_ttl']] ?? config('cloudflare.defaults.cache_ttl', 600));

            $token = $this->decryptIfEncrypted($tokenRaw);
            if ($token === '') {
                $token = trim((string) config('cloudflare.env_fallback.api_token', ''));
            }
            if ($accountId === '') {
                $accountId = trim((string) config('cloudflare.env_fallback.account_id', ''));
            }

            return [
                'api_token' => $token,
                'account_id' => $accountId,
                'timeout' => max(5, min(120, $timeout)),
                'cache_ttl' => max(60, min(3600, $cacheTtl)),
                'token_configured' => $token !== '' || $tokenRaw !== '',
            ];
        });
    }

    /**
     * @return array{account_id: string, timeout: int, cache_ttl: int, has_token: bool}
     */
    public function getFormSettings(): array
    {
        $config = $this->getConnectionConfig();

        return [
            'account_id' => $config['account_id'],
            'timeout' => $config['timeout'],
            'cache_ttl' => $config['cache_ttl'],
            'has_token' => $config['token_configured'],
        ];
    }

    public function updateSettings(array $data): void
    {
        $keys = config('cloudflare.keys');

        if (array_key_exists('account_id', $data)) {
            $accountId = CloudflareApiService::sanitizeAccountId((string) $data['account_id']);
            SystemSetting::set($keys['account_id'], $accountId, 'string', self::GROUP);
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

    public function clearInvalidAccountIdIfNeeded(): bool
    {
        $keys = config('cloudflare.keys');
        $stored = SystemSetting::query()
            ->where('group', self::GROUP)
            ->where('key', $keys['account_id'])
            ->value('value');

        if (! is_string($stored) || $stored === '' || CloudflareApiService::isValidAccountId($stored)) {
            return false;
        }

        SystemSetting::set($keys['account_id'], '', 'string', self::GROUP);
        $this->clearCache();

        return true;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('cloudflare_account_id');

        $config = $this->getConnectionConfig();
        $accountId = CloudflareApiService::sanitizeAccountId($config['account_id'] ?? '');
        if ($accountId !== '') {
            Cache::forget('cloudflare_registrar_v2_'.$accountId);
            Cache::forget('cloudflare_registrar_domains_'.$accountId);
        }

        // مفاتيح قديمة (قبل v2 أو account_id خاطئ مثل بريد)
        Cache::forget('cloudflare_zones_all');
        Cache::forget('cloudflare_registrar_domains');
        Cache::forget('cloudflare_zones_v2_'.md5('|'));
        Cache::forget('cloudflare_zones_v2_'.md5(''));
    }

    public function initializeDefaults(): void
    {
        $keys = config('cloudflare.keys');
        $defaults = config('cloudflare.defaults');

        foreach ([
            $keys['api_token'] => $defaults['api_token'],
            $keys['account_id'] => $defaults['account_id'],
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
