<?php

namespace App\Services\Whm;

use App\Models\Customer;
use App\Models\PackageOrderRequest;
use App\Models\Product;
use App\Models\User;
use App\Models\WhmAccount;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WhmAccountService
{
    public function __construct(
        protected WhmApiService $api,
        protected WhmSettingsService $settings
    ) {}

    /**
     * @return array{success: bool, message: string, synced?: int}
     */
    public function syncFromWhm(): array
    {
        $result = $this->api->listAccounts();
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل جلب الحسابات من WHM'];
        }

        $synced = 0;
        foreach ($result['accounts'] ?? [] as $acct) {
            if (! is_array($acct)) {
                continue;
            }
            $username = trim((string) ($acct['user'] ?? $acct['username'] ?? ''));
            $domain = trim((string) ($acct['domain'] ?? ''));
            if ($username === '' || $domain === '') {
                continue;
            }

            $status = $this->resolveAccountStatusFromWhm($acct);

            WhmAccount::updateOrCreate(
                ['username' => $username],
                [
                    'domain' => $domain,
                    'email' => $this->extractEmailFromWhm($acct),
                    'joined_at' => $this->extractJoinedAtFromWhm($acct),
                    'package' => (string) ($acct['plan'] ?? $acct['package'] ?? ''),
                    'status' => $status,
                    'metadata' => $acct,
                ]
            );
            $synced++;
        }

        $backfilled = $this->backfillFromMetadata();

        $message = "تمت مزامنة {$synced} حساباً من WHM";
        if ($backfilled > 0) {
            $message .= " — وملء {$backfilled} حقلاً من البيانات المحفوظة";
        }

        return ['success' => true, 'message' => $message, 'synced' => $synced];
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount}
     */
    public function createFromOrder(PackageOrderRequest $order): array
    {
        $product = $order->product;
        if (! $product) {
            return ['success' => false, 'message' => 'المنتج غير موجود'];
        }

        if ($order->whm_account_id) {
            $existing = WhmAccount::find($order->whm_account_id);
            if ($existing && $existing->status !== 'terminated') {
                return ['success' => false, 'message' => 'يوجد حساب WHM مرتبط بهذا الطلب مسبقاً', 'account' => $existing];
            }
        }

        $config = $this->resolveProvisionConfig($product);
        if ($config === null) {
            return ['success' => false, 'message' => 'المنتج غير مربوط بتزويد WHM — اضبط whm_provision في المنتج'];
        }

        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة'];
        }

        $domain = trim((string) ($config['domain'] ?? ''));
        if ($domain === '') {
            $domain = $this->suggestDomainFromOrder($order, $config);
        }

        $username = $this->uniqueUsername($config['username_prefix'] ?? '', $order);
        $password = Str::password(16);

        $create = $this->api->createAccount([
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'plan' => $config['package'] ?? $this->api->getDefaultPackage(),
            'contactemail' => $order->email,
        ]);

        if (! ($create['success'] ?? false)) {
            return ['success' => false, 'message' => $create['message'] ?? 'فشل إنشاء الحساب في WHM'];
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

        $account = WhmAccount::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'username' => $username,
            'domain' => $domain,
            'email' => $this->extractEmailFromWhm($meta) ?? ($order->email ? strtolower($order->email) : null),
            'joined_at' => now(),
            'package' => $config['package'] ?? $this->api->getDefaultPackage(),
            'status' => 'active',
            'metadata' => array_merge($meta, [
                'package_order_request_id' => $order->id,
                'provision_password_set' => true,
            ]),
        ]);

        $order->update([
            'whm_account_id' => $account->id,
            'status' => PackageOrderRequest::STATUS_CONVERTED,
        ]);

        return [
            'success' => true,
            'message' => "تم إنشاء حساب cPanel ({$username}@{$domain})",
            'account' => $account,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveProvisionConfig(Product $product): ?array
    {
        $raw = $product->whm_provision;
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
            'domain' => $raw['domain'] ?? null,
            'username_prefix' => $raw['username_prefix'] ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function suggestDomainFromOrder(PackageOrderRequest $order, array $config): string
    {
        $slug = Str::slug($order->name, '');
        if ($slug === '') {
            $slug = 'client'.$order->id;
        }

        $base = $this->settings->getConnectionConfig()['default_domain_suffix'] ?? '';
        if ($base !== '') {
            return strtolower($slug.'.'.ltrim($base, '.'));
        }

        return strtolower($slug.'.example.com');
    }

    /**
     * @param  array<string, mixed>  $acct
     */
    public function extractEmailFromWhm(array $acct): ?string
    {
        $normalized = $this->normalizeWhmKeys($acct);

        foreach (['email', 'contactemail', 'acctemail'] as $key) {
            $value = trim((string) ($normalized[$key] ?? ''));
            if ($value === '' || ! str_contains($value, '@')) {
                continue;
            }
            $value = strtolower($value);
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }

            return $value;
        }

        return null;
    }

    public function extractJoinedAtFromWhm(array $acct): ?Carbon
    {
        $normalized = $this->normalizeWhmKeys($acct);

        $unix = $normalized['unix_startdate'] ?? null;
        if ($unix !== null && $unix !== '' && is_numeric($unix) && (int) $unix > 0) {
            return Carbon::createFromTimestamp((int) $unix);
        }

        $startdate = trim((string) ($normalized['startdate'] ?? ''));
        if ($startdate !== '') {
            try {
                return Carbon::parse($startdate);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public function backfillFromMetadata(): int
    {
        $updated = 0;

        WhmAccount::query()
            ->where(function ($q) {
                $q->whereNull('email')->orWhere('email', '')->orWhereNull('joined_at');
            })
            ->whereNotNull('metadata')
            ->chunkById(100, function ($accounts) use (&$updated) {
                foreach ($accounts as $account) {
                    $meta = $account->metadata;
                    if (! is_array($meta) || $meta === []) {
                        continue;
                    }

                    $changes = [];
                    if (empty($account->email)) {
                        $email = $this->extractEmailFromWhm($meta);
                        if ($email) {
                            $changes['email'] = $email;
                        }
                    }
                    if ($account->joined_at === null) {
                        $joined = $this->extractJoinedAtFromWhm($meta);
                        if ($joined) {
                            $changes['joined_at'] = $joined;
                        }
                    }

                    if ($changes !== []) {
                        $account->update($changes);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $acct
     * @return array<string, mixed>
     */
    protected function normalizeWhmKeys(array $acct): array
    {
        $out = [];
        foreach ($acct as $key => $value) {
            $out[strtolower((string) $key)] = $value;
        }

        return $out;
    }

    /**
     * WHM listaccts: suspended = 0|1 (أحياناً نص). لا نعتمد على suspendreason وحده.
     *
     * @param  array<string, mixed>  $acct
     */
    protected function resolveAccountStatusFromWhm(array $acct): string
    {
        if ($this->whmFlagIsOn($acct['suspended'] ?? null)) {
            return 'suspended';
        }

        if ($this->whmFlagIsOn($acct['outgoing_suspended'] ?? null)) {
            return 'suspended';
        }

        $suspendTime = $acct['suspendtime'] ?? null;
        if ($suspendTime !== null && $suspendTime !== '' && $suspendTime !== 0 && $suspendTime !== '0') {
            return 'suspended';
        }

        return 'active';
    }

    protected function whmFlagIsOn(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === false) {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['0', 'no', 'false', 'off', ''], true)) {
            return false;
        }

        if (in_array($normalized, ['1', 'yes', 'true', 'on', 'suspended'], true)) {
            return true;
        }

        return false;
    }

    protected function uniqueUsername(string $prefix, PackageOrderRequest $order): string
    {
        $base = Str::slug($prefix.$order->name, '');
        $base = preg_replace('/[^a-z0-9]/', '', strtolower($base)) ?: 'u'.$order->id;
        $base = substr($base, 0, 8);

        $candidate = $base;
        $i = 0;
        while (WhmAccount::where('username', $candidate)->exists()) {
            $i++;
            $candidate = substr($base, 0, 6).$i;
        }

        return $candidate;
    }

    public function suspend(WhmAccount $account, ?string $reason = null): array
    {
        $result = $this->api->suspendAccount($account->username, $reason);
        if ($result['success'] ?? false) {
            $account->update(['status' => 'suspended']);
        }

        return $result;
    }

    public function unsuspend(WhmAccount $account): array
    {
        $result = $this->api->unsuspendAccount($account->username);
        if ($result['success'] ?? false) {
            $account->update(['status' => 'active']);
        }

        return $result;
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount}
     */
    public function updateContactEmail(WhmAccount $account, string $email): array
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return ['success' => false, 'message' => 'بريد غير صالح'];
        }

        $result = $this->api->modifyAccount($account->username, [
            'contactemail' => $email,
        ]);

        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل تحديث البريد في WHM'];
        }

        $meta = is_array($account->metadata) ? $account->metadata : [];
        $meta['email'] = $email;
        $meta['contactemail'] = $email;

        $account->update([
            'email' => $email,
            'metadata' => $meta,
        ]);

        return ['success' => true, 'message' => 'تم تحديث بريد التواصل في WHM', 'account' => $account->fresh()];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function updatePassword(WhmAccount $account, string $password): array
    {
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'];
        }

        $result = $this->api->changePassword($account->username, $password, true);

        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل تغيير كلمة المرور في WHM'];
        }

        return [
            'success' => true,
            'message' => 'تم تغيير كلمة المرور في WHM (cPanel / FTP / MySQL)',
        ];
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount, redirect_url?: string}
     */
    public function renameUsername(WhmAccount $account, string $newUsername): array
    {
        $newUsername = strtolower(trim($newUsername));
        if (! preg_match('/^[a-z][a-z0-9]{0,15}$/', $newUsername)) {
            return ['success' => false, 'message' => 'اسم المستخدم غير صالح (حروف إنجليزية وأرقام، يبدأ بحرف)'];
        }

        if ($newUsername === $account->username) {
            return ['success' => false, 'message' => 'اسم المستخدم الجديد مطابق للحالي'];
        }

        if (WhmAccount::where('username', $newUsername)->where('id', '!=', $account->id)->exists()) {
            return ['success' => false, 'message' => 'اسم المستخدم مستخدم مسبقاً في اللوحة'];
        }

        $oldUsername = $account->username;
        $result = $this->api->modifyAccount($oldUsername, [
            'newuser' => $newUsername,
        ]);

        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل إعادة تسمية المستخدم في WHM'];
        }

        $meta = is_array($account->metadata) ? $account->metadata : [];
        $meta['user'] = $newUsername;
        if (is_array($result['data'] ?? null)) {
            $meta = array_merge($meta, $result['data']);
        }

        $account->update([
            'username' => $newUsername,
            'metadata' => $meta,
        ]);

        return [
            'success' => true,
            'message' => "تم تغيير اسم المستخدم من {$oldUsername} إلى {$newUsername} في WHM",
            'account' => $account->fresh(),
            'redirect_url' => route('admin.whm.accounts.show', $account->fresh()),
        ];
    }

    /**
     * @return array{success: bool, message: string, url?: string, settings_url?: string}
     */
    /**
     * @return array{success: bool, message: string, account?: WhmAccount, client_label?: string|null}
     */
    public function assignClient(WhmAccount $account, ?int $userId): array
    {
        if ($account->status === 'terminated') {
            return ['success' => false, 'message' => 'لا يمكن ربط حساب محذوف بعميل'];
        }

        if ($userId !== null) {
            $user = \App\Models\User::find($userId);
            if (! $user) {
                return ['success' => false, 'message' => 'المستخدم غير موجود'];
            }
        }

        $account->update(['user_id' => $userId]);

        $account = $account->fresh()->load('client');

        return [
            'success' => true,
            'message' => $userId ? 'تم ربط الحساب بالعميل' : 'تم إلغاء ربط العميل',
            'account' => $account,
            'client_label' => $account->client_label,
        ];
    }

    public function cpanelLoginUrl(WhmAccount $account): array
    {
        if ($account->status === 'terminated') {
            return ['success' => false, 'message' => 'لا يمكن فتح cPanel لحساب محذوف'];
        }

        $username = trim($account->username);
        if ($username === '') {
            return ['success' => false, 'message' => 'اسم المستخدم غير موجود'];
        }

        if (! $this->api->isConfigured()) {
            return [
                'success' => false,
                'message' => 'إعدادات WHM غير مكتملة — اضبط الاتصال أولاً',
                'settings_url' => route('admin.whm.settings.index'),
            ];
        }

        $result = $this->api->createUserSession($username, 'cpaneld');
        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'فشل إنشاء جلسة cPanel — تحقق من صلاحيات API',
            ];
        }

        return [
            'success' => true,
            'message' => 'جاري فتح cPanel',
            'url' => $result['url'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPackagesForForms(): array
    {
        $result = $this->api->listPackages('creatable');
        if (! ($result['success'] ?? false)) {
            $fallback = trim($this->api->getDefaultPackage());
            if ($fallback !== '') {
                return [['name' => $fallback]];
            }

            return [];
        }

        return $result['packages'] ?? [];
    }

    /**
     * @return array{success: bool, message: string, summary?: array<string, mixed>}
     */
    public function refreshSummary(WhmAccount $account, bool $force = false): array
    {
        if ($account->status === 'terminated') {
            return ['success' => false, 'message' => 'الحساب محذوف'];
        }

        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة'];
        }

        $result = $this->api->accountSummary($account->username);
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل جلب ملخص الحساب'];
        }

        $summary = is_array($result['summary'] ?? null) ? $result['summary'] : [];
        $meta = is_array($account->metadata) ? $account->metadata : [];
        $meta['summary'] = $summary;
        $meta['summary_synced_at'] = now()->toIso8601String();
        $account->update(['metadata' => $meta]);

        return ['success' => true, 'message' => 'تم تحديث ملخص الحساب', 'summary' => $summary];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCachedSummary(WhmAccount $account): ?array
    {
        $meta = is_array($account->metadata) ? $account->metadata : [];
        $summary = $meta['summary'] ?? null;

        return is_array($summary) ? $summary : null;
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount}
     */
    public function changePackage(WhmAccount $account, string $package): array
    {
        if ($account->status === 'terminated') {
            return ['success' => false, 'message' => 'لا يمكن تغيير باقة حساب محذوف'];
        }

        $package = trim($package);
        if ($package === '') {
            return ['success' => false, 'message' => 'اختر باقة صالحة'];
        }

        $result = $this->api->modifyAccount($account->username, ['plan' => $package]);
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل تغيير الباقة في WHM'];
        }

        $account->update(['package' => $package]);
        $this->refreshSummary($account, true);

        return [
            'success' => true,
            'message' => 'تم تغيير الباقة في WHM',
            'account' => $account->fresh(),
        ];
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount}
     */
    public function terminate(WhmAccount $account, bool $keepDns = false): array
    {
        if ($account->status === 'terminated') {
            return ['success' => false, 'message' => 'الحساب محذوف مسبقاً'];
        }

        $result = $this->api->terminateAccount($account->username, $keepDns);
        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل حذف الحساب من WHM'];
        }

        $account->update(['status' => 'terminated']);

        return [
            'success' => true,
            'message' => 'تم حذف الحساب من WHM',
            'account' => $account->fresh(),
        ];
    }

    public function userOwnsAccount(User $user, WhmAccount $account): bool
    {
        return (int) $account->user_id === (int) $user->id;
    }

    /**
     * @return array{success: bool, message: string, url?: string}
     */
    public function portalCpanelLoginUrl(User $user, WhmAccount $account): array
    {
        if (! $this->userOwnsAccount($user, $account)) {
            return ['success' => false, 'message' => 'لا يمكنك فتح هذا الحساب'];
        }

        return $this->cpanelLoginUrl($account);
    }

    /**
     * @return array{success: bool, message?: string, records?: array<int, array<string, mixed>>}
     */
    public function dnsZoneForDomain(string $domain): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة'];
        }

        return $this->api->dumpZone($domain);
    }

    /**
     * @return array{success: bool, message?: string, hosts?: array<int, array<string, mixed>>}
     */
    public function sslStatusForAccount(WhmAccount $account): array
    {
        if ($account->status === 'terminated' || ! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'غير متاح'];
        }

        $response = $this->api->cpanelUapi($account->username, 'SSL', 'installed_hosts');
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $hosts = $data['data'] ?? $data;
        if (! is_array($hosts)) {
            $hosts = [];
        }
        if ($hosts !== [] && ! array_is_list($hosts)) {
            $hosts = [$hosts];
        }

        return ['success' => true, 'hosts' => $hosts];
    }

    /**
     * @return array{label: string, badge: string, main_domain?: array<string, mixed>|null}
     */
    public function formatSslBadgeForDomain(WhmAccount $account, ?string $domain = null): array
    {
        $domain = strtolower(trim($domain ?? $account->domain));
        $result = $this->sslStatusForAccount($account);
        if (! ($result['success'] ?? false)) {
            return ['label' => 'غير معروف', 'badge' => 'bg-secondary-transparent', 'main_domain' => null];
        }

        $match = null;
        foreach ($result['hosts'] ?? [] as $host) {
            if (! is_array($host)) {
                continue;
            }
            $domains = $host['domains'] ?? [];
            if (! is_array($domains)) {
                continue;
            }
            foreach ($domains as $d) {
                if (strtolower((string) $d) === $domain) {
                    $match = $host;
                    break 2;
                }
            }
            if (($host['servername'] ?? '') === $domain) {
                $match = $host;
                break;
            }
        }

        if ($match === null) {
            return ['label' => 'لا شهادة', 'badge' => 'bg-warning-transparent', 'main_domain' => null];
        }

        $cert = $match['certificate'] ?? [];
        $notAfter = is_array($cert) ? (int) ($cert['not_after'] ?? 0) : 0;
        if ($notAfter > 0 && $notAfter < time()) {
            return ['label' => 'منتهية', 'badge' => 'bg-danger-transparent', 'main_domain' => $match];
        }

        if (! empty($cert['is_self_signed'])) {
            return ['label' => 'ذاتية التوقيع', 'badge' => 'bg-info-transparent', 'main_domain' => $match];
        }

        return ['label' => 'صالحة', 'badge' => 'bg-success-transparent', 'main_domain' => $match];
    }
}
