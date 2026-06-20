<?php

namespace App\Services\Coolify;

use Illuminate\Support\Facades\File;

/**
 * يزامن إعدادات Terminal Bridge من قاعدة البيانات إلى ملف runtime
 * ليقرأه services/terminal-bridge دون تعديل .env يدوياً.
 */
class TerminalBridgeRuntimeService
{
    public function __construct(
        protected CoolifySettingsService $settings,
        protected CoolifySshExecutor $ssh
    ) {}

    public function runtimePath(): string
    {
        return storage_path('app/terminal-bridge/runtime.json');
    }

    public function sync(): void
    {
        $bridge = $this->settings->getTerminalBridgeConfig();
        $ssh = $this->settings->getSshConfig();

        $keyPath = '';
        if ($ssh['ssh_key_configured'] ?? false) {
            $resolved = $this->ssh->resolveKeyPathForTest($ssh['ssh_private_key_path'] ?? '');
            if ($resolved !== '' && is_file($resolved)) {
                $keyPath = $resolved;
            } elseif ($ssh['ssh_private_key'] ?? '') {
                $default = $this->ssh->defaultStorageKeyPath();
                File::ensureDirectoryExists(dirname($default));
                if (! is_file($default)) {
                    File::put($default, $ssh['ssh_private_key']);
                }
                $keyPath = $default;
            }
        }

        $payload = [
            'secret' => (string) ($bridge['secret'] ?? ''),
            'port' => (int) ($bridge['port'] ?? 3099),
            'ssh_user' => (string) ($ssh['ssh_user'] ?? 'root'),
            'ssh_port' => (int) $this->settings->getSshPort(),
            'ssh_private_key_path' => $keyPath,
            'bridge_url' => (string) ($bridge['url'] ?? ''),
            'enabled' => (bool) ($bridge['enabled'] ?? false),
            'updated_at' => now()->toIso8601String(),
        ];

        File::ensureDirectoryExists(dirname($this->runtimePath()));
        File::put($this->runtimePath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
