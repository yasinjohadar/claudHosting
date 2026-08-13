<?php

namespace App\Services\Whm\Wordpress;

use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmSshExecutor;

/**
 * Run WP-CLI on cPanel WordPress installs via SSH (as account user).
 */
class WhmWordpressCliService
{
    protected const WP_PHAR = '.wp-cli.phar';

    protected const WP_PHAR_URL = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

    public function __construct(protected WhmSshExecutor $ssh) {}

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function run(WhmWordpressSite $site, string $wpArgs, int $timeout = 300): array
    {
        $path = $this->installPath($site);
        $username = $this->cpanelUser($site);
        if ($path === '' || $username === '') {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'message' => 'مسار التثبيت أو مستخدم cPanel غير متوفر — أعد البحث عن المواقع',
            ];
        }

        $wpArgs = trim($wpArgs);
        if ($wpArgs === '') {
            return ['success' => false, 'output' => '', 'exit_code' => 1, 'message' => 'أمر فارغ'];
        }

        $ensure = $this->ensureWpCliPhar($path, $username);
        if (! ($ensure['success'] ?? false)) {
            return $ensure;
        }

        $remote = $this->buildWpCommand($path, $username, $wpArgs);
        $result = $this->ssh->run($remote, $timeout);

        if (! ($result['success'] ?? false)) {
            $result['message'] = $result['message'] ?? trim($result['output'] ?? 'فشل WP-CLI');
        }

        return $result;
    }

    public function runLong(WhmWordpressSite $site, string $wpArgs, int $timeout = 600): array
    {
        return $this->run($site, $wpArgs, $timeout);
    }

    public function diagnose(WhmWordpressSite $site): string
    {
        $lines = [];
        $lines[] = '=== WHM / cPanel WordPress diagnose ===';
        $lines[] = 'SSH configured: '.($this->ssh->isConfigured() ? 'yes' : 'no');
        $lines[] = 'SSH host: '.$this->ssh->resolveHost();
        $lines[] = 'cPanel user: '.$this->cpanelUser($site);
        $lines[] = 'Install path: '.$this->installPath($site);

        $test = $this->ssh->testConnection();
        $lines[] = 'SSH test: '.(($test['success'] ?? false) ? 'OK' : 'FAIL').' — '.($test['message'] ?? '');

        $ver = $this->run($site, 'core version', 90);
        $lines[] = 'wp core version: '.(($ver['success'] ?? false) ? trim($ver['output']) : ('FAIL: '.($ver['output'] ?: $ver['message'] ?? '')));

        return implode("\n", $lines);
    }

    protected function installPath(WhmWordpressSite $site): string
    {
        return rtrim(trim((string) ($site->path ?? '')), '/');
    }

    protected function cpanelUser(WhmWordpressSite $site): string
    {
        $account = $site->relationLoaded('account') ? $site->account : $site->account()->first();

        return trim((string) ($account?->username ?? ''));
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    protected function ensureWpCliPhar(string $path, string $username): array
    {
        $phar = $path.'/'.self::WP_PHAR;
        $check = $this->ssh->run(
            'if [ -f '.$this->unixArg($phar).' ]; then echo ok; else echo missing; fi',
            30
        );

        if (($check['success'] ?? false) && str_contains($check['output'] ?? '', 'ok')) {
            return ['success' => true, 'output' => '', 'exit_code' => 0];
        }

        $download = sprintf(
            'curl -fsSL %s -o %s && chown %s:%s %s && chmod 755 %s && echo downloaded',
            $this->unixArg(self::WP_PHAR_URL),
            $this->unixArg($phar),
            $this->unixArg($username),
            $this->unixArg($username),
            $this->unixArg($phar),
            $this->unixArg($phar)
        );

        $result = $this->ssh->run($download, 120);
        if (! ($result['success'] ?? false) || ! str_contains($result['output'] ?? '', 'downloaded')) {
            $wget = sprintf(
                'wget -q -O %s %s && chown %s:%s %s && chmod 755 %s && echo downloaded',
                $this->unixArg($phar),
                $this->unixArg(self::WP_PHAR_URL),
                $this->unixArg($username),
                $this->unixArg($username),
                $this->unixArg($phar),
                $this->unixArg($phar)
            );
            $result = $this->ssh->run($wget, 120);
        }

        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'output' => $result['output'] ?? '',
                'exit_code' => 1,
                'message' => 'تعذر تنزيل wp-cli.phar على السيرفر: '.($result['output'] ?? ''),
            ];
        }

        return ['success' => true, 'output' => '', 'exit_code' => 0];
    }

    protected function buildWpCommand(string $path, string $username, string $wpArgs): string
    {
        $phar = $path.'/'.self::WP_PHAR;
        $inner = sprintf(
            'cd %s && (command -v php >/dev/null && php %s --path=%s %s || /usr/local/bin/php %s --path=%s %s)',
            $this->unixArg($path),
            $this->unixArg($phar),
            $this->unixArg($path),
            $wpArgs,
            $this->unixArg($phar),
            $this->unixArg($path),
            $wpArgs
        );

        return sprintf(
            'sudo -u %s -H bash -lc %s',
            $this->unixArg($username),
            $this->unixArg($inner)
        );
    }

    /** Unix-safe shell argument (remote Linux host; not Windows escapeshellarg). */
    protected function unixArg(string $value): string
    {
        return "'".str_replace("'", "'\\''", $value)."'";
    }
}
