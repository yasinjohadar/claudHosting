<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Models\CoolifyActivityLog;
use App\Models\AppStorageConfig;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\CoolifySnapshotStorageService;
use App\Services\Coolify\CoolifySshExecutor;
use App\Services\CloudflareApiService;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoolifySettingsController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CloudflareApiService $cloudflare,
        protected CoolifySettingsService $settings,
        protected CoolifySshExecutor $ssh,
        protected CoolifySnapshotStorageService $snapshotStorage
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
            'wordpress_management_queue' => 'nullable|string|max:64|regex:/^[a-zA-Z0-9_\-]+$/',
            'wordpress_redis_enabled' => 'nullable|boolean',
            'wordpress_redis_host' => 'nullable|string|max:255',
            'wordpress_redis_port' => 'nullable|integer|min:1|max:65535',
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
            'wordpress_management_queue' => $validated['wordpress_management_queue'] ?? null,
            'wordpress_redis_enabled' => $request->boolean('wordpress_redis_enabled', false),
            'wordpress_redis_host' => $validated['wordpress_redis_host'] ?? null,
            'wordpress_redis_port' => $validated['wordpress_redis_port'] ?? null,
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

    public function overview()
    {
        $stats = $this->coolify->getDashboardStats();
        $configured = $this->coolify->isConfigured();
        $connected = $configured && ($stats['connected'] ?? false);

        $recentDeployments = [];
        $failedDeployments = [];
        if ($connected) {
            $allDeployments = $this->coolifyList($this->coolify->listDeployments());
            $recentDeployments = array_slice($allDeployments, 0, 5);
            $failedDeployments = array_values(array_filter(
                $allDeployments,
                fn (array $d) => in_array(
                    strtolower((string) ($d['status'] ?? '')),
                    ['failed', 'error', 'cancelled'],
                    true
                )
            ));
            $failedDeployments = array_slice($failedDeployments, 0, 5);
        }

        $activityLogs = [];
        try {
            $activityLogs = CoolifyActivityLog::with('user')->latest()->limit(10)->get();
        } catch (\Throwable) {
            // migration may not be run
        }

        return view('admin.coolify.overview', compact(
            'stats', 'configured', 'connected', 'recentDeployments', 'failedDeployments', 'activityLogs'
        ));
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
