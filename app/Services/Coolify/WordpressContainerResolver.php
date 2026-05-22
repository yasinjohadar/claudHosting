<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Str;

class WordpressContainerResolver
{
    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySshExecutor $ssh,
        protected CoolifySettingsService $settings
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     host?: string,
     *     container_id?: string,
     *     container_name?: string,
     *     image?: string,
     *     message?: string
     * }
     */
    public function resolve(CoolifyWordpressSite $site, bool $forceRefresh = false): array
    {
        if (! $this->settings->getSshConfig()['ssh_key_configured']) {
            return [
                'success' => false,
                'message' => 'إعدادات SSH غير مكتملة. اضبط المفتاح في إعدادات Coolify.',
            ];
        }

        if (! filled($site->server_uuid)) {
            return ['success' => false, 'message' => 'السيرفر غير محدد للموقع.'];
        }

        if (! filled($site->service_uuid)) {
            return ['success' => false, 'message' => 'الخدمة غير منشأة على Coolify بعد.'];
        }

        $metadata = $site->metadata ?? [];
        $cachedHost = trim((string) ($metadata['wp_ssh_host'] ?? ''));
        if (! $forceRefresh && ! empty($metadata['wp_container_id']) && $cachedHost !== '' && ! $this->coolify->isUnusableSshHost($cachedHost)) {
            $verify = $this->verifyContainer($cachedHost, $metadata['wp_container_id']);
            if ($verify) {
                return [
                    'success' => true,
                    'host' => $cachedHost,
                    'container_id' => $metadata['wp_container_id'],
                    'container_name' => $metadata['wp_container_name'] ?? '',
                    'image' => $metadata['wp_container_image'] ?? '',
                ];
            }
        }

        $ssh = $this->resolveWorkingSshHost((string) $site->server_uuid, $site);
        if (! ($ssh['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $ssh['message'] ?? 'تعذّر الاتصال SSH بالسيرفر.',
            ];
        }

        $host = $ssh['host'];

        $hints = $this->collectNameHints($site);
        $container = $this->findViaCoolifyCompose($host, $site);
        if ($container === null) {
            $container = $this->findWordpressContainer($host, $hints);
        }

        if ($container === null) {
            return [
                'success' => false,
                'message' => 'لم تُعثر على حاوية WordPress قيد التشغيل على السيرفر.',
            ];
        }

        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_ssh_host' => $host,
                'wp_container_id' => $container['id'],
                'wp_container_name' => $container['name'],
                'wp_container_image' => $container['image'],
                'wp_resolved_at' => now()->toIso8601String(),
            ]),
        ]);

        return [
            'success' => true,
            'host' => $host,
            'container_id' => $container['id'],
            'container_name' => $container['name'],
            'image' => $container['image'],
        ];
    }

    /**
     * يختبر SSH فعلياً على كل مرشّح حتى ينجح اتصال.
     *
     * @return array{success: bool, host?: string, source?: string, message?: string, tried?: array<int, string>, last_ssh_error?: string}
     */
    /**
     * @return array<int, array{host: string, source: string, priority: int}>
     */
    protected function extraSshHostsForSite(CoolifyWordpressSite $site): array
    {
        $extra = [];
        $origin = trim((string) (($site->metadata ?? [])['cloudflare']['origin'] ?? ''));
        if ($origin !== '' && filter_var($origin, FILTER_VALIDATE_IP) !== false) {
            $extra[] = ['host' => $origin, 'source' => 'cloudflare_dns_origin', 'priority' => 5];
        }

        return $extra;
    }

    protected function resolveWorkingSshHost(string $serverUuid, CoolifyWordpressSite $site): array
    {
        $metadata = $site->metadata ?? [];
        $cachedHost = trim((string) ($metadata['wp_ssh_host'] ?? ''));
        if ($cachedHost !== '' && ! $this->coolify->isUnusableSshHost($cachedHost)) {
            $test = $this->ssh->testConnection($cachedHost);
            if ($test['success'] ?? false) {
                return [
                    'success' => true,
                    'host' => $cachedHost,
                    'source' => 'cached_site',
                ];
            }
        }

        $candidates = $this->coolify->listServerSshHostCandidates($serverUuid, $this->extraSshHostsForSite($site));
        if ($candidates === []) {
            $desc = $this->coolify->describeServerConnection($serverUuid);

            return [
                'success' => false,
                'message' => 'لا يوجد IP للـ SSH. Coolify يعرض IP السيرفر كـ «'.($desc['raw_ip'] ?: '—').'». '
                    .'افتح إعدادات Coolify → SSH → «عنوان SSH للسيرفر» وضع IP الـ VPS (مثال من لوحة الاستضافة: 82.x.x.x). '
                    .'لا تستخدم coolify.claudsoft.com.',
            ];
        }

        $tried = [];
        $lastError = '';

        foreach ($candidates as $candidate) {
            $host = $candidate['host'];
            $label = $host.' ('.$candidate['source'].')';
            $tried[] = $label;

            $test = $this->ssh->testConnection($host);
            if ($test['success'] ?? false) {
                return [
                    'success' => true,
                    'host' => $host,
                    'source' => $candidate['source'],
                ];
            }

            $lastError = trim((string) ($test['message'] ?? ''));
        }

        $apiNote = '';
        foreach ($candidates as $candidate) {
            if (($candidate['source'] ?? '') === 'api_url' && ! filter_var($candidate['host'], FILTER_VALIDATE_IP)) {
                $apiNote = ' نطاق لوحة Coolify ('.$candidate['host'].') لا يقبل SSH عادةً — استخدم IP السيرفر في الإعدادات.';
                break;
            }
        }

        return [
            'success' => false,
            'message' => 'فشل SSH لكل العناوين: '.implode(' → ', $tried).'. آخر خطأ: '
                .($lastError !== '' ? $lastError : 'غير معروف').'.'.$apiNote,
            'tried' => $tried,
            'last_ssh_error' => $lastError,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function collectNameHints(CoolifyWordpressSite $site): array
    {
        $hints = array_filter([
            $site->slug,
            Str::slug($site->display_name, '-'),
        ]);

        if (filled($site->service_uuid)) {
            $response = $this->coolify->getService($site->service_uuid);
            if ($response['success'] ?? false) {
                $service = $response['data'] ?? [];
                if (is_array($service)) {
                    foreach ($this->coolify->normalizeList($service['applications'] ?? []) as $app) {
                        if (! is_array($app)) {
                            continue;
                        }
                        foreach (['name', 'uuid'] as $key) {
                            $v = trim((string) ($app[$key] ?? ''));
                            if ($v !== '') {
                                $hints[] = $v;
                            }
                        }
                    }
                    $hints[] = (string) ($service['name'] ?? '');
                    $hints[] = (string) ($service['uuid'] ?? '');
                }
            }
        }

        return array_values(array_unique(array_filter($hints)));
    }

    /**
     * @param  array<int, string>  $hints
     * @return array{id: string, name: string, image: string}|null
     */
    public function discoveryDebugReport(CoolifyWordpressSite $site): string
    {
        $lines = [];
        $lines[] = '=== SSH candidates (probe) ===';
        $serverDesc = $this->coolify->describeServerConnection((string) $site->server_uuid);
        $lines[] = 'Coolify server name: '.($serverDesc['name'] ?: '—');
        $lines[] = 'Coolify API ip field: '.($serverDesc['raw_ip'] ?: '—');
        if ($this->coolify->isUnusableSshHost($serverDesc['raw_ip'])) {
            $lines[] = '⚠ هذا IP غير صالح لـ SSH من Windows — استخدم IP الـ VPS في الإعدادات.';
        }
        $cfOrigin = trim((string) (($site->metadata ?? [])['cloudflare']['origin'] ?? ''));
        if ($cfOrigin !== '') {
            $lines[] = 'Cloudflare origin (metadata): '.$cfOrigin;
        }
        $lines[] = '';

        $candidates = $this->coolify->listServerSshHostCandidates((string) $site->server_uuid, $this->extraSshHostsForSite($site));
        $fallback = $this->settings->getSshHostFallback();
        $lines[] = 'settings fallback: '.($fallback !== '' ? $fallback : '(مطلوب — IP الـ VPS وليس coolify.claudsoft.com)');
        $lines[] = 'ssh port: '.$this->settings->getSshPort();
        $lines[] = 'key configured: '.($this->settings->getSshConfig()['ssh_key_configured'] ? 'yes' : 'NO');
        if ($candidates === []) {
            $lines[] = '';
            $lines[] = 'لا يوجد مرشّح SSH. احفظ IP في: لوحة التحكم → إعدادات Coolify → عنوان SSH للسيرفر';
            $lines[] = 'ثم «اختبار SSH» بنفس الـ IP قبل تشخيص الموقع.';

            return implode("\n", $lines);
        }
        $lines[] = '';

        foreach ($candidates as $candidate) {
            $test = $this->ssh->testConnection($candidate['host']);
            $status = ($test['success'] ?? false) ? 'OK' : 'FAIL';
            $lines[] = sprintf('[%s] %s (%s) — %s', $status, $candidate['host'], $candidate['source'], $test['message'] ?? '');
        }

        $ssh = $this->resolveWorkingSshHost((string) $site->server_uuid, $site);
        $lines[] = '';
        $lines[] = '=== Selected SSH host ===';
        $lines[] = 'resolved: '.(($ssh['success'] ?? false) ? ($ssh['host'] ?? '—') : 'FAIL');
        $lines[] = 'source: '.($ssh['source'] ?? '—');
        if (! ($ssh['success'] ?? false)) {
            $lines[] = 'hint: '.($ssh['message'] ?? '');

            return implode("\n", $lines);
        }
        $lines[] = '';
        $host = $ssh['host'] ?? '';

        $lines[] = 'Host: '.$host;
        $lines[] = 'service_uuid: '.($site->service_uuid ?? '—');
        $lines[] = '';

        foreach ([
            'docker ps --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}"',
            'docker ps 2>&1 | head -n 25',
        ] as $cmd) {
            $result = $this->ssh->run($host, $cmd, 45);
            $lines[] = '$ '.$cmd;
            if ($result['success'] ?? false) {
                $lines[] = $result['output'] !== '' ? $result['output'] : '(empty output)';
            } else {
                $lines[] = 'FAIL (exit '.($result['exit_code'] ?? '?').'): '.($result['output'] !== '' ? $result['output'] : 'no output — تحقق من المفتاح والـ IP');
            }
            $lines[] = '';
        }

        $ping = $this->ssh->run($host, 'echo ssh-ok && uname -a', 20);
        $lines[] = 'SSH echo: '.(($ping['success'] ?? false) ? ($ping['output'] ?: 'ok') : 'FAIL — '.$ping['output']);
        $lines[] = '';

        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $site->service_uuid);
        foreach ([
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
            '/root/coolify/services/'.$uuid,
        ] as $dir) {
            if ($uuid === '') {
                break;
            }
            $check = $this->ssh->run($host, 'test -d '.escapeshellarg($dir).' && echo yes || echo no', 15);
            $lines[] = 'dir '.$dir.': '.trim($check['output'] ?? '');
            if (! str_contains($check['output'] ?? '', 'yes')) {
                continue;
            }
            $ps = $this->ssh->run($host, 'cd '.escapeshellarg($dir).' && docker compose ps 2>&1', 45);
            $lines[] = $ps['output'] ?? '';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function findViaCoolifyCompose(string $host, CoolifyWordpressSite $site): ?array
    {
        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $site->service_uuid);
        if ($uuid === '') {
            return null;
        }

        foreach ([
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
            '/data/coolify/applications/'.$uuid,
        ] as $dir) {
            $yes = $this->ssh->run($host, 'test -f '.escapeshellarg($dir.'/docker-compose.yml').' && echo yes', 15);
            if (! str_contains($yes['output'] ?? '', 'yes')) {
                continue;
            }

            $svcList = $this->ssh->run($host, 'cd '.escapeshellarg($dir).' && docker compose config --services 2>/dev/null', 30);
            $services = array_values(array_filter(array_map('trim', explode("\n", $svcList['output'] ?? ''))));

            if ($services === []) {
                $services = ['wordpress'];
            }

            usort($services, function (string $a, string $b) {
                $score = fn (string $s) => str_contains(strtolower($s), 'wordpress') ? 0 : 1;

                return $score($a) <=> $score($b);
            });

            foreach ($services as $serviceName) {
                $lower = strtolower($serviceName);
                if (str_contains($lower, 'mariadb') || str_contains($lower, 'mysql') || str_contains($lower, 'redis') || str_contains($lower, 'mongo')) {
                    continue;
                }

                $ps = $this->ssh->run(
                    $host,
                    'cd '.escapeshellarg($dir).' && docker compose ps -q '.escapeshellarg($serviceName).' 2>/dev/null',
                    30
                );
                foreach (preg_split('/\s+/', trim($ps['output'] ?? '')) as $id) {
                    $id = trim($id);
                    if ($id === '' || ! $this->verifyContainer($host, $id)) {
                        continue;
                    }
                    $meta = $this->inspectContainerMeta($host, $id);

                    return [
                        'id' => $id,
                        'name' => $meta['name'] ?: $serviceName,
                        'image' => $meta['image'] ?: 'wordpress',
                    ];
                }
            }

            $all = $this->ssh->run($host, 'cd '.escapeshellarg($dir).' && docker compose ps -q 2>/dev/null', 30);
            foreach (preg_split('/\s+/', trim($all['output'] ?? '')) as $id) {
                $id = trim($id);
                if ($id === '' || ! $this->verifyContainer($host, $id)) {
                    continue;
                }
                $meta = $this->inspectContainerMeta($host, $id);
                $name = strtolower($meta['name']);
                $image = strtolower($meta['image']);
                if ($this->isDatabaseContainer($name, $image)) {
                    continue;
                }

                return [
                    'id' => $id,
                    'name' => $meta['name'],
                    'image' => $meta['image'],
                ];
            }
        }

        return null;
    }

    /**
     * @return array{name: string, image: string}
     */
    protected function inspectContainerMeta(string $host, string $id): array
    {
        $format = '{{.Name}}|{{.Config.Image}}';
        $result = $this->ssh->run($host, 'docker inspect -f '.escapeshellarg($format).' '.escapeshellarg($id), 20);
        $parts = explode('|', trim($result['output'] ?? ''), 2);
        $name = ltrim($parts[0] ?? '', '/');
        $image = $parts[1] ?? '';

        return ['name' => $name, 'image' => $image];
    }

    protected function findWordpressContainer(string $host, array $hints): ?array
    {
        foreach ([
            'docker ps --format "{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}"',
            'docker ps --format "{{.ID}}\t{{.Names}}\t{{.Image}}"',
            'docker ps',
        ] as $command) {
            $result = $this->ssh->run($host, $command, 60);
            if (! ($result['success'] ?? false)) {
                continue;
            }

            $container = $this->parseDockerPsOutput($result['output'] ?? '', $hints, $host);
            if ($container !== null) {
                return $container;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $hints
     * @return array{id: string, name: string, image: string}|null
     */
    protected function parseDockerPsOutput(string $output, array $hints, string $host): ?array
    {
        $candidates = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'CONTAINER')) {
                continue;
            }

            $parts = str_contains($line, "\t") ? explode("\t", $line) : preg_split('/\s{2,}/', $line);
            if (count($parts) < 3) {
                continue;
            }

            $id = trim($parts[0]);
            if (strlen($id) < 4) {
                continue;
            }
            if (! preg_match('/^[a-f0-9]+$/i', $id)) {
                continue;
            }

            $name = strtolower(trim($parts[1]));
            $image = strtolower(trim($parts[2]));
            $status = strtolower(trim($parts[3] ?? 'running'));

            if ($this->isDatabaseContainer($name, $image)) {
                continue;
            }

            if (! $this->isDockerStatusRunning($status) && ! $this->verifyContainer($host, $id)) {
                continue;
            }

            $score = 0;
            if (str_contains($image, 'wordpress') || str_contains($name, 'wordpress')) {
                $score += 10;
            }
            foreach ($hints as $hint) {
                $hint = strtolower($hint);
                if ($hint !== '' && str_contains($name, $hint)) {
                    $score += 5;
                }
            }

            $candidates[] = [
                'id' => $id,
                'name' => trim($parts[1]),
                'image' => trim($parts[2]),
                'score' => $score,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        $best = $candidates[0];

        return [
            'id' => $best['id'],
            'name' => $best['name'],
            'image' => $best['image'],
        ];
    }

    protected function isDockerStatusRunning(string $status): bool
    {
        $status = strtolower(trim($status));
        if ($status === '') {
            return true;
        }

        foreach (['running', 'healthy', 'started', 'active', 'up'] as $needle) {
            if (str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function isDatabaseContainer(string $name, string $image): bool
    {
        foreach (['mariadb', 'mysql', 'postgres', 'redis', 'mongo'] as $db) {
            if (str_contains($name, $db) || str_contains($image, $db)) {
                return true;
            }
        }

        return false;
    }

    protected function verifyContainer(string $host, string $containerId): bool
    {
        $id = preg_replace('/[^a-f0-9]/i', '', $containerId) ?: $containerId;
        $format = '{{.State.Running}}';
        $result = $this->ssh->run(
            $host,
            'docker inspect -f '.escapeshellarg($format).' '.escapeshellarg($id).' 2>/dev/null',
            30
        );

        return ($result['success'] ?? false) && trim($result['output']) === 'true';
    }
}
