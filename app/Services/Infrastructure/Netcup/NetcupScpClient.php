<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetcupScpClient
{
    use NetcupScpHelpers;

    public const TOKEN_CACHE_KEY = 'netcup_scp_access_token';

    public const TOKEN_LOCK_KEY = 'netcup_scp_access_token_lock';

    protected ?string $lastTokenError = null;

    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function baseUrl(): string
    {
        return rtrim((string) config('infrastructure.netcup.api_base'), '/');
    }

    public function pingUrl(): string
    {
        return rtrim((string) config('infrastructure.netcup.ping_url'), '/');
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function test(): array
    {
        $ping = $this->ping();
        if ($ping['success']) {
            return ['success' => true, 'message' => 'الاتصال بـ Netcup SCP ناجح'];
        }

        $res = $this->request('GET', '/servers', ['limit' => 1]);

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'الاتصال بـ Netcup SCP ناجح' : ($res['message'] ?? 'فشل'),
        ];
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function ping(): array
    {
        try {
            $response = Http::timeout(15)->acceptJson()->get($this->pingUrl());

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'SCP API متاح' : 'HTTP '.$response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function maintenance(): array
    {
        return $this->wrapResponse($this->request('GET', '/maintenance'));
    }

    public function getUserId(): ?string
    {
        return $this->resolveUserId($this->settings);
    }

    /**
     * @return array{success: bool, message?: string, body?: mixed, task_uuid?: ?string}
     */
    public function request(string $method, string $path, array $query = [], ?array $json = null): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return [
                'success' => false,
                'message' => $this->lastTokenError ?? 'توكن Netcup غير متاح — راجع بيانات SCP أدناه',
            ];
        }

        try {
            $url = $this->baseUrl().'/'.ltrim($path, '/');
            $pending = Http::timeout(60)
                ->acceptJson()
                ->withToken($token);

            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url, $query),
                'POST' => $pending->asJson()->post($url, $json ?? []),
                'PUT' => $pending->asJson()->put($url, $json ?? []),
                'PATCH' => $pending->asJson()->patch($url, $json ?? []),
                'DELETE' => $pending->delete($url, $query),
                default => $pending->send($method, $url, ['json' => $json]),
            };

            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);
                $token = $this->accessToken(true);
                if ($token !== null) {
                    return $this->request($method, $path, $query, $json);
                }

                return [
                    'success' => false,
                    'message' => $this->lastTokenError ?? 'انتهت صلاحية توكن Netcup — أعد اختبار الاتصال',
                ];
            }

            $body = $response->json();
            if (! $response->successful()) {
                $message = is_array($body)
                    ? (string) ($body['message'] ?? $body['error'] ?? json_encode($body))
                    : 'HTTP '.$response->status();

                Log::warning('Netcup SCP API request failed', [
                    'method' => $method,
                    'path' => $path,
                    'status' => $response->status(),
                    'message' => $message,
                ]);

                return ['success' => false, 'message' => $message, 'body' => $body];
            }

            return [
                'success' => true,
                'body' => $body,
                'task_uuid' => $this->extractTaskUuid($body),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function accessToken(bool $forceRefresh = false): ?string
    {
        if ($forceRefresh) {
            Cache::forget(self::TOKEN_CACHE_KEY);
        }

        $this->lastTokenError = null;

        try {
            return Cache::lock(self::TOKEN_LOCK_KEY, 20)->block(10, function () {
                return Cache::remember(self::TOKEN_CACHE_KEY, 270, function () {
                    return $this->fetchAccessToken();
                });
            });
        } catch (LockTimeoutException) {
            return Cache::get(self::TOKEN_CACHE_KEY);
        }
    }

    protected function fetchAccessToken(): ?string
    {
        $creds = $this->settings->getCredentials();
        $tokenUrl = (string) config('infrastructure.netcup.token_url');
        $clientId = (string) config('infrastructure.netcup.oauth_client_id', 'scp');

        $refreshToken = trim((string) ($creds['netcup_refresh_token'] ?? ''));
        if ($refreshToken !== '') {
            $token = $this->requestToken($tokenUrl, [
                'grant_type' => 'refresh_token',
                'client_id' => $clientId,
                'refresh_token' => $refreshToken,
            ]);

            if ($token !== null) {
                return $token;
            }
        }

        $customerNumber = trim((string) ($creds['netcup_customer_number'] ?? $creds['netcup_client_id'] ?? ''));
        $apiPassword = trim((string) ($creds['netcup_api_password'] ?? $creds['netcup_client_secret'] ?? ''));

        if ($customerNumber !== '' && $apiPassword !== '') {
            $token = $this->requestToken($tokenUrl, [
                'grant_type' => 'password',
                'client_id' => $clientId,
                'username' => $customerNumber,
                'password' => $apiPassword,
                'scope' => 'offline_access openid',
            ]);

            if ($token !== null) {
                return $token;
            }
        }

        if ($this->lastTokenError === null) {
            $this->lastTokenError = 'اربط SCP عبر Device Flow أو أدخل رقم العميل + API Password';
        }

        return null;
    }

    /**
     * @param  array<string, string>  $payload
     */
    protected function requestToken(string $tokenUrl, array $payload): ?string
    {
        try {
            $response = Http::asForm()->timeout(30)->post($tokenUrl, $payload);

            if (! $response->successful()) {
                $body = $response->json();
                $error = is_array($body)
                    ? (string) ($body['error_description'] ?? $body['error'] ?? json_encode($body))
                    : 'HTTP '.$response->status();
                $this->lastTokenError = 'فشل مصادقة Netcup SCP: '.$error;

                Log::warning('Netcup SCP token request failed', [
                    'grant_type' => $payload['grant_type'] ?? null,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return null;
            }

            $newRefresh = $response->json('refresh_token');
            if (is_string($newRefresh) && $newRefresh !== '') {
                $this->settings->save(['netcup_refresh_token' => $newRefresh]);
            }

            $accessToken = $response->json('access_token');
            if (is_string($accessToken) && $accessToken !== '') {
                $this->persistUserIdFromToken($accessToken);
            }

            return is_string($accessToken) && $accessToken !== '' ? $accessToken : null;
        } catch (\Throwable $e) {
            $this->lastTokenError = 'فشل مصادقة Netcup SCP: '.$e->getMessage();

            Log::error('Netcup SCP token request exception', [
                'grant_type' => $payload['grant_type'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function persistUserIdFromToken(string $accessToken): void
    {
        $parts = explode('.', $accessToken);
        if (count($parts) < 2) {
            return;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')) ?: '', true);
        if (is_array($payload) && ! empty($payload['sub'])) {
            $this->settings->save(['netcup_scp_user_id' => (string) $payload['sub']]);
        }
    }
}
