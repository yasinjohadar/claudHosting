<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NetcupDeviceAuthService
{
    public function __construct(protected InfrastructureSettingsService $settings) {}

    /**
     * @return array{success: bool, message?: string, poll_token?: string, user_code?: string, verification_uri?: string, verification_uri_complete?: string, expires_in?: int, interval?: int}
     */
    public function start(int $userId): array
    {
        $deviceUrl = (string) config('infrastructure.netcup.device_auth_url');
        $clientId = (string) config('infrastructure.netcup.oauth_client_id', 'scp');

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($deviceUrl, [
                    'client_id' => $clientId,
                    'scope' => 'offline_access openid',
                ]);

            if (! $response->successful()) {
                $body = $response->json();

                return [
                    'success' => false,
                    'message' => $this->formatOAuthError($body, $response->status()),
                ];
            }

            $deviceCode = (string) $response->json('device_code', '');
            if ($deviceCode === '') {
                return ['success' => false, 'message' => 'استجابة غير متوقعة من Netcup SCP'];
            }

            $pollToken = Str::random(40);
            $expiresIn = (int) $response->json('expires_in', 600);
            $interval = max(3, (int) $response->json('interval', 5));

            Cache::put($this->cacheKey($pollToken), [
                'user_id' => $userId,
                'device_code' => $deviceCode,
                'interval' => $interval,
                'expires_at' => now()->addSeconds($expiresIn)->timestamp,
            ], $expiresIn + 30);

            return [
                'success' => true,
                'poll_token' => $pollToken,
                'user_code' => (string) $response->json('user_code', ''),
                'verification_uri' => (string) $response->json('verification_uri', ''),
                'verification_uri_complete' => (string) $response->json('verification_uri_complete', ''),
                'expires_in' => $expiresIn,
                'interval' => $interval,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, status: string, message?: string, interval?: int, user_label?: string}
     */
    public function poll(string $pollToken, int $userId): array
    {
        $session = Cache::get($this->cacheKey($pollToken));

        if (! is_array($session) || ($session['user_id'] ?? null) !== $userId) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'جلسة الربط غير صالحة أو منتهية — ابدأ من جديد',
            ];
        }

        if (now()->timestamp > (int) ($session['expires_at'] ?? 0)) {
            Cache::forget($this->cacheKey($pollToken));

            return [
                'success' => false,
                'status' => 'error',
                'message' => 'انتهت مهلة ربط SCP — ابدأ من جديد',
            ];
        }

        $tokenUrl = (string) config('infrastructure.netcup.token_url');
        $clientId = (string) config('infrastructure.netcup.oauth_client_id', 'scp');

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                    'device_code' => (string) ($session['device_code'] ?? ''),
                    'client_id' => $clientId,
                ]);

            if ($response->successful()) {
                $refreshToken = (string) $response->json('refresh_token', '');
                if ($refreshToken === '') {
                    return [
                        'success' => false,
                        'status' => 'error',
                        'message' => 'لم يُرجع Netcup refresh token — تأكد من الموافقة على offline_access',
                    ];
                }

                $this->settings->save(['netcup_refresh_token' => $refreshToken]);
                $accessToken = (string) $response->json('access_token', '');
                $this->persistUserIdFromAccessToken($accessToken);
                $this->settings->clearCache();
                Cache::forget($this->cacheKey($pollToken));

                $userLabel = $this->fetchUserLabel((string) $response->json('access_token', ''));

                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => 'تم ربط Netcup SCP بنجاح',
                    'user_label' => $userLabel,
                ];
            }

            $body = $response->json();
            $error = (string) ($body['error'] ?? '');

            if (in_array($error, ['authorization_pending', 'slow_down'], true)) {
                $interval = (int) ($session['interval'] ?? 5);
                if ($error === 'slow_down') {
                    $interval = min(15, $interval + 2);
                    $session['interval'] = $interval;
                    Cache::put($this->cacheKey($pollToken), $session, max(60, (int) $session['expires_at'] - now()->timestamp));
                }

                return [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'بانتظار الموافقة في SCP…',
                    'interval' => $interval,
                ];
            }

            Cache::forget($this->cacheKey($pollToken));

            return [
                'success' => false,
                'status' => 'error',
                'message' => $this->formatOAuthError($body, $response->status()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function revoke(): array
    {
        $creds = $this->settings->getCredentials();
        $refreshToken = trim((string) ($creds['netcup_refresh_token'] ?? ''));

        if ($refreshToken === '') {
            return ['success' => false, 'message' => 'لا يوجد Refresh Token محفوظ'];
        }

        $revokeUrl = (string) config('infrastructure.netcup.revoke_url');
        $clientId = (string) config('infrastructure.netcup.oauth_client_id', 'scp');

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($revokeUrl, [
                    'client_id' => $clientId,
                    'token' => $refreshToken,
                    'token_type_hint' => 'refresh_token',
                ]);

            $this->settings->clearCredential('netcup_refresh_token');
            $this->settings->clearCache();

            if ($response->successful() || $response->status() === 400) {
                return ['success' => true, 'message' => 'تم إلغاء ربط Netcup SCP'];
            }

            return [
                'success' => false,
                'message' => 'فشل الإلغاء: HTTP '.$response->status(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function fetchUserLabel(string $accessToken): ?string
    {
        if ($accessToken === '') {
            return null;
        }

        $this->persistUserIdFromAccessToken($accessToken);

        $userinfoUrl = (string) config('infrastructure.netcup.userinfo_url');

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withToken($accessToken)
                ->get($userinfoUrl);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->json();
            $name = trim((string) ($body['name'] ?? $body['preferred_username'] ?? ''));
            $sub = trim((string) ($body['sub'] ?? $body['id'] ?? ''));

            if ($sub !== '') {
                $this->settings->save(['netcup_scp_user_id' => $sub]);
            }

            if ($name !== '' && $sub !== '') {
                return $name.' ('.$sub.')';
            }

            return $name !== '' ? $name : ($sub !== '' ? $sub : null);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  mixed  $body
     */
    protected function formatOAuthError($body, int $status): string
    {
        if (is_array($body)) {
            $description = (string) ($body['error_description'] ?? $body['error'] ?? '');

            return $description !== '' ? $description : 'HTTP '.$status;
        }

        return 'HTTP '.$status;
    }

    protected function cacheKey(string $pollToken): string
    {
        return 'netcup_device_auth_'.hash('sha256', $pollToken);
    }

    protected function persistUserIdFromAccessToken(string $accessToken): void
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
