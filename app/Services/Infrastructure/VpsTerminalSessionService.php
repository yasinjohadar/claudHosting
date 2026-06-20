<?php

namespace App\Services\Infrastructure;

use App\Models\VpsActionLog;
use App\Models\VpsServer;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\TerminalSessionService;
use Illuminate\Support\Str;

class VpsTerminalSessionService
{
    public function __construct(
        protected VpsMetricsService $metrics,
        protected CoolifySettingsService $settings,
        protected TerminalSessionService $terminal
    ) {}

    /**
     * @return array{success: bool, ready?: bool, message?: string, settings_url?: string, bridge_url?: string}
     */
    public function readiness(VpsServer $server): array
    {
        $bridge = $this->settings->getTerminalBridgeConfig();

        if (! ($bridge['enabled'] ?? false)) {
            return [
                'success' => false,
                'ready' => false,
                'message' => 'خدمة Terminal Bridge غير مفعّلة. فعّلها من إعدادات Coolify → Terminal.',
                'bridge_url' => route('admin.coolify.settings.section', 'terminal'),
            ];
        }

        if (trim((string) ($bridge['secret'] ?? '')) === '') {
            return [
                'success' => false,
                'ready' => false,
                'message' => 'سر Terminal Bridge غير مضبوط.',
                'bridge_url' => route('admin.coolify.settings.section', 'terminal'),
            ];
        }

        if (! $server->isRunning()) {
            return [
                'success' => false,
                'ready' => false,
                'message' => 'السيرفر متوقف — SSH Terminal غير متاح حتى يعمل السيرفر.',
            ];
        }

        $endpoint = $this->metrics->resolveEndpoint($server);
        if (! ($endpoint['success'] ?? false)) {
            return [
                'success' => false,
                'ready' => false,
                'message' => $endpoint['message'] ?? 'تعذّر تحديد نقطة SSH',
                'settings_url' => $endpoint['settings_url'] ?? route('admin.coolify.settings.section', 'ssh'),
            ];
        }

        return [
            'success' => true,
            'ready' => true,
            'host' => $endpoint['host'],
        ];
    }

    /**
     * @return array{success: bool, token?: string, ws_url?: string, expires_at?: string, message?: string, settings_url?: string, bridge_url?: string}
     */
    public function createSession(VpsServer $server, int $userId): array
    {
        $ready = $this->readiness($server);
        if (! ($ready['ready'] ?? false)) {
            return $ready;
        }

        $bridge = $this->settings->getTerminalBridgeConfig();
        $ssh = $this->settings->getSshConfig();
        $endpoint = $this->metrics->resolveEndpoint($server);

        $ttl = (int) ($bridge['token_ttl_seconds'] ?? 900);
        $exp = time() + $ttl;
        $payload = [
            'mode' => 'host',
            'sub' => (string) $userId,
            'vps' => $server->uuid,
            'host' => $endpoint['host'],
            'ssh_user' => $ssh['ssh_user'] ?? 'root',
            'ssh_port' => $this->settings->getSshPort(),
            'exp' => $exp,
            'jti' => (string) Str::uuid(),
        ];

        $secret = (string) ($bridge['secret'] ?? '');
        $token = $this->terminal->encodeToken($payload, $secret);
        $base = rtrim((string) ($bridge['url'] ?? ''), '/');
        $wsBase = preg_replace('#^http#', 'ws', $base);
        $wsBase = preg_replace('#^https#', 'wss', $wsBase);

        VpsActionLog::query()->create([
            'vps_server_id' => $server->id,
            'user_id' => $userId,
            'action' => 'terminal.session',
            'success' => true,
            'message' => 'فتح جلسة SSH Terminal',
            'meta' => ['host' => $endpoint['host'], 'jti' => $payload['jti']],
        ]);

        return [
            'success' => true,
            'token' => $token,
            'ws_url' => $wsBase.'/host-session',
            'expires_at' => date('c', $exp),
        ];
    }
}
