<?php

namespace App\Services\Whm\Wordpress;

use App\Jobs\RunWhmWordpressManagementJob;
use App\Models\WhmWordpressOperation;
use App\Models\WhmWordpressSite;
use App\Services\Coolify\WordpressCliActionRunner;
use App\Services\Whm\WhmSshExecutor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhmWordpressManagementService
{
    /** Actions not applicable on cPanel (Docker / Coolify-only). */
    protected array $unsupported = [
        'docker_compose_pull',
        'docker_compose_stop',
        'docker_compose_start',
        'docker_compose_restart',
        'redis_apply_env',
        'bootstrap_mcp',
    ];

    public function __construct(
        protected WhmWordpressCliService $cli,
        protected WhmSshExecutor $ssh,
        protected WordpressCliActionRunner $actionRunner
    ) {}

    /**
     * @return array{ui_ready: bool, ssh_ready: bool, execute_ready: bool, message: string, ssh_host_required?: bool}
     */
    public function getManagementState(WhmWordpressSite $site): array
    {
        $pathOk = trim((string) ($site->path ?? '')) !== '';
        $sshReady = $this->ssh->isConfigured();
        $host = $this->ssh->resolveHost();
        $hostOk = $host !== '';

        $message = 'جاهز للإدارة عبر WP-CLI على cPanel';
        if (! $pathOk) {
            $message = 'مسار التثبيت غير معروف — اضغط بحث / تحديث لاكتشاف الموقع';
        } elseif (! $sshReady) {
            $message = 'اضبط مفتاح SSH في إعدادات WHM لإدارة WordPress';
        } elseif (! $hostOk) {
            $message = 'اضبط عنوان SSH لسيرفر WHM';
        }

        return [
            'ui_ready' => $pathOk && $site->status !== WhmWordpressSite::STATUS_MISSING,
            'ssh_ready' => $sshReady && $hostOk,
            'execute_ready' => $pathOk && $sshReady && $hostOk && $site->status !== WhmWordpressSite::STATUS_MISSING,
            'message' => $message,
            'ssh_host_required' => $sshReady && ! $hostOk,
        ];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function canManage(WhmWordpressSite $site): array
    {
        $state = $this->getManagementState($site);
        if (! $state['execute_ready']) {
            return ['ok' => false, 'message' => $state['message']];
        }

        return ['ok' => true, 'message' => $state['message']];
    }

    /**
     * @return array{success: bool, data?: array<string, mixed>, message?: string}
     */
    public function getSiteInfo(WhmWordpressSite $site, bool $refresh = false): array
    {
        $check = $this->canManage($site);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير متاح'];
        }

        $metadata = $site->metadata ?? [];
        if (! $refresh && ! empty($metadata['wp_info']) && ! empty($metadata['wp_info_fetched_at'])) {
            $fetched = strtotime((string) $metadata['wp_info_fetched_at']);
            if ($fetched && (time() - $fetched) < 120) {
                return ['success' => true, 'data' => $metadata['wp_info']];
            }
        }

        @set_time_limit(300);

        $coreVersion = $this->cli->run($site, 'core version', 120);
        if (! ($coreVersion['success'] ?? false)) {
            return [
                'success' => false,
                'message' => Str::limit(trim($coreVersion['output'] ?? $coreVersion['message'] ?? 'فشل WP-CLI'), 500)
                    .' — جرّب «تشخيص الاتصال».',
            ];
        }

        $coreUpdate = $this->cli->run($site, 'core check-update --format=json', 90);
        $plugins = $this->cli->run($site, 'plugin list --format=json --fields=name,status,version,update,update_version', 120);
        $themes = $this->cli->run($site, 'theme list --format=json --fields=name,status,version,update,update_version', 120);
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
            'plugins_count' => count($pluginList),
            'themes_updates_count' => $this->countAvailableUpdates($themeList),
            'themes_count' => count($themeList),
            'users' => $this->parseJsonLines($users['output'] ?? ''),
            'cli' => $this->parseJsonObject($cliInfo['output'] ?? ''),
            'container' => [
                'id' => null,
                'name' => 'cPanel:'.($site->account?->username ?? ''),
                'image' => 'cpanel',
            ],
            'maintenance' => $this->detectMaintenance($site),
            'fetched_at' => now()->toIso8601String(),
        ];

        $site->update([
            'metadata' => array_merge($metadata, [
                'wp_info' => $info,
                'wp_info_fetched_at' => now()->toIso8601String(),
            ]),
            'wp_version' => $info['core_version'] ?: $site->wp_version,
        ]);

        return ['success' => true, 'data' => $info];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, output?: string, job_id?: string, async?: bool, data?: mixed}
     */
    public function executeAction(WhmWordpressSite $site, string $action, array $params = [], ?int $userId = null): array
    {
        if (! $this->actionRunner->isAllowed($action)) {
            return ['success' => false, 'message' => 'إجراء غير مسموح'];
        }

        if (in_array($action, $this->unsupported, true)) {
            return ['success' => false, 'message' => 'هذا الإجراء خاص بـ Coolify/Docker ولا ينطبق على cPanel'];
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
        $isSafeDryRun = $action === 'search_replace' && ! empty($params['dry_run']);
        if (! empty($def['confirm']) && ! $isSafeDryRun && empty($params['_confirmed'])) {
            return [
                'success' => false,
                'message' => (string) $def['confirm'],
                'requires_confirmation' => true,
            ];
        }

        $check = $this->canManage($site);
        $skip = in_array($action, ['refresh_info', 'diagnose'], true);
        if (! $skip && ! ($check['ok'] ?? false)) {
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
    public function runSyncAction(WhmWordpressSite $site, string $action, array $params = [], ?int $userId = null, ?string $jobId = null): array
    {
        @set_time_limit(in_array($action, ['refresh_info', 'db_export', 'raw_cli', 'core_update', 'core_reinstall'], true) ? 600 : 180);

        $operation = $this->startOperation($site, $action, $params, $userId, $jobId);

        $special = $this->runSpecialAction($site, $action, $params, $userId);
        if ($special !== null) {
            $this->finishOperation($operation, $special);
            $special['operation_id'] = $operation->id;

            return $special;
        }

        $resolved = $this->actionRunner->resolve($action, $params);
        if (! ($resolved['success'] ?? false)) {
            $result = ['success' => false, 'message' => $resolved['message'] ?? 'فشل'];
            $this->finishOperation($operation, $result);
            $result['operation_id'] = $operation->id;

            return $result;
        }

        $command = $resolved['command'] ?? '';
        $timeout = (int) ($resolved['timeout'] ?? 120);
        $long = $timeout > 120 || in_array($action, ['db_export', 'plugin_update_all', 'theme_update_all'], true);
        $result = $long
            ? $this->cli->runLong($site, $command, $timeout)
            : $this->cli->run($site, $command, $timeout);

        $resultFile = null;
        $output = $result['output'] ?? '';
        if ($action === 'db_export' && ($result['success'] ?? false)) {
            $resultFile = $this->storeDbExportFile($site, $operation, (string) $output);
            $output = $resultFile['summary'];
        }

        $final = $this->finalizeActionResult($site, $action, $result['success'] ?? false, $output, $userId);
        if ($resultFile !== null) {
            $final['result_file'] = $resultFile;
        }
        $this->finishOperation($operation, $final, $resultFile);
        $final['operation_id'] = $operation->id;

        return $final;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function startOperation(WhmWordpressSite $site, string $action, array $params, ?int $userId, ?string $jobId): WhmWordpressOperation
    {
        if ($jobId !== null) {
            $existing = WhmWordpressOperation::query()->where('job_id', $jobId)->first();
            if ($existing) {
                $existing->update(['status' => 'running']);

                return $existing;
            }
        }

        return WhmWordpressOperation::create([
            'whm_wordpress_site_id' => $site->id,
            'user_id' => $userId,
            'job_id' => $jobId,
            'action' => $action,
            'action_label' => $this->actionRunner->label($action) ?? $action,
            'params' => $this->sanitizeParamsForLog($params),
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array{path: string, size: int, summary: string}|null  $resultFile
     */
    protected function finishOperation(WhmWordpressOperation $operation, array $result, ?array $resultFile = null): void
    {
        $operation->update([
            'status' => ($result['success'] ?? false) ? 'completed' : 'failed',
            'success' => (bool) ($result['success'] ?? false),
            'message' => is_string($result['message'] ?? null) ? Str::limit((string) $result['message'], 1000) : null,
            'output' => Str::limit((string) ($result['output'] ?? ''), 20000),
            'result_file_path' => $resultFile['path'] ?? null,
            'result_file_size' => $resultFile['size'] ?? null,
            'finished_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function sanitizeParamsForLog(array $params): array
    {
        $safe = $params;
        unset($safe['password'], $safe['_confirmed']);

        return $safe;
    }

    /**
     * @return array{path: string, size: int, summary: string}
     */
    protected function storeDbExportFile(WhmWordpressSite $site, WhmWordpressOperation $operation, string $sqlOutput): array
    {
        $gzipped = gzencode(trim($sqlOutput), 6) ?: '';
        $relativePath = 'wordpress-exports/whm/'.$site->id.'/'.$operation->id.'.sql.gz';
        Storage::disk('local')->put($relativePath, $gzipped);
        $size = (int) Storage::disk('local')->size($relativePath);

        return [
            'path' => $relativePath,
            'size' => $size,
            'summary' => 'تم تصدير قاعدة البيانات بنجاح — الحجم: '.$this->formatBytes($size),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    protected function runSpecialAction(WhmWordpressSite $site, string $action, array $params, ?int $userId): ?array
    {
        $def = $this->actionRunner->definition($action);
        if (($def['type'] ?? '') !== 'special') {
            return null;
        }

        $handler = $def['handler'] ?? $action;

        return match ($handler) {
            'diagnose' => [
                'success' => true,
                'message' => 'تقرير التشخيص',
                'output' => $this->cli->diagnose($site),
            ],
            'refresh_info' => (function () use ($site) {
                $info = $this->getSiteInfo($site, true);

                return [
                    'success' => $info['success'] ?? false,
                    'message' => $info['message'] ?? 'تم تحديث المعلومات',
                    'data' => $info['data'] ?? null,
                ];
            })(),
            'raw_cli' => $this->runRawCli($site, $params, $userId),
            'core_update' => $this->runCoreUpdate($site, $userId),
            'core_reinstall' => $this->runCoreReinstall($site, $userId),
            'user_reset_password' => $this->runUserResetPassword($site, $params, $userId),
            'user_create' => $this->runUserCreate($site, $params, $userId),
            'search_replace' => $this->runSearchReplace($site, $params, $userId),
            'bootstrap_mcp', 'redis_apply_env', 'docker_compose_pull', 'docker_compose_lifecycle' => [
                'success' => false,
                'message' => 'غير مدعوم على cPanel',
            ],
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function runRawCli(WhmWordpressSite $site, array $params, ?int $userId): array
    {
        $command = trim((string) ($params['command'] ?? ''));
        $result = $this->cli->runLong($site, $command, 600);

        return $this->finalizeActionResult($site, 'raw_cli: '.$command, $result['success'] ?? false, $result['output'] ?? '', $userId);
    }

    protected function runCoreUpdate(WhmWordpressSite $site, ?int $userId): array
    {
        $r1 = $this->cli->runLong($site, 'core update --version=latest', 600);
        if (! ($r1['success'] ?? false)) {
            $r1 = $this->cli->runLong($site, 'core update', 600);
        }
        $r2 = $this->cli->run($site, 'core update-db', 120);
        $output = trim(($r1['output'] ?? '')."\n".($r2['output'] ?? ''));
        $success = ($r1['success'] ?? false) && ($r2['success'] ?? false);

        return $this->finalizeActionResult($site, 'core_update', $success, $output, $userId);
    }

    protected function runCoreReinstall(WhmWordpressSite $site, ?int $userId): array
    {
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

        return $this->finalizeActionResult($site, 'core_reinstall', $success, $output, $userId);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function runUserResetPassword(WhmWordpressSite $site, array $params, ?int $userId): array
    {
        $login = preg_replace('/[^a-z0-9_@.\-]/i', '', (string) ($params['login'] ?? ''));
        if ($login === '') {
            return ['success' => false, 'message' => 'اسم المستخدم مطلوب'];
        }
        $password = (string) ($params['password'] ?? '');
        if ($password === '') {
            $password = Str::password(16, symbols: true);
        }
        $result = $this->cli->run($site, 'user update '.$login.' --user_pass='.escapeshellarg($password), 60);
        $base = $this->finalizeActionResult($site, 'user_reset_password', $result['success'] ?? false, $result['output'] ?? '', $userId);
        if ($result['success'] ?? false) {
            $base['generated_password'] = $password;
            $base['login'] = $login;
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function runUserCreate(WhmWordpressSite $site, array $params, ?int $userId): array
    {
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
        $base = $this->finalizeActionResult($site, 'user_create', $result['success'] ?? false, $result['output'] ?? '', $userId);
        if ($result['success'] ?? false) {
            $base['generated_password'] = $password;
            $base['login'] = $login;
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function runSearchReplace(WhmWordpressSite $site, array $params, ?int $userId): array
    {
        $old = (string) ($params['old'] ?? '');
        $new = (string) ($params['new'] ?? '');
        if ($old === '') {
            return ['success' => false, 'message' => 'القيمة القديمة مطلوبة'];
        }
        $dry = ! empty($params['dry_run']);
        $cmd = sprintf(
            'search-replace %s %s --all-tables%s',
            escapeshellarg($old),
            escapeshellarg($new),
            $dry ? ' --dry-run' : ''
        );
        if (! $dry && empty($params['confirm_dangerous'])) {
            return ['success' => false, 'message' => 'أكّد العملية الخطرة'];
        }
        $result = $this->cli->runLong($site, $cmd, 600);

        return $this->finalizeActionResult($site, 'search_replace', $result['success'] ?? false, $result['output'] ?? '', $userId);
    }

    protected function finalizeActionResult(WhmWordpressSite $site, string $action, bool $success, string $output, ?int $userId): array
    {
        $this->appendLog($site, $action, $success ? 'نجح' : 'فشل', $output);

        if ($success && in_array($action, ['plugin_update', 'plugin_update_all', 'plugin_install', 'plugin_activate', 'plugin_deactivate', 'plugin_delete', 'theme_update', 'theme_update_all', 'theme_install', 'theme_activate', 'theme_delete', 'core_update', 'core_reinstall', 'user_create', 'user_delete', 'user_update_role', 'maintenance_activate', 'maintenance_deactivate'], true)) {
            $this->getSiteInfo($site->fresh(), true);
        }

        return [
            'success' => $success,
            'message' => $success ? 'تم تنفيذ الإجراء' : 'فشل تنفيذ الإجراء',
            'output' => $output,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function dispatchAsync(WhmWordpressSite $site, string $action, array $params, ?int $userId): array
    {
        $metadata = $site->metadata ?? [];
        if (in_array($metadata['wp_job']['status'] ?? '', ['queued', 'running'], true)) {
            return ['success' => false, 'message' => 'يوجد عملية قيد التنفيذ بالفعل', 'async' => true];
        }

        $jobId = (string) Str::uuid();
        $metadata['wp_job'] = [
            'id' => $jobId,
            'action' => $action,
            'status' => 'queued',
            'progress_label' => 'قيد الانتظار',
            'output' => '',
            'started_at' => now()->toIso8601String(),
        ];
        $site->update(['metadata' => $metadata]);

        WhmWordpressOperation::create([
            'whm_wordpress_site_id' => $site->id,
            'user_id' => $userId,
            'job_id' => $jobId,
            'action' => $action,
            'action_label' => $this->actionRunner->label($action) ?? $action,
            'params' => $this->sanitizeParamsForLog($params),
            'status' => 'queued',
            'started_at' => now(),
        ]);

        RunWhmWordpressManagementJob::dispatch($site->id, $action, $params, $jobId, $userId);

        return [
            'success' => true,
            'async' => true,
            'job_id' => $jobId,
            'message' => 'جاري التنفيذ في الخلفية',
        ];
    }

    public function appendLog(WhmWordpressSite $site, string $action, string $status, string $output = ''): void
    {
        $metadata = $site->metadata ?? [];
        $log = $metadata['wp_management_log'] ?? [];
        if (! is_array($log)) {
            $log = [];
        }
        array_unshift($log, [
            'at' => now()->toIso8601String(),
            'action' => $action,
            'status' => $status,
            'output' => Str::limit($output, 2000),
        ]);
        $metadata['wp_management_log'] = array_slice($log, 0, 40);
        $site->update(['metadata' => $metadata]);
    }

    public function getJobStatus(WhmWordpressSite $site): array
    {
        $site->refresh();
        $job = ($site->metadata ?? [])['wp_job'] ?? null;
        if (! is_array($job) || empty($job['id'])) {
            return ['success' => true, 'job' => null];
        }

        if (in_array($job['status'] ?? '', ['queued', 'running'], true) && $this->wpJobIsStale($job)) {
            $job = array_merge($job, [
                'status' => 'failed',
                'progress_label' => 'انتهت المهلة — شغّل معالج الطابور (queue:work) أو اضغط «تحديث القائمة» مرة أخرى',
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

    public function clearWpJobRecord(WhmWordpressSite $site): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        unset($metadata['wp_job']);
        $site->update(['metadata' => $metadata]);
    }

    public function clearStuckWpJob(WhmWordpressSite $site, int $maxMinutes = 10): void
    {
        $site->refresh();
        $metadata = $site->metadata ?? [];
        $job = $metadata['wp_job'] ?? null;
        if (! is_array($job) || ! in_array($job['status'] ?? '', ['queued', 'running'], true)) {
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

    protected function detectMaintenance(WhmWordpressSite $site): bool
    {
        $result = $this->cli->run($site, 'maintenance-mode status', 30);
        $out = strtolower($result['output'] ?? '');

        return str_contains($out, 'active') || str_contains($out, 'enabled');
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parseJsonLines(string $output): array
    {
        $output = trim($output);
        if ($output === '') {
            return [];
        }
        $decoded = json_decode($output, true);
        if (is_array($decoded)) {
            return array_values(array_filter($decoded, 'is_array'));
        }

        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseJsonObject(string $output): ?array
    {
        $decoded = json_decode(trim($output), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function countAvailableUpdates(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            if (strtolower((string) ($row['update'] ?? '')) === 'available') {
                $n++;
            }
        }

        return $n;
    }
}
