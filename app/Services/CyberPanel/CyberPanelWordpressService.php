<?php

namespace App\Services\CyberPanel;

use App\Models\CyberPanelWebsite;
use App\Models\CyberPanelWordpressSite;
use Illuminate\Support\Str;

class CyberPanelWordpressService
{
    public function __construct(protected CyberPanelApiService $api) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string, site?: CyberPanelWordpressSite}
     */
    public function installOnWebsite(CyberPanelWebsite $website, array $params = []): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات CyberPanel غير مكتملة'];
        }

        $domain = $website->domain;
        $adminUser = trim((string) ($params['admin_user'] ?? 'admin'));
        $adminPassword = (string) ($params['admin_password'] ?? $this->generateSafePassword());
        $adminEmail = trim((string) ($params['admin_email'] ?? $website->email ?? 'admin@'.$domain));
        $title = trim((string) ($params['title'] ?? $domain));

        $response = $this->api->deployWordPress([
            'domain' => $domain,
            'title' => $title,
            'admin_user' => $adminUser,
            'admin_password' => $adminPassword,
            'admin_email' => $adminEmail,
            'create_site' => 0,
        ]);

        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $tempStatusPath = trim((string) ($responseData['tempStatusPath'] ?? ''));
        $status = 'failed';
        $message = $response['message'] ?? 'فشل تثبيت WordPress';

        if ($response['success'] ?? false) {
            if ($tempStatusPath !== '') {
                $poll = $this->waitForInstallStatus($tempStatusPath);
                $status = ($poll['success'] ?? false) ? 'running' : 'provisioning';
                $message = $poll['message'] ?? $message;

                if (($poll['completed'] ?? false) && ! ($poll['success'] ?? false)) {
                    $status = 'failed';
                } elseif (($poll['completed'] ?? false) && ($poll['success'] ?? false)) {
                    $status = 'running';
                }
            } else {
                $status = 'running';
                $message = 'تم تثبيت WordPress على '.$domain;
            }
        }

        $site = CyberPanelWordpressSite::updateOrCreate(
            ['cyberpanel_website_id' => $website->id],
            [
                'domain' => $domain,
                'wp_user' => $adminUser,
                'wp_admin_url' => 'https://'.$domain.'/wp-admin',
                'status' => $status,
                'metadata' => array_merge($responseData, [
                    'install_response' => $response,
                    'temp_status_path' => $tempStatusPath !== '' ? $tempStatusPath : null,
                    'admin_password_set' => true,
                ]),
            ]
        );

        if ($status !== 'failed') {
            $site->storeAdminPassword($adminPassword);
        }

        if (! ($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $message,
                'site' => $site,
            ];
        }

        return [
            'success' => $status !== 'failed',
            'message' => $status === 'provisioning'
                ? 'جاري تثبيت WordPress على '.$domain.' — '.$message
                : 'تم تثبيت WordPress على '.$domain,
            'site' => $site->fresh(),
        ];
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function issueSslForWebsite(CyberPanelWebsite $website): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات CyberPanel غير مكتملة'];
        }

        $response = $this->api->issueSsl($website->domain);

        $metadata = is_array($website->metadata) ? $website->metadata : [];
        $metadata['ssl'] = [
            'issued_at' => now()->toIso8601String(),
            'response' => $response['data'] ?? $response,
            'success' => (bool) ($response['success'] ?? false),
        ];

        $website->update(['metadata' => $metadata]);

        if ($website->wordpressSite) {
            $wpMeta = is_array($website->wordpressSite->metadata) ? $website->wordpressSite->metadata : [];
            $wpMeta['ssl'] = $metadata['ssl'];
            $website->wordpressSite->update(['metadata' => $wpMeta]);
        }

        if (! ($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'فشل إصدار شهادة SSL',
            ];
        }

        return [
            'success' => true,
            'message' => 'تم إصدار شهادة SSL لـ '.$website->domain,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message: string, site?: CyberPanelWordpressSite}
     */
    public function installWordpressAndSsl(CyberPanelWebsite $website, array $params = []): array
    {
        $wp = $this->installOnWebsite($website, $params);
        $ssl = $this->issueSslForWebsite($website);

        $messages = [$wp['message']];

        if ($ssl['success']) {
            $messages[] = $ssl['message'];
        } else {
            $messages[] = 'SSL: '.$ssl['message'];
        }

        $success = ($wp['success'] ?? false) && ($ssl['success'] ?? false);

        return [
            'success' => $success,
            'message' => implode(' — ', $messages),
            'site' => $wp['site'] ?? null,
        ];
    }

    /**
     * @return array{success: bool, message: string, site?: CyberPanelWordpressSite}
     */
    public function refreshInstallStatus(CyberPanelWordpressSite $site): array
    {
        $meta = is_array($site->metadata) ? $site->metadata : [];
        $tempStatusPath = trim((string) ($meta['temp_status_path'] ?? ''));

        if ($tempStatusPath === '') {
            return [
                'success' => $site->status === 'running',
                'message' => $site->status === 'running' ? 'WordPress يعمل' : 'لا يوجد تتبع تثبيت نشط',
                'site' => $site,
            ];
        }

        $poll = $this->api->pollInstallStatus($tempStatusPath);
        $status = $site->status;

        if ($poll['completed'] ?? false) {
            $status = ($poll['success'] ?? false) ? 'running' : 'failed';
            $site->update([
                'status' => $status,
                'metadata' => array_merge($meta, [
                    'last_poll' => $poll,
                    'temp_status_path' => ($poll['success'] ?? false) ? null : $tempStatusPath,
                ]),
            ]);
        }

        return [
            'success' => $status === 'running',
            'message' => $poll['message'] ?? 'تم تحديث الحالة',
            'site' => $site->fresh(),
        ];
    }

    /**
     * @return array{success: bool, message: string, completed: bool}
     */
    protected function waitForInstallStatus(string $statusFile, int $maxAttempts = 15, int $sleepSeconds = 2): array
    {
        $last = ['success' => false, 'message' => 'انتهت مهلة انتظار التثبيت', 'completed' => false];

        for ($i = 0; $i < $maxAttempts; $i++) {
            if ($i > 0) {
                sleep($sleepSeconds);
            }

            $last = $this->api->pollInstallStatus($statusFile);
            if ($last['completed'] ?? false) {
                return $last;
            }
        }

        return $last;
    }

    protected function generateSafePassword(): string
    {
        return Str::password(16, letters: true, numbers: true, symbols: false);
    }

    /**
     * بيانات الدخول للـ SSO — يفضّل CyberPanel AutoLogin (مستخدم cyberpanel + كلمة مرور مؤقتة).
     *
     * @return array{success: bool, message: string, username?: string, password?: string, source?: string}
     */
    public function resolveLoginCredentials(CyberPanelWordpressSite $site, bool $preferCyberPanelSso = true): array
    {
        if ($preferCyberPanelSso) {
            $sso = $this->fetchCyberPanelSsoCredentials($site);
            if ($sso['success'] ?? false) {
                return $sso;
            }

            $stored = $this->resolveStoredLoginCredentials($site);
            if ($stored['success'] ?? false) {
                return $stored;
            }

            return [
                'success' => false,
                'message' => $sso['message'] ?? $stored['message'] ?? 'تعذّر الحصول على بيانات دخول WordPress',
            ];
        }

        $stored = $this->resolveStoredLoginCredentials($site);
        if ($stored['success'] ?? false) {
            return $stored;
        }

        return $this->fetchCyberPanelSsoCredentials($site);
    }

    /**
     * @return array{success: bool, message: string, username?: string, password?: string, source?: string}
     */
    protected function resolveStoredLoginCredentials(CyberPanelWordpressSite $site): array
    {
        $password = $site->getAdminPassword();
        $username = trim((string) ($site->wp_user ?? ''));

        if ($username === '' || $password === null || $password === '') {
            return ['success' => false, 'message' => 'لا توجد بيانات دخول محفوظة'];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'username' => $username,
            'password' => $password,
            'source' => 'stored',
        ];
    }

    /**
     * CyberPanel ينشئ/يحدّث مستخدم WP باسم cyberpanel ويعيد كلمة مرور مؤقتة.
     *
     * @return array{success: bool, message: string, username?: string, password?: string, source?: string}
     */
    protected function fetchCyberPanelSsoCredentials(CyberPanelWordpressSite $site): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات CyberPanel غير مكتملة'];
        }

        $response = $this->api->wordpressAutoLogin($site->domain);
        if (! ($response['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $response['message'] ?? 'تعذّر الحصول على بيانات دخول WordPress من CyberPanel',
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $tempPassword = (string) ($data['password'] ?? '');
        if ($tempPassword === '') {
            return ['success' => false, 'message' => 'لم يُرجع CyberPanel كلمة مرور للدخول التلقائي'];
        }

        $site->storeAdminPassword($tempPassword, 'cyberpanel');

        return [
            'success' => true,
            'message' => 'OK',
            'username' => 'cyberpanel',
            'password' => $tempPassword,
            'source' => 'cyberpanel_auto_login',
        ];
    }

    /**
     * @return array{success: bool, message: string, login_url?: string, username?: string, password?: string, redirect_to?: string}
     */
    public function buildAutoLoginPayload(CyberPanelWordpressSite $site): array
    {
        if ($site->status !== 'running') {
            return ['success' => false, 'message' => 'WordPress غير جاهز بعد'];
        }

        $creds = $this->resolveLoginCredentials($site);
        if (! ($creds['success'] ?? false)) {
            return ['success' => false, 'message' => $creds['message']];
        }

        $loginUrl = rtrim($site->public_url ?? ('https://'.$site->domain), '/').'/wp-login.php';
        $redirectTo = $site->admin_url ?? rtrim($site->public_url ?? ('https://'.$site->domain), '/').'/wp-admin/';

        return [
            'success' => true,
            'message' => 'OK',
            'login_url' => $loginUrl,
            'username' => $creds['username'],
            'password' => $creds['password'],
            'redirect_to' => $redirectTo,
            'source' => $creds['source'] ?? null,
        ];
    }

    /**
     * @return array{success: bool, message: string, site?: CyberPanelWordpressSite}
     */
    public function saveAdminCredentials(CyberPanelWordpressSite $site, string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['success' => false, 'message' => 'اسم المستخدم وكلمة المرور مطلوبان'];
        }

        $site->update(['wp_user' => $username]);
        $site->storeAdminPassword($password);

        return [
            'success' => true,
            'message' => 'تم حفظ بيانات دخول WordPress',
            'site' => $site->fresh(),
        ];
    }
}
