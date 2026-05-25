<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Str;

class TerminalSessionService
{
    public function __construct(
        protected ContainerContextFactory $contextFactory,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @return array{success: bool, token?: string, ws_url?: string, expires_at?: string, message?: string}
     */
    public function createSession(CoolifyWordpressSite $site, int $userId): array
    {
        if (! config('terminal_bridge.enabled')) {
            return ['success' => false, 'message' => 'خدمة Terminal غير مفعّلة. فعّل TERMINAL_BRIDGE_ENABLED وشغّل terminal-bridge.'];
        }

        $secret = config('terminal_bridge.secret');
        if ($secret === '') {
            return ['success' => false, 'message' => 'TERMINAL_BRIDGE_SECRET غير مضبوط'];
        }

        $ctx = $this->contextFactory->forSite($site);
        if (! ($ctx['success'] ?? false)) {
            return ['success' => false, 'message' => $ctx['message'] ?? 'غير متاح'];
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $ssh = $this->settings->getSshConfig();

        $ttl = (int) config('terminal_bridge.token_ttl_seconds', 900);
        $exp = time() + $ttl;
        $payload = [
            'sub' => (string) $userId,
            'site' => $site->uuid,
            'host' => $context->host,
            'container_id' => $context->containerId,
            'wordpress_root' => $context->wordpressRoot,
            'ssh_user' => $ssh['ssh_user'] ?? 'root',
            'ssh_port' => (int) ($ssh['ssh_port'] ?? 22),
            'exp' => $exp,
            'jti' => (string) Str::uuid(),
        ];

        $token = $this->encodeToken($payload, $secret);
        $base = rtrim(config('terminal_bridge.url'), '/');
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
