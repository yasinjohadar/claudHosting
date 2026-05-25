<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;

class WordpressCliService
{
    protected const WP_PHAR_BASENAME = '.wp-cli.phar';

    protected const WPCLI_IMAGE = 'wpcli/wp-cli:php8.3';

    protected const WPCLI_PHAR_URL = 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

    /** @var array<string, string> */
    protected array $pathCache = [];

    public function __construct(
        protected WordpressContainerResolver $resolver,
        protected CoolifySshExecutor $ssh
    ) {}

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function run(CoolifyWordpressSite $site, string $wpArgs, int $timeout = 300, bool $longRunning = false): array
    {
        $resolved = $this->resolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'message' => $resolved['message'] ?? 'تعذّر تحديد الحاوية',
            ];
        }

        $host = $resolved['host'];
        $containerId = $resolved['container_id'];
        $wpArgs = trim($wpArgs);
        if ($wpArgs === '') {
            return ['success' => false, 'output' => '', 'exit_code' => 1, 'message' => 'أمر فارغ'];
        }

        $path = $this->resolveWordpressPath($site, $host, $containerId);
        $appTimeout = $longRunning ? $timeout : min($timeout, 120);
        $sidecarTimeout = $longRunning ? $timeout : min($timeout, 180);
        $methods = [
            fn () => $this->runViaWpCliSidecar($host, $containerId, $wpArgs, $path, $sidecarTimeout, true),
            fn () => $this->runViaWpCliSidecar($host, $containerId, $wpArgs, $path, $sidecarTimeout, false),
            fn () => $this->runViaDockerExec($host, $containerId, $wpArgs, $path, $appTimeout),
            fn () => $this->runViaComposeLabels($host, $containerId, $wpArgs, $path, $appTimeout),
            fn () => $this->runViaComposePaths($site, $host, $wpArgs, $path, $appTimeout),
        ];

        $last = ['success' => false, 'output' => '', 'exit_code' => 1];
        $attemptLogs = [];
        foreach ($methods as $method) {
            $last = $method();
            if ($last['success'] ?? false) {
                return $last;
            }
            $snippet = trim($last['output'] ?? '');
            if ($snippet !== '') {
                $attemptLogs[] = $snippet;
            }
        }

        if ($attemptLogs !== []) {
            $last['output'] = implode("\n---\n", array_slice($attemptLogs, -3));
        } elseif (trim($last['output'] ?? '') === '') {
            $last['output'] = 'فشل WP-CLI بكل الطرق. اضغط «تشخيص الاتصال» وانسخ التقرير.';
        }

        return $last;
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function runLong(CoolifyWordpressSite $site, string $wpArgs, int $timeout = 600): array
    {
        return $this->run($site, $wpArgs, $timeout, true);
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function composeLifecycle(CoolifyWordpressSite $site, string $operation): array
    {
        $operation = match ($operation) {
            'stop', 'start', 'restart' => $operation,
            default => '',
        };
        if ($operation === '') {
            return ['success' => false, 'output' => '', 'exit_code' => 1, 'message' => 'عملية docker غير صالحة'];
        }

        if (! filled($site->service_uuid)) {
            return ['success' => false, 'output' => '', 'exit_code' => 1, 'message' => 'معرّف الخدمة غير موجود'];
        }

        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', $site->service_uuid);
        $paths = [
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
        ];

        $commands = [];
        foreach ($paths as $path) {
            $commands[] = sprintf(
                'if [ -d %s ]; then cd %s && docker compose %s; fi',
                escapeshellarg($path),
                escapeshellarg($path),
                $operation
            );
        }

        $command = implode(' ; ', $commands).' ; echo done';

        return $this->runOnHost($site, $command, 300);
    }

    public function diagnose(CoolifyWordpressSite $site): string
    {
        @set_time_limit(120);

        $lines = [];
        $resolved = $this->resolver->resolve($site, false);

        if (! ($resolved['success'] ?? false)) {
            return 'تعذّر تحديد الحاوية: '.($resolved['message'] ?? '—')."\n\n".$this->resolver->discoveryDebugReport($site);
        }

        $host = $resolved['host'];
        $containerId = $resolved['container_id'];
        $lines[] = '=== SSH ===';
        $lines[] = 'Host: '.$host;
        $lines[] = 'Container ID: '.$containerId;
        $lines[] = 'Name: '.($resolved['container_name'] ?? '—');
        $lines[] = 'Image: '.($resolved['image'] ?? '—');

        $ping = $this->ssh->run($host, 'echo ssh-ok', 15);
        $lines[] = 'SSH test: '.(($ping['success'] ?? false) ? 'OK' : 'FAIL');
        if (! ($ping['success'] ?? false)) {
            $lines[] = $ping['output'] ?? '';

            return implode("\n", $lines);
        }

        $lines[] = '';
        $lines[] = '=== Docker Compose labels ===';
        $composeDir = $this->inspectComposeWorkingDir($host, $containerId);
        $composeService = $this->inspectComposeServiceName($host, $containerId);
        $lines[] = 'working_dir: '.($composeDir ?: '—');
        $lines[] = 'service: '.($composeService ?: '—');

        $path = $this->resolveWordpressPath($site, $host, $containerId);
        $lines[] = '';
        $lines[] = '=== WordPress path ===';
        $lines[] = 'path: '.$path;
        $cfg = $this->ssh->run($host, 'docker exec '.escapeshellarg($containerId).' test -f '.escapeshellarg($path.'/wp-config.php').' && echo yes', 20);
        $lines[] = 'wp-config.php: '.(str_contains($cfg['output'] ?? '', 'yes') ? 'yes' : 'NO — '.$cfg['output']);

        $lines[] = '';
        $lines[] = '=== WP-CLI attempts ===';

        if ($composeDir !== null) {
            $compose = $this->execCompose($host, $composeDir, $composeService, 'core version', $path, 35);
            $lines[] = '[compose exec] exit='.($compose['exit_code'] ?? '?').' success='.(($compose['success'] ?? false) ? 'yes' : 'no');
            $lines[] = trim($compose['output'] ?? '') ?: '(empty)';
        }

        $exec = $this->runViaDockerExec($host, $containerId, 'core version', $path, 35);
        $lines[] = '[docker exec] exit='.($exec['exit_code'] ?? '?').' success='.(($exec['success'] ?? false) ? 'yes' : 'no');
        $lines[] = trim($exec['output'] ?? '') ?: '(empty)';

        $sidecar = $this->runViaWpCliSidecar($host, $containerId, 'core version', $path, 90, true);
        $lines[] = '[sidecar wpcli +network] exit='.($sidecar['exit_code'] ?? '?').' success='.(($sidecar['success'] ?? false) ? 'yes' : 'no');
        $lines[] = trim($sidecar['output'] ?? '') ?: '(empty)';
        if (! ($sidecar['success'] ?? false)) {
            $sidecar2 = $this->runViaWpCliSidecar($host, $containerId, 'core version', $path, 90, false);
            $lines[] = '[sidecar wpcli] exit='.($sidecar2['exit_code'] ?? '?').' success='.(($sidecar2['success'] ?? false) ? 'yes' : 'no');
            $lines[] = trim($sidecar2['output'] ?? '') ?: '(empty)';
        }

        $lines[] = '';
        $lines[] = '=== PHP / phar في الحاوية ===';
        $phpCheck = $this->ssh->run($host, 'docker exec '.escapeshellarg($containerId).' sh -c '.escapeshellarg('command -v php; php -v 2>&1 | head -1'), 25);
        $lines[] = trim($phpCheck['output'] ?? '') ?: '(no php)';
        $pharPath = $this->wpPharPath($path);
        $pharCheck = $this->ssh->run($host, 'docker exec '.escapeshellarg($containerId).' test -f '.escapeshellarg($pharPath).' && echo phar-exists', 15);
        $lines[] = 'phar '.$pharPath.': '.(str_contains($pharCheck['output'] ?? '', 'phar-exists') ? 'yes' : 'no');

        return implode("\n", $lines);
    }

    public function getResolver(): WordpressContainerResolver
    {
        return $this->resolver;
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function runViaComposeLabels(string $host, string $containerId, string $wpArgs, string $path, int $timeout): array
    {
        $dir = $this->inspectComposeWorkingDir($host, $containerId);
        if ($dir === null) {
            return ['success' => false, 'output' => '', 'exit_code' => 1];
        }

        $service = $this->inspectComposeServiceName($host, $containerId) ?? $this->pickComposeWordpressService(
            $this->listComposeServices($host, $dir)
        );

        return $this->execCompose($host, $dir, $service, $wpArgs, $path, $timeout);
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function runViaComposePaths(CoolifyWordpressSite $site, string $host, string $wpArgs, string $path, int $timeout): array
    {
        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $site->service_uuid);
        if ($uuid === '') {
            return ['success' => false, 'output' => '', 'exit_code' => 1];
        }

        foreach ([
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
            '/data/coolify/applications/'.$uuid,
        ] as $dir) {
            $check = $this->ssh->run($host, 'test -f '.escapeshellarg($dir.'/docker-compose.yml').' && echo yes', 15);
            if (! str_contains($check['output'] ?? '', 'yes')) {
                continue;
            }

            $service = $this->pickComposeWordpressService($this->listComposeServices($host, $dir));
            $result = $this->execCompose($host, $dir, $service, $wpArgs, $path, $timeout);
            if ($result['success'] ?? false) {
                return $result;
            }
        }

        return ['success' => false, 'output' => '', 'exit_code' => 1];
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function runViaWpCliSidecar(string $host, string $containerId, string $wpArgs, string $path, int $timeout, bool $shareNetwork = true): array
    {
        $this->ensureWpCliImage($host);

        $inner = $this->wpExecInnerCommandSidecar($wpArgs, $path);
        $network = $shareNetwork
            ? sprintf('--network container:%s ', escapeshellarg($containerId))
            : '';

        $cmd = sprintf(
            'docker run --rm %s--volumes-from %s %s sh -c %s',
            $network,
            escapeshellarg($containerId),
            self::WPCLI_IMAGE,
            escapeshellarg($inner)
        );

        return $this->ssh->run($host, $cmd, $timeout);
    }

    protected function ensureWpCliImage(string $host): void
    {
        $check = $this->ssh->run($host, 'docker image inspect '.self::WPCLI_IMAGE.' >/dev/null 2>&1', 20);
        if ($check['success'] ?? false) {
            return;
        }

        $this->ssh->run($host, 'docker pull '.self::WPCLI_IMAGE, 300);
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function runViaDockerExec(string $host, string $containerId, string $wpArgs, string $path, int $timeout): array
    {
        $inner = $this->wpExecInnerCommandAppContainer($wpArgs, $path);
        $last = ['success' => false, 'output' => '', 'exit_code' => 1];

        foreach (['-u root ', ''] as $userFlag) {
            $remote = sprintf(
                'docker exec %s%s sh -c %s',
                $userFlag,
                escapeshellarg($containerId),
                escapeshellarg($inner)
            );
            $last = $this->ssh->run($host, $remote, $timeout);
            if ($last['success'] ?? false) {
                return $last;
            }
        }

        return $last;
    }

    /**
     * داخل حاوية WordPress: لا نستخدم أمر wp (غالباً غير موجود) — فقط php + phar.
     */
    protected function wpExecInnerCommandAppContainer(string $wpArgs, string $path): string
    {
        $flags = $this->wpFlags($path, true);
        $phar = $this->wpPharPath($path);
        $url = self::WPCLI_PHAR_URL;

        return sprintf(
            'PHAR=%s; PHP=$(command -v php 2>/dev/null || command -v php83 2>/dev/null || command -v php8 2>/dev/null || echo php); run() { "$PHP" "$PHAR" %s %s; }; if [ ! -f "$PHAR" ]; then (curl -fsSL -o "$PHAR" %s || wget -qO "$PHAR" %s); fi; run',
            escapeshellarg($phar),
            $flags,
            $wpArgs,
            escapeshellarg($url),
            escapeshellarg($url)
        );
    }

    /**
     * حاوية wpcli/wp-cli — الأمر wp مضمّن.
     */
    protected function wpExecInnerCommandSidecar(string $wpArgs, string $path): string
    {
        $flags = $this->wpFlags($path, true);

        return "wp {$flags} {$wpArgs}";
    }

    protected function wpPharPath(string $wordpressPath): string
    {
        return rtrim($wordpressPath, '/').'/'.self::WP_PHAR_BASENAME;
    }

    /**
     * @return array{success: bool, output: string, exit_code: int}
     */
    protected function execCompose(string $host, string $dir, ?string $serviceName, string $wpArgs, string $path, int $timeout): array
    {
        if ($serviceName === null || $serviceName === '') {
            return ['success' => false, 'output' => 'لم يُعثر على اسم خدمة wordpress في compose', 'exit_code' => 1];
        }

        $dirQ = escapeshellarg($dir);
        $svc = escapeshellarg($serviceName);
        $inner = $this->wpExecInnerCommandAppContainer($wpArgs, $path);

        $last = ['success' => false, 'output' => '', 'exit_code' => 1];
        foreach ([
            "cd {$dirQ} && docker compose exec -T {$svc} sh -c ".escapeshellarg($inner),
            "cd {$dirQ} && docker compose exec -T -u www-data {$svc} sh -c ".escapeshellarg($inner),
        ] as $cmd) {
            $last = $this->ssh->run($host, $cmd, $timeout);
            if ($last['success'] ?? false) {
                return $last;
            }
        }

        return $last;
    }

    protected function wpFlags(string $path, bool $allowRoot): string
    {
        $safePath = str_replace("'", "'\\''", $path);
        $flags = "--path='{$safePath}' --skip-plugins --skip-themes";
        if ($allowRoot) {
            $flags .= ' --allow-root';
        }

        return $flags;
    }

    protected function inspectComposeWorkingDir(string $host, string $containerId): ?string
    {
        $format = '{{index .Config.Labels "com.docker.compose.project.working_dir"}}';
        $cmd = 'docker inspect -f '.escapeshellarg($format).' '.escapeshellarg($containerId);
        $result = $this->ssh->run($host, $cmd, 20);
        $dir = trim($result['output'] ?? '');
        if ($dir !== '' && $dir !== '<no value>' && ! str_starts_with($dir, 'template:')) {
            return $dir;
        }

        return null;
    }

    protected function inspectComposeServiceName(string $host, string $containerId): ?string
    {
        $format = '{{index .Config.Labels "com.docker.compose.service"}}';
        $cmd = 'docker inspect -f '.escapeshellarg($format).' '.escapeshellarg($containerId);
        $result = $this->ssh->run($host, $cmd, 20);
        $name = trim($result['output'] ?? '');
        if ($name !== '' && $name !== '<no value>' && ! str_starts_with($name, 'template:')) {
            return $name;
        }

        return null;
    }

    protected function listComposeServices(string $host, string $dir): string
    {
        $dirQ = escapeshellarg($dir);
        $result = $this->ssh->run($host, "cd {$dirQ} && docker compose config --services 2>&1", 30);

        return $result['output'] ?? '';
    }

    protected function pickComposeWordpressService(string $output): string
    {
        $services = array_filter(array_map('trim', explode("\n", $output)));
        foreach ($services as $name) {
            if (str_contains(strtolower($name), 'wordpress')) {
                return $name;
            }
        }

        foreach ($services as $name) {
            $lower = strtolower($name);
            if (! str_contains($lower, 'mariadb') && ! str_contains($lower, 'mysql') && ! str_contains($lower, 'redis')) {
                return $name;
            }
        }

        return $services[0] ?? '';
    }

    public function resolveWordpressPathForSite(CoolifyWordpressSite $site, string $host, string $containerId): string
    {
        return $this->resolveWordpressPath($site, $host, $containerId);
    }

    protected function resolveWordpressPath(CoolifyWordpressSite $site, string $host, string $containerId): string
    {
        $cacheKey = $site->id.'|'.$containerId;
        if (isset($this->pathCache[$cacheKey])) {
            return $this->pathCache[$cacheKey];
        }

        $metadata = $site->metadata ?? [];
        if (! empty($metadata['wp_install_path'])) {
            return $this->pathCache[$cacheKey] = (string) $metadata['wp_install_path'];
        }

        foreach (['/var/www/html', '/app', '/var/www/html/wordpress'] as $candidate) {
            $check = sprintf(
                'docker exec %s test -f %s/wp-config.php',
                escapeshellarg($containerId),
                escapeshellarg($candidate)
            );
            if ($this->ssh->run($host, $check, 20)['success'] ?? false) {
                $site->update(['metadata' => array_merge($metadata, ['wp_install_path' => $candidate])]);

                return $this->pathCache[$cacheKey] = $candidate;
            }
        }

        return $this->pathCache[$cacheKey] = '/var/www/html';
    }

    /**
     * @return array{success: bool, output: string, exit_code: int, message?: string}
     */
    public function runOnHost(CoolifyWordpressSite $site, string $command, int $timeout = 600): array
    {
        $resolved = $this->resolver->resolve($site);
        if (! ($resolved['success'] ?? false)) {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'message' => $resolved['message'] ?? 'تعذّر تحديد السيرفر',
            ];
        }

        $result = $this->ssh->run($resolved['host'], $command, $timeout);

        return [
            'success' => $result['success'] ?? false,
            'output' => $result['output'] ?? '',
            'exit_code' => $result['exit_code'] ?? 1,
        ];
    }
}
