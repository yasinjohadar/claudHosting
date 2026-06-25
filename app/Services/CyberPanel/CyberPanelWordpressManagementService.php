<?php

namespace App\Services\CyberPanel;

use App\Models\CyberPanelWordpressSite;
use Illuminate\Support\Facades\Auth;

class CyberPanelWordpressManagementService
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelSettingsService $settings
    ) {}

    /**
     * @return array{ui_ready: bool, api_ready: bool, execute_ready: bool, message: string}
     */
    public function getManagementState(CyberPanelWordpressSite $site): array
    {
        $apiReady = $this->api->isConfigured() && $this->api->supportsCloudOperations();
        $uiReady = $site->status === 'running';
        $message = '';

        if (! $apiReady) {
            $message = 'أكمل إعدادات CyberPanel وفعّل CloudAPI.';
        } elseif (! $uiReady) {
            $message = match ($site->status) {
                'provisioning' => 'WordPress قيد التثبيت — انتظر اكتمال العملية.',
                'failed' => 'فشل تثبيت WordPress — أعد المحاولة من صفحة الموقع.',
                default => 'WordPress غير جاهز بعد.',
            };
        } else {
            $message = 'جاهز للإدارة عبر CyberPanel API';
        }

        return [
            'ui_ready' => $uiReady,
            'api_ready' => $apiReady,
            'execute_ready' => $uiReady && $apiReady,
            'message' => $message,
        ];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function canManage(CyberPanelWordpressSite $site): array
    {
        $state = $this->getManagementState($site);

        return [
            'ok' => $state['execute_ready'],
            'message' => $state['message'],
        ];
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>|null}
     */
    public function getSiteInfo(CyberPanelWordpressSite $site, bool $refresh = false): array
    {
        $meta = is_array($site->metadata) ? $site->metadata : [];
        $cached = is_array($meta['wp_info'] ?? null) ? $meta['wp_info'] : null;

        if (! $refresh && $cached !== null) {
            return ['success' => true, 'data' => $cached, 'message' => 'OK'];
        }

        return $this->refreshSiteInfo($site);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, async?: bool}
     */
    public function executeAction(CyberPanelWordpressSite $site, string $action, array $params = [], ?int $userId = null): array
    {
        $registry = config('cyberpanel_wordpress.actions', []);
        if (! isset($registry[$action])) {
            return ['success' => false, 'message' => 'إجراء غير معروف: '.$action];
        }

        $can = $this->canManage($site);
        if (! $can['ok'] && ! in_array($action, ['diagnose', 'backup_list'], true)) {
            return ['success' => false, 'message' => $can['message'] ?? 'غير جاهز'];
        }

        $label = config('cyberpanel_wordpress.action_labels.'.$action, $action);
        $userId = $userId ?? Auth::id();

        if ($action === 'refresh_info') {
            $result = $this->refreshSiteInfo($site);
            $this->appendManagementLog($site, $action, $label, $result['success'] ?? false, $result['message'] ?? '', $userId);

            return $result;
        }

        if ($action === 'diagnose') {
            $result = $this->diagnose($site);
            $this->appendManagementLog($site, $action, $label, $result['success'] ?? false, $result['message'] ?? '', $userId);

            return $result;
        }

        $def = $registry[$action];
        if (($def['type'] ?? '') === 'user') {
            $result = $this->dispatchUserAction($site, $action, $params);
            if (($result['success'] ?? false)) {
                $this->refreshSiteInfo($site, false);
            }
            $this->appendManagementLog($site, $action, $label, $result['success'] ?? false, $result['message'] ?? '', $userId);

            return $result;
        }

        $result = $this->dispatchApiAction($site, $action, $registry[$action], $params);

        if ($action === 'core_reinstall' && ($result['success'] ?? false) && ! empty($result['data']['wp_id'])) {
            $meta = is_array($site->metadata) ? $site->metadata : [];
            $meta['wp_manager_id'] = (int) $result['data']['wp_id'];
            $site->update(['metadata' => $meta]);
        }

        if (($result['success'] ?? false) && ! in_array($action, ['backup_list'], true)) {
            $this->refreshSiteInfo($site, false);
        }

        if ($action === 'backup_create' || $action === 'backup_restore') {
            $this->storeBackupJob($site, $result);
        }

        $this->appendManagementLog($site, $action, $label, $result['success'] ?? false, $result['message'] ?? '', $userId);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed}
     */
    protected function dispatchApiAction(CyberPanelWordpressSite $site, string $action, array $def, array $params): array
    {
        $domain = $site->domain;
        $method = (string) ($def['method'] ?? '');

        return match ($method) {
            'toggleWpExtension' => $this->api->toggleWpExtension($domain, (string) ($params['slug'] ?? '')),
            'updateWpPlugins' => $this->api->updateWpPlugins(
                $domain,
                ! empty($def['all']) ? 'all' : (string) ($params['slug'] ?? ''),
            ),
            'deleteWpPlugins' => $this->api->deleteWpPlugins($domain, (string) ($params['slug'] ?? '')),
            'updateWpThemes' => $this->api->updateWpThemes(
                $domain,
                ! empty($def['all']) ? 'all' : (string) ($params['slug'] ?? ''),
            ),
            'deleteWpThemes' => $this->api->deleteWpThemes($domain, (string) ($params['slug'] ?? '')),
            'updateWpSetting' => $this->api->updateWpSetting(
                $domain,
                (string) ($def['setting'] ?? ''),
                (bool) ($def['value'] ?? false)
            ),
            'saveWpAutoUpdateSettings' => $this->api->saveWpAutoUpdateSettings(
                $domain,
                (string) ($params['wp_core'] ?? 'Minor and Security Updates'),
                (string) ($params['plugins'] ?? 'Enabled'),
                (string) ($params['themes'] ?? 'Enabled'),
            ),
            'createWpBackup' => $this->api->createWpBackup($domain, [
                'data' => (int) ($params['include_files'] ?? 1),
                'databases' => (int) ($params['include_database'] ?? 1),
                'emails' => (int) ($params['include_emails'] ?? 0),
            ]),
            'listWpBackups' => $this->api->listWpBackups($domain),
            'deleteWpBackup' => $this->api->deleteWpBackup($domain, (string) ($params['backup_file'] ?? '')),
            'restoreWpBackup' => $this->api->restoreWpBackup(
                $domain,
                (string) ($params['backup_file'] ?? ''),
                isset($params['source_domain']) ? (string) $params['source_domain'] : null,
            ),
            'reinstallWpCore' => $this->api->reinstallWpCore(
                $domain,
                $this->resolveStoredWpManagerId($site, $params),
            ),
            default => ['success' => false, 'message' => 'طريقة API غير مدعومة'],
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, generated_password?: string, login?: string}
     */
    protected function dispatchUserAction(CyberPanelWordpressSite $site, string $action, array $params): array
    {
        $domain = $site->domain;

        return match ($action) {
            'user_create' => $this->api->createWpUser(
                $domain,
                (string) ($params['login'] ?? ''),
                (string) ($params['email'] ?? ''),
                (string) ($params['role'] ?? 'subscriber'),
                isset($params['password']) ? (string) $params['password'] : null,
            ),
            'user_reset_password' => $this->api->updateWpUserPassword(
                $domain,
                (string) ($params['login'] ?? ''),
                isset($params['password']) ? (string) $params['password'] : null,
            ),
            'user_update_role' => $this->api->updateWpUserRole(
                $domain,
                (string) ($params['login'] ?? ''),
                (string) ($params['role'] ?? 'subscriber'),
            ),
            'user_delete' => $this->api->deleteWpUser(
                $domain,
                (int) ($params['user_id'] ?? 0),
                (int) ($params['reassign_to'] ?? 1),
            ),
            default => ['success' => false, 'message' => 'إجراء مستخدم غير مدعوم'],
        };
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function refreshSiteInfo(CyberPanelWordpressSite $site, bool $persist = true): array
    {
        $domain = $site->domain;
        $pluginsResponse = $this->api->getWpPlugins($domain);
        $themesResponse = $this->api->getWpThemes($domain);
        $usersResponse = $this->api->getWpUsers($domain);

        if (! ($pluginsResponse['success'] ?? false) && ! ($themesResponse['success'] ?? false) && ! ($usersResponse['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $pluginsResponse['message'] ?? $themesResponse['message'] ?? $usersResponse['message'] ?? 'تعذّر جلب معلومات WordPress',
            ];
        }

        $plugins = ($pluginsResponse['success'] ?? false) ? ($pluginsResponse['data'] ?? []) : [];
        $themes = ($themesResponse['success'] ?? false) ? ($themesResponse['data'] ?? []) : [];
        $users = ($usersResponse['success'] ?? false) ? ($usersResponse['data'] ?? []) : [];

        $info = [
            'domain' => $domain,
            'plugins' => $plugins,
            'themes' => $themes,
            'users' => $users,
            'plugins_count' => count($plugins),
            'themes_count' => count($themes),
            'users_count' => count($users),
            'plugins_updates_count' => $this->countUpdates($plugins),
            'themes_updates_count' => $this->countUpdates($themes),
            'core_version' => $this->detectCoreVersion($plugins),
            'php_version' => $site->website?->php_version,
            'fetched_at' => now()->toIso8601String(),
            'toggles' => is_array($site->metadata['setting_toggles'] ?? null) ? $site->metadata['setting_toggles'] : [],
        ];

        if ($persist) {
            $meta = is_array($site->metadata) ? $site->metadata : [];
            $meta['wp_info'] = $info;
            $site->update(['metadata' => $meta]);
        }

        return ['success' => true, 'message' => 'تم تحديث المعلومات', 'data' => $info];
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function diagnose(CyberPanelWordpressSite $site): array
    {
        $verify = $this->api->verifyConnection();
        $plugins = $this->api->getWpPlugins($site->domain);

        $ok = ($verify['success'] ?? false) && ($plugins['success'] ?? false);

        return [
            'success' => $ok,
            'message' => $ok
                ? 'الاتصال بـ CyberPanel وWordPress يعمل.'
                : ($plugins['message'] ?? $verify['message'] ?? 'فشل التشخيص'),
            'data' => [
                'verify' => $verify,
                'plugins_probe' => $plugins['success'] ?? false,
                'domain' => $site->domain,
                'checked_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    public function getStatus(CyberPanelWordpressSite $site): array
    {
        $meta = is_array($site->metadata) ? $site->metadata : [];
        $job = is_array($meta['backup_job'] ?? null) ? $meta['backup_job'] : null;

        if ($job && ! empty($job['temp_status_path'])) {
            $poll = $this->api->pollInstallStatus((string) $job['temp_status_path']);
            if ($poll['completed'] ?? false) {
                $meta['backup_job'] = array_merge($job, [
                    'completed' => true,
                    'success' => $poll['success'] ?? false,
                    'message' => $poll['message'] ?? '',
                    'finished_at' => now()->toIso8601String(),
                ]);
                $site->update(['metadata' => $meta]);
            }

            return [
                'success' => true,
                'data' => [
                    'site_status' => $site->status,
                    'backup_job' => $meta['backup_job'],
                    'install_status' => null,
                ],
            ];
        }

        $installPath = trim((string) ($meta['temp_status_path'] ?? ''));
        $installStatus = null;
        if ($site->status === 'provisioning' && $installPath !== '') {
            $installStatus = $this->api->pollInstallStatus($installPath);
        }

        return [
            'success' => true,
            'data' => [
                'site_status' => $site->status,
                'status_label' => $site->status_label,
                'backup_job' => $meta['backup_job'] ?? null,
                'install_status' => $installStatus,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getCyberPanelLinks(CyberPanelWordpressSite $site): array
    {
        return $this->settings->buildCyberPanelDeepLinks($site->domain);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function resolveStoredWpManagerId(CyberPanelWordpressSite $site, array $params): ?int
    {
        if (isset($params['wp_id']) && (int) $params['wp_id'] > 0) {
            return (int) $params['wp_id'];
        }

        $meta = is_array($site->metadata) ? $site->metadata : [];
        $stored = (int) ($meta['wp_manager_id'] ?? 0);

        return $stored > 0 ? $stored : null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function countUpdates(array $items): int
    {
        return count(array_filter($items, fn ($row) => strtolower((string) ($row['update'] ?? '')) === 'available'));
    }

    /**
     * @param  list<array<string, mixed>>  $plugins
     */
    protected function detectCoreVersion(array $plugins): ?string
    {
        foreach ($plugins as $plugin) {
            $name = strtolower((string) ($plugin['name'] ?? ''));
            if (str_contains($name, 'wordpress') || ($plugin['title'] ?? '') === 'WordPress') {
                return (string) ($plugin['version'] ?? '') ?: null;
            }
        }

        return null;
    }

  /**
     * @param  array{success?: bool, message?: string, data?: mixed}  $result
     */
    protected function storeBackupJob(CyberPanelWordpressSite $site, array $result): void
    {
        if (! ($result['success'] ?? false)) {
            return;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $tempPath = trim((string) ($data['tempStatusPath'] ?? $data['temp_status_path'] ?? ''));
        if ($tempPath === '') {
            return;
        }

        $meta = is_array($site->metadata) ? $site->metadata : [];
        $meta['backup_job'] = [
            'temp_status_path' => $tempPath,
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'path' => $data['path'] ?? null,
        ];
        $site->update(['metadata' => $meta]);
    }

    protected function appendManagementLog(
        CyberPanelWordpressSite $site,
        string $action,
        string $label,
        bool $success,
        string $message,
        ?int $userId
    ): void {
        $meta = is_array($site->metadata) ? $site->metadata : [];
        $log = is_array($meta['cp_wp_management_log'] ?? null) ? $meta['cp_wp_management_log'] : [];

        array_unshift($log, [
            'action' => $action,
            'label' => $label,
            'success' => $success,
            'message' => $message,
            'user_id' => $userId,
            'at' => now()->toIso8601String(),
        ]);

        $limit = (int) config('cyberpanel_wordpress.management_log_limit', 80);
        $meta['cp_wp_management_log'] = array_slice($log, 0, max(10, $limit));
        $site->update(['metadata' => $meta]);
    }
}
