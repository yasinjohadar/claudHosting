<?php

namespace App\Services\Coolify;

use App\Jobs\ProvisionWordpressSiteJob;
use App\Models\CoolifyWordpressSite;
use App\Models\PackageOrderRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WhmcsHostProvisioningService
{
    public function __construct(protected CoolifySettingsService $settings) {}

    /**
     * @return array{success: bool, message: string, site?: CoolifyWordpressSite}
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
                'whmcs_order_id' => $order->whmcs_order_id,
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
     * @return array<string, mixed>|null
     */
    public function resolveProvisionConfig(Product $product): ?array
    {
        $raw = $product->coolify_provision;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw) || empty($raw['enabled'])) {
            $mapped = $this->mapFromConfigOptions($product);
            if ($mapped === null) {
                return null;
            }
            $raw = $mapped;
        }

        $serverUuid = trim((string) ($raw['server_uuid'] ?? $this->settings->getWordpressDefaultServerUuid()));
        if ($serverUuid === '') {
            return null;
        }

        return [
            'enabled' => true,
            'server_uuid' => $serverUuid,
            'project_mode' => $raw['project_mode'] ?? 'shared',
            'project_uuid' => $raw['project_uuid'] ?? $this->settings->getWordpressSharedProjectUuid(),
            'environment_name' => $raw['environment_name'] ?? $this->settings->getWordpressDefaultEnvironment(),
            'slug_prefix' => $raw['slug_prefix'] ?? '',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function mapFromConfigOptions(Product $product): ?array
    {
        $map = config('coolify.whmcs_provision_map', []);
        if ($map === []) {
            return null;
        }

        $server = trim((string) ($product->{$map['server_uuid'] ?? 'configoption1'} ?? ''));
        if ($server === '') {
            return null;
        }

        return [
            'enabled' => true,
            'server_uuid' => $server,
            'project_mode' => $product->{$map['project_mode'] ?? 'configoption2'} ?? 'shared',
            'project_uuid' => $product->{$map['project_uuid'] ?? 'configoption3'} ?? null,
            'environment_name' => $product->{$map['environment_name'] ?? 'configoption4'} ?? null,
            'slug_prefix' => $product->{$map['slug_prefix'] ?? 'configoption5'} ?? '',
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
