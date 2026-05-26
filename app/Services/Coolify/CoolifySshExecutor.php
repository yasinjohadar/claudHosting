<?php

namespace App\Services\Coolify;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CoolifySshExecutor
{
    public function __construct(protected CoolifySettingsService $settings) {}

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    public function run(string $host, string $command, ?int $timeout = 600, ?int $port = null): array
    {
        return $this->runWithKeyFile($host, $command, $timeout, null, $port);
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, command?: string}
     */
    protected function runWithKeyFile(string $host, string $command, int $timeout, ?string $keyPath, ?int $port = null): array
    {
        $process = $this->buildSshProcess($host, $command, $keyPath, $port);

        if ($process === null) {
            return [
                'success' => false,
                'output' => 'إعدادات SSH غير مكتملة (مفتاح أو مستخدم)',
                'exit_code' => 1,
            ];
        }

        $process->setTimeout($timeout);
        $this->configureSshProcess($process);
        $process->run();

        $result = $this->processResult($process);
        if (! $result['success'] && trim($result['output']) === '' && PHP_OS_FAMILY === 'Windows') {
            $fallback = $this->runSshWindowsShell($host, $command, $timeout, $process);
            if ($fallback !== null) {
                return $fallback;
            }
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, details?: string}
     */
    public function defaultStorageKeyPath(): string
    {
        return storage_path('app/coolify-keys/server.pem');
    }

    /**
     * يضبط مسار المفتاح عند الحفظ: يصلح PEM المكسور، يفضّل storage/app/coolify-keys/server.pem إن فشل C:\temp.
     *
     * @return array{ssh_private_key_path: ?string, ssh_private_key: ?string, notice: ?string}
     */
    public function resolveSettingsKeyForSave(?string $path, ?string $inline): array
    {
        $path = trim((string) $path);
        $inline = trim((string) $inline);

        if ($path !== '' && $this->looksLikePemContent($path)) {
            return [
                'ssh_private_key_path' => $path,
                'ssh_private_key' => $inline !== '' ? $inline : null,
                'notice' => null,
            ];
        }

        if ($inline !== '') {
            $stored = $this->persistPrivateKey($inline, 'server');
            if ($stored === null) {
                return [
                    'ssh_private_key_path' => $path !== '' ? $path : null,
                    'ssh_private_key' => $inline,
                    'notice' => null,
                ];
            }

            return [
                'ssh_private_key_path' => $stored,
                'ssh_private_key' => '',
                'notice' => 'تم حفظ المفتاح في: '.$stored,
            ];
        }

        if ($path === '') {
            $fallback = $this->defaultStorageKeyPath();
            if ($this->keyFilePassesSshKeygen($fallback)) {
                return [
                    'ssh_private_key_path' => $fallback,
                    'ssh_private_key' => '',
                    'notice' => 'تم استخدام المفتاح الافتراضي: '.$fallback,
                ];
            }

            return ['ssh_private_key_path' => null, 'ssh_private_key' => '', 'notice' => null];
        }

        $resolved = $this->resolveKeyFilePath($path);
        if ($resolved !== '' && $this->keyFilePassesSshKeygen($resolved)) {
            return [
                'ssh_private_key_path' => $resolved,
                'ssh_private_key' => '',
                'notice' => $resolved !== realpath($path) ? 'تم إصلاح تنسيق المفتاح تلقائياً: '.$resolved : null,
            ];
        }

        $fallback = $this->defaultStorageKeyPath();
        if ($this->keyFilePassesSshKeygen($fallback)) {
            return [
                'ssh_private_key_path' => $fallback,
                'ssh_private_key' => '',
                'notice' => 'ملف المفتاح «'.$path.'» غير صالح (invalid format). تم التبديل إلى: '.$fallback,
            ];
        }

        return [
            'ssh_private_key_path' => $path,
            'ssh_private_key' => '',
            'notice' => null,
        ];
    }

    public function testConnection(string $host, ?string $inlinePrivateKey = null, ?string $overrideKeyPath = null): array
    {
        $host = trim($host);
        if ($host === '') {
            return ['success' => false, 'message' => 'أدخل IP السيرفر'];
        }

        $local = $this->diagnoseLocalSshClient();
        if (! ($local['ok'] ?? false)) {
            return ['success' => false, 'message' => $local['message'] ?? 'SSH غير متاح'];
        }

        $config = $this->settings->getSshConfig();
        $path = $this->resolveEffectiveKeyPath($config, $overrideKeyPath);
        if ($path !== '') {
            $fileCheck = $this->diagnoseKeyFile($path);
            if (! ($fileCheck['ok'] ?? false)) {
                return ['success' => false, 'message' => $fileCheck['message'] ?? 'ملف المفتاح غير متاح'];
            }

            $this->hardenKeyFilePermissions($path);

            $result = $this->runWithKeyFile($host, 'echo coolify-ssh-ok', 30, $path);
            $result['command'] = ($result['command'] ?? '').' [key: '.$path.']';
        } else {
            $keyCheck = $this->diagnosePrivateKey($inlinePrivateKey);
            if (! ($keyCheck['ok'] ?? false)) {
                return ['success' => false, 'message' => $keyCheck['message'] ?? 'مفتاح غير صالح'];
            }

            $result = $this->runWithOptionalKey($host, 'echo coolify-ssh-ok', 30, $inlinePrivateKey);
        }

        if ($result['success'] && str_contains($result['output'], 'coolify-ssh-ok')) {
            return ['success' => true, 'message' => 'تم الاتصال SSH بنجاح'];
        }

        $message = $result['output'] !== '' ? $result['output'] : $this->describeSshFailure($result['exit_code']);

        if (str_contains(strtolower($message), 'permission denied')) {
            $message .= "\n\nتلميح: تأكد أن المفتاح العام (public key) مضاف في السيرفر "
                .'(/root/.ssh/authorized_keys) — نفس المفتاح المسجّل في Coolify → Servers → SSH.';
        }

        if (str_contains(strtolower($message), 'too open') || str_contains(strtolower($message), 'bad permissions')) {
            $hintPath = $path !== '' ? $path : ($config['ssh_private_key_path'] ?? 'C:\\temp\\coolify-key.pem');
            $message .= "\n\nتلميح (Windows): نفّذ في PowerShell:\n"
                .'icacls '.escapeshellarg($hintPath).' /inheritance:r /grant:r "%USERNAME%:R"';
        }

        if (str_contains(strtolower($message), 'invalid format')) {
            $message .= "\n\nالمفتاح مكسور عند النسخ. استخدم المسار:\n".$this->defaultStorageKeyPath()
                ."\nأو نفّذ: php artisan coolify:fix-ssh";
        }

        if (str_contains(strtolower($message), 'permission denied (publickey')) {
            $hintPath = $path !== '' ? $path : ($config['ssh_private_key_path'] ?? '');
            $message .= "\n\nالمفتاح موجود محلياً لكن السيرفر يرفضه. من PowerShell:\n"
                .'ssh-keygen -y -f '.($hintPath !== '' ? $hintPath : 'مسار-المفتاح.pem')."\n"
                .'ثم أضف السطر الناتج إلى /root/.ssh/authorized_keys على السيرفر.';
        }

        return [
            'success' => false,
            'message' => $message,
            'details' => $result['command'] ?? null,
            'diagnostics' => $this->getSshDiagnostics($path !== '' ? $path : null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSshDiagnostics(?string $keyPath = null): array
    {
        $bin = $this->sshBinary();
        $keyPath = $keyPath ?? $this->defaultStorageKeyPath();

        return [
            'php_sapi' => PHP_SAPI,
            'php_user' => function_exists('get_current_user') ? get_current_user() : null,
            'proc_open' => function_exists('proc_open'),
            'ssh_binary' => $bin,
            'ssh_binary_exists' => $bin !== 'ssh' && is_file($bin),
            'key_path' => $keyPath,
            'key_readable' => is_file($keyPath) && is_readable($keyPath),
            'keygen_ok' => is_file($keyPath) && $this->keyFilePassesSshKeygen($keyPath),
            'path_env' => substr((string) getenv('PATH'), 0, 200),
        ];
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, command?: string}
     */
    protected function runWithOptionalKey(string $host, string $command, int $timeout, ?string $inlinePrivateKey): array
    {
        $tempKey = null;
        if ($inlinePrivateKey !== null && trim($inlinePrivateKey) !== '') {
            $tempKey = $this->writeTempKey($inlinePrivateKey);
            if ($tempKey === null) {
                return [
                    'success' => false,
                    'output' => 'تعذّر حفظ المفتاح (صلاحيات). استخدم مسار ملف فقط: C:\\temp\\coolify-key.pem واترك لصق PEM فارغاً.',
                    'exit_code' => 1,
                ];
            }
        }

        $process = $this->buildSshProcess($host, $command, $tempKey);
        if ($process === null) {
            if ($tempKey) {
                @unlink($tempKey);
            }

            return [
                'success' => false,
                'output' => 'إعدادات SSH غير مكتملة',
                'exit_code' => 1,
            ];
        }

        $process->setTimeout($timeout);
        $process->run();
        $result = $this->processResult($process);
        $result['command'] = $this->maskCommandLine($process->getCommandLine());

        if ($tempKey) {
            @unlink($tempKey);
        }

        return $result;
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    protected function diagnoseLocalSshClient(): array
    {
        if (! function_exists('proc_open')) {
            return [
                'ok' => false,
                'message' => 'دالة proc_open معطّلة في php.ini — Laravel لا يستطيع تشغيل ssh. أزل proc_open من disable_functions.',
            ];
        }

        $bin = $this->sshBinary();
        if ($bin !== 'ssh' && is_file($bin)) {
            return ['ok' => true];
        }

        $probe = new Process([$bin, '-V']);
        $probe->setTimeout(10);
        $this->configureSshProcess($probe);
        $probe->run();
        $out = trim($probe->getOutput().$probe->getErrorOutput());

        if ($probe->isSuccessful() || str_contains(strtolower($out), 'openssh')) {
            return ['ok' => true];
        }

        return [
            'ok' => false,
            'message' => 'برنامج SSH غير موجود في PATH لـ PHP. على Windows: فعّل «OpenSSH Client» من Optional Features، '
                .'أو أعد تشغيل Laragon/XAMPP بعد التثبيت. المسار المتوقع: C:\\Windows\\System32\\OpenSSH\\ssh.exe',
        ];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    protected function diagnosePrivateKey(?string $inlineKey): array
    {
        $config = $this->settings->getSshConfig();
        $key = trim((string) ($inlineKey ?? $config['ssh_private_key'] ?? ''));
        $path = trim((string) ($config['ssh_private_key_path'] ?? ''));

        if ($this->looksLikePemContent($path)) {
            return [
                'ok' => false,
                'message' => 'وضعت محتوى المفتاح في حقل «مسار مفتاح SSH» بالخطأ. افرغ ذلك الحقل والصق PEM في «لصق المفتاح»، أو احفظه في ملف مثل C:\\temp\\coolify-key.pem وضع المسار فقط.',
            ];
        }

        if ($key === '' && ($path === '' || ! is_file($path))) {
            return ['ok' => false, 'message' => 'لا يوجد مفتاح SSH. الصق PEM في «لصق المفتاح» واحفظ، أو ضع مسار ملف .pem صالح.'];
        }

        if ($key !== '') {
            if (! str_contains($key, 'PRIVATE KEY')) {
                return ['ok' => false, 'message' => 'المفتاح الملصوق لا يبدو PEM صالحاً (يجب أن يحتوي PRIVATE KEY).'];
            }

            return ['ok' => true];
        }

        return is_file($path) ? ['ok' => true] : ['ok' => false, 'message' => 'ملف المفتاح غير موجود: '.$path];
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function processResult(Process $process): array
    {
        $output = trim($process->getOutput()."\n".$process->getErrorOutput());
        $exitCode = $process->getExitCode() ?? 1;

        if ($output === '' && ! $process->isSuccessful()) {
            $output = $this->describeSshFailure($exitCode);
        }

        return [
            'success' => $process->isSuccessful(),
            'output' => $output,
            'exit_code' => $exitCode,
        ];
    }

    protected function describeSshFailure(int $exitCode): string
    {
        $port = $this->settings->getSshPort();

        return 'فشل SSH (exit '.$exitCode.'). تحقق من: (1) IP السيرفر، (2) المفتاح مطابق لـ authorized_keys على السيرفر، '
            .'(3) المنفذ '.$port.' مفتوح، (4) PHP يرى OpenSSH (راجع التفاصيل بعد الحفظ).';
    }

    protected function normalizeSshPort(int $port): int
    {
        if ($port <= 0 || $port > 65535) {
            return 22;
        }

        return $port;
    }

    protected function isInvalidSshHost(string $host): bool
    {
        $host = strtolower(trim($host));

        return $host === ''
            || $host === 'unknown'
            || str_contains($host, ' ');
    }

    protected function sshBinary(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:\\Windows\\System32\\OpenSSH\\ssh.exe',
                (getenv('SystemRoot') ?: 'C:\\Windows').'\\System32\\OpenSSH\\ssh.exe',
            ] as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }

            return 'C:\\Windows\\System32\\OpenSSH\\ssh.exe';
        }

        return 'ssh';
    }

    protected function buildSshProcess(string $host, string $remoteCommand, ?string $overrideKeyPath = null, ?int $portOverride = null): ?Process
    {
        $config = $this->settings->getSshConfig();
        $user = $config['ssh_user'] ?: 'root';
        $host = trim($host);

        if ($host === '' || $this->isInvalidSshHost($host)) {
            return null;
        }

        $keyPath = $overrideKeyPath ?? $this->resolveKeyPath($config);
        if ($keyPath === null) {
            return null;
        }

        $runKey = $this->materializeKeyForSsh($keyPath) ?? $keyPath;
        $port = $this->normalizeSshPort($portOverride ?? $this->settings->getSshPort());

        return new Process([
            $this->sshBinary(),
            '-i', $runKey,
            '-p', (string) $port,
            '-o', 'BatchMode=yes',
            '-o', 'ConnectTimeout=20',
            '-o', 'ConnectionAttempts=1',
            '-o', 'IdentitiesOnly=yes',
            '-o', 'PreferredAuthentications=publickey',
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=NUL',
            $user.'@'.$host,
            $remoteCommand,
        ]);
    }

    protected function configureSshProcess(Process $process): void
    {
        $env = $this->sshProcessEnvironment();
        if (is_array($env) && $env !== []) {
            $process->setEnv($env);
        }
    }

    /**
     * @return array<string, string>|null null = inherit full process environment (recommended).
     */
    protected function sshProcessEnvironment(): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $env = [];
        $sources = [$_ENV, $_SERVER];
        if (function_exists('getenv')) {
            $all = getenv();
            if (is_array($all)) {
                $sources[] = $all;
            }
        }

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }
            foreach ($source as $name => $value) {
                if (is_string($name) && is_string($value) && $value !== '') {
                    $env[$name] = $value;
                }
            }
        }

        if ($env === []) {
            return null;
        }

        $systemRoot = $env['SYSTEMROOT'] ?? 'C:\\Windows';
        $path = $env['PATH'] ?? '';
        $openSsh = $systemRoot.'\\System32\\OpenSSH';
        if (! str_contains(strtolower($path), 'openssh')) {
            $path = $openSsh.';'.$systemRoot.'\\System32;'.$path;
        }
        $env['PATH'] = $path;
        $env['SYSTEMROOT'] = $systemRoot;

        if (! isset($env['USERPROFILE']) && isset($env['HOME'])) {
            $env['USERPROFILE'] = $env['HOME'];
        }

        return $env;
    }

    protected function materializeKeyForSsh(string $sourcePath): ?string
    {
        if (! is_readable($sourcePath)) {
            return null;
        }

        $content = @file_get_contents($sourcePath);
        if ($content === false || ! str_contains($content, 'PRIVATE KEY')) {
            return null;
        }

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'claudhosting-coolify-keys';
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            $dir = storage_path('app'.DIRECTORY_SEPARATOR.'coolify-keys');
        }

        $dest = $dir.DIRECTORY_SEPARATOR.'runtime-'.substr(md5($content), 0, 16).'.pem';
        if (! is_file($dest) || @md5_file($dest) !== md5($content)) {
            if (@file_put_contents($dest, $content) === false) {
                return $sourcePath;
            }
        }

        $this->hardenKeyFilePermissions($dest);

        return $dest;
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, command?: string}|null
     */
    protected function runSshWindowsShell(string $host, string $command, int $timeout, Process $failed): ?array
    {
        $cmdLine = $failed->getCommandLine();
        if (! is_string($cmdLine) || $cmdLine === '') {
            return null;
        }

        $shell = getenv('COMSPEC') ?: 'C:\\Windows\\System32\\cmd.exe';
        $process = Process::fromShellCommandline($cmdLine, null, $this->sshProcessEnvironment());
        $process->setTimeout($timeout);
        $process->run();

        $result = $this->processResult($process);
        $result['command'] = $this->maskCommandLine($cmdLine).' [shell-fallback]';

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function resolveKeyPathForTest(string $path): string
    {
        return $this->resolveKeyFilePath($path);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveEffectiveKeyPath(array $config, ?string $overrideKeyPath): string
    {
        $candidates = array_filter([
            $this->resolveKeyFilePath($config['ssh_private_key_path'] ?? ''),
            $this->resolveKeyFilePath($this->defaultStorageKeyPath()),
            $overrideKeyPath !== null ? $this->resolveKeyFilePath(trim($overrideKeyPath)) : '',
        ]);

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate !== '' && $this->keyFilePassesSshKeygen($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0] ?? '';
    }

    protected function resolveKeyFilePath(mixed $path): string
    {
        $path = trim((string) $path);
        if ($path === '' || $this->looksLikePemContent($path)) {
            return '';
        }

        $real = realpath($path);
        if ($real === false || ! is_file($real)) {
            return '';
        }

        $normalized = $this->normalizePemFileOnDisk($real);

        return $normalized !== '' ? $normalized : $real;
    }

    /**
     * يصلح مفاتيح OpenSSH المكسورة عند النسخ من المتصفح (أسطر base64 مكررة/مقسومة خطأ).
     */
    protected function normalizePemFileOnDisk(string $path): string
    {
        $raw = @file_get_contents($path);
        if ($raw === false || ! str_contains($raw, 'BEGIN OPENSSH PRIVATE KEY')) {
            return '';
        }

        if (! preg_match('/-----BEGIN OPENSSH PRIVATE KEY-----\s*([\s\S]*?)\s*-----END OPENSSH PRIVATE KEY-----/', $raw, $m)) {
            return '';
        }

        $b64 = preg_replace('/\s+/', '', $m[1] ?? '');
        if ($b64 === '' || strlen($b64) < 100) {
            return '';
        }

        $lines = ['-----BEGIN OPENSSH PRIVATE KEY-----'];
        for ($i = 0; $i < strlen($b64); $i += 70) {
            $lines[] = substr($b64, $i, 70);
        }
        $lines[] = '-----END OPENSSH PRIVATE KEY-----';
        $fixed = implode("\n", $lines)."\n";

        $check = new Process(['ssh-keygen', '-y', '-f', $path]);
        $check->setTimeout(10);
        $check->run();
        if ($check->isSuccessful()) {
            return '';
        }

        $out = $this->persistPrivateKey($fixed, 'normalized_'.md5($path));
        if ($out === null) {
            return '';
        }

        return $out;
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    protected function diagnoseKeyFile(string $path): array
    {
        if (! is_file($path)) {
            return ['ok' => false, 'message' => 'ملف المفتاح غير موجود: '.$path];
        }

        if (! is_readable($path)) {
            return [
                'ok' => false,
                'message' => 'PHP لا يقرأ الملف (صلاحيات). انسخ المفتاح إلى: '
                    .storage_path('app/coolify-keys/server.pem').' وحدّث المسار.',
            ];
        }

        $head = @file_get_contents($path, false, null, 0, 80);
        if ($head === false || ! str_contains($head, 'PRIVATE KEY')) {
            return ['ok' => false, 'message' => 'الملف لا يبدو مفتاح PEM صالحاً: '.$path];
        }

        $perm = $this->ensureWindowsKeyPermissions($path);
        if (! ($perm['ok'] ?? true)) {
            return ['ok' => false, 'message' => $perm['message'] ?? 'صلاحيات الملف'];
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    protected function ensureWindowsKeyPermissions(string $path): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['ok' => true];
        }

        $this->hardenKeyFilePermissions($path);

        return ['ok' => true, 'message' => 'تم ضبط صلاحيات المفتاح. أعد اختبار SSH.'];
    }

    protected function resolveKeyPath(array $config): ?string
    {
        $path = $this->resolveKeyFilePath($config['ssh_private_key_path'] ?? '');
        if ($path !== '') {
            return $path;
        }

        if (! empty($config['ssh_private_key'])) {
            return $this->persistPrivateKey($config['ssh_private_key']);
        }

        return null;
    }

    protected function writeTempKey(string $pem): ?string
    {
        return $this->persistPrivateKey($pem, 'test_'.Str::random(12));
    }

    /**
     * @return array<int, string>
     */
    protected function keyDirectoryCandidates(): array
    {
        $cache = trim((string) config('coolify.defaults.ssh_key_cache_path', 'coolify-keys'));

        return array_values(array_unique(array_filter([
            storage_path('app/'.$cache),
            sys_get_temp_dir().DIRECTORY_SEPARATOR.'claudhosting-coolify-keys',
        ])));
    }

    protected function persistPrivateKey(string $pem, ?string $basename = null): ?string
    {
        $basename = $basename ?? 'ssh_key_'.md5($pem);
        $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $basename) ?: 'ssh_key';
        $content = $this->normalizePrivateKey($pem);

        foreach ($this->keyDirectoryCandidates() as $dir) {
            try {
                if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
                    continue;
                }

                $path = $dir.DIRECTORY_SEPARATOR.$basename.'.pem';
                if (@file_put_contents($path, $content) === false) {
                    continue;
                }

                $this->hardenKeyFilePermissions($path);

                return $path;
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    protected function normalizePrivateKey(string $pem): string
    {
        $pem = str_replace(["\r\n", "\r"], "\n", trim($pem));

        return $pem."\n";
    }

    protected function looksLikePemContent(string $value): bool
    {
        return str_contains($value, '-----BEGIN') && str_contains($value, 'PRIVATE KEY');
    }

    public function keyFilePassesSshKeygen(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || ! is_file($path)) {
            return false;
        }

        $resolved = $this->resolveKeyFilePath($path);
        $checkPath = $resolved !== '' ? $resolved : $path;

        $keygen = PHP_OS_FAMILY === 'Windows'
            ? (is_file('C:\\Windows\\System32\\OpenSSH\\ssh-keygen.exe') ? 'C:\\Windows\\System32\\OpenSSH\\ssh-keygen.exe' : 'ssh-keygen')
            : 'ssh-keygen';

        $check = new Process([$keygen, '-y', '-f', $checkPath]);
        $check->setTimeout(15);
        $this->configureSshProcess($check);
        $check->run();

        return $check->isSuccessful();
    }

    protected function hardenKeyFilePermissions(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $q = escapeshellarg($path);
            @shell_exec('icacls '.$q.' /inheritance:r 2>nul');
            @shell_exec('icacls '.$q.' /grant:r "%USERNAME%:(R)" 2>nul');
            @shell_exec('icacls '.$q.' /grant:r "SYSTEM:(R)" 2>nul');
            @shell_exec('icacls '.$q.' /remove:g "BUILTIN\\Users" 2>nul');
            @shell_exec('icacls '.$q.' /remove:g "Everyone" 2>nul');
            @shell_exec('icacls '.$q.' /remove:g "BUILTIN\\Authenticated Users" 2>nul');
        } else {
            @chmod($path, 0600);
        }
    }

    protected function maskCommandLine(mixed $commandLine): string
    {
        $line = is_array($commandLine) ? implode(' ', $commandLine) : (string) $commandLine;

        return preg_replace('/-i\s+\S+/', '-i [key]', $line) ?? $line;
    }

    /**
     * @return array{success: bool, output: string, remote_path: string}
     */
    public function backupVolumeToTemp(string $host, string $volumeName, string $tag): array
    {
        $remotePath = $this->remoteTempArchivePath($tag, $volumeName);
        $dir = dirname(str_replace("'", "'\\''", $remotePath));
        $archiveFile = basename(str_replace("'", "'\\''", $remotePath));
        $volume = str_replace("'", "'\\''", $volumeName);

        $cmd = sprintf(
            "docker run --rm -v '%s':/data -v '%s':/backup alpine tar czf /backup/%s -C /data . 2>&1",
            $volume,
            $dir,
            $archiveFile
        );

        $result = $this->run($host, $cmd, 3600);

        return array_merge($result, ['remote_path' => $remotePath]);
    }

    public function restoreVolume(string $host, string $volumeName, string $remoteArchivePath, bool $stopBefore = true): array
    {
        $volume = str_replace("'", "'\\''", $volumeName);
        $dir = dirname(str_replace("'", "'\\''", $remoteArchivePath));
        $archiveFile = basename(str_replace("'", "'\\''", $remoteArchivePath));

        $stop = $stopBefore
            ? "docker ps -q --filter volume={$volume} | xargs -r docker stop 2>/dev/null; "
            : '';

        $cmd = sprintf(
            "%sdocker run --rm -v '%s':/data -v '%s':/backup alpine sh -c 'cd /data && rm -rf ./* 2>/dev/null; tar xzf /backup/%s -C /data' 2>&1",
            $stop,
            $volume,
            $dir,
            $archiveFile
        );

        return $this->run($host, $cmd, 3600);
    }

    public function removeRemoteFile(string $host, string $remotePath): array
    {
        $escaped = str_replace("'", "'\\''", $remotePath);

        return $this->run($host, "rm -f '{$escaped}' 2>&1", 60);
    }

    /**
     * @return array{success: bool, output: string}
     */
    public function downloadFile(string $host, string $remotePath, string $localPath): array
    {
        $scp = $this->buildScpCommand($host, $remotePath, $localPath, false);
        if ($scp === null) {
            return ['success' => false, 'output' => 'إعدادات SSH غير مكتملة'];
        }

        File::ensureDirectoryExists(dirname($localPath));
        $process = Process::fromShellCommandline($scp, null, null, null, 3600);
        $process->run();

        return [
            'success' => $process->isSuccessful() && is_file($localPath),
            'output' => trim($process->getOutput()."\n".$process->getErrorOutput()),
        ];
    }

    /**
     * @return array{success: bool, output: string}
     */
    public function uploadFile(string $host, string $localPath, string $remotePath): array
    {
        if (! is_file($localPath)) {
            return ['success' => false, 'output' => 'الملف المحلي غير موجود'];
        }

        $scp = $this->buildScpCommand($host, $remotePath, $localPath, true);
        if ($scp === null) {
            return ['success' => false, 'output' => 'إعدادات SSH غير مكتملة'];
        }

        $dir = dirname(str_replace("'", "'\\''", $remotePath));
        $this->run($host, "mkdir -p '{$dir}'", 30);

        $process = Process::fromShellCommandline($scp, null, null, null, 3600);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => trim($process->getOutput()."\n".$process->getErrorOutput()),
        ];
    }

    public function remoteTempArchivePath(string $tag, string $volumeName): string
    {
        $safeTag = Str::slug($tag);
        $safeVol = preg_replace('/[^a-zA-Z0-9._-]/', '_', $volumeName) ?: 'volume';

        return '/tmp/coolify-'.$safeTag.'-'.$safeVol.'.tar.gz';
    }

    protected function buildScpCommand(string $host, string $remotePath, string $localPath, bool $upload): ?string
    {
        $config = $this->settings->getSshConfig();
        $user = $config['ssh_user'] ?: 'root';
        $host = trim($host);

        if ($host === '') {
            return null;
        }

        $keyPath = $this->resolveKeyPath($config);
        if ($keyPath === null) {
            return null;
        }

        $port = $this->settings->getSshPort();
        $bin = $this->sshBinary();
        $scpBin = str_ends_with(strtolower($bin), 'ssh.exe')
            ? preg_replace('/ssh\.exe$/i', 'scp.exe', $bin)
            : 'scp';

        $keyArg = '-i '.escapeshellarg($keyPath);
        $portArg = '-P '.(int) $port;
        $opts = '-o BatchMode=yes -o ConnectTimeout=20 -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new';
        $remote = escapeshellarg($user.'@'.$host.':'.$remotePath);
        $local = escapeshellarg($localPath);

        if ($upload) {
            return sprintf('%s %s %s %s %s %s', escapeshellarg($scpBin), $keyArg, $portArg, $opts, $local, $remote);
        }

        return sprintf('%s %s %s %s %s %s', escapeshellarg($scpBin), $keyArg, $portArg, $opts, $remote, $local);
    }
}
