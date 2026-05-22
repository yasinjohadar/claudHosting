<?php

namespace App\Services\Coolify;

use App\Jobs\RunWordpressManagementJob;
use App\Models\CoolifyActivityLog;
use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Str;

class WordpressManagementService
{
    public const ASYNC_ACTIONS = [
        'refresh_info',
        'diagnose',
        'core_update',
        'core_reinstall',
        'plugin_update_all',
        'theme_update_all',
        'docker_compose_pull',
        'bootstrap_mcp',
    ];

    public const ALLOWED_ACTIONS = [
        'refresh_info',
        'core_update',
        'core_reinstall',
        'core_update_db',
        'core_check_update',
        'cache_flush',
        'rewrite_flush',
        'maintenance_activate',
        'maintenance_deactivate',
        'plugin_update_all',
        'plugin_update',
        'theme_update_all',
        'user_reset_password',
        'docker_compose_pull',
        'redis_apply_env',
        'diagnose',
        'bootstrap_mcp',
    ];

    public function __construct(
        protected WordpressCliService $cli,
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected WordpressSiteProvisioningService $provisioning,
        protected WordpressMcpBootstrapService $mcpBootstrap
    ) {}

    /**
     * @return array{ui_ready: bool, ssh_ready: bool, execute_ready: bool, message: string}
     */
    public function getManagementState(CoolifyWordpressSite $site): array
    {
        $sshReady = (bool) ($this->settings->getSshConfig()['ssh_key_configured'] ?? false);
        $uiReady = $this->canShowManagementUi($site);
        $message = '';

        if (! filled($site->service_uuid)) {
            $message = 'لا توجد خدمة Coolify مرتبطة.';
        } elseif (! $uiReady) {
            $message = in_array($site->status, ['failed'], true)
                ? 'الموقع فاشل — أعد المحاولة أو راجع Coolify.'
                : 'انتظر تشغيل حاويات wordpress و mariadb على Coolify';
        } elseif (! $sshReady) {
            $message = 'اضبط مفتاح SSH في إعدادات Coolify لتنفيذ التحديثات';
        } elseif ($this->coolify->requiresSshHostFallback() && $this->coolify->listServerSshHostCandidates((string) $site->server_uuid, $this->extraSshHostCandidates($site)) === []) {
            $message = 'اضبط IP السيرفر في إعدادات Coolify (حقل عنوان SSH) — لا تستخدم نطاق لوحة Coolify';
        } else {
            $message = 'جاهز للإدارة عبر WP-CLI';
        }

        $hostConfigured = ! $this->coolify->requiresSshHostFallback()
            || $this->coolify->listServerSshHostCandidates((string) $site->server_uuid, $this->extraSshHostCandidates($site)) !== [];

        return [
            'ui_ready' => $uiReady,
            'ssh_ready' => $sshReady && $hostConfigured,
            'execute_ready' => $uiReady && $sshReady && $hostConfigured,
            'message' => $message,
            'ssh_host_required' => $sshReady && ! $hostConfigured,
        ];
    }

    /**
     * @return array<int, array{host: string, source: string, priority: int}>
     */
    protected function extraSshHostCandidates(CoolifyWordpressSite $site): array
    {
        $extra = [];
        $origin = trim((string) (($site->metadata ?? [])['cloudflare']['origin'] ?? ''));
        if ($origin !== '' && filter_var($origin, FILTER_VALIDATE_IP) !== false) {
            $extra[] = ['host' => $origin, 'source' => 'cloudflare_dns_origin', 'priority' => 5];
        }

        return $extra;
    }

    public function canShowManagementUi(CoolifyWordpressSite $site): bool
    {
        if (! filled($site->service_uuid)) {
            return false;
        }

        if ($site->status === 'running') {
            return true;
        }

        return $this->isStackOperational($site);
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function canManage(CoolifyWordpressSite $site): array
    {
        $state = $this->getManagementState($site);

        if (! $state['execute_ready']) {
            return ['ok' => false, 'message' => $state['message']];
        }

        return ['ok' => true, 'message' => $state['message']];
    }

    public function isStackOperational(CoolifyWordpressSite $site): bool
    {
        $metadata = $site->metadata ?? [];
        if (! empty($metadata['coolify_stack_healthy'])) {
            return true;
        }

        $components = $metadata['coolify_components'] ?? [];
        if ($components !== []) {
            return $this->componentsLookHealthy($components);
        }

        if (! $this->coolify->isConfigured() || ! filled($site->service_uuid)) {
            return false;
        }

        $response = $this->coolify->getService($site->service_uuid);
        if (! ($response['success'] ?? false)) {
            return false;
        }

        $service = $response['data'] ?? [];

        return is_array($service) && $this->coolify->isServiceStackHealthy($service);
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    protected function componentsLookHealthy(array $components): bool
    {
        $dbOk = false;
        $appOk = false;

        foreach ($components as $component) {
            $ok = $this->coolify->isComponentStatusRunning((string) ($component['status'] ?? ''));
            if (($component['role'] ?? '') === 'database') {
                $dbOk = $dbOk || $ok;
            }
            if (($component['role'] ?? '') === 'application') {
                $appOk = $appOk || $ok;
            }
        }

        return $dbOk && $appOk;
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, message?: string, job_id?: string, async?: bool}
     */
    public function getSiteInfo(CoolifyWordpressSite $site, bool $refresh = false): array
    {
        $check = $this->canManage($site);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير متاح'];
        }

        $metadata = $site->metadata ?? [];
        if (! $refresh && ! empty($metadata['wp_info']) && ! empty($metadata['wp_info_fetched_at'])) {
            $cachedVersion = trim((string) ($metadata['wp_info']['core_version'] ?? ''));
            $fetched = strtotime((string) $metadata['wp_info_fetched_at']);
            if ($cachedVersion !== '' && $fetched && (time() - $fetched) < 120) {
                return ['success' => true, 'data' => $metadata['wp_info']];
            }
        }

        @set_time_limit(300);

        $resolved = $this->cli->getResolver()->resolve($site);
        $containerMeta = [
            'id' => $resolved['container_id'] ?? ($metadata['wp_container_id'] ?? null),
            'name' => $resolved['container_name'] ?? ($metadata['wp_container_name'] ?? null),
            'image' => $resolved['image'] ?? ($metadata['wp_container_image'] ?? null),
        ];

        $coreVersion = $this->cli->run($site, 'core version', 45);
        if (! ($coreVersion['success'] ?? false)) {
            return [
                'success' => false,
                'message' => Str::limit(trim($coreVersion['output'] ?? $coreVersion['message'] ?? 'فشل WP-CLI'), 500)
                    .' — جرّب «تشخيص الاتصال».',
            ];
        }

        $coreUpdate = $this->cli->run($site, 'core check-update --format=json', 45);
        $plugins = $this->cli->run($site, 'plugin list --format=json', 45);
        $themes = $this->cli->run($site, 'theme list --format=json', 45);
        $users = $this->cli->run($site, 'user list --format=json', 45);
        $cliInfo = $this->cli->run($site, 'cli info --format=json', 45);

        $info = [
            'core_version' => trim($coreVersion['output'] ?? ''),
            'core_updates' => $this->parseJsonLines($coreUpdate['output'] ?? ''),
            'plugins' => $this->parseJsonLines($plugins['output'] ?? ''),
            'themes' => $this->parseJsonLines($themes['output'] ?? ''),
            'users' => $this->parseJsonLines($users['output'] ?? ''),
            'cli' => $this->parseJsonObject($cliInfo['output'] ?? ''),
            'container' => $containerMeta,
            'maintenance' => $this->detectMaintenance($site),
            'fetched_at' => now()->toIso8601String(),
        ];

        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_info' => $info,
                'wp_info_fetched_at' => now()->toIso8601String(),
            ]),
        ]);

        return ['success' => true, 'data' => $info];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, output?: string, job_id?: string, async?: bool, data?: array<string, mixed>}
     */
    public function executeAction(CoolifyWordpressSite $site, string $action, array $params = [], ?int $userId = null): array
    {
        if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
            return ['success' => false, 'message' => 'إجراء غير مسموح'];
        }

        $check = $this->canManage($site);
        $skipManageCheck = in_array($action, ['refresh_info', 'diagnose'], true);
        if (! $skipManageCheck && ! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير متاح'];
        }

        if ($action === 'diagnose') {
            $state = $this->getManagementState($site);
            if (! $state['ssh_ready']) {
                return ['success' => false, 'message' => $state['message']];
            }
        }

        if (in_array($action, self::ASYNC_ACTIONS, true)) {
            return $this->dispatchAsync($site, $action, $params, $userId);
        }

        return $this->runSyncAction($site, $action, $params, $userId);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function runSyncAction(CoolifyWordpressSite $site, string $action, array $params = [], ?int $userId = null): array
    {
        @set_time_limit($action === 'refresh_info' ? 300 : 120);

        $output = '';
        $success = true;
        $extra = [];

        switch ($action) {
            case 'diagnose':
                $output = $this->cli->diagnose($site);

                return [
                    'success' => true,
                    'message' => 'تقرير التشخيص',
                    'output' => $output,
                ];

            case 'refresh_info':
                $info = $this->getSiteInfo($site, true);

                return [
                    'success' => $info['success'] ?? false,
                    'message' => $info['message'] ?? 'تم تحديث المعلومات',
                    'data' => $info['data'] ?? null,
                ];

            case 'core_check_update':
                $result = $this->cli->run($site, 'core check-update', 120);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'core_update_db':
                $result = $this->cli->run($site, 'core update-db', 120);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'core_reinstall':
                $r1 = $this->cli->run($site, 'core update --force', 600);
                $output = $r1['output'] ?? '';
                $success = $r1['success'] ?? false;
                if (! $success) {
                    $r2 = $this->cli->run($site, 'core download --force --skip-content', 600);
                    $output = trim($output."\n".($r2['output'] ?? ''));
                    $success = $r2['success'] ?? false;
                }
                if ($success) {
                    $r3 = $this->cli->run($site, 'core update-db', 120);
                    $output = trim($output."\n".($r3['output'] ?? ''));
                    $success = ($r3['success'] ?? false) && $success;
                }
                break;

            case 'core_update':
                $r1 = $this->cli->run($site, 'core update --version=latest', 600);
                if (! ($r1['success'] ?? false)) {
                    $r1 = $this->cli->run($site, 'core update', 600);
                }
                $r2 = $this->cli->run($site, 'core update-db', 120);
                $output = trim(($r1['output'] ?? '')."\n".($r2['output'] ?? ''));
                $success = ($r1['success'] ?? false) && ($r2['success'] ?? false);
                break;

            case 'cache_flush':
                $result = $this->cli->run($site, 'cache flush', 60);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'rewrite_flush':
                $result = $this->cli->run($site, 'rewrite flush', 60);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'maintenance_activate':
                $result = $this->cli->run($site, 'maintenance-mode activate', 60);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'maintenance_deactivate':
                $result = $this->cli->run($site, 'maintenance-mode deactivate', 60);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'plugin_update_all':
                $result = $this->cli->run($site, 'plugin update --all', 900);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'plugin_update':
                $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($params['slug'] ?? ''));
                if ($slug === '') {
                    return ['success' => false, 'message' => 'معرّف الإضافة مطلوب'];
                }
                $result = $this->cli->run($site, 'plugin update '.$slug, 300);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'theme_update_all':
                $result = $this->cli->run($site, 'theme update --all', 600);
                $output = $result['output'];
                $success = $result['success'];
                break;

            case 'user_reset_password':
                $login = preg_replace('/[^a-z0-9_@.\-]/i', '', (string) ($params['login'] ?? ''));
                if ($login === '') {
                    return ['success' => false, 'message' => 'اسم المستخدم مطلوب'];
                }
                $password = (string) ($params['password'] ?? '');
                if ($password === '') {
                    $password = Str::password(16, symbols: true);
                }
                $escaped = escapeshellarg($password);
                $result = $this->cli->run($site, 'user update '.$login.' --user_pass='.$escaped, 60);
                $output = $result['output'];
                $success = $result['success'];
                if ($success) {
                    $extra['generated_password'] = $password;
                    $extra['login'] = $login;
                }
                break;

            case 'docker_compose_pull':
                $result = $this->pullDockerCompose($site);
                $output = $result['output'] ?? '';
                $success = $result['success'] ?? false;
                if ($success) {
                    $this->provisioning->syncSiteFromCoolify($site->fresh());
                    $this->getSiteInfo($site->fresh(), true);
                }
                break;

            case 'redis_apply_env':
                $result = $this->applyRedisEnv($site);
                return $result;

            case 'bootstrap_mcp':
                $boot = $this->mcpBootstrap->bootstrap($site);
                $output = $boot['output'] ?? '';
                $success = $boot['success'] ?? false;
                $this->appendLog($site, $action, $success ? 'نجح' : 'فشل', $output);
                $this->recordActivity($site, $action, $success, $userId);

                return array_merge([
                    'success' => $success,
                    'message' => $boot['message'] ?? ($success ? 'تم تركيب MCP' : 'فشل التركيب'),
                    'output' => $output,
                    'data' => $boot['data'] ?? null,
                ]);

            default:
                return ['success' => false, 'message' => 'إجراء غير مدعوم'];
        }

        $this->appendLog($site, $action, $success ? 'نجح' : 'فشل', $output);
        $this->recordActivity($site, $action, $success, $userId);

        if ($success) {
            $this->getSiteInfo($site->fresh(), true);
        }

        $failMessage = 'فشل تنفيذ الإجراء';
        if (! $success && trim($output) !== '') {
            $failMessage = Str::limit(trim($output), 400);
        }

        return array_merge([
            'success' => $success,
            'message' => $success ? 'تم تنفيذ الإجراء' : $failMessage,
            'output' => $output,
        ], $extra);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, job_id?: string, async: bool, message?: string}
     */
    protected function dispatchAsync(CoolifyWordpressSite $site, string $action, array $params, ?int $userId): array
    {
        $metadata = $site->metadata ?? [];
        if (($metadata['wp_job']['status'] ?? '') === 'running') {
            return ['success' => false, 'message' => 'يوجد عملية قيد التنفيذ بالفعل', 'async' => true];
        }

        $jobId = (string) Str::uuid();
        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_job' => [
                    'id' => $jobId,
                    'action' => $action,
                    'params' => $params,
                    'status' => 'running',
                    'output' => '',
                    'user_id' => $userId,
                    'started_at' => now()->toIso8601String(),
                    'finished_at' => null,
                ],
            ]),
        ]);

        RunWordpressManagementJob::dispatch($site->id, $action, $params, $jobId, $userId);

        return [
            'success' => true,
            'async' => true,
            'job_id' => $jobId,
            'message' => 'تم إرسال العملية إلى الطابور',
        ];
    }

    /**
     * @return array{success: bool, output: string, message?: string}
     */
    public function pullDockerCompose(CoolifyWordpressSite $site): array
    {
        if (! filled($site->service_uuid)) {
            return ['success' => false, 'output' => '', 'message' => 'معرّف الخدمة غير موجود'];
        }

        $uuid = preg_replace('/[^a-zA-Z0-9_-]/', '', $site->service_uuid);
        $paths = [
            '/data/coolify/services/'.$uuid,
            '/var/lib/coolify/services/'.$uuid,
        ];

        $commands = [];
        foreach ($paths as $path) {
            $commands[] = sprintf(
                'if [ -d %s ]; then cd %s && docker compose pull && docker compose up -d; fi',
                escapeshellarg($path),
                escapeshellarg($path)
            );
        }

        $command = implode(' ; ', $commands).' ; echo done';

        return $this->cli->runOnHost($site, $command, 900);
    }

    /**
     * @return array{success: bool, message: string, output?: string}
     */
    public function applyRedisEnv(CoolifyWordpressSite $site): array
    {
        if (! $this->settings->getWordpressRedisEnabled()) {
            return ['success' => false, 'message' => 'Redis معطّل في الإعدادات العامة'];
        }

        $host = $this->settings->getWordpressRedisHost();
        $port = $this->settings->getWordpressRedisPort();

        if ($host === '') {
            return ['success' => false, 'message' => 'اضبط عنوان Redis في إعدادات Coolify'];
        }

        if (! filled($site->service_uuid)) {
            return ['success' => false, 'message' => 'لا توجد خدمة Coolify'];
        }

        $envs = $this->coolify->normalizeList($this->coolify->listServiceEnvs($site->service_uuid)['data'] ?? []);
        $toSet = [
            'WP_REDIS_HOST' => $host,
            'WP_REDIS_PORT' => (string) $port,
        ];

        $output = [];
        foreach ($toSet as $key => $value) {
            $existing = null;
            foreach ($envs as $env) {
                if (! is_array($env)) {
                    continue;
                }
                if (($env['key'] ?? '') === $key) {
                    $existing = $env;
                    break;
                }
            }

            if ($existing !== null && filled($existing['uuid'] ?? null)) {
                $response = $this->coolify->updateServiceEnv(
                    $site->service_uuid,
                    (string) $existing['uuid'],
                    ['key' => $key, 'value' => $value]
                );
            } else {
                $response = $this->coolify->createServiceEnv($site->service_uuid, [
                    'key' => $key,
                    'value' => $value,
                    'is_preview' => false,
                    'is_literal' => true,
                ]);
            }

            $output[] = $key.': '.($response['success'] ? 'ok' : ($response['message'] ?? 'fail'));
        }

        $this->appendLog($site, 'redis_apply_env', 'تم', implode("\n", $output));

        return [
            'success' => true,
            'message' => 'تم تحديث متغيرات Redis على Coolify. ثبّت إضافة Redis Object Cache من لوحة WP.',
            'output' => implode("\n", $output),
        ];
    }

    public function appendLog(CoolifyWordpressSite $site, string $action, string $status, string $output = ''): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        $log = $metadata['wp_management_log'] ?? [];
        $log[] = [
            'at' => now()->toIso8601String(),
            'action' => $action,
            'status' => $status,
            'output' => Str::limit($output, 8000),
        ];
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        $site->update(['metadata' => array_merge($metadata, ['wp_management_log' => $log])]);
    }

    protected function recordActivity(CoolifyWordpressSite $site, string $action, bool $success, ?int $userId): void
    {
        CoolifyActivityLog::record(
            'wordpress_'.$action,
            'wordpress_site',
            $site->uuid,
            $site->display_name,
            $success ? 'نجح: '.$action : 'فشل: '.$action,
            ['user_id' => $userId]
        );
    }

    protected function detectMaintenance(CoolifyWordpressSite $site): bool
    {
        $result = $this->cli->run($site, 'maintenance-mode status', 30);

        return str_contains(strtolower($result['output'] ?? ''), 'active');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseJsonLines(string $output): array
    {
        $output = trim($output);
        if ($output === '') {
            return [];
        }

        $decoded = json_decode($output, true);
        if (is_array($decoded)) {
            return isset($decoded[0]) ? $decoded : [$decoded];
        }

        $items = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (is_array($row)) {
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseJsonObject(string $output): ?array
    {
        $output = trim($output);
        if ($output === '') {
            return null;
        }
        $decoded = json_decode($output, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{success: bool, job?: array<string, mixed>|null}
     */
    public function getJobStatus(CoolifyWordpressSite $site): array
    {
        $job = ($site->metadata ?? [])['wp_job'] ?? null;

        return ['success' => true, 'job' => is_array($job) ? $job : null];
    }
}
