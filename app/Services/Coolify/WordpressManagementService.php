<?php

namespace App\Services\Coolify;

use App\Jobs\RunWordpressManagementJob;
use App\Models\CoolifyActivityLog;
use App\Models\CoolifyWordpressSite;
use App\Services\CoolifyApiService;
use Illuminate\Support\Str;

class WordpressManagementService
{
    public function __construct(
        protected WordpressCliService $cli,
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected WordpressSiteProvisioningService $provisioning,
        protected WordpressMcpBootstrapService $mcpBootstrap,
        protected WordpressCliActionRunner $actionRunner
    ) {}

    /**
     * @return list<string>
     */
    public static function allowedActions(): array
    {
        return app(WordpressCliActionRunner::class)->allowedActionNames();
    }

    /**
     * @return list<string>
     */
    public static function asyncActions(): array
    {
        return app(WordpressCliActionRunner::class)->asyncActionNames();
    }

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

        $coreVersion = $this->cli->run($site, 'core version', 120);
        if (! ($coreVersion['success'] ?? false)) {
            $hint = trim($coreVersion['output'] ?? $coreVersion['message'] ?? 'فشل WP-CLI');
            if (str_contains(strtolower($hint), 'wp: not found') || str_contains(strtolower($hint), 'not found')) {
                $hint = 'WP-CLI غير مثبت في الحاوية — سيتم استخدام wp-cli.phar تلقائياً؛ إن استمر الخطأ: '.$hint;
            }

            return [
                'success' => false,
                'message' => Str::limit($hint, 500).' — جرّب «تشخيص الاتصال».',
            ];
        }

        $coreUpdate = $this->cli->run($site, 'core check-update --format=json', 90);
        $plugins = $this->cli->run(
            $site,
            'plugin list --format=json --fields=name,status,version,update,update_version',
            120
        );
        $themes = $this->cli->run(
            $site,
            'theme list --format=json --fields=name,status,version,update,update_version',
            120
        );
        $users = $this->cli->run($site, 'user list --format=json', 90);
        $cliInfo = $this->cli->run($site, 'cli info --format=json', 90);

        $pluginList = $this->parseJsonLines($plugins['output'] ?? '');
        $themeList = $this->parseJsonLines($themes['output'] ?? '');

        $info = [
            'core_version' => trim($coreVersion['output'] ?? ''),
            'core_updates' => $this->parseJsonLines($coreUpdate['output'] ?? ''),
            'plugins' => $pluginList,
            'themes' => $themeList,
            'plugins_updates_count' => $this->countAvailableUpdates($pluginList),
            'themes_updates_count' => $this->countAvailableUpdates($themeList),
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
        if (! $this->actionRunner->isAllowed($action)) {
            return ['success' => false, 'message' => 'إجراء غير مسموح'];
        }

        if ($action === 'raw_cli') {
            $validation = $this->actionRunner->validateRawCommand(
                (string) ($params['command'] ?? ''),
                (bool) ($params['confirm_dangerous'] ?? false)
            );
            if (! ($validation['success'] ?? false)) {
                return ['success' => false, 'message' => $validation['message'] ?? 'أمر غير صالح'];
            }
        }

        $def = $this->actionRunner->definition($action);
        if (! empty($def['confirm']) && empty($params['_confirmed'])) {
            // UI handles confirm; optional server-side skip when _confirmed=1
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

        if ($this->actionRunner->isAsync($action)) {
            return $this->dispatchAsync($site, $action, $params, $userId);
        }

        $this->clearWpJobRecord($site);

        return $this->runSyncAction($site, $action, $params, $userId);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function runSyncAction(CoolifyWordpressSite $site, string $action, array $params = [], ?int $userId = null): array
    {
        @set_time_limit(in_array($action, ['refresh_info', 'db_export', 'raw_cli', 'core_update', 'core_reinstall'], true) ? 600 : 180);

        $special = $this->runSpecialAction($site, $action, $params, $userId);
        if ($special !== null) {
            return $special;
        }

        $resolved = $this->actionRunner->resolve($action, $params);
        if (! ($resolved['success'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'فشل'];
        }

        $command = $resolved['command'] ?? '';
        $timeout = (int) ($resolved['timeout'] ?? 120);
        $long = $timeout > 120 || in_array($action, ['db_export', 'plugin_update_all', 'theme_update_all'], true);
        $result = $long
            ? $this->cli->runLong($site, $command, $timeout)
            : $this->cli->run($site, $command, $timeout);

        return $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    protected function runSpecialAction(CoolifyWordpressSite $site, string $action, array $params, ?int $userId): ?array
    {
        $def = $this->actionRunner->definition($action);
        if (($def['type'] ?? '') !== 'special') {
            return null;
        }

        $handler = $def['handler'] ?? $action;
        $output = '';
        $success = true;
        $extra = [];

        switch ($handler) {
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

            case 'redis_apply_env':
                return $this->applyRedisEnv($site);

            case 'docker_compose_pull':
                $result = $this->pullDockerCompose($site);
                $output = $result['output'] ?? '';
                $success = $result['success'] ?? false;
                if ($success) {
                    $this->provisioning->syncSiteFromCoolify($site->fresh());
                    $this->getSiteInfo($site->fresh(), true);
                }

                return $this->finalizeActionResult($site, $action, $success, $output, $userId);

            case 'docker_compose_lifecycle':
                $op = (string) ($def['lifecycle'] ?? 'restart');
                $result = $this->cli->composeLifecycle($site, $op);
                if ($result['success'] ?? false) {
                    $this->provisioning->syncSiteFromCoolify($site->fresh());
                }

                return $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId);

            case 'raw_cli':
                $command = trim((string) ($params['command'] ?? ''));
                $result = $this->cli->runLong($site, $command, 600);

                return $this->finalizeActionResult($site, 'raw_cli: '.$command, $result['success'] ?? false, $result['output'] ?? '', $userId);

            case 'core_update':
                $r1 = $this->cli->runLong($site, 'core update --version=latest', 600);
                if (! ($r1['success'] ?? false)) {
                    $r1 = $this->cli->runLong($site, 'core update', 600);
                }
                $r2 = $this->cli->run($site, 'core update-db', 120);
                $output = trim(($r1['output'] ?? '')."\n".($r2['output'] ?? ''));
                $success = ($r1['success'] ?? false) && ($r2['success'] ?? false);

                return $this->finalizeActionResult($site, $action, $success, $output, $userId);

            case 'core_reinstall':
                $r1 = $this->cli->runLong($site, 'core update --force', 600);
                $output = $r1['output'] ?? '';
                $success = $r1['success'] ?? false;
                if (! $success) {
                    $r2 = $this->cli->runLong($site, 'core download --force --skip-content', 600);
                    $output = trim($output."\n".($r2['output'] ?? ''));
                    $success = $r2['success'] ?? false;
                }
                if ($success) {
                    $r3 = $this->cli->run($site, 'core update-db', 120);
                    $output = trim($output."\n".($r3['output'] ?? ''));
                    $success = ($r3['success'] ?? false) && $success;
                }

                return $this->finalizeActionResult($site, $action, $success, $output, $userId);

            case 'user_reset_password':
                $login = preg_replace('/[^a-z0-9_@.\-]/i', '', (string) ($params['login'] ?? ''));
                if ($login === '') {
                    return ['success' => false, 'message' => 'اسم المستخدم مطلوب'];
                }
                $password = (string) ($params['password'] ?? '');
                if ($password === '') {
                    $password = Str::password(16, symbols: true);
                }
                $result = $this->cli->run($site, 'user update '.$login.' --user_pass='.escapeshellarg($password), 60);
                $extra = $result['success'] ? ['generated_password' => $password, 'login' => $login] : [];

                return array_merge(
                    $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId),
                    $extra
                );

            case 'user_create':
                $login = preg_replace('/[^a-z0-9_@.\-]/i', '', (string) ($params['login'] ?? ''));
                $email = filter_var($params['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
                $role = preg_replace('/[^a-z0-9_-]/i', '', (string) ($params['role'] ?? 'subscriber')) ?: 'subscriber';
                if ($login === '' || $email === '') {
                    return ['success' => false, 'message' => 'اسم المستخدم والبريد مطلوبان'];
                }
                $password = (string) ($params['password'] ?? '');
                if ($password === '') {
                    $password = Str::password(16, symbols: true);
                }
                $cmd = sprintf(
                    'user create %s %s --user_pass=%s --role=%s --porcelain',
                    escapeshellarg($login),
                    escapeshellarg($email),
                    escapeshellarg($password),
                    escapeshellarg($role)
                );
                $result = $this->cli->run($site, $cmd, 120);
                $extra = $result['success'] ? ['generated_password' => $password, 'login' => $login] : [];

                return array_merge(
                    $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId),
                    $extra
                );

            case 'search_replace':
                $old = (string) ($params['old'] ?? '');
                $new = (string) ($params['new'] ?? '');
                if ($old === '') {
                    return ['success' => false, 'message' => 'النص القديم مطلوب'];
                }
                $dry = ! empty($params['dry_run']);
                $flag = $dry ? '--dry-run' : '';
                $cmd = sprintf(
                    'search-replace %s %s %s --all-tables',
                    escapeshellarg($old),
                    escapeshellarg($new),
                    $flag
                );
                $result = $this->cli->runLong($site, trim($cmd), 600);

                return $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId);

            case 'post_create':
                $title = Str::limit(strip_tags((string) ($params['title'] ?? '')), 200, '');
                if ($title === '') {
                    return ['success' => false, 'message' => 'العنوان مطلوب'];
                }
                $postType = preg_replace('/[^a-z0-9_-]/i', '', (string) ($params['post_type'] ?? 'post')) ?: 'post';
                $status = preg_replace('/[^a-z0-9_-]/i', '', (string) ($params['status'] ?? 'draft')) ?: 'draft';
                $cmd = sprintf(
                    'post create --post_title=%s --post_type=%s --post_status=%s --porcelain',
                    escapeshellarg($title),
                    escapeshellarg($postType),
                    escapeshellarg($status)
                );
                $result = $this->cli->run($site, $cmd, 120);

                return $this->finalizeActionResult($site, $action, $result['success'] ?? false, $result['output'] ?? '', $userId);

            default:
                return null;
        }
    }

    /**
     * @return array{success: bool, message: string, output: string, data?: array<string, mixed>|null}
     */
    protected function finalizeActionResult(CoolifyWordpressSite $site, string $action, bool $success, string $output, ?int $userId): array
    {
        $this->appendLog($site, $action, $success ? 'نجح' : 'فشل', $output);
        $this->recordActivity($site, $action, $success, $userId);

        $data = null;
        if ($success && ! str_starts_with($action, 'raw_cli')) {
            if ($this->shouldRefreshExtensionsOnly($action)) {
                $data = $this->refreshExtensionListsOnly($site);
            } elseif ($this->shouldFullRefreshSiteInfo($action)) {
                $refreshed = $this->getSiteInfo($site->fresh(), true);
                $data = $refreshed['data'] ?? null;
            }
        }

        $failMessage = 'فشل تنفيذ الإجراء';
        if (! $success && trim($output) !== '') {
            $failMessage = Str::limit(trim($output), 400);
        }

        $message = $success ? 'تم تنفيذ الإجراء' : $failMessage;
        if ($success && $this->shouldRefreshExtensionsOnly($action)) {
            $message = 'تم التنفيذ وتحديث قائمة الإضافات/القوالب';
        }

        return [
            'success' => $success,
            'message' => $message,
            'output' => $output,
            'data' => $data,
        ];
    }

    protected function shouldRefreshExtensionsOnly(string $action): bool
    {
        return in_array($action, [
            'plugin_update', 'theme_update', 'plugin_update_all', 'theme_update_all',
            'plugin_activate', 'plugin_deactivate', 'plugin_delete', 'plugin_install',
            'theme_activate', 'theme_delete', 'theme_install',
        ], true);
    }

    protected function shouldFullRefreshSiteInfo(string $action): bool
    {
        return ! $this->shouldRefreshExtensionsOnly($action)
            && ! in_array($action, ['diagnose', 'bootstrap_mcp'], true);
    }

    /**
     * تحديث سريع للإضافات/القوالب فقط (أمران WP-CLI) دون إعادة جلب كل معلومات الموقع.
     *
     * @return array<string, mixed>|null
     */
    public function refreshExtensionListsOnly(CoolifyWordpressSite $site): ?array
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        $info = is_array($metadata['wp_info'] ?? null) ? $metadata['wp_info'] : [];

        $plugins = $this->cli->run(
            $site,
            'plugin list --format=json --fields=name,status,version,update,update_version',
            120
        );
        $themes = $this->cli->run(
            $site,
            'theme list --format=json --fields=name,status,version,update,update_version',
            120
        );

        $pluginList = ($plugins['success'] ?? false)
            ? $this->parseJsonLines($plugins['output'] ?? '')
            : ($info['plugins'] ?? []);
        $themeList = ($themes['success'] ?? false)
            ? $this->parseJsonLines($themes['output'] ?? '')
            : ($info['themes'] ?? []);

        if (! ($plugins['success'] ?? false) && ! ($themes['success'] ?? false)) {
            return null;
        }

        $info['plugins'] = $pluginList;
        $info['themes'] = $themeList;
        $info['plugins_updates_count'] = $this->countAvailableUpdates($pluginList);
        $info['themes_updates_count'] = $this->countAvailableUpdates($themeList);
        $info['fetched_at'] = now()->toIso8601String();

        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_info' => $info,
                'wp_info_fetched_at' => now()->toIso8601String(),
            ]),
        ]);

        return $info;
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
                    'progress_label' => $this->actionProgressLabel($action, $params),
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
        $site->refresh();
        $job = ($site->metadata ?? [])['wp_job'] ?? null;
        if (! is_array($job)) {
            return ['success' => true, 'job' => null];
        }

        if (($job['status'] ?? '') === 'running' && $this->wpJobIsStale($job)) {
            $job = array_merge($job, [
                'status' => 'failed',
                'progress_label' => 'انتهت المهلة — شغّل معالج الطابور (queue:work) أو اضغط «تحديث القائمة» مرة أخرى',
                'output' => (string) ($job['output'] ?? ''),
                'finished_at' => now()->toIso8601String(),
            ]);
        }

        $terminalStatus = $job['status'] ?? '';
        if (in_array($terminalStatus, ['failed', 'completed'], true)) {
            $age = $this->wpJobFinishedSecondsAgo($job);
            if ($age === null || $age > 20) {
                $this->clearWpJobRecord($site);

                return ['success' => true, 'job' => null];
            }
        }

        return ['success' => true, 'job' => $job];
    }

    public function clearWpJobRecord(CoolifyWordpressSite $site): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        if (! isset($metadata['wp_job'])) {
            return;
        }
        unset($metadata['wp_job']);
        $site->update(['metadata' => $metadata]);
    }

    /**
     * @param  array<string, mixed>  $job
     */
    protected function wpJobFinishedSecondsAgo(array $job): ?int
    {
        $finished = $job['finished_at'] ?? $job['started_at'] ?? null;
        if (! is_string($finished) || $finished === '') {
            return null;
        }

        try {
            return (int) \Illuminate\Support\Carbon::parse($finished)->diffInSeconds(now());
        } catch (\Throwable) {
            return null;
        }
    }

    public function clearStuckWpJob(CoolifyWordpressSite $site, int $maxMinutes = 10): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        $job = $metadata['wp_job'] ?? null;
        if (! is_array($job) || ($job['status'] ?? '') !== 'running') {
            return;
        }

        if (! $this->wpJobIsStale($job, $maxMinutes)) {
            return;
        }

        $job['status'] = 'failed';
        $job['progress_label'] = 'أُلغيت مهمة عالقة — سيتم الجلب مباشرة من السيرفر';
        $job['finished_at'] = now()->toIso8601String();
        $site->update(['metadata' => array_merge($metadata, ['wp_job' => $job])]);
    }

    /**
     * @param  array<string, mixed>  $job
     */
    protected function wpJobIsStale(array $job, int $maxMinutes = 15): bool
    {
        $started = $job['started_at'] ?? null;
        if (! is_string($started) || $started === '') {
            return true;
        }

        try {
            return \Illuminate\Support\Carbon::parse($started)->diffInMinutes(now()) >= $maxMinutes;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function countAvailableUpdates(array $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (strtolower((string) ($item['update'] ?? '')) === 'available') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function actionProgressLabel(string $action, array $params = []): string
    {
        $slug = trim((string) ($params['slug'] ?? ''));

        return match ($action) {
            'refresh_info' => 'جلب معلومات WordPress',
            'plugin_update_all' => 'تحديث كل الإضافات',
            'theme_update_all' => 'تحديث كل القوالب',
            'plugin_update' => $slug !== '' ? 'تحديث الإضافة: '.$slug : 'تحديث إضافة',
            'theme_update' => $slug !== '' ? 'تحديث القالب: '.$slug : 'تحديث قالب',
            'plugin_install' => $slug !== '' ? 'تثبيت الإضافة: '.$slug : 'تثبيت إضافة',
            'theme_install' => $slug !== '' ? 'تثبيت القالب: '.$slug : 'تثبيت قالب',
            'core_update' => 'تحديث WordPress Core',
            'core_reinstall' => 'إعادة تثبيت ملفات Core',
            'bootstrap_mcp' => 'تركيب MCP + WP-CLI',
            'db_export' => 'تصدير قاعدة البيانات',
            'docker_compose_pull' => 'سحب صور Docker',
            default => $this->actionRunner->label($action) ?? $action,
        };
    }
}
