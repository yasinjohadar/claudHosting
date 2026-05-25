<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Admin\Coolify\Concerns\HandlesCoolifyResponses;
use App\Http\Controllers\Controller;
use App\Jobs\ProvisionWordpressSiteJob;
use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\WordpressCloudflareService;
use App\Services\Coolify\WordpressManagementService;
use App\Services\Coolify\WordpressSiteProvisioningService;
use App\Services\CoolifyApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CoolifyWordpressSiteController extends Controller
{
    use HandlesCoolifyResponses;

    public function __construct(
        protected CoolifyApiService $coolify,
        protected CoolifySettingsService $settings,
        protected WordpressSiteProvisioningService $provisioning,
        protected WordpressManagementService $wpManagement,
        protected WordpressCloudflareService $wordpressCloudflare
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $sites = CoolifyWordpressSite::with('creator')->latest()->paginate(20);
        $readiness = $this->settings->getWordpressReadiness();

        return view('admin.coolify.wordpress-sites.index', compact('sites', 'readiness'));
    }

    public function create(Request $request)
    {
        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('يرجى ضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $readiness = $this->settings->getWordpressReadiness();
        if (! $readiness['ready']) {
            return $this->coolifyRedirectError(
                'اضبط النطاق الأساسي والسيرفر الافتراضي في إعدادات Coolify.',
                'admin.coolify.settings.index'
            );
        }

        $projects = $this->coolifyList($this->coolify->listProjects());
        $servers = $this->coolifyList($this->coolify->listServers());

        return view('admin.coolify.wordpress-sites.create', [
            'projects' => $projects,
            'servers' => $servers,
            'baseDomain' => $this->settings->getWordpressBaseDomain(),
            'defaultServer' => $this->settings->getWordpressDefaultServerUuid(),
            'sharedProject' => $this->settings->getWordpressSharedProjectUuid(),
            'defaultEnvironment' => $this->settings->getWordpressDefaultEnvironment(),
            'defaultCloudflareEnabled' => $this->settings->getWordpressCloudflareEnabled(),
            'defaultSecurityPreset' => $this->settings->getWordpressSecurityPreset(),
            'securityPresets' => $this->settings->getWordpressSecurityPresetOptions(),
            'step' => max(1, min(3, (int) $request->get('step', 1))),
            'prefill' => $request->only(['display_name', 'slug', 'project_mode', 'project_uuid', 'server_uuid', 'environment_name', 'description', 'cloudflare_enabled', 'security_preset']),
        ]);
    }

    public function store(Request $request)
    {
        $readiness = $this->settings->getWordpressReadiness();
        if (! $readiness['ready']) {
            return $this->coolifyRedirectError('إعدادات WordPress غير مكتملة.', 'admin.coolify.settings.index');
        }

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', Rule::unique('coolify_wordpress_sites', 'slug')],
            'project_mode' => 'required|in:new,shared',
            'project_uuid' => 'nullable|string|required_if:project_mode,shared',
            'server_uuid' => 'required|string',
            'environment_name' => 'nullable|string|max:64',
            'description' => 'nullable|string|max:2000',
            'cloudflare_enabled' => 'nullable|boolean',
            'security_preset' => 'nullable|string|in:basic,performance,strict',
        ], [
            'slug.min' => 'المعرّف الفرعي: 3 أحرف على الأقل (مطابق لمتطلبات Coolify).',
            'slug.regex' => 'المعرّف الفرعي: أحرف إنجليزية صغيرة وأرقام وشرطة فقط.',
            'project_uuid.required_if' => 'اختر المشروع المشترك.',
        ]);

        $publicUrl = $this->settings->buildWordpressPublicUrl($validated['slug']);

        $projectUuid = null;
        if ($validated['project_mode'] === 'shared') {
            $projectUuid = $validated['project_uuid'] ?: $this->settings->getWordpressSharedProjectUuid();
            if ($projectUuid === '') {
                return back()->withInput()->with('error', 'حدّد مشروعاً مشتركاً في المعالج أو في إعدادات Coolify.');
            }
        }

        $preflight = $this->provisioning->preflight(
            $validated['server_uuid'],
            $validated['project_mode'],
            $projectUuid
        );

        if (! ($preflight['ok'] ?? false)) {
            return back()->withInput()->with('error', $preflight['message'] ?? 'فشل التحقق من Coolify');
        }

        $cloudflareEnabled = $request->has('cloudflare_enabled')
            ? $request->boolean('cloudflare_enabled')
            : $this->settings->getWordpressCloudflareEnabled();

        $securityPreset = $validated['security_preset'] ?? $this->settings->getWordpressSecurityPreset();

        $site = CoolifyWordpressSite::create([
            'display_name' => $validated['display_name'],
            'slug' => $validated['slug'],
            'project_mode' => $validated['project_mode'],
            'project_uuid' => $projectUuid,
            'server_uuid' => $validated['server_uuid'],
            'environment_name' => $validated['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'public_url' => $publicUrl,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'metadata' => [
                'cloudflare_enabled' => $cloudflareEnabled,
                'security_preset' => $securityPreset,
            ],
            'created_by' => Auth::id(),
        ]);

        ProvisionWordpressSiteJob::dispatch($site->id);

        return redirect()
            ->route('admin.coolify.wordpress-sites.show', $site->uuid)
            ->with('success', 'تم بدء إنشاء الموقع في الخلفية. تأكد من تشغيل queue worker.');
    }

    public function show(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        if ($site->service_uuid && $this->coolify->isConfigured()) {
            try {
                $this->provisioning->syncSiteFromCoolify($site);
                $site->refresh();
            } catch (\Throwable) {
                // keep cached data
            }
        }

        $wpManagementState = $this->wpManagement->getManagementState($site);
        $wpCanManage = [
            'ok' => $wpManagementState['execute_ready'],
            'message' => $wpManagementState['message'],
        ];
        $wpInfo = ($site->metadata ?? [])['wp_info'] ?? null;

        return view('admin.coolify.wordpress-sites.show', compact(
            'site',
            'uuid',
            'wpCanManage',
            'wpManagementState',
            'wpInfo'
        ));
    }

    public function wpInfo(string $uuid): JsonResponse
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        if (request()->boolean('refresh')) {
            $queued = $this->wpManagement->executeAction($site, 'refresh_info', [], Auth::id());
            if ($queued['async'] ?? false) {
                return response()->json([
                    'success' => true,
                    'async' => true,
                    'job_id' => $queued['job_id'] ?? null,
                    'message' => $queued['message'] ?? 'جاري التحديث في الخلفية…',
                    'can_manage' => $this->wpManagement->canManage($site->fresh()),
                ]);
            }

            return response()->json([
                'success' => $queued['success'] ?? false,
                'data' => $queued['data'] ?? null,
                'message' => $queued['message'] ?? null,
                'can_manage' => $this->wpManagement->canManage($site->fresh()),
            ]);
        }

        $result = $this->wpManagement->getSiteInfo($site, false);

        return response()->json([
            'success' => $result['success'] ?? false,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? null,
            'can_manage' => $this->wpManagement->canManage($site->fresh()),
        ]);
    }

    public function wpAction(Request $request, string $uuid): JsonResponse
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'action' => 'required|string|max:64',
            'slug' => 'nullable|string|max:128',
            'login' => 'nullable|string|max:128',
            'password' => 'nullable|string|max:128',
            'command' => 'nullable|string|max:512',
            'role' => 'nullable|string|max:32',
            'email' => 'nullable|email|max:191',
            'user_id' => 'nullable|string|max:16',
            'post_id' => 'nullable|string|max:16',
            'option' => 'nullable|string|max:128',
            'value' => 'nullable|string|max:500',
            'old' => 'nullable|string|max:255',
            'new' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:200',
            'post_type' => 'nullable|string|max:32',
            'status' => 'nullable|string|max:32',
            'hook' => 'nullable|string|max:128',
            'activate' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
            'confirm_dangerous' => 'nullable|boolean',
            '_confirmed' => 'nullable|boolean',
        ]);

        $params = array_filter([
            'slug' => $validated['slug'] ?? null,
            'login' => $validated['login'] ?? null,
            'password' => $validated['password'] ?? null,
            'command' => $validated['command'] ?? null,
            'role' => $validated['role'] ?? null,
            'email' => $validated['email'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'post_id' => $validated['post_id'] ?? null,
            'option' => $validated['option'] ?? null,
            'value' => $validated['value'] ?? null,
            'old' => $validated['old'] ?? null,
            'new' => $validated['new'] ?? null,
            'title' => $validated['title'] ?? null,
            'post_type' => $validated['post_type'] ?? null,
            'status' => $validated['status'] ?? null,
            'hook' => $validated['hook'] ?? null,
            'activate' => $request->boolean('activate'),
            'dry_run' => $request->boolean('dry_run'),
            'confirm_dangerous' => $request->boolean('confirm_dangerous'),
            '_confirmed' => $request->boolean('_confirmed'),
        ], fn ($v) => $v !== null && $v !== '' && $v !== false);

        $result = $this->wpManagement->executeAction(
            $site,
            $validated['action'],
            $params,
            Auth::id()
        );

        if ($result['async'] ?? false) {
            return response()->json([
                'success' => true,
                'async' => true,
                'job_id' => $result['job_id'] ?? null,
                'message' => $result['message'] ?? 'قيد التنفيذ',
            ]);
        }

        $response = [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? null,
            'output' => $result['output'] ?? null,
            'data' => $result['data'] ?? null,
        ];

        if (! empty($result['generated_password'])) {
            $response['generated_password'] = $result['generated_password'];
            $response['login'] = $result['login'] ?? null;
        }

        return response()->json($response);
    }

    public function wpJob(string $uuid): JsonResponse
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();
        $status = $this->wpManagement->getJobStatus($site);

        return response()->json([
            'success' => true,
            'job' => $status['job'] ?? null,
        ]);
    }

    public function status(string $uuid): JsonResponse
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();
        $metadata = $site->metadata ?? [];

        $payload = [
            'success' => true,
            'status' => $site->status,
            'local_status' => $site->status,
            'public_url' => $site->public_url,
            'admin_url' => $site->admin_url,
            'error_message' => $site->error_message,
            'service_uuid' => $site->service_uuid,
            'project_uuid' => $site->project_uuid,
            'provisioning_step' => $metadata['provisioning_step'] ?? null,
            'provision_log' => $metadata['provision_log'] ?? [],
            'coolify_status' => $metadata['coolify_service_status'] ?? null,
            'components' => $metadata['coolify_components'] ?? [],
            'is_healthy' => false,
            'container_logs' => [],
            'queue_stale_hint' => null,
            'updated_at' => $site->updated_at?->toIso8601String(),
            'cloudflare' => $metadata['cloudflare'] ?? null,
            'cloudflare_enabled' => filter_var($metadata['cloudflare_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'domain_warning' => $metadata['domain_warning'] ?? null,
            'wp_info' => $metadata['wp_info'] ?? null,
            'wp_job' => $metadata['wp_job'] ?? null,
            'wp_management_log' => $metadata['wp_management_log'] ?? [],
            'coolify_default_url' => $metadata['coolify_default_url'] ?? null,
            'coolify_default_admin_url' => $metadata['coolify_default_admin_url'] ?? null,
            'custom_public_url' => $site->public_url,
        ];

        if ($this->coolify->isConfigured() && filled($site->service_uuid)) {
            $response = $this->coolify->getService($site->service_uuid);
            if ($response['success'] ?? false) {
                $service = is_array($response['data'] ?? null) ? $response['data'] : [];
                $components = $this->coolify->extractServiceComponentStatuses($service);
                $coolifyStatus = strtolower((string) ($service['status'] ?? ''));
                $coolifyUrls = $this->coolify->resolveCoolifyUrlMetadata($service);

                $metadata = array_merge($metadata, [
                    'coolify_service_status' => $coolifyStatus,
                    'coolify_components' => $components,
                ], $coolifyUrls);
                $site->update(['metadata' => $metadata]);

                $payload['coolify_default_url'] = $coolifyUrls['coolify_default_url'];
                $payload['coolify_default_admin_url'] = $coolifyUrls['coolify_default_admin_url'];

                $payload['coolify_status'] = $coolifyStatus;
                $payload['components'] = $components;
                $isHealthy = $this->coolify->isServiceStackHealthy($service);
                $payload['is_healthy'] = $isHealthy;
                $payload['container_logs'] = $this->coolify->fetchServiceApplicationLogs($service, 60);

                if ($isHealthy && in_array($site->status, ['provisioning', 'pending'], true)) {
                    try {
                        $this->provisioning->syncSiteFromCoolify($site);
                        $site->refresh();
                        $payload['status'] = $site->status;
                        $payload['local_status'] = $site->status;
                        $metadata = $site->metadata ?? [];
                        $payload['public_url'] = $site->public_url;
                        $payload['admin_url'] = $site->admin_url;
                        $payload['custom_public_url'] = $site->public_url;
                        $payload['coolify_default_url'] = $metadata['coolify_default_url'] ?? $payload['coolify_default_url'];
                        $payload['coolify_default_admin_url'] = $metadata['coolify_default_admin_url'] ?? $payload['coolify_default_admin_url'];
                    } catch (\Throwable) {
                        // non-fatal
                    }
                }

                $metadata['coolify_stack_healthy'] = $isHealthy;
            }
        }

        $payload['provisioning_step'] = $metadata['provisioning_step'] ?? $payload['provisioning_step'];
        $payload['provision_log'] = $metadata['provision_log'] ?? $payload['provision_log'];
        $payload['queue_stale_hint'] = $this->queueStaleHint($site);

        return response()->json($payload);
    }

    protected function queueStaleHint(CoolifyWordpressSite $site): ?string
    {
        if (! in_array($site->status, ['pending', 'provisioning'], true)) {
            return null;
        }

        $log = $site->metadata['provision_log'] ?? [];
        $updatedMinutesAgo = $site->updated_at?->diffInMinutes(now()) ?? 0;

        if ($log === [] && $updatedMinutesAgo >= 2) {
            return 'يبدو أن الطابور متوقف. شغّل: php artisan queue:work --queue=coolify-provision';
        }

        if ($updatedMinutesAgo >= 5 && in_array($site->metadata['provisioning_step'] ?? '', ['start', ''], true)) {
            return 'لم يبدأ الـ job بعد. تحقق من queue worker.';
        }

        return null;
    }

    public function edit(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();
        $baseDomain = $this->settings->getWordpressBaseDomain();

        return view('admin.coolify.wordpress-sites.edit', compact('site', 'uuid', 'baseDomain'));
    }

    public function update(Request $request, string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/',
                Rule::unique('coolify_wordpress_sites', 'slug')->ignore($site->id),
            ],
            'description' => 'nullable|string|max:2000',
        ]);

        $slugChanged = $validated['slug'] !== $site->slug;
        $publicUrl = $this->settings->buildWordpressPublicUrl($validated['slug']);

        $site->update([
            'display_name' => $validated['display_name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'public_url' => $publicUrl,
            'admin_url' => rtrim($publicUrl, '/').'/wp-admin',
        ]);

        if ($site->service_uuid && $this->coolify->isConfigured()) {
            $nameResponse = $this->coolify->updateService($site->service_uuid, [
                'name' => $validated['slug'],
                'description' => $validated['description'] ?? null,
            ]);
            if (! ($nameResponse['success'] ?? false)) {
                return back()->withInput()->with('error', $nameResponse['message'] ?? 'فشل تحديث الخدمة على Coolify');
            }

            if ($slugChanged) {
                try {
                    $this->provisioning->updateSiteDomain($site, $publicUrl);
                } catch (\Throwable $e) {
                    return back()->withInput()->with('error', $e->getMessage());
                }
            } else {
                try {
                    $this->provisioning->syncSiteFromCoolify($site);
                } catch (\Throwable) {
                    // non-fatal
                }
            }
        }

        if ($site->project_uuid && $site->project_mode === 'new') {
            $this->coolify->updateProject($site->project_uuid, ['name' => $validated['display_name']]);
        }

        return redirect()
            ->route('admin.coolify.wordpress-sites.show', $site->uuid)
            ->with('success', 'تم حفظ التعديلات');
    }

    public function syncCloudflare(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        $result = $this->wordpressCloudflare->syncFromExistingDns($site);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'فشل مزامنة Cloudflare');
        }

        $meta = $result['metadata'] ?? [];
        $fqdn = $meta['fqdn'] ?? $site->slug;

        return redirect()
            ->route('admin.coolify.wordpress-sites.show', $site->uuid)
            ->with('success', 'تمت مزامنة Cloudflare من السجل الحالي: '.$fqdn);
    }

    public function retry(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        if (! in_array($site->status, ['failed', 'pending'], true)) {
            return back()->with('error', 'إعادة المحاولة متاحة فقط للمواقع الفاشلة أو المعلقة.');
        }

        $updates = [
            'status' => 'pending',
            'error_message' => null,
        ];

        // إن وُجدت الخدمة على Coolify نُعيد التشغيل فقط دون إنشاء خدمة مكررة
        if ($site->service_uuid === null || $site->service_uuid === '') {
            $updates['service_uuid'] = null;
        }

        $site->update($updates);

        ProvisionWordpressSiteJob::dispatch($site->id);

        $message = filled($site->service_uuid)
            ? 'تم إرسال إعادة تشغيل الخدمة إلى الطابور (نفس الخدمة على Coolify).'
            : 'تم إرسال إعادة الإنشاء إلى الطابور.';

        return redirect()
            ->route('admin.coolify.wordpress-sites.show', $site->uuid)
            ->with('success', $message);
    }

    public function restartCoolify(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        if (! $site->service_uuid) {
            return back()->with('error', 'لا توجد خدمة على Coolify لإعادة تشغيلها.');
        }

        if (! $this->coolify->isConfigured()) {
            return $this->coolifyRedirectError('اضبط إعدادات Coolify أولاً.', 'admin.coolify.settings.index');
        }

        $response = $this->coolify->restartService($site->service_uuid);
        if (! ($response['success'] ?? false)) {
            return back()->with('error', $response['message'] ?? 'فشل طلب إعادة التشغيل على Coolify');
        }

        $site->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        ProvisionWordpressSiteJob::dispatch($site->id);

        return redirect()
            ->route('admin.coolify.wordpress-sites.show', $site->uuid)
            ->with('success', 'تم إرسال إعادة التشغيل على Coolify إلى الطابور. انتظر دقيقتين ثم حدّث الصفحة.');
    }

    public function destroy(string $uuid)
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();

        $hadService = (bool) $site->service_uuid;
        if ($hadService) {
            $this->coolify->deleteService($site->service_uuid);
        }

        $site->delete();

        return redirect()
            ->route('admin.coolify.wordpress-sites.index')
            ->with('success', 'تم حذف السجل المحلي'.($hadService ? ' وطلب حذف الخدمة من Coolify' : ''));
    }
}
