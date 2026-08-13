<?php

namespace App\Services\Whm;

use App\Services\Coolify\CoolifySettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * SSH to the WHM/cPanel server for WP-CLI WordPress management.
 * Falls back to Coolify SSH key when WHM key is empty (same VPS setups).
 */
class WhmSshExecutor
{
    protected const SSH_CACHE_KEY = 'whm_ssh_config';

    public function __construct(
        protected WhmSettingsService $settings,
        protected CoolifySettingsService $coolifySettings
    ) {}

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function run(string $command, int $timeout = 120): array
    {
        $host = $this->resolveHost();
        if ($host === '') {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'message' => 'اضبط عنوان SSH لسيرفر WHM في إعدادات WHM',
            ];
        }

        $keyPath = $this->resolveKeyPath();
        if ($keyPath === null) {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'message' => 'اضبط مفتاح SSH في إعدادات WHM (أو Coolify إن كان نفس السيرفر)',
            ];
        }

        $ssh = $this->sshBinary();
        $user = $this->sshUser();
        $port = $this->sshPort();

        $process = new Process([
            $ssh,
            '-i', $keyPath,
            '-p', (string) $port,
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=20',
            '-o', 'ConnectionAttempts=1',
            '-o', 'IdentitiesOnly=yes',
            '-o', 'PreferredAuthentications=publickey',
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile='.(PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'),
            $user.'@'.$host,
            $command,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::warning('WHM SSH failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'output' => $e->getMessage(),
                'exit_code' => 1,
                'message' => $e->getMessage(),
            ];
        }

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        return [
            'success' => $process->isSuccessful(),
            'output' => $output,
            'exit_code' => $process->getExitCode() ?? 1,
        ];
    }

    /**
     * @return array{success: bool, message: string, output?: string}
     */
    public function testConnection(): array
    {
        $result = $this->run('echo whm-ssh-ok', 30);
        if (($result['success'] ?? false) && str_contains($result['output'] ?? '', 'whm-ssh-ok')) {
            return ['success' => true, 'message' => 'اتصال SSH ناجح', 'output' => $result['output']];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? ($result['output'] ?: 'فشل اختبار SSH'),
            'output' => $result['output'] ?? '',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->resolveHost() !== '' && $this->resolveKeyPath() !== null;
    }

    public function resolveHost(): string
    {
        $config = $this->getSshConfig();
        $host = trim((string) ($config['ssh_host'] ?? ''));
        if ($host !== '') {
            return $this->normalizeHost($host);
        }

        $apiHost = trim((string) ($this->settings->getConnectionConfig()['host'] ?? ''));

        return $this->normalizeHost($apiHost);
    }

    /**
     * @return array{ssh_host: string, ssh_user: string, ssh_port: int, ssh_private_key: string, ssh_private_key_path: string, ssh_key_configured: bool, using_coolify_key: bool}
     */
    public function getSshConfig(): array
    {
        return Cache::remember(self::SSH_CACHE_KEY, 300, function () {
            $this->settings->initializeDefaults();
            $keys = config('whm.keys');
            $stored = \App\Models\SystemSetting::query()
                ->where('group', 'whm')
                ->pluck('value', 'key')
                ->toArray();

            $keyRaw = $stored[$keys['ssh_private_key']] ?? '';
            $privateKey = $this->decryptIfEncrypted($keyRaw);
            $path = trim((string) ($stored[$keys['ssh_private_key_path']] ?? ''));
            $host = trim((string) ($stored[$keys['ssh_host']] ?? ''));
            $user = trim((string) ($stored[$keys['ssh_user']] ?? 'root')) ?: 'root';
            $port = (int) ($stored[$keys['ssh_port']] ?? 22);
            if ($port <= 0 || $port > 65535) {
                $port = 22;
            }

            $usingCoolify = false;
            $configured = $privateKey !== '' || ($path !== '' && is_file($path));
            if (! $configured) {
                $coolify = $this->coolifySettings->getSshConfig();
                if ($coolify['ssh_key_configured'] ?? false) {
                    $privateKey = (string) ($coolify['ssh_private_key'] ?? '');
                    $path = (string) ($coolify['ssh_private_key_path'] ?? '');
                    $configured = true;
                    $usingCoolify = true;
                }
            }

            return [
                'ssh_host' => $host,
                'ssh_user' => $user,
                'ssh_port' => $port,
                'ssh_private_key' => $privateKey,
                'ssh_private_key_path' => $path,
                'ssh_key_configured' => $configured,
                'using_coolify_key' => $usingCoolify,
            ];
        });
    }

    public function clearSshCache(): void
    {
        Cache::forget(self::SSH_CACHE_KEY);
    }

    protected function sshUser(): string
    {
        return $this->getSshConfig()['ssh_user'] ?: 'root';
    }

    protected function sshPort(): int
    {
        return (int) ($this->getSshConfig()['ssh_port'] ?? 22);
    }

    protected function resolveKeyPath(): ?string
    {
        $config = $this->getSshConfig();
        $path = trim((string) ($config['ssh_private_key_path'] ?? ''));
        if ($path !== '' && is_file($path)) {
            return $path;
        }

        $pem = trim((string) ($config['ssh_private_key'] ?? ''));
        if ($pem === '' || ! str_contains($pem, 'PRIVATE KEY')) {
            return null;
        }

        $dir = 'whm-keys';
        Storage::disk('local')->makeDirectory($dir);
        $file = storage_path('app/'.$dir.'/ssh_key_'.md5($pem).'.pem');
        if (! is_file($file)) {
            file_put_contents($file, $pem);
            if (PHP_OS_FAMILY !== 'Windows') {
                @chmod($file, 0600);
            }
        }

        return $file;
    }

    protected function normalizeHost(string $host): string
    {
        $host = trim($host);
        if ($host === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $host)) {
            $parsed = parse_url($host);
            $host = (string) ($parsed['host'] ?? '');
        }

        $host = preg_replace('#:\d+$#', '', $host) ?? $host;

        return strtolower(trim($host));
    }

    protected function sshBinary(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $path = 'C:\\Windows\\System32\\OpenSSH\\ssh.exe';
            if (is_file($path)) {
                return $path;
            }
        }

        return 'ssh';
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
