<?php

namespace App\Services\CyberPanel;

use App\Models\Customer;
use App\Models\CyberPanelWebsite;
use App\Models\PackageOrderRequest;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CyberPanelWebsiteService
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelSettingsService $settings,
        protected CyberPanelSubscriptionBillingService $billing,
        protected CyberPanelWordpressService $wordpress
    ) {}

    /**
     * @return array{success: bool, message: string, synced?: int}
     */
    public function syncFromRemote(): array
    {
        $result = $this->api->listWebsites();
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل جلب المواقع من CyberPanel'];
        }

        $synced = 0;
        foreach ($result['websites'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $domain = $this->extractDomain($row);
            if ($domain === '') {
                continue;
            }

            $website = CyberPanelWebsite::updateOrCreate(
                ['domain' => $domain],
                [
                    'owner' => $this->extractOwner($row),
                    'email' => $this->extractEmail($row),
                    'package' => $this->extractPackage($row),
                    'php_version' => $this->extractPhp($row),
                    'status' => $this->resolveStatusFromRemote($row),
                    'metadata' => $row,
                ]
            );

            $this->billing->ensureSubscriptionDates($website);
            $synced++;
        }

        return ['success' => true, 'message' => "تمت مزامنة {$synced} موقعاً من CyberPanel", 'synced' => $synced];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, website?: CyberPanelWebsite}
     */
    public function createManual(array $data): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات CyberPanel غير مكتملة'];
        }

        $domain = strtolower(trim((string) ($data['domain'] ?? '')));
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        if (CyberPanelWebsite::where('domain', $domain)->exists()) {
            return ['success' => false, 'message' => 'النطاق مسجّل مسبقاً في النظام'];
        }

        $config = $this->settings->getConnectionConfig();
        $owner = trim((string) ($data['owner'] ?? $config['default_owner']));
        $package = trim((string) ($data['package'] ?? $config['default_package']));
        $php = trim((string) ($data['php_version'] ?? $config['default_php_version']));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['owner_password'] ?? Str::password(14));

        $create = $this->api->createWebsite([
            'domain' => $domain,
            'package' => $package,
            'email' => $email,
            'owner' => $owner,
            'php_version' => $php,
            'owner_password' => $password,
            'ssl' => (int) ($data['ssl'] ?? 0),
        ]);

        if (! ($create['success'] ?? false)) {
            return ['success' => false, 'message' => $create['message'] ?? 'فشل إنشاء الموقع في CyberPanel'];
        }

        $customerId = null;
        $userId = $data['user_id'] ?? null;
        if ($userId) {
            $user = User::with('customer')->find($userId);
            $customerId = $user?->customer?->id;
        }

        $meta = is_array($create['data'] ?? null) ? $create['data'] : [];

        $website = CyberPanelWebsite::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'domain' => $domain,
            'owner' => $owner,
            'email' => $email !== '' ? $email : null,
            'package' => $package,
            'php_version' => $php,
            'status' => 'active',
            'joined_at' => now(),
            'metadata' => array_merge($meta, ['owner_password_set' => true]),
        ]);

        $bootstrap = $this->bootstrapNewWebsiteSubscription($website);

        if (! empty($data['install_wordpress'])) {
            $this->wordpress->installOnWebsite($website, $data['wordpress'] ?? []);
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء الموقع — '.$bootstrap['message'],
            'website' => $website->fresh(),
        ];
    }

    /**
     * @return array{success: bool, message: string, website?: CyberPanelWebsite}
     */
    public function createFromOrder(PackageOrderRequest $order): array
    {
        $product = $order->product;
        if (! $product) {
            return ['success' => false, 'message' => 'المنتج غير موجود'];
        }

        if ($order->cyberpanel_website_id) {
            $existing = CyberPanelWebsite::find($order->cyberpanel_website_id);
            if ($existing && $existing->status !== 'terminated') {
                return ['success' => false, 'message' => 'يوجد موقع CyberPanel مرتبط بهذا الطلب', 'website' => $existing];
            }
        }

        $config = $this->resolveProvisionConfig($product);
        if ($config === null) {
            return ['success' => false, 'message' => 'المنتج غير مربوط بتزويد CyberPanel'];
        }

        $domain = trim((string) ($config['domain'] ?? ''));
        if ($domain === '') {
            $domain = $this->suggestDomainFromOrder($order, $config);
        }

        $owner = trim((string) ($config['owner'] ?? $this->api->getDefaultOwner()));
        $email = $order->email ? strtolower($order->email) : null;

        $create = $this->api->createWebsite([
            'domain' => $domain,
            'package' => $config['package'],
            'email' => $email ?? '',
            'owner' => $owner,
            'php_version' => $config['php_version'] ?? $this->api->getDefaultPhpVersion(),
            'owner_password' => Str::password(14),
        ]);

        if (! ($create['success'] ?? false)) {
            return ['success' => false, 'message' => $create['message'] ?? 'فشل إنشاء الموقع'];
        }

        $userId = $order->user_id;
        $customerId = null;
        if ($userId) {
            $order->load('user.customer');
            $customerId = $order->user?->customer?->id;
        }
        if (! $customerId && $order->email) {
            $customer = Customer::where('email', $order->email)->first();
            $customerId = $customer?->id;
            $userId = $userId ?? $customer?->user_id;
        }

        $meta = is_array($create['data'] ?? null) ? $create['data'] : [];

        $website = CyberPanelWebsite::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'domain' => $domain,
            'owner' => $owner,
            'email' => $email,
            'package' => $config['package'],
            'php_version' => $config['php_version'] ?? $this->api->getDefaultPhpVersion(),
            'status' => 'active',
            'joined_at' => now(),
            'metadata' => array_merge($meta, [
                'package_order_request_id' => $order->id,
                'owner_password_set' => true,
            ]),
        ]);

        $order->update([
            'cyberpanel_website_id' => $website->id,
            'status' => PackageOrderRequest::STATUS_CONVERTED,
        ]);

        $bootstrap = $this->bootstrapNewWebsiteSubscription($website);

        if (! empty($config['install_wordpress'])) {
            $wp = $config['wordpress'] ?? [];
            $this->wordpress->installOnWebsite($website, array_merge($wp, [
                'admin_email' => $email,
                'title' => $order->name ?? $domain,
            ]));
        }

        return [
            'success' => true,
            'message' => "تم إنشاء موقع CyberPanel ({$domain}) — ".$bootstrap['message'],
            'website' => $website->fresh(),
        ];
    }

    /**
     * @return array{success: bool, message: string, website?: CyberPanelWebsite}
     */
    public function bootstrapNewWebsiteSubscription(CyberPanelWebsite $website, ?float $amountOverride = null): array
    {
        return $this->billing->bootstrapNewWebsite($website, $amountOverride);
    }

    /**
     * @return array{success: bool, message: string, website?: CyberPanelWebsite}
     */
    public function renewSubscription(CyberPanelWebsite $website, ?float $amountOverride = null): array
    {
        if ($website->status === 'terminated') {
            return ['success' => false, 'message' => 'لا يمكن تجديد موقع محذوف'];
        }

        if ($this->billing->resolveCustomer($website) === null) {
            return ['success' => false, 'message' => 'اربط الموقع بعميل قبل التجديد'];
        }

        try {
            $invoice = DB::transaction(function () use ($website, $amountOverride) {
                $this->billing->ensureSubscriptionDates($website);
                $endsAt = $this->billing->extendSubscriptionEnd($website->fresh());
                $website->update([
                    'subscription_ends_at' => $endsAt,
                    'last_renewed_at' => now(),
                ]);

                $invoiceResult = $this->billing->createSubscriptionInvoice($website->fresh(), 'renewal', $amountOverride);
                if (! ($invoiceResult['success'] ?? false)) {
                    throw new \RuntimeException($invoiceResult['message'] ?? 'فشل إنشاء الفاتورة');
                }

                return $invoiceResult['invoice'];
            });

            return [
                'success' => true,
                'message' => 'تم تجديد الاشتراك وإنشاء الفاتورة',
                'website' => $website->fresh(),
                'invoice' => $invoice,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function suspend(CyberPanelWebsite $website): array
    {
        $res = $this->api->suspendWebsite($website->domain);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل التعليق'];
        }
        $website->update(['status' => 'suspended']);

        return ['success' => true, 'message' => 'تم تعليق الموقع'];
    }

    public function unsuspend(CyberPanelWebsite $website): array
    {
        $res = $this->api->unsuspendWebsite($website->domain);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل إلغاء التعليق'];
        }
        $website->update(['status' => 'active']);

        return ['success' => true, 'message' => 'تم تفعيل الموقع'];
    }

    public function terminate(CyberPanelWebsite $website): array
    {
        $res = $this->api->deleteWebsite($website->domain);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل الحذف من CyberPanel'];
        }
        $website->update(['status' => 'terminated']);

        return ['success' => true, 'message' => 'تم حذف الموقع من CyberPanel'];
    }

    public function changePackage(CyberPanelWebsite $website, string $packageName): array
    {
        $res = $this->api->changePackage($website->domain, $packageName);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل تغيير الباقة'];
        }
        $website->update(['package' => $packageName]);

        return ['success' => true, 'message' => 'تم تغيير الباقة'];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function assignClient(?int $userId, CyberPanelWebsite $website): array
    {
        $customerId = null;
        if ($userId) {
            $user = User::with('customer')->find($userId);
            if (! $user) {
                return ['success' => false, 'message' => 'المستخدم غير موجود'];
            }
            $customerId = $user->customer?->id;
        }

        $website->update([
            'user_id' => $userId,
            'customer_id' => $customerId,
        ]);

        return ['success' => true, 'message' => $userId ? 'تم ربط العميل' : 'تم إلغاء الربط'];
    }

    /**
     * @return array<int, string>
     */
    public function listPackagesForForms(): array
    {
        return app(CyberPanelPackageService::class)->listPackageNames();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveProvisionConfig(Product $product): ?array
    {
        $raw = $product->cyberpanel_provision;
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw) || empty($raw['enabled'])) {
            return null;
        }

        $package = trim((string) ($raw['package'] ?? $this->api->getDefaultPackage()));
        if ($package === '') {
            return null;
        }

        return [
            'enabled' => true,
            'package' => $package,
            'php_version' => $raw['php_version'] ?? $this->api->getDefaultPhpVersion(),
            'owner' => $raw['owner'] ?? $this->api->getDefaultOwner(),
            'domain' => $raw['domain'] ?? null,
            'domain_suffix' => $raw['domain_suffix'] ?? null,
            'install_wordpress' => ! empty($raw['install_wordpress']),
            'wordpress' => is_array($raw['wordpress'] ?? null) ? $raw['wordpress'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function suggestDomainFromOrder(PackageOrderRequest $order, array $config): string
    {
        $slug = Str::slug($order->name, '');
        if ($slug === '') {
            $slug = 'site'.$order->id;
        }

        $base = trim((string) ($config['domain_suffix'] ?? ''));
        if ($base === '') {
            $base = $this->settings->getConnectionConfig()['default_domain_suffix'] ?? '';
        }
        if ($base !== '') {
            return strtolower($slug.'.'.ltrim($base, '.'));
        }

        return strtolower($slug.'.example.com');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractDomain(array $row): string
    {
        foreach (['domain', 'domainName', 'website', 'name'] as $key) {
            $val = trim((string) ($row[$key] ?? ''));
            if ($val !== '') {
                return strtolower(preg_replace('#^https?://#i', '', rtrim($val, '/')));
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractOwner(array $row): ?string
    {
        foreach (['owner', 'websiteOwner', 'admin'] as $key) {
            $val = trim((string) ($row[$key] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractEmail(array $row): ?string
    {
        foreach (['email', 'adminEmail', 'ownerEmail'] as $key) {
            $val = strtolower(trim((string) ($row[$key] ?? '')));
            if ($val !== '' && str_contains($val, '@')) {
                return $val;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractPackage(array $row): ?string
    {
        foreach (['package', 'packageName', 'plan'] as $key) {
            $val = trim((string) ($row[$key] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function extractPhp(array $row): ?string
    {
        foreach (['phpSelection', 'php', 'php_version'] as $key) {
            $val = trim((string) ($row[$key] ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveStatusFromRemote(array $row): string
    {
        $state = strtolower(trim((string) ($row['state'] ?? $row['status'] ?? '')));
        if (str_contains($state, 'suspend')) {
            return 'suspended';
        }

        return 'active';
    }
}
