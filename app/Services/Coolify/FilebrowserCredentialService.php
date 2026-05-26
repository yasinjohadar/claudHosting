<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class FilebrowserCredentialService
{
    public function __construct(
        protected FilebrowserContainerResolver $containerResolver,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @return array{ok: bool, message?: string}
     */
    public function ensureCredentials(CoolifyWordpressSite $site, bool $force = false): array
    {
        if (! ($site->metadata['filebrowser_enabled'] ?? false) && ! $this->settings->getWordpressFilebrowserEnabled()) {
            return ['ok' => false, 'message' => 'FileBrowser غير مفعّل على هذا الموقع'];
        }

        $metadata = $site->metadata ?? [];
        if (! $force && $this->hasStoredCredentials($metadata)) {
            return ['ok' => true];
        }

        $resolved = $this->containerResolver->resolve($site, $force);
        if (! ($resolved['success'] ?? false)) {
            return ['ok' => false, 'message' => $resolved['message'] ?? 'تعذّر الوصول لحاوية FileBrowser'];
        }

        $password = Str::password($this->settings->getWordpressFilebrowserPasswordLength());
        $username = $this->settings->getWordpressFilebrowserAdminUsername();

        $apply = $this->applyCredentialsOnContainer(
            (string) $resolved['host'],
            (string) $resolved['container_id'],
            $username,
            $password
        );

        if (! ($apply['success'] ?? false)) {
            return ['ok' => false, 'message' => $apply['message'] ?? 'فشل تعيين مستخدم FileBrowser'];
        }

        $site->update([
            'metadata' => array_merge($site->fresh()->metadata ?? [], [
                'filebrowser_username' => $username,
                'filebrowser_password' => Crypt::encryptString($password),
                'filebrowser_credentials_set_at' => now()->toIso8601String(),
            ]),
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function rotate(CoolifyWordpressSite $site): array
    {
        return $this->ensureCredentials($site, force: true);
    }

    /**
     * @return array{username: string, password: string}|null
     */
    public function getCredentials(CoolifyWordpressSite $site): ?array
    {
        $metadata = $site->metadata ?? [];
        $encrypted = $metadata['filebrowser_password'] ?? null;
        $username = trim((string) ($metadata['filebrowser_username'] ?? ''));

        if ($username === '' || ! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            $password = Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }

        if ($password === '') {
            return null;
        }

        return ['username' => $username, 'password' => $password];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function hasStoredCredentials(array $metadata): bool
    {
        return filled($metadata['filebrowser_username'] ?? null)
            && filled($metadata['filebrowser_password'] ?? null);
    }

    /**
     * @return array{success: bool, message?: string, database_path?: string}
     */
    public function applyCredentialsOnContainer(string $host, string $containerId, string $username, string $password): array
    {
        $dbPath = $this->detectDatabasePath($host, $containerId);
        if ($dbPath === null) {
            return ['success' => false, 'message' => 'تعذّر تحديد مسار قاعدة بيانات FileBrowser داخل الحاوية'];
        }

        $userQ = escapeshellarg($username);
        $passQ = escapeshellarg($password);
        $dbQ = escapeshellarg($dbPath);

        $inner = sprintf(
            'filebrowser users update %s %s --perm.admin -d %s 2>/dev/null || filebrowser users add %s %s --perm.admin -d %s; '
            .'filebrowser users update root %s --perm.admin -d %s 2>/dev/null || true',
            $userQ,
            $passQ,
            $dbQ,
            $userQ,
            $passQ,
            $dbQ,
            $passQ,
            $dbQ
        );

        $remote = sprintf(
            'docker exec %s sh -c %s',
            escapeshellarg($containerId),
            escapeshellarg($inner)
        );

        $result = $this->ssh->run($host, $remote, 45);
        if ($result['success'] ?? false) {
            return ['success' => true, 'database_path' => $dbPath];
        }

        $remoteRoot = sprintf(
            'docker exec -u root %s sh -c %s',
            escapeshellarg($containerId),
            escapeshellarg($inner)
        );
        $resultRoot = $this->ssh->run($host, $remoteRoot, 45);

        if ($resultRoot['success'] ?? false) {
            return ['success' => true, 'database_path' => $dbPath];
        }

        $output = trim($resultRoot['output'] ?? $result['output'] ?? '');

        return [
            'success' => false,
            'message' => $output !== '' ? $output : 'فشل تنفيذ filebrowser users',
        ];
    }

    protected function detectDatabasePath(string $host, string $containerId): ?string
    {
        $detect = 'if [ -f /database/filebrowser.db ]; then echo /database/filebrowser.db; elif [ -f /database.db ]; then echo /database.db; else echo ""; fi';

        foreach (['', '-u root '] as $userFlag) {
            $cmd = sprintf(
                'docker exec %s%s sh -c %s',
                $userFlag,
                escapeshellarg($containerId),
                escapeshellarg($detect)
            );
            $result = $this->ssh->run($host, $cmd, 30);
            $path = trim($result['output'] ?? '');
            if ($path !== '' && str_ends_with($path, '.db')) {
                return $path;
            }
        }

        return '/database/filebrowser.db';
    }

    /**
     * استخراج كلمة المرور من سجل التهيئة الأولى (احتياطي).
     */
    public function parsePasswordFromLogs(string $logs): ?array
    {
        if (preg_match("/User '([^']+)' initialized with randomly generated password:\s*(\S+)/i", $logs, $m)) {
            return ['username' => $m[1], 'password' => $m[2]];
        }

        return null;
    }
}
