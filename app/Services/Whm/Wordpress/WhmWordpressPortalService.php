<?php

namespace App\Services\Whm\Wordpress;

use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmApiService;
use Illuminate\Support\Facades\Log;

class WhmWordpressPortalService
{
    public function __construct(
        protected WhmApiService $api,
        protected SoftaculousApiClient $softaculous
    ) {}

    /**
     * @return array{success: bool, message: string, url?: string}
     */
    public function openSiteUrl(WhmWordpressSite $site): array
    {
        $url = $site->public_url;
        if ($url === null || $url === '') {
            return ['success' => false, 'message' => 'لا يوجد رابط للموقع'];
        }

        return ['success' => true, 'message' => 'فتح الموقع', 'url' => $url];
    }

    /**
     * @return array{success: bool, message: string, url?: string}
     */
    public function wpAdminUrl(WhmWordpressSite $site): array
    {
        $account = $site->account;
        if (! $account instanceof WhmAccount) {
            return ['success' => false, 'message' => 'حساب cPanel غير مرتبط'];
        }

        if ($site->source === WhmWordpressSite::SOURCE_SOFTACULOUS && $site->external_id) {
            $signed = $this->softaculousSignOn($account, $site->external_id);
            if ($signed['success'] ?? false) {
                return $signed;
            }
            Log::info('Softaculous sign_on failed, fallback wp-admin', [
                'site_id' => $site->id,
                'message' => $signed['message'] ?? null,
            ]);
        }

        $admin = $site->wp_admin_url;
        if ($admin === null) {
            return ['success' => false, 'message' => 'لا يمكن بناء رابط wp-admin — أضف رابط الموقع أولاً'];
        }

        return [
            'success' => true,
            'message' => 'فتح لوحة ووردبريس',
            'url' => $admin,
        ];
    }

    /**
     * Open Softaculous / WP Toolkit / File Manager via cPanel SSO.
     *
     * @return array{success: bool, message: string, url?: string}
     */
    public function managerUrl(WhmWordpressSite $site): array
    {
        $account = $site->account;
        if (! $account instanceof WhmAccount) {
            return ['success' => false, 'message' => 'حساب cPanel غير مرتبط'];
        }

        $username = trim((string) $account->username);
        if ($username === '') {
            return ['success' => false, 'message' => 'اسم مستخدم cPanel فارغ'];
        }

        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة'];
        }

        $session = $this->api->createUserSession($username, 'cpaneld');
        if (! ($session['success'] ?? false) || empty($session['url'])) {
            return [
                'success' => false,
                'message' => $session['message'] ?? 'فشل إنشاء جلسة cPanel',
            ];
        }

        if ($site->source === WhmWordpressSite::SOURCE_SOFTACULOUS && $site->external_id) {
            $softUrl = $this->buildSoftaculousManageUrl($session['url'], $site->external_id);
            if ($softUrl !== null) {
                return [
                    'success' => true,
                    'message' => 'فتح Softaculous',
                    'url' => $softUrl,
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'فتح cPanel',
            'url' => $session['url'],
        ];
    }

    protected function buildSoftaculousManageUrl(string $sessionUrl, string $insid): ?string
    {
        if (! preg_match('#^(https?://[^/]+(?:/cpsess[^/]+)?)#i', $sessionUrl, $m)) {
            return null;
        }

        $root = rtrim($m[1], '/');

        return $root.'/frontend/jupiter/softaculous/index.live.php?act=editdetail&insid='.urlencode($insid);
    }

    /**
     * @return array{success: bool, message: string, url?: string}
     */
    protected function softaculousSignOn(WhmAccount $account, string $insid): array
    {
        $username = trim((string) $account->username);
        $session = $this->api->createUserSession($username, 'cpaneld');
        if (! ($session['success'] ?? false) || empty($session['url'])) {
            return ['success' => false, 'message' => $session['message'] ?? 'فشل جلسة cPanel'];
        }

        $result = $this->softaculous->call(
            $session['url'],
            'act=sign_on&insid='.urlencode($insid).'&api=json'
        );

        if (! ($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['message'] ?? 'فشل Softaculous sign_on'];
        }

        $data = $result['data'] ?? [];
        $url = $this->extractSignOnUrl($data);
        if ($url === null) {
            return ['success' => false, 'message' => 'Softaculous لم يُرجع رابط دخول'];
        }

        return ['success' => true, 'message' => 'دخول ووردبريس عبر Softaculous', 'url' => $url];
    }

    /**
     * @param  mixed  $data
     */
    protected function extractSignOnUrl(mixed $data): ?string
    {
        if (! is_array($data)) {
            return null;
        }

        foreach (['sign_on_url', 'url', 'login_url', 'redirect'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        if (isset($data['result']) && is_array($data['result'])) {
            return $this->extractSignOnUrl($data['result']);
        }

        return null;
    }
}
