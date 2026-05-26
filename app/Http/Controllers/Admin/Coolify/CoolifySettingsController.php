<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Models\AppStorageConfig;
use App\Models\ClientCoolifyProject;
use App\Models\CoolifyActivityLog;
use App\Models\CoolifyCatalogItem;
use App\Models\CoolifySnapshotSchedule;
use App\Models\CoolifyWordpressSite;
use App\Services\CloudflareApiService;
use App\Services\Coolify\CoolifyCatalogService;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifySnapshotStorageService;
use App\Services\Coolify\CoolifySshExecutor;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CoolifySettingsController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CloudflareApiService $cloudflare,
        protected CoolifySettingsService $settings,
        protected CoolifySshExecutor $ssh,
        protected CoolifySnapshotStorageService $snapshotStorage,
        protected CoolifyCatalogService $catalog
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->settings->initializeDefaults();
        $configured = $this->coolify->isConfigured();
        $synced = [];

        if ($configured && $this->coolify->ping()) {
            $synced = $this->settings->syncSnapshotStorageFromCoolify($this->coolify);
        }

        $form = $this->settings->getFormSettings();
        $connected = $configured && $this->coolify->ping();
        $version = $configured ? $this->coolify->getVersion() : null;
        $health = $configured ? $this->coolify->getHealth() : null;
        $readiness = $this->settings->getSnapshotReadiness();

        $storageConfigs = AppStorageConfig::query()
            ->where('is_active', true)
            ->whereIn('driver', config('coolify.snapshot_storage_drivers', ['s3']))
            ->orderBy('priority')
            ->orderBy('name')
            ->get(['id', 'name', 'driver']);

        $coolifyS3Storages = $configured ? $this->coolify->listS3Storages() : [];
        $snapshotStorageReady = $this->snapshotStorage->isConfigured();
        $wordpressReadiness = $this->settings->getWordpressReadiness();
        $wordpressServers = $configured ? $this->coolifyList($this->coolify->listServers()) : [];
        $wordpressProjects = $configured ? $this->coolifyList($this->coolify->listProjects()) : [];
        $cloudflareZones = $this->cloudflare->isConfigured()
            ? $this->cloudflare->listAllZones($form['wordpress_base_domain'] ?? null)
            : [];

        return view('admin.coolify.settings.index', compact(
            'form',
            'configured',
            'connected',
            'version',
            'health',
            'storageConfigs',
            'coolifyS3Storages',
            'snapshotStorageReady',
            'readiness',
            'synced',
            'wordpressReadiness',
            'wordpressServers',
            'wordpressProjects',
            'cloudflareZones'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_url' => 'required|url|max:500',
            'api_token' => 'nullable|string|max:2000',
            'timeout' => 'nullable|integer|min:5|max:120',
            'ssh_user' => 'nullable|string|max:64',
            'ssh_private_key' => 'nullable|string|max:10000',
            'ssh_private_key_path' => 'nullable|string|max:500',
            'ssh_host_fallback' => 'nullable|string|max:255',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'backup_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'snapshot_storage_config_id' => 'nullable|integer|min:1',
            'coolify_s3_storage_uuid' => 'nullable|string|max:255',
            's3_prefix' => 'nullable|string|max:255',
            'wordpress_base_domain' => 'nullable|string|max:255',
            'wordpress_default_server_uuid' => 'nullable|string|max:255',
            'wordpress_shared_project_uuid' => 'nullable|string|max:255',
            'wordpress_default_environment' => 'nullable|string|max:64',
            'wordpress_instant_deploy' => 'nullable|boolean',
            'wordpress_provision_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'wordpress_default_destination_uuid' => 'nullable|string|max:255',
            'wordpress_service_type' => 'nullable|string|in:wordpress-with-mariadb,wordpress-with-mysql,wordpress-without-database',
            'wordpress_cloudflare_zone_id' => 'nullable|string|max:64',
            'wordpress_cloudflare_proxied' => 'nullable|boolean',
            'wordpress_cloudflare_ssl_mode' => 'nullable|string|in:off,flexible,full,strict',
            'wordpress_security_preset' => 'nullable|string|in:basic,performance,strict',
            'wordpress_cloudflare_enabled' => 'nullable|boolean',
            'wordpress_docker_tag' => 'nullable|string|max:128',
            'wordpress_filebrowser_enabled' => 'nullable|boolean',
            'wordpress_filebrowser_subdomain_prefix' => 'nullable|string|max:32|regex:/^[a-z0-9-]+$/',
            'wordpress_filebrowser_subdomain_style' => 'nullable|string|in:flat,nested',
            'wordpress_filebrowser_open_mode' => 'nullable|string|in:embed,new_tab',
            'wordpress_filebrowser_admin_username' => 'nullable|string|max:32|regex:/^[a-z0-9_-]+$/',
            'wordpress_filebrowser_password_length' => 'nullable|integer|min:12|max:64',
            'wordpress_management_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'wordpress_redis_enabled' => 'nullable|boolean',
            'wordpress_redis_host' => 'nullable|string|max:255',
            'wordpress_redis_port' => 'nullable|integer|min:1|max:65535',
            'terminal_bridge_enabled' => 'nullable|boolean',
            'terminal_bridge_url' => 'nullable|url|max:500',
            'terminal_bridge_secret' => 'nullable|string|max:2000',
            'terminal_bridge_port' => 'nullable|integer|min:1|max:65535',
            'terminal_bridge_token_ttl' => 'nullable|integer|min:60|max:86400',
        ], [
            'api_url.required' => 'عنوان Coolify API مطلوب',
            'api_url.url' => 'عنوان API غير صالح',
            'backup_queue.regex' => 'اسم الطابور يجب أن يحتوي أحرفاً إنجليزية وأرقاماً و _ - فقط',
        ]);

        $existing = $this->settings->getFormSettings();
        if (empty($validated['api_token']) && ! $existing['has_token']) {
            return back()->withInput()->with('error', 'رمز API مطلوب عند الإعداد لأول مرة');
        }

        $pathInput = trim((string) ($validated['ssh_private_key_path'] ?? ''));
        if (str_contains($pathInput, '-----BEGIN') && str_contains($pathInput, 'PRIVATE KEY')
            && empty($validated['ssh_private_key'])) {
            return back()->withInput()->with('error', 'ضع المفتاح في «لصق المفتاح (PEM)» وليس في حقل المسار. أو احفظه في ملف .pem واكتب المسار فقط.');
        }

        $sshKey = $this->ssh->resolveSettingsKeyForSave(
            $validated['ssh_private_key_path'] ?? null,
            $validated['ssh_private_key'] ?? null
        );

        $this->settings->updateSettings([
            'api_url' => $validated['api_url'],
            'api_token' => $validated['api_token'] ?? null,
            'timeout' => $validated['timeout'] ?? 30,
            'ssh_user' => $validated['ssh_user'] ?? null,
            'ssh_private_key' => $sshKey['ssh_private_key'],
            'ssh_private_key_path' => $sshKey['ssh_private_key_path'],
            'ssh_host_fallback' => $validated['ssh_host_fallback'] ?? null,
            'ssh_port' => $validated['ssh_port'] ?? null,
            'backup_queue' => $validated['backup_queue'] ?? null,
            'snapshot_storage_config_id' => $validated['snapshot_storage_config_id'] ?? null,
            'coolify_s3_storage_uuid' => $validated['coolify_s3_storage_uuid'] ?? null,
            's3_prefix' => $validated['s3_prefix'] ?? null,
            'wordpress_base_domain' => $validated['wordpress_base_domain'] ?? null,
            'wordpress_default_server_uuid' => $validated['wordpress_default_server_uuid'] ?? null,
            'wordpress_shared_project_uuid' => $validated['wordpress_shared_project_uuid'] ?? null,
            'wordpress_default_environment' => $validated['wordpress_default_environment'] ?? null,
            'wordpress_instant_deploy' => $request->boolean('wordpress_instant_deploy', true),
            'wordpress_provision_queue' => $validated['wordpress_provision_queue'] ?? null,
            'wordpress_default_destination_uuid' => $validated['wordpress_default_destination_uuid'] ?? null,
            'wordpress_service_type' => $validated['wordpress_service_type'] ?? null,
            'wordpress_cloudflare_zone_id' => $validated['wordpress_cloudflare_zone_id'] ?? null,
            'wordpress_cloudflare_proxied' => $request->boolean('wordpress_cloudflare_proxied', true),
            'wordpress_cloudflare_ssl_mode' => $validated['wordpress_cloudflare_ssl_mode'] ?? null,
            'wordpress_security_preset' => $validated['wordpress_security_preset'] ?? null,
            'wordpress_cloudflare_enabled' => $request->boolean('wordpress_cloudflare_enabled', true),
            'wordpress_docker_tag' => $validated['wordpress_docker_tag'] ?? null,
            'wordpress_filebrowser_enabled' => $request->boolean('wordpress_filebrowser_enabled', true),
            'wordpress_filebrowser_subdomain_prefix' => $validated['wordpress_filebrowser_subdomain_prefix'] ?? null,
            'wordpress_filebrowser_subdomain_style' => $validated['wordpress_filebrowser_subdomain_style'] ?? null,
            'wordpress_filebrowser_open_mode' => $validated['wordpress_filebrowser_open_mode'] ?? null,
            'wordpress_filebrowser_admin_username' => $validated['wordpress_filebrowser_admin_username'] ?? null,
            'wordpress_filebrowser_password_length' => $validated['wordpress_filebrowser_password_length'] ?? null,
            'wordpress_management_queue' => $validated['wordpress_management_queue'] ?? null,
            'wordpress_redis_enabled' => $request->boolean('wordpress_redis_enabled', false),
            'wordpress_redis_host' => $validated['wordpress_redis_host'] ?? null,
            'wordpress_redis_port' => $validated['wordpress_redis_port'] ?? null,
            'terminal_bridge_enabled' => $request->boolean('terminal_bridge_enabled'),
            'terminal_bridge_url' => $validated['terminal_bridge_url'] ?? null,
            'terminal_bridge_secret' => $validated['terminal_bridge_secret'] ?? null,
            'terminal_bridge_port' => $validated['terminal_bridge_port'] ?? null,
            'terminal_bridge_token_ttl' => $validated['terminal_bridge_token_ttl'] ?? null,
        ]);

        $this->coolify->refreshConnection();
        $synced = $this->settings->syncSnapshotStorageFromCoolify($this->coolify);

        $message = 'تم حفظ إعدادات Coolify بنجاح';
        if ($synced !== []) {
            $message .= ' — تم ضبط تلقائياً: '.implode(', ', $synced);
        }
        if (! empty($sshKey['notice'])) {
            $message .= ' — '.$sshKey['notice'];
        }

        return redirect()->route('admin.coolify.settings.index')
            ->with('success', $message);
    }

    public function overview(Request $request)
    {
        if ($request->boolean('refresh')) {
            $this->coolify->clearDashboardCache();
        }

        $stats = $this->coolify->getDashboardStats();
        $configured = $this->coolify->isConfigured();
        $connected = $configured && ($stats['connected'] ?? false);

        $apiVersion = null;
        $systemHealth = null;
        $systemHealthOk = false;
        if ($connected) {
            $versionRes = $this->coolify->getVersion();
            if ($versionRes['success'] ?? false) {
                $apiVersion = $this->formatCoolifyVersionLabel($versionRes['data'] ?? null);
            }
            $healthRes = $this->coolify->getHealth();
            if ($healthRes['success'] ?? false) {
                $systemHealth = $healthRes['data'] ?? $healthRes['message'] ?? null;
                $systemHealthOk = true;
            }
        }

        $recentDeployments = [];
        $failedDeployments = [];
        $failedCount = 0;
        if ($connected) {
            $allDeployments = $this->coolifyList($this->coolify->listDeployments());
            $failedDeployments = array_values(array_filter(
                $allDeployments,
                fn (array $d) => in_array(
                    strtolower((string) ($d['status'] ?? '')),
                    ['failed', 'error', 'cancelled'],
                    true
                )
            ));
            $failedCount = count($failedDeployments);
            $failedDeployments = array_slice($failedDeployments, 0, 5);
            $recentDeployments = array_slice($allDeployments, 0, 8);
        }

        $activityLogs = [];
        try {
            $activityLogs = CoolifyActivityLog::with('user')->latest()->limit(12)->get();
        } catch (\Throwable) {
            // migration may not be run
        }

        $catalogEnabledCount = count($this->catalog->getCatalog(true));

        $localStats = [
            'wordpress_sites' => $this->safeModelCount(CoolifyWordpressSite::class),
            'catalog_items' => $this->safeModelCount(CoolifyCatalogItem::class),
            'catalog_enabled' => $catalogEnabledCount,
            'client_projects' => $this->safeModelCount(ClientCoolifyProject::class),
            'snapshot_schedules' => $this->safeModelCount(CoolifySnapshotSchedule::class),
            'snapshot_schedules_enabled' => $this->safeQuery(fn () => CoolifySnapshotSchedule::where('enabled', true)->count()),
            'activity_today' => $this->safeActivityTodayCount(),
        ];

        $apiWidgets = [
            ['key' => 'servers', 'label' => 'السيرفرات', 'desc' => 'عقد الاستضافة المتصلة', 'route' => 'admin.coolify.servers.index', 'icon' => 'fe-server', 'accent' => 'primary'],
            ['key' => 'projects', 'label' => 'المشاريع', 'desc' => 'بيئات العمل والفرق', 'route' => 'admin.coolify.projects.index', 'icon' => 'fe-layers', 'accent' => 'info'],
            ['key' => 'applications', 'label' => 'التطبيقات', 'desc' => 'تطبيقات ونشر Git', 'route' => 'admin.coolify.applications.index', 'icon' => 'fe-box', 'accent' => 'success'],
            ['key' => 'databases', 'label' => 'قواعد البيانات', 'desc' => 'MySQL · Postgres · Redis', 'route' => 'admin.coolify.databases.index', 'icon' => 'fe-database', 'accent' => 'warning'],
            ['key' => 'services', 'label' => 'الخدمات', 'desc' => 'حاويات وخدمات Docker', 'route' => 'admin.coolify.services.index', 'icon' => 'fe-grid', 'accent' => 'secondary'],
            ['key' => 'deployments', 'label' => 'النشرات', 'desc' => 'سجل النشر والحالة', 'route' => 'admin.coolify.deployments.index', 'icon' => 'fe-upload-cloud', 'accent' => 'danger'],
        ];

        $panelWidgets = [
            ['label' => 'كتالوج الموارد', 'count' => $catalogEnabledCount, 'route' => 'admin.coolify.catalog.index', 'icon' => 'fe-package', 'accent' => 'info', 'desc' => 'تثبيت ديناميكي من Coolify'],
            ['label' => 'مركز النسخ', 'count' => null, 'route' => 'admin.coolify.backups.index', 'icon' => 'fe-hard-drive', 'accent' => 'warning', 'desc' => 'قواعد + لقطات'],
            ['label' => 'جداول اللقطات', 'count' => $localStats['snapshot_schedules'], 'route' => 'admin.coolify.backups.schedules.index', 'icon' => 'fe-calendar', 'accent' => 'info', 'desc' => ($localStats['snapshot_schedules_enabled'] ?? 0).' جدول مفعّل'],
            ['label' => 'مركز العمليات', 'count' => null, 'route' => 'admin.coolify.operations.index', 'icon' => 'fe-activity', 'accent' => 'danger', 'desc' => 'تنبيهات وفحص'],
            ['label' => 'مواقع WordPress', 'count' => $localStats['wordpress_sites'], 'route' => 'admin.coolify.wordpress-sites.index', 'icon' => 'fab fa-wordpress', 'accent' => 'primary', 'desc' => 'توفير وإدارة'],
            ['label' => 'مشاريع العملاء', 'count' => $localStats['client_projects'], 'route' => 'admin.coolify.teams.index', 'icon' => 'fe-users', 'accent' => 'success', 'desc' => 'ربط بالعملاء'],
            ['label' => 'كل الموارد', 'count' => null, 'route' => 'admin.coolify.resources.index', 'icon' => 'fe-list', 'accent' => 'secondary', 'desc' => 'عرض موحّد'],
            ['label' => 'جاهزية الاستضافة', 'count' => null, 'route' => 'admin.coolify.readiness.index', 'icon' => 'fe-check-circle', 'accent' => 'success', 'desc' => 'فحص المتطلبات'],
        ];

        $integrationWidgets = [
            ['label' => 'Hetzner Cloud', 'count' => null, 'route' => 'admin.coolify.hetzner.index', 'icon' => 'fe-cloud', 'accent' => 'primary', 'desc' => 'إنشاء سيرفرات'],
            ['label' => 'GitHub Apps', 'count' => null, 'route' => 'admin.coolify.github-apps.index', 'icon' => 'fab fa-github', 'accent' => 'secondary', 'desc' => 'ربط المستودعات'],
            ['label' => 'Cloud Tokens', 'count' => null, 'route' => 'admin.coolify.cloud-tokens.index', 'icon' => 'fe-key', 'accent' => 'info', 'desc' => 'مفاتيح API'],
            ['label' => 'المفاتيح الخاصة', 'count' => null, 'route' => 'admin.coolify.private-keys.index', 'icon' => 'fe-lock', 'accent' => 'warning', 'desc' => 'SSH keys'],
            ['label' => 'إعدادات Coolify', 'count' => null, 'route' => 'admin.coolify.settings.index', 'icon' => 'fe-settings', 'accent' => 'teal', 'desc' => 'API · SSH · S3'],
            ['label' => 'النظام', 'count' => null, 'route' => 'admin.coolify.system.index', 'icon' => 'fe-cpu', 'accent' => 'secondary', 'desc' => 'إصدار وصحة'],
        ];

        $quickActions = [
            ['label' => 'معالج لقطة', 'route' => 'admin.coolify.backups.projects.wizard', 'icon' => 'fe-camera', 'class' => 'btn-outline-info'],
            ['label' => 'كتالوج الموارد', 'route' => 'admin.coolify.catalog.index', 'icon' => 'fe-package', 'class' => 'btn-primary'],
            ['label' => 'مركز العمليات', 'route' => 'admin.coolify.operations.index', 'icon' => 'fe-activity', 'class' => 'btn-outline-warning'],
            ['label' => 'جداول اللقطات', 'route' => 'admin.coolify.backups.schedules.index', 'icon' => 'fe-calendar', 'class' => 'btn-outline-secondary'],
            ['label' => 'Hetzner', 'route' => 'admin.coolify.hetzner.index', 'icon' => 'fe-cloud', 'class' => 'btn-outline-primary'],
            ['label' => 'الإعدادات', 'route' => 'admin.coolify.settings.index', 'icon' => 'fe-settings', 'class' => 'btn-outline-primary'],
        ];

        $apiResourceTotal = ($stats['servers'] ?? 0)
            + ($stats['projects'] ?? 0)
            + ($stats['applications'] ?? 0)
            + ($stats['databases'] ?? 0)
            + ($stats['services'] ?? 0)
            + ($stats['deployments'] ?? 0);
        $apiListBlocked = $connected && $apiResourceTotal === 0 && ! empty($stats['api_errors'] ?? []);
        $apiListEmpty = $connected && $apiResourceTotal === 0 && empty($stats['api_errors'] ?? []);

        return view('admin.coolify.overview', compact(
            'stats',
            'configured',
            'connected',
            'apiListBlocked',
            'apiListEmpty',
            'apiResourceTotal',
            'recentDeployments',
            'failedDeployments',
            'failedCount',
            'activityLogs',
            'localStats',
            'apiWidgets',
            'panelWidgets',
            'integrationWidgets',
            'quickActions',
            'apiVersion',
            'systemHealth',
            'systemHealthOk',
        ));
    }

    protected function formatCoolifyVersionLabel(mixed $data): ?string
    {
        if (is_string($data) && $data !== '') {
            return $data;
        }
        if (! is_array($data)) {
            return null;
        }
        foreach (['version', 'coolify_version'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }
        if (! empty($data['raw']) && is_string($data['raw'])) {
            return $data['raw'];
        }

        return null;
    }

    protected function safeModelCount(string $modelClass): int
    {
        try {
            return $modelClass::query()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function safeActivityTodayCount(): int
    {
        try {
            return CoolifyActivityLog::query()->whereDate('created_at', today())->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function safeQuery(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->coolify->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى حفظ عنوان API ورمز التوكن من صفحة إعدادات Coolify أولاً',
            ]);
        }

        $health = $this->coolify->getHealth();
        $version = $this->coolify->getVersion();

        if ($health['success'] || $version['success']) {
            $this->coolify->clearDashboardCache();

            return response()->json([
                'success' => true,
                'message' => 'تم الاتصال بنجاح بـ Coolify',
                'health' => $health['data'] ?? null,
                'version' => $version['data'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $health['message'] ?? $version['message'] ?? 'فشل الاتصال',
        ]);
    }

    public function testSsh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => 'required|string|max:255',
            'ssh_private_key' => 'nullable|string|max:20000',
        ]);

        // يختبر الإعدادات المحفوظة في قاعدة البيانات — لا مسار الحقل قبل الحفظ.
        $inlineKey = trim((string) ($validated['ssh_private_key'] ?? ''));

        $result = $this->ssh->testConnection(
            $validated['host'],
            $inlineKey !== '' ? $inlineKey : null
        );

        if (! ($result['success'] ?? false)) {
            $result['diagnostics'] = $result['diagnostics']
                ?? $this->ssh->getSshDiagnostics();
        }

        return response()->json($result);
    }

    public function testTerminalBridge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'terminal_bridge_enabled' => 'nullable|boolean',
            'terminal_bridge_url' => 'nullable|url|max:500',
        ]);

        $bridge = $this->settings->getTerminalBridgeConfig();

        $enabled = array_key_exists('terminal_bridge_enabled', $validated)
            ? filter_var($validated['terminal_bridge_enabled'], FILTER_VALIDATE_BOOLEAN)
            : (bool) ($bridge['enabled'] ?? false);

        if (! $enabled) {
            return response()->json([
                'success' => false,
                'message' => 'فعّل «تفعيل Terminal في لوحة مواقع WordPress» ثم اضغط «حفظ الإعدادات» قبل استخدام التبويب في المواقع.',
            ]);
        }

        $url = isset($validated['terminal_bridge_url']) && trim((string) $validated['terminal_bridge_url']) !== ''
            ? rtrim(trim((string) $validated['terminal_bridge_url']), '/')
            : rtrim((string) ($bridge['url'] ?? ''), '/');

        if ($url === '') {
            return response()->json([
                'success' => false,
                'message' => 'أدخل عنوان HTTP للجسر (مثال: http://127.0.0.1:3099).',
            ]);
        }

        $inlineSecret = trim((string) $request->input('terminal_bridge_secret', ''));
        $secretReady = ($bridge['secret_configured'] ?? false) || $inlineSecret !== '';

        try {
            $response = Http::timeout(8)->get($url.'/health');
            if ($response->successful() && ($response->json('ok') === true || $response->json('ok') === 1)) {
                $message = 'الجسر يعمل: '.$url.'/health';
                if (! ($bridge['enabled'] ?? false)) {
                    $message .= ' — لم يُحفظ التفعيل بعد: اضغط «حفظ الإعدادات».';
                } elseif (! ($bridge['secret_configured'] ?? false)) {
                    $message .= $secretReady
                        ? ' — احفظ الإعدادات لتثبيت السر.'
                        : ' — أضف سر JWT واحفظ لتشغيل Terminal في المواقع.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'استجابة غير متوقعة من الجسر (HTTP '.$response->status().').',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذّر الاتصال بـ '.$url.' — '.$e->getMessage().' تأكد أن خدمة terminal-bridge تعمل (مثال: cd services/terminal-bridge && npm start).',
            ]);
        }
    }

    public function discoverS3(): JsonResponse
    {
        if (! $this->coolify->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'اضبط عنوان API والتوكن أولاً',
            ]);
        }

        $synced = $this->settings->syncSnapshotStorageFromCoolify($this->coolify);
        $storages = $this->coolify->listS3Storages();
        $uuid = $this->settings->getCoolifyS3StorageUuid();

        if ($uuid === '') {
            $discovered = $this->coolify->discoverS3StorageUuidFromBackups();
            if ($discovered) {
                $this->settings->updateSettings(['coolify_s3_storage_uuid' => $discovered]);
                $uuid = $discovered;
                $synced[] = 'coolify_s3_storage_uuid';
            }
        }

        $coolifyStoragesUrl = rtrim($this->settings->getConnectionConfig()['api_url'] ?? '', '/').'/storages';

        $message = $uuid !== ''
            ? 'تم العثور على UUID وحفظه.'
            : 'Coolify 4 لا يعرض قائمة S3 عبر API. انسخ UUID يدوياً من Storages في Coolify، أو أنشئ نسخة DB واحدة مع S3 ثم أعد الجلب. بدون UUID: لقطات التطبيقات تعمل؛ قواعد البيانات تُحفظ كـ manifest على S3 فقط.';

        return response()->json([
            'success' => true,
            'found' => $uuid !== '',
            'message' => $message,
            'uuid' => $uuid,
            'storages' => $storages,
            'synced' => $synced,
            'coolify_storages_url' => $coolifyStoragesUrl,
            'readiness' => $this->settings->getSnapshotReadiness(),
        ]);
    }
}
