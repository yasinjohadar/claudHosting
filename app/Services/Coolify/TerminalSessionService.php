<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TerminalSessionService
{
    public function __construct(
        protected ContainerContextFactory $contextFactory,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * Pings the terminal-bridge microservice's /health endpoint, cached briefly
     * to avoid hammering it on every tab open / poll.
     *
     * @return array{success: bool, message: string}
     */
    public function checkBridgeHealth(?string $urlOverride = null): array
    {
        $bridge = $this->settings->getTerminalBridgeConfig();
        $enabled = $urlOverride !== null ? true : (bool) ($bridge['enabled'] ?? false);

        if (! $enabled) {
            return ['success' => false, 'message' => 'خدمة Terminal غير مفعّلة'];
        }

        $url = $urlOverride !== null
            ? rtrim($urlOverride, '/')
            : rtrim((string) ($bridge['url'] ?? ''), '/');

        if ($url === '') {
            return ['success' => false, 'message' => 'أدخل عنوان HTTP للجسر (مثال: http://127.0.0.1:3099).'];
        }

        $cacheKey = 'terminal-bridge:health:'.md5($url);
        if ($urlOverride === null) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $response = Http::timeout(8)->get($url.'/health');
            $result = ($response->successful() && ($response->json('ok') === true || $response->json('ok') === 1))
                ? ['success' => true, 'message' => 'الجسر يعمل: '.$url.'/health']
                : ['success' => false, 'message' => 'استجابة غير متوقعة من الجسر (HTTP '.$response->status().').'];
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => 'تعذّر الاتصال بـ '.$url.' — '.$e->getMessage().' تأكد أن خدمة terminal-bridge تعمل (مثال: cd services/terminal-bridge && npm start).',
            ];
        }

        if ($urlOverride === null) {
            Cache::put($cacheKey, $result, now()->addSeconds(15));
        }

        return $result;
    }

    /**
     * @return array{success: bool, token?: string, ws_url?: string, expires_at?: string, message?: string}
     */
    public function createSession(CoolifyWordpressSite $site, int $userId): array
    {
        $bridge = $this->settings->getTerminalBridgeConfig();

        if (! ($bridge['enabled'] ?? false)) {
            return ['success' => false, 'message' => 'خدمة Terminal غير مفعّلة. فعّلها من إعدادات Coolify → تبويب Terminal وشغّل خدمة terminal-bridge.'];
        }

        $secret = (string) ($bridge['secret'] ?? '');
        if ($secret === '') {
            return ['success' => false, 'message' => 'سر Terminal Bridge غير مضبوط — أضفه من إعدادات Coolify → Terminal.'];
        }

        $ctx = $this->contextFactory->forSite($site);
        if (! ($ctx['success'] ?? false)) {
            return ['success' => false, 'message' => $ctx['message'] ?? 'غير متاح'];
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $ssh = $this->settings->getSshConfig();

        $ttl = (int) ($bridge['token_ttl_seconds'] ?? 900);
        $exp = time() + $ttl;
        $payload = [
            'sub' => (string) $userId,
            'site' => $site->uuid,
            'host' => $context->host,
            'container_id' => $context->containerId,
            'wordpress_root' => $context->wordpressRoot,
            'ssh_user' => $ssh['ssh_user'] ?? 'root',
            'ssh_port' => $this->settings->getSshPort(),
            'exp' => $exp,
            'jti' => (string) Str::uuid(),
        ];

        $token = $this->encodeToken($payload, $secret);
        $base = rtrim((string) ($bridge['url'] ?? ''), '/');
        $wsBase = preg_replace('#^http#', 'ws', $base);
        $wsBase = preg_replace('#^https#', 'wss', $wsBase);

        return [
            'success' => true,
            'token' => $token,
            'ws_url' => $wsBase.'/session',
            'expires_at' => date('c', $exp),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function encodeToken(array $payload, string $secret): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($payload));
        $sig = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $secret, true));

        return $header.'.'.$body.'.'.$sig;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
