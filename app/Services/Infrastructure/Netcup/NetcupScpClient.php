<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NetcupScpClient
{
    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function baseUrl(): string
    {
        return rtrim((string) config('infrastructure.netcup.api_base'), '/');
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function test(): array
    {
        $res = $this->request('GET', '/servers', ['limit' => 1]);

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'الاتصال بـ Netcup SCP ناجح' : ($res['message'] ?? 'فشل'),
        ];
    }

    /**
     * @return array{success: bool, message?: string, body?: mixed}
     */
    public function request(string $method, string $path, array $query = [], ?array $json = null): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'message' => 'توكن Netcup غير متاح — راجع Client ID/Secret'];
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
                Cache::forget($this->tokenCacheKey());
                $token = $this->accessToken(true);
                if ($token !== null) {
                    return $this->request($method, $path, $query, $json);
                }
            }

            $body = $response->json();
            if (! $response->successful()) {
                $message = is_array($body)
                    ? (string) ($body['message'] ?? $body['error'] ?? json_encode($body))
                    : 'HTTP '.$response->status();

                return ['success' => false, 'message' => $message, 'body' => $body];
            }

            return ['success' => true, 'body' => $body];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function accessToken(bool $forceRefresh = false): ?string
    {
        if ($forceRefresh) {
            Cache::forget($this->tokenCacheKey());
        }

        return Cache::remember($this->tokenCacheKey(), 3300, function () {
            $creds = $this->settings->getCredentials();
            $clientId = $creds['netcup_client_id'] ?? '';
            $clientSecret = $creds['netcup_client_secret'] ?? '';

            if ($clientId === '' || $clientSecret === '') {
                return null;
            }

            $tokenUrl = config('infrastructure.netcup.token_url');

            try {
                $response = Http::asForm()
                    ->timeout(30)
                    ->post($tokenUrl, [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json('access_token');
            } catch (\Throwable) {
                return null;
            }
        });
    }

    protected function tokenCacheKey(): string
    {
        $creds = $this->settings->getCredentials();

        return 'netcup_scp_token_'.md5($creds['netcup_client_id'] ?? 'x');
    }
}
