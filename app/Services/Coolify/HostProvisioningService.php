<?php

namespace App\Services\Coolify;

use App\Jobs\ProvisionWordpressSiteJob;
use App\Models\ClientCoolifyProject;
use App\Models\CoolifyWordpressSite;
use App\Models\PackageOrderRequest;
use App\Models\Product;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HostProvisioningService
{
    public function __construct(
        protected CoolifySettingsService $settings,
        protected CoolifyCatalogService $catalog,
        protected CoolifyApiService $coolify
    ) {}

    /**
     * @return array{success: bool, message: string, site?: CoolifyWordpressSite, resource_uuid?: string}
     */
    public function provisionFromOrder(PackageOrderRequest $order): array
    {
        $product = $order->product;
        if (! $product) {
            return ['success' => false, 'message' => 'المنتج غير موجود'];
        }

        $config = $this->resolveProvisionConfig($product);
        if ($config === null) {
            return ['success' => false, 'message' => 'المنتج غير مربوط بتزويد Coolify — اضبط coolify_provision في المنتج'];
        }

        $type = $config['provision_type'] ?? 'wordpress';
        if ($type === 'catalog') {
            return $this->provisionCatalogFromOrder($order, $config);
        }

        if ($order->coolify_wordpress_site_id) {
            $existing = CoolifyWordpressSite::find($order->coolify_wordpress_site_id);
            if ($existing && in_array($existing->status, ['provisioning', 'running'], true)) {
                return ['success' => false, 'message' => 'التزويد قيد التنفيذ أو مكتمل مسبقاً', 'site' => $existing];
            }
        }

        $slug = $this->buildSlug($order, $config);
        $readiness = app(CoolifyReadinessService::class)->run([
            'server_uuid' => $config['server_uuid'],
            'project_uuid' => $config['project_uuid'] ?? null,
        ]);
        if (! ($readiness['ready'] ?? false)) {
            return ['success' => false, 'message' => 'جاهزية الاستضافة غير مكتملة — راجع معالج الجاهزية'];
        }

        $projectMode = ($config['project_mode'] ?? 'shared') === 'new' ? 'new' : 'shared';
        $site = CoolifyWordpressSite::create([
            'display_name' => $order->name.' — '.$product->name,
            'slug' => $slug,
            'project_mode' => $projectMode,
            'project_uuid' => $projectMode === 'shared' ? ($config['project_uuid'] ?? null) : null,
            'server_uuid' => $config['server_uuid'],
            'environment_name' => $config['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'status' => 'pending',
            'description' => 'تزويد من طلب #'.$order->id,
            'metadata' => [
                'package_order_request_id' => $order->id,
                'product_id' => $product->id,
            ],
            'created_by' => Auth::id(),
        ]);

        $order->update([
            'coolify_wordpress_site_id' => $site->id,
            'provision_status' => 'provisioning',
        ]);

        ProvisionWordpressSiteJob::dispatch($site->id);

        return ['success' => true, 'message' => 'بدأ تزويد WordPress في الطابور', 'site' => $site];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{success: bool, message: string, resource_uuid?: string}
     */
    public function provisionCatalogFromOrder(PackageOrderRequest $order, array $config): array
    {
        if (! $this->coolify->isConfigured()) {
            return ['success' => false, 'message' => 'Coolify غير مضبوط'];
        }

        $slug = trim((string) ($config['catalog_slug'] ?? ''));
        if ($slug === '') {
            return ['success' => false, 'message' => 'catalog_slug مطلوب في coolify_provision'];
        }

        $item = $this->catalog->findBySlug($slug);
        if (! $item || ! $this->catalog->canInstall($item)) {
            return ['success' => false, 'message' => 'عنصر الكتالوج غير قابل للتثبيت: '.$slug];
        }

        $readiness = app(CoolifyReadinessService::class)->run([
            'server_uuid' => $config['server_uuid'],
            'project_uuid' => $config['project_uuid'] ?? null,
        ]);
        if (! ($readiness['ready'] ?? false)) {
            return ['success' => false, 'message' => 'جاهزية الاستضافة غير مكتملة'];
        }

        $projectUuid = $config['project_uuid'] ?? null;
        $projectName = null;
        if (($config['project_mode'] ?? 'shared') === 'new') {
            $projectName = 'order-'.$order->id.'-'.Str::slug($order->name, '-');
            $projectRes = $this->coolify->createProject(['name' => $projectName]);
            if (! ($projectRes['success'] ?? false)) {
                return ['success' => false, 'message' => $projectRes['message'] ?? 'فشل إنشاء المشروع'];
            }
            $project = is_array($projectRes['data'] ?? null) ? $projectRes['data'] : [];
            $projectUuid = (string) ($project['uuid'] ?? '');
        }

        if ($projectUuid === '') {
            return ['success' => false, 'message' => 'project_uuid مطلوب'];
        }

        $payload = [
            'project_uuid' => $projectUuid,
            'server_uuid' => $config['server_uuid'],
            'environment_name' => $config['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'name' => $order->name.' — '.($item['name_ar'] ?? $slug),
            'description' => 'تزويد طلب #'.$order->id,
        ];

        $category = $item['category'] ?? '';
        $coolifyKey = $item['coolify_key'] ?? '';
        if ($category === 'database') {
            $response = $this->coolify->createDatabase($coolifyKey, $payload);
        } elseif ($category === 'service' || (($item['category'] ?? '') === 'custom' && ($item['install_mode'] ?? '') === 'service')) {
            $response = $this->coolify->createService(array_merge($payload, ['type' => $coolifyKey]));
        } else {
            return ['success' => false, 'message' => 'نوع الكتالوج غير مدعوم للتزويد التلقائي'];
        }

        if (! ($response['success'] ?? false)) {
            return ['success' => false, 'message' => $response['message'] ?? 'فشل التثبيت'];
        }

        $created = $response['data'] ?? [];
        $resourceUuid = is_array($created) ? (string) ($created['uuid'] ?? '') : '';

        if ($order->user_id) {
            ClientCoolifyProject::updateOrCreate(
                ['project_uuid' => $projectUuid, 'user_id' => $order->user_id],
                ['project_name' => $projectName ?? null, 'coolify_team_id' => null]
            );
        }

        $order->update([
            'provision_status' => 'completed',
        ]);

        $this->coolify->clearDashboardCache();

        return [
            'success' => true,
            'message' => 'تم تثبيت '.$slug.' من الكتالوج',
            'resource_uuid' => $resourceUuid,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveProvisionConfig(Product $product): ?array
    {
        $raw = $product->coolify_provision;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw) || empty($raw['enabled'])) {
            return null;
        }

        $serverUuid = trim((string) ($raw['server_uuid'] ?? $this->settings->getWordpressDefaultServerUuid()));
        if ($serverUuid === '') {
            return null;
        }

        return [
            'enabled' => true,
            'provision_type' => $raw['provision_type'] ?? 'wordpress',
            'catalog_slug' => $raw['catalog_slug'] ?? null,
            'server_uuid' => $serverUuid,
            'project_mode' => $raw['project_mode'] ?? 'shared',
            'project_uuid' => $raw['project_uuid'] ?? $this->settings->getWordpressSharedProjectUuid(),
            'environment_name' => $raw['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'slug_prefix' => $raw['slug_prefix'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function buildSlug(PackageOrderRequest $order, array $config): string
    {
        $base = Str::slug(($config['slug_prefix'] ?? '').$order->name, '-');
        if ($base === '') {
            $base = 'order-'.$order->id;
        }

        return CoolifyWordpressSite::uniqueSlug(substr($base, 0, 50));
    }

    public function syncOrderProvisionStatus(PackageOrderRequest $order): void
    {
        $site = $order->coolifyWordpressSite;
        if (! $site) {
            return;
        }

        $order->update(['provision_status' => $site->status]);
    }
}
