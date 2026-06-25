<?php

namespace App\Services\CyberPanel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use GuzzleHttp\Cookie\CookieJar;

class CyberPanelApiService
{
    protected string $host = '';

    protected int $port = 8090;

    protected string $adminUser = '';

    protected string $adminPassword = '';

    protected string $apiToken = '';

    protected string $apiStyle = 'cloud';

    protected bool $verifySsl = true;

    protected int $timeout = 60;

    protected string $defaultPackage = 'Default';

    protected string $defaultPhpVersion = 'PHP 8.3';

    protected string $defaultOwner = 'admin';

    protected ?string $resolvedAuthHeader = null;

    protected bool $authDiscoveryAttempted = false;

    public function __construct(protected CyberPanelSettingsService $settings)
    {
        $this->loadConnectionConfig();
    }

    protected function loadConnectionConfig(): void
    {
        $config = $this->settings->getConnectionConfig();
        $this->host = $config['host'] ?? '';
        $this->port = (int) ($config['port'] ?? 8090);
        $this->adminUser = $config['admin_user'] ?? 'admin';
        $this->adminPassword = $config['admin_password'] ?? '';
        $this->apiToken = $config['api_token'] ?? '';
        $this->apiStyle = $config['api_style'] ?? 'cloud';
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? true);
        $this->timeout = (int) ($config['timeout'] ?? 60);
        $this->defaultPackage = $config['default_package'] ?? 'Default';
        $this->defaultPhpVersion = $config['default_php_version'] ?? 'PHP 8.3';
        $this->defaultOwner = $config['default_owner'] ?? 'admin';
    }

    public function refreshConnection(): void
    {
        $this->settings->clearCache();
        $this->resolvedAuthHeader = null;
        $this->authDiscoveryAttempted = false;
        $this->loadConnectionConfig();
    }

    public function isConfigured(): bool
    {
        return $this->host !== ''
            && $this->adminUser !== ''
            && ($this->adminPassword !== '' || $this->apiToken !== '');
    }

    public function hasCloudToken(): bool
    {
        return $this->apiToken !== '' || $this->adminPassword !== '';
    }

    public function supportsCloudOperations(): bool
    {
        return $this->apiStyle !== 'legacy' && $this->hasCloudToken();
    }

    /**
     * @return array{success: bool, message: string, status: int}|null
     */
    protected function cloudAuthGuard(): ?array
    {
        if ($this->apiStyle === 'legacy' || $this->hasCloudToken()) {
            return null;
        }

        return [
            'success' => false,
            'message' => 'أدخل كلمة مرور مدير CyberPanel في الإعدادات، وفعّل API Access للمستخدم admin من لوحة CyberPanel.',
            'status' => 0,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function buildAuthorizationCandidates(): array
    {
        if ($this->adminUser === '' || $this->adminPassword === '') {
            return [];
        }

        $credentials = $this->adminUser.':'.$this->adminPassword;

        return array_values(array_unique([
            'Basic '.hash('sha256', $credentials),
            'Basic '.base64_encode($credentials),
        ]));
    }

    public function normalizeAuthorizationHeader(string $token): string
    {
        $token = trim($token);
        if (str_starts_with($token, 'Basic ') || str_starts_with($token, 'Bearer ')) {
            return $token;
        }

        return 'Basic '.$token;
    }

    public function discoverAuthorizationHeader(): ?string
    {
        if ($this->authDiscoveryAttempted) {
            return $this->resolvedAuthHeader !== '' ? $this->resolvedAuthHeader : null;
        }

        $this->authDiscoveryAttempted = true;

        if ($this->apiToken !== '') {
            $this->resolvedAuthHeader = $this->normalizeAuthorizationHeader($this->apiToken);

            return $this->resolvedAuthHeader;
        }

        if ($this->adminUser === '' || $this->adminPassword === '') {
            $this->resolvedAuthHeader = '';

            return null;
        }

        $probeBody = [
            'controller' => 'verifyLogin',
            'serverUserName' => $this->adminUser,
            'adminUser' => $this->adminUser,
            'adminPass' => $this->adminPassword,
        ];

        foreach ($this->buildAuthorizationCandidates() as $candidate) {
            $response = $this->httpPost(
                $this->getBaseUrl().'/cloudAPI/',
                $probeBody,
                $candidate,
                true
            );

            if ($response['success'] ?? false) {
                $this->resolvedAuthHeader = $candidate;

                return $candidate;
            }
        }

        $candidates = $this->buildAuthorizationCandidates();
        $this->resolvedAuthHeader = $candidates[0] ?? '';

        return $this->resolvedAuthHeader !== '' ? $this->resolvedAuthHeader : null;
    }

    protected function safePassword(int $length = 16): string
    {
        return Str::password($length, letters: true, numbers: true, symbols: false);
    }

    public function getDefaultPackage(): string
    {
        return $this->defaultPackage;
    }

    public function getDefaultPhpVersion(): string
    {
        return $this->defaultPhpVersion;
    }

    public function getDefaultOwner(): string
    {
        return $this->defaultOwner;
    }

    public function getBaseUrl(): string
    {
        $host = $this->host;
        if ($host === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $host)) {
            $host = 'https://'.$host;
        }

        $parsed = parse_url($host);
        $scheme = $parsed['scheme'] ?? 'https';
        $hostname = $parsed['host'] ?? ltrim($host, '/');
        $port = $parsed['port'] ?? $this->port;

        return $scheme.'://'.$hostname.':'.$port;
    }

    public function getPanelUrl(): string
    {
        return $this->getBaseUrl();
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function ping(): array
    {
        $verify = $this->verifyConnection();
        if ($verify['success'] ?? false) {
            return $verify;
        }

        return $this->listPackages();
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function verifyConnection(): array
    {
        return $this->legacyRequest('verifyConn', []);
    }

    /**
     * @return array{success: bool, message?: string, websites?: array<int, array<string, mixed>>, status?: int}
     */
    public function listWebsites(): array
    {
        $response = $this->cloudRequest('fetchWebsites', []);
        if (! ($response['success'] ?? false)) {
            $response = $this->legacyRequest('fetchWebsites', []);
        }

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $websites = $this->normalizeList($data['websites'] ?? $data['websiteList'] ?? $data ?? []);

        return ['success' => true, 'websites' => $websites, 'status' => $response['status'] ?? 200];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function createWebsite(array $params): array
    {
        $domain = trim((string) ($params['domain'] ?? $params['domainName'] ?? ''));
        $package = trim((string) ($params['package'] ?? $params['packageName'] ?? $this->defaultPackage));
        $email = trim((string) ($params['email'] ?? $params['ownerEmail'] ?? $params['adminEmail'] ?? ''));
        $owner = trim((string) ($params['owner'] ?? $params['websiteOwner'] ?? $this->defaultOwner));
        $php = trim((string) ($params['php_version'] ?? $params['phpSelection'] ?? $this->defaultPhpVersion));
        $password = (string) ($params['owner_password'] ?? $params['ownerPassword'] ?? '');

        $cloudPayload = array_filter([
            'domainName' => $domain,
            'package' => $package,
            'adminEmail' => $email,
            'websiteOwner' => $owner,
            'ownerPassword' => $password !== '' ? $password : null,
            'phpSelection' => $php,
            'ssl' => (int) ($params['ssl'] ?? 0),
            'dkimCheck' => (int) ($params['dkimCheck'] ?? 0),
            'openBasedir' => (int) ($params['openBasedir'] ?? 0),
        ], fn ($v) => $v !== null);

        $response = $this->cloudRequest('submitWebsiteCreation', $cloudPayload);
        if ($response['success'] ?? false) {
            return $response;
        }

        $legacyPayload = array_filter([
            'domainName' => $domain,
            'packageName' => $package,
            'ownerEmail' => $email,
            'websiteOwner' => $owner,
            'ownerPassword' => $password !== '' ? $password : null,
            'phpSelection' => $php,
            'acl' => $params['acl'] ?? 'user',
        ], fn ($v) => $v !== null);

        return $this->legacyRequest('createWebsite', $legacyPayload);
    }

    public function deleteWebsite(string $domain): array
    {
        $domain = trim($domain);
        $response = $this->cloudRequest('deleteWebsite', ['websiteName' => $domain]);
        if ($response['success'] ?? false) {
            return $response;
        }

        return $this->legacyRequest('deleteWebsite', ['websiteName' => $domain]);
    }

    public function suspendWebsite(string $domain): array
    {
        return $this->changeWebsiteStatus($domain, 'Suspend');
    }

    public function unsuspendWebsite(string $domain): array
    {
        return $this->changeWebsiteStatus($domain, 'Un-Suspend');
    }

    protected function changeWebsiteStatus(string $domain, string $state): array
    {
        $domain = trim($domain);
        $response = $this->cloudRequest('submitWebsiteStatus', [
            'websiteName' => $domain,
            'state' => $state,
        ]);
        if ($response['success'] ?? false) {
            return $response;
        }

        return $this->legacyRequest('submitWebsiteStatus', [
            'websiteName' => $domain,
            'state' => $state,
        ]);
    }

    public function changePackage(string $domain, string $packageName): array
    {
        $response = $this->cloudRequest('changePackage', [
            'websiteName' => trim($domain),
            'packageName' => trim($packageName),
        ]);
        if ($response['success'] ?? false) {
            return $response;
        }

        return $this->legacyRequest('changePackage', [
            'domainName' => trim($domain),
            'packageName' => trim($packageName),
        ]);
    }

    /**
     * @return array{success: bool, message?: string, packages?: array<int, array<string, mixed>>, status?: int}
     */
    public function listPackages(): array
    {
        $cacheKey = 'cyberpanel_packages_list';

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->cloudRequest('fetchPackages', []);
        if (! ($response['success'] ?? false)) {
            $response = $this->legacyRequest('listPackage', []);
        }
        if (! ($response['success'] ?? false)) {
            $response = $this->legacyRequest('fetchPackages', []);
        }

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $packages = $this->normalizeList($data['packages'] ?? $data['packageList'] ?? $data ?? []);

        $result = ['success' => true, 'packages' => $packages, 'status' => $response['status'] ?? 200];
        Cache::put($cacheKey, $result, 900);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function createPackage(array $params): array
    {
        $payload = [
            'packageName' => trim((string) ($params['packageName'] ?? '')),
            'diskSpace' => (int) ($params['diskSpace'] ?? 1000),
            'bandwidth' => (int) ($params['bandwidth'] ?? 10000),
            'emailAccounts' => (int) ($params['emailAccounts'] ?? 100),
            'dataBases' => (int) ($params['dataBases'] ?? 100),
            'ftpAccounts' => (int) ($params['ftpAccounts'] ?? 100),
            'allowedDomains' => (int) ($params['allowedDomains'] ?? 100),
            'owner' => trim((string) ($params['owner'] ?? $this->defaultOwner)),
        ];

        $response = $this->cloudRequest('createPackage', $payload);
        if ($response['success'] ?? false) {
            Cache::forget('cyberpanel_packages_list');

            return $response;
        }

        $legacy = $this->legacyRequest('createPackage', $payload);
        if ($legacy['success'] ?? false) {
            Cache::forget('cyberpanel_packages_list');
        }

        return $legacy;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function installWordPress(array $params): array
    {
        return $this->deployWordPress($params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function deployWordPress(array $params): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim((string) ($params['domain'] ?? $params['domainName'] ?? ''));
        $password = (string) ($params['admin_password'] ?? $params['password'] ?? $params['passwordByPass'] ?? '');

        $payload = [
            'domain' => $domain,
            'email' => trim((string) ($params['admin_email'] ?? $params['email'] ?? '')),
            'passwordByPass' => $password !== '' ? $password : $this->safePassword(),
            'appsSet' => (string) ($params['apps_set'] ?? $params['appsSet'] ?? 'WordPress + LSCache + Classic Editor'),
            'pluginUpdates' => (string) ($params['plugin_updates'] ?? $params['pluginUpdates'] ?? 'Enabled'),
            'themeUpdates' => (string) ($params['theme_updates'] ?? $params['themeUpdates'] ?? 'Enabled'),
            'title' => trim((string) ($params['title'] ?? $domain ?: 'WordPress Site')),
            'updates' => (string) ($params['updates'] ?? 'Minor and Security Updates'),
            'userName' => trim((string) ($params['admin_user'] ?? $params['userName'] ?? 'admin')),
            'version' => (string) ($params['version'] ?? 'latest'),
            'createSite' => (int) ($params['create_site'] ?? $params['createSite'] ?? 0),
        ];

        return $this->cloudRequest('DeployWordPress', $payload);
    }

    public function issueSsl(string $domain): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب لإصدار SSL'];
        }

        return $this->cloudRequest('issueSSL', ['virtualHost' => $domain]);
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function wordpressAutoLogin(string $domain): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        return $this->cloudRequest('AutoLogin', ['domain' => $domain]);
    }

    public function getWpPlugins(string $domain): array
    {
        return $this->wpListRequest('GetCurrentPlugins', $domain);
    }

    public function getWpThemes(string $domain): array
    {
        return $this->wpListRequest('GetCurrentThemes', $domain);
    }

    public function getWpUsers(string $domain): array
    {
        $session = $this->establishWordPressRestSession($domain);
        if (! ($session['success'] ?? false)) {
            return $session;
        }

        $response = $this->wordpressRestRequest(
            $session,
            'GET',
            '/wp-json/wp/v2/users',
            ['per_page' => 100, 'context' => 'edit']
        );

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $users = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'success' => true,
            'message' => 'OK',
            'data' => array_map(fn (array $user) => $this->normalizeWpRestUser($user), $users),
            'status' => $response['status'] ?? 200,
        ];
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>|null, generated_password?: string, login?: string}
     */
    public function createWpUser(string $domain, string $login, string $email, string $role, ?string $password = null): array
    {
        $login = preg_replace('/[^a-z0-9_@.\-]/i', '', trim($login));
        $email = trim($email);
        $role = $this->sanitizeWpRole($role);

        if ($login === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'اسم المستخدم والبريد الإلكتروني الصحيح مطلوبان'];
        }

        $password = trim((string) ($password ?? ''));
        if ($password === '') {
            $password = Str::password(16, letters: true, numbers: true, symbols: false);
        }

        $session = $this->establishWordPressRestSession($domain);
        if (! ($session['success'] ?? false)) {
            return $session;
        }

        $response = $this->wordpressRestRequest($session, 'POST', '/wp-json/wp/v2/users', [
            'username' => $login,
            'email' => $email,
            'password' => $password,
            'roles' => [$role],
        ]);

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء المستخدم «'.$login.'»',
            'data' => is_array($response['data'] ?? null) ? $this->normalizeWpRestUser($response['data']) : null,
            'generated_password' => $password,
            'login' => $login,
        ];
    }

    /**
     * @return array{success: bool, message?: string, generated_password?: string, login?: string}
     */
    public function updateWpUserPassword(string $domain, string $login, ?string $password = null): array
    {
        $login = trim($login);
        if ($login === '') {
            return ['success' => false, 'message' => 'اسم المستخدم مطلوب'];
        }

        $resolved = $this->resolveWpUserIdByLogin($domain, $login);
        if (! ($resolved['success'] ?? false)) {
            return $resolved;
        }

        $password = trim((string) ($password ?? ''));
        if ($password === '') {
            $password = Str::password(16, letters: true, numbers: true, symbols: false);
        }

        $session = $this->establishWordPressRestSession($domain);
        if (! ($session['success'] ?? false)) {
            return $session;
        }

        $userId = (int) $resolved['user_id'];
        $response = $this->wordpressRestRequest($session, 'POST', '/wp-json/wp/v2/users/'.$userId, [
            'password' => $password,
        ]);

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        return [
            'success' => true,
            'message' => 'تم تغيير كلمة مرور «'.$login.'»',
            'generated_password' => $password,
            'login' => $login,
        ];
    }

    public function updateWpUserRole(string $domain, string $login, string $role): array
    {
        $login = trim($login);
        $role = $this->sanitizeWpRole($role);
        if ($login === '') {
            return ['success' => false, 'message' => 'اسم المستخدم مطلوب'];
        }

        $resolved = $this->resolveWpUserIdByLogin($domain, $login);
        if (! ($resolved['success'] ?? false)) {
            return $resolved;
        }

        $session = $this->establishWordPressRestSession($domain);
        if (! ($session['success'] ?? false)) {
            return $session;
        }

        $response = $this->wordpressRestRequest($session, 'POST', '/wp-json/wp/v2/users/'.(int) $resolved['user_id'], [
            'roles' => [$role],
        ]);

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        return [
            'success' => true,
            'message' => 'تم تحديث دور «'.$login.'» إلى '.$role,
        ];
    }

    public function deleteWpUser(string $domain, int $userId, int $reassignTo = 1): array
    {
        if ($userId < 1) {
            return ['success' => false, 'message' => 'معرّف المستخدم مطلوب'];
        }

        $session = $this->establishWordPressRestSession($domain);
        if (! ($session['success'] ?? false)) {
            return $session;
        }

        $response = $this->wordpressRestRequest(
            $session,
            'DELETE',
            '/wp-json/wp/v2/users/'.$userId,
            ['force' => 'true', 'reassign' => max(1, $reassignTo)]
        );

        if (! ($response['success'] ?? false)) {
            return $response;
        }

        return [
            'success' => true,
            'message' => 'تم حذف المستخدم',
        ];
    }

    /**
     * @return array{success: bool, message?: string, client?: \Illuminate\Http\Client\PendingRequest, site_url?: string, nonce?: string}
     */
    protected function establishWordPressRestSession(string $domain): array
    {
        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        $probe = $this->getWpPlugins($domain);
        if (! ($probe['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'تعذّر الوصول إلى WordPress عبر CyberPanel API — تحقق من الإعدادات والنطاق',
            ];
        }

        $login = $this->wordpressAutoLogin($domain);
        if (! ($login['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $login['message'] ?? 'تعذّر الحصول على بيانات دخول WordPress من CyberPanel',
            ];
        }

        $loginData = is_array($login['data'] ?? null) ? $login['data'] : [];
        $password = (string) ($loginData['password'] ?? '');
        if ($password === '') {
            return ['success' => false, 'message' => 'لم يُرجع CyberPanel كلمة مرور للدخول إلى WordPress'];
        }

        $siteUrl = 'https://'.$domain;
        $jar = new CookieJar;
        $client = Http::timeout(max(180, $this->timeout))
            ->withOptions(['cookies' => $jar, 'verify' => $this->verifySsl, 'allow_redirects' => true]);

        $client->get($siteUrl.'/wp-login.php');

        $loginResponse = $client->asForm()->post($siteUrl.'/wp-login.php', [
            'log' => 'cyberpanel',
            'pwd' => $password,
            'wp-submit' => 'Log In',
            'redirect_to' => $siteUrl.'/wp-admin/',
            'rememberme' => 'forever',
        ]);

        if (! $loginResponse->successful() && ! $loginResponse->redirect()) {
            return ['success' => false, 'message' => 'فشل تسجيل الدخول إلى WordPress'];
        }

        $adminPage = $client->get($siteUrl.'/wp-admin/');
        if (! $adminPage->successful()) {
            return ['success' => false, 'message' => 'تعذّر فتح لوحة WordPress'];
        }

        $nonce = $this->extractWpRestNonce($adminPage->body());
        if ($nonce === null) {
            return ['success' => false, 'message' => 'تعذّر الحصول على رمز REST API من WordPress'];
        }

        return [
            'success' => true,
            'client' => $client,
            'site_url' => $siteUrl,
            'nonce' => $nonce,
        ];
    }

    /**
     * @param  array{client?: \Illuminate\Http\Client\PendingRequest, site_url?: string, nonce?: string}  $session
     * @param  array<string, mixed>  $queryOrBody
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    protected function wordpressRestRequest(array $session, string $method, string $path, array $queryOrBody = []): array
    {
        $client = $session['client'] ?? null;
        $siteUrl = rtrim((string) ($session['site_url'] ?? ''), '/');
        $nonce = (string) ($session['nonce'] ?? '');

        if (! $client || $siteUrl === '' || $nonce === '') {
            return ['success' => false, 'message' => 'جلسة WordPress غير صالحة'];
        }

        $url = $siteUrl.$path;
        $request = $client->withHeaders([
            'X-WP-Nonce' => $nonce,
            'Accept' => 'application/json',
        ]);

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $queryOrBody),
            'POST' => $request->asJson()->post($url, $queryOrBody),
            'PUT' => $request->asJson()->put($url, $queryOrBody),
            'DELETE' => $request->delete($url, $queryOrBody),
            default => null,
        };

        if ($response === null) {
            return ['success' => false, 'message' => 'طريقة HTTP غير مدعومة'];
        }

        $status = $response->status();
        $json = $response->json();

        if ($response->successful()) {
            return ['success' => true, 'data' => $json, 'status' => $status];
        }

        $message = $this->extractWordPressRestError($json, $status);

        return ['success' => false, 'message' => $message, 'data' => $json, 'status' => $status];
    }

    /**
     * @return array{success: bool, message?: string, user_id?: int}
     */
    protected function resolveWpUserIdByLogin(string $domain, string $login): array
    {
        $users = $this->getWpUsers($domain);
        if (! ($users['success'] ?? false)) {
            return ['success' => false, 'message' => $users['message'] ?? 'تعذّر جلب المستخدمين'];
        }

        $needle = strtolower($login);
        foreach ($users['data'] ?? [] as $user) {
            if (! is_array($user)) {
                continue;
            }
            $candidate = strtolower((string) ($user['user_login'] ?? $user['slug'] ?? ''));
            if ($candidate === $needle) {
                return ['success' => true, 'user_id' => (int) ($user['ID'] ?? $user['id'] ?? 0)];
            }
        }

        return ['success' => false, 'message' => 'المستخدم «'.$login.'» غير موجود'];
    }

  /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>
     */
    protected function normalizeWpRestUser(array $user): array
    {
        $roles = is_array($user['roles'] ?? null) ? $user['roles'] : [];

        return [
            'ID' => (int) ($user['id'] ?? $user['ID'] ?? 0),
            'id' => (int) ($user['id'] ?? $user['ID'] ?? 0),
            'user_login' => (string) ($user['slug'] ?? $user['user_login'] ?? ''),
            'user_email' => (string) ($user['email'] ?? $user['user_email'] ?? ''),
            'display_name' => (string) ($user['name'] ?? $user['display_name'] ?? ''),
            'roles' => $roles,
            'role' => (string) ($roles[0] ?? 'subscriber'),
        ];
    }

    protected function sanitizeWpRole(string $role): string
    {
        $role = preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($role))) ?: 'subscriber';
        $allowed = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];

        return in_array($role, $allowed, true) ? $role : 'subscriber';
    }

    protected function extractWpRestNonce(string $html): ?string
    {
        if (preg_match('/wpApiSettings\s*=\s*\{[^}]*"nonce"\s*:\s*"([^"]+)"/s', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/"nonce"\s*:\s*"([a-f0-9]+)"/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    protected function extractWordPressRestError(mixed $json, int $status): string
    {
        if (is_array($json)) {
            $message = trim((string) ($json['message'] ?? ''));
            if ($message !== '') {
                if (isset($json['code']) && $json['code'] !== '') {
                    return $message.' ('.$json['code'].')';
                }

                return $message;
            }
        }

        return match (true) {
            $status === 401 => 'غير مصرّح — تحقق من صلاحيات مستخدم cyberpanel',
            $status === 403 => 'ممنوع — لا تملك صلاحية إدارة المستخدمين',
            $status === 404 => 'المسار غير موجود على WordPress',
            default => 'فشل طلب WordPress REST API (HTTP '.$status.')',
        };
    }

    public function toggleWpExtension(string $domain, string $slug): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        $slug = trim($slug);
        if ($domain === '' || $slug === '') {
            return ['success' => false, 'message' => 'النطاق ومعرّف الإضافة/القالب مطلوبان'];
        }

        return $this->cloudRequest('ChangeState', [
            'domain' => $domain,
            'plugin' => $slug,
        ]);
    }

    /**
     * @param  list<string>  $selected
     */
    public function updateWpPlugins(string $domain, string $plugin = 'all', array $selected = [], bool $allChecked = false): array
    {
        return $this->wpBulkExtensionRequest('UpdatePlugins', $domain, $plugin, $selected, $allChecked);
    }

    /**
     * @param  list<string>  $selected
     */
    public function deleteWpPlugins(string $domain, string $plugin, array $selected = []): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $payload = ['domain' => trim($domain), 'plugin' => $plugin];
        if ($plugin === 'selected' && $selected !== []) {
            $payload['plugins'] = array_values($selected);
        }

        return $this->cloudRequest('DeletePlugins', $payload);
    }

    /**
     * @param  list<string>  $selected
     */
    public function updateWpThemes(string $domain, string $plugin = 'all', array $selected = [], bool $allChecked = false): array
    {
        return $this->wpBulkExtensionRequest('UpdateThemes', $domain, $plugin, $selected, $allChecked);
    }

    /**
     * @param  list<string>  $selected
     */
    public function deleteWpThemes(string $domain, string $plugin, array $selected = []): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $payload = ['domain' => trim($domain), 'plugin' => $plugin];
        if ($plugin === 'selected' && $selected !== []) {
            $payload['plugins'] = array_values($selected);
        }

        return $this->cloudRequest('DeleteThemes', $payload);
    }

    public function updateWpSetting(string $domain, string $setting, bool $value): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        return $this->cloudRequest('UpdateWPSettings', [
            'domain' => $domain,
            'setting' => $setting,
            'settingValue' => $value ? 1 : 0,
        ]);
    }

    public function saveWpAutoUpdateSettings(string $domain, string $wpCore, string $plugins, string $themes): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        return $this->cloudRequest('SaveAutoUpdateSettings', [
            'domainName' => $domain,
            'domain' => $domain,
            'wpCore' => $wpCore,
            'plugins' => $plugins,
            'themes' => $themes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function createWpBackup(string $domain, array $options = []): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        return $this->cloudRequest('SubmitCloudBackup', array_merge([
            'domain' => $domain,
            'data' => 1,
            'emails' => 0,
            'databases' => 1,
            'port' => 0,
            'ip' => 0,
            'destinationDomain' => 'None',
        ], $options));
    }

    public function listWpBackups(string $domain): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        $response = $this->cloudRequest('getCurrentCloudBackups', ['domainName' => $domain]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $items = $this->parseWpJsonList($response['data']['data'] ?? $response['data'] ?? null);

        return [
            'success' => true,
            'message' => $response['message'] ?? 'OK',
            'data' => $items,
            'status' => $response['status'] ?? 200,
        ];
    }

    public function deleteWpBackup(string $domain, string $backupFile): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        return $this->cloudRequest('deleteCloudBackup', [
            'domainName' => trim($domain),
            'backupFile' => trim($backupFile),
        ]);
    }

    public function restoreWpBackup(string $domain, string $backupFile, ?string $sourceDomain = null): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        return $this->cloudRequest('SubmitCloudBackupRestore', array_filter([
            'domain' => trim($domain),
            'backupFile' => trim($backupFile),
            'sourceDomain' => $sourceDomain ?? 'None',
        ], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Reinstall WordPress core files preserving wp-content and wp-config.php.
     * Tries CyberPanel WP Manager first, then WordPress dashboard reinstall.
     *
     * @return array{success: bool, message?: string, data?: array<string, mixed>|null, status?: int}
     */
    public function reinstallWpCore(string $domain, ?int $wpId = null): array
    {
        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        if ($this->adminPassword !== '') {
            $manager = $this->reinstallWpCoreViaWpManager($domain, $wpId);
            if ($manager['success'] ?? false) {
                return $manager;
            }
        }

        return $this->reinstallWpCoreViaWordPressDashboard($domain);
    }

    /**
     * @return array{success: bool, message?: string, data?: array<string, mixed>|null, status?: int}
     */
    protected function reinstallWpCoreViaWpManager(string $domain, ?int $wpId = null): array
    {
        try {
            $session = $this->establishWebsiteSession();
            if (! ($session['success'] ?? false)) {
                return $session;
            }

            /** @var CookieJar $jar */
            $jar = $session['jar'];
            $this->warmWebsiteSession($jar);

            if ($wpId === null) {
                $resolved = $this->resolveWpSiteId($domain, $jar);
                if (! ($resolved['success'] ?? false)) {
                    return $resolved;
                }
                $wpId = (int) $resolved['wp_id'];
            }

            $response = $this->websiteClientWithCsrf($jar)
                ->acceptJson()
                ->asJson()
                ->post($this->getBaseUrl().'/websiteFunctions/installwpcore', [
                    'WPid' => $wpId,
                ]);

            $json = $response->json();
            if (! is_array($json)) {
                return [
                    'success' => false,
                    'message' => 'استجابة غير متوقعة من CyberPanel عند إعادة التثبيت',
                    'status' => $response->status(),
                ];
            }

            $ok = (int) ($json['status'] ?? 0) === 1 && (int) ($json['installStatus'] ?? 0) === 1;
            $message = trim((string) ($json['error_message'] ?? ''));
            if ($message === '' || strtolower($message) === 'none') {
                $message = $ok
                    ? 'تمت إعادة تثبيت ملفات WordPress عبر CyberPanel WP Manager'
                    : 'فشل إعادة تثبيت ملفات WordPress عبر WP Manager';
            }

            return [
                'success' => $ok,
                'message' => $message,
                'data' => [
                    'method' => 'cyberpanel_wp_manager',
                    'wp_id' => $wpId,
                    'output' => $json['result'] ?? null,
                ],
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::warning('CyberPanel WP Manager core reinstall failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reinstall via wp-admin/update-core.php (official WP reinstall, keeps wp-content).
     *
     * @return array{success: bool, message?: string, data?: array<string, mixed>|null, status?: int}
     */
    protected function reinstallWpCoreViaWordPressDashboard(string $domain): array
    {
        $probe = $this->getWpPlugins($domain);
        if (! ($probe['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'تعذّر الوصول إلى WordPress عبر CyberPanel API — تحقق من الإعدادات والنطاق',
            ];
        }

        $login = $this->wordpressAutoLogin($domain);
        if (! ($login['success'] ?? false)) {
            return [
                'success' => false,
                'message' => $login['message'] ?? 'تعذّر الحصول على بيانات دخول WordPress من CyberPanel',
            ];
        }

        $loginData = is_array($login['data'] ?? null) ? $login['data'] : [];
        $password = (string) ($loginData['password'] ?? '');
        if ($password === '') {
            return ['success' => false, 'message' => 'لم يُرجع CyberPanel كلمة مرور للدخول إلى WordPress'];
        }

        $siteUrl = 'https://'.$domain;
        $jar = new CookieJar;
        $client = Http::timeout(max(180, $this->timeout))
            ->withOptions(['cookies' => $jar, 'verify' => $this->verifySsl, 'allow_redirects' => true]);

        $client->get($siteUrl.'/wp-login.php');

        $loginResponse = $client->asForm()->post($siteUrl.'/wp-login.php', [
            'log' => 'cyberpanel',
            'pwd' => $password,
            'wp-submit' => 'Log In',
            'redirect_to' => $siteUrl.'/wp-admin/',
            'rememberme' => 'forever',
        ]);

        if (! $loginResponse->successful() && ! $loginResponse->redirect()) {
            return ['success' => false, 'message' => 'فشل تسجيل الدخول إلى WordPress'];
        }

        $updatePage = $client->get($siteUrl.'/wp-admin/update-core.php');
        if (! $updatePage->successful()) {
            return ['success' => false, 'message' => 'تعذّر فتح صفحة تحديثات WordPress'];
        }

        $nonce = null;
        if (preg_match('/do-core-reinstall[^"\']*_wpnonce=([a-f0-9]+)/i', $updatePage->body(), $matches)) {
            $nonce = $matches[1];
        }

        if ($nonce === null) {
            return [
                'success' => false,
                'message' => 'لم تُعثر على خيار إعادة التثبيت في لوحة WordPress — قد لا تملك صلاحية manage_options',
            ];
        }

        $reinstall = $client->get($siteUrl.'/wp-admin/update-core.php?action=do-core-reinstall&_wpnonce='.$nonce);
        $body = $reinstall->body();

        $ok = $reinstall->successful() && (
            stripos($body, 'WordPress updated successfully') !== false
            || stripos($body, 'تم تحديث ووردبريس') !== false
            || stripos($body, 'Re-installing') !== false
            || stripos($body, 'إعادة تثبيت') !== false
            || (stripos($body, 'wp-admin') !== false && stripos($body, 'fatal error') === false)
        );

        return [
            'success' => $ok,
            'message' => $ok
                ? 'تمت إعادة تثبيت ملفات WordPress عبر لوحة التحكم (wp-content وwp-config.php محفوظان)'
                : 'فشل إعادة التثبيت عبر لوحة WordPress',
            'data' => ['method' => 'wordpress_dashboard'],
            'status' => $reinstall->status(),
        ];
    }

    /**
     * @return array{success: bool, message?: string, jar?: CookieJar}
     */
    protected function establishWebsiteSession(): array
    {
        if ($this->adminUser === '' || $this->adminPassword === '') {
            return ['success' => false, 'message' => 'بيانات دخول المدير غير مكتملة'];
        }

        $jar = new CookieJar;
        $response = $this->makeWebsiteHttpClient($jar)
            ->asForm()
            ->post($this->getBaseUrl().'/api/loginAPI', [
                'username' => $this->adminUser,
                'password' => $this->adminPassword,
            ]);

        $body = $response->body();
        if (stripos($body, 'Invalid Credentials') !== false) {
            return ['success' => false, 'message' => 'فشل تسجيل الدخول إلى CyberPanel — تحقق من كلمة مرور المدير'];
        }
        if (stripos($body, 'API Access Disabled') !== false) {
            return ['success' => false, 'message' => 'API Access معطّل لحساب المدير في CyberPanel'];
        }

        if ($response->successful() || $response->redirect()) {
            $this->warmWebsiteSession($jar);

            return ['success' => true, 'jar' => $jar];
        }

        return [
            'success' => false,
            'message' => 'تعذّر فتح جلسة لوحة CyberPanel (HTTP '.$response->status().')',
        ];
    }

    /**
     * @return array{success: bool, message?: string, wp_id?: int}
     */
    protected function resolveWpSiteId(string $domain, CookieJar $jar): array
    {
        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        $wpId = $this->resolveWpSiteIdFromCloudApi($domain);
        if ($wpId !== null) {
            return ['success' => true, 'wp_id' => $wpId];
        }

        $this->scanWordpressSitesInManager($jar);

        $wpId = $this->resolveWpSiteIdFromCloudApi($domain);
        if ($wpId !== null) {
            return ['success' => true, 'wp_id' => $wpId];
        }

        $details = $this->websiteClientWithCsrf($jar)
            ->asForm()
            ->post($this->getBaseUrl().'/websiteFunctions/fetchWPDetails', ['domain' => $domain]);

        if ($details->ok()) {
            $json = $details->json();
            if (is_array($json) && (int) ($json['status'] ?? 0) === 1) {
                $sites = is_array($json['sites'] ?? null) ? $json['sites'] : [];
                $match = $this->pickWpSiteForDomain($sites, $domain);
                if ($match !== null && isset($match['id'])) {
                    return ['success' => true, 'wp_id' => (int) $match['id']];
                }
            }
        }

        $list = $this->websiteClientWithCsrf($jar)->get($this->getBaseUrl().'/websiteFunctions/ListWPSites');
        if ($list->ok()) {
            $wpId = $this->extractWpSiteIdFromListHtml($list->body(), $domain);
            if ($wpId !== null) {
                return ['success' => true, 'wp_id' => $wpId];
            }
        }

        $wpId = $this->extractWpSiteIdFromDomainPage($jar, $domain);
        if ($wpId !== null) {
            return ['success' => true, 'wp_id' => $wpId];
        }

        return [
            'success' => false,
            'message' => 'تعذّر تحديد الموقع في WP Manager. تأكد أن WordPress مثبت على النطاق، ثم جرّب فتح WP Manager في CyberPanel مرة واحدة.',
        ];
    }

    protected function scanWordpressSitesInManager(CookieJar $jar): void
    {
        try {
            $this->websiteClientWithCsrf($jar)
                ->acceptJson()
                ->asJson()
                ->post($this->getBaseUrl().'/websiteFunctions/ScanWordpressSite', []);
        } catch (\Throwable) {
            // Non-fatal: scan may be unavailable on older CyberPanel builds.
        }
    }

    protected function warmWebsiteSession(CookieJar $jar): void
    {
        try {
            $this->makeWebsiteHttpClient($jar)->get($this->getBaseUrl().'/websiteFunctions/ListWPSites');
        } catch (\Throwable) {
            // Ignore — CSRF cookie may already exist from login redirect.
        }
    }

    protected function readCsrfTokenFromJar(CookieJar $jar): ?string
    {
        foreach ($jar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'csrftoken') {
                $value = trim((string) ($cookie['Value'] ?? ''));

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    protected function websiteClientWithCsrf(CookieJar $jar): \Illuminate\Http\Client\PendingRequest
    {
        $client = $this->makeWebsiteHttpClient($jar);
        $token = $this->readCsrfTokenFromJar($jar);
        if ($token !== null) {
            $client = $client->withHeaders([
                'X-CSRFToken' => $token,
                'Referer' => $this->getBaseUrl().'/',
            ]);
        }

        return $client;
    }

    protected function resolveWpSiteIdFromCloudApi(string $domain): ?int
    {
        if (! $this->supportsCloudOperations()) {
            return null;
        }

        $response = $this->cloudRequest('fetchWebsites', ['page' => 1]);
        if (! ($response['success'] ?? false)) {
            return null;
        }

        $websites = $this->extractWebsitesFromFetchResponse($response['data'] ?? null);
        $needle = strtolower($domain);

        foreach ($websites as $site) {
            if (! is_array($site)) {
                continue;
            }
            if (strtolower((string) ($site['domain'] ?? '')) !== $needle) {
                continue;
            }

            $wpSites = is_array($site['wp_sites'] ?? null) ? $site['wp_sites'] : [];
            if ($wpSites === []) {
                continue;
            }

            $match = $this->pickWpSiteForDomain($wpSites, $domain);

            if ($match !== null && isset($match['id'])) {
                return (int) $match['id'];
            }

            $fallback = (int) ($wpSites[0]['id'] ?? 0);

            return $fallback > 0 ? $fallback : null;
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function extractWebsitesFromFetchResponse(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $this->extractWebsitesFromFetchResponse($decoded) : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        if (isset($raw['data']) && is_string($raw['data'])) {
            $decoded = json_decode($raw['data'], true);

            return is_array($decoded) ? $decoded : [];
        }

        if (isset($raw['data']) && is_array($raw['data'])) {
            return array_is_list($raw['data']) ? $raw['data'] : $this->normalizeList($raw['data']);
        }

        if (array_is_list($raw)) {
            return array_values(array_filter($raw, 'is_array'));
        }

        return $this->normalizeList($raw);
    }

    protected function extractWpSiteIdFromDomainPage(CookieJar $jar, string $domain): ?int
    {
        $encoded = rawurlencode($domain);
        $candidates = [
            $this->getBaseUrl().'/websiteFunctions/'.$encoded.'/workspace',
            $this->getBaseUrl().'/websiteFunctions/'.$encoded.'/',
        ];

        foreach ($candidates as $url) {
            try {
                $response = $this->makeWebsiteHttpClient($jar)->get($url);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->ok()) {
                continue;
            }

            if (preg_match_all('/WPHome\?[^"\']*ID=(\d+)/i', $response->body(), $matches)) {
                $ids = array_map('intval', $matches[1]);
                if (count($ids) === 1) {
                    return $ids[0];
                }
            }

            if (preg_match_all('/data-site-id=["\'](\d+)["\']/i', $response->body(), $matches)) {
                $ids = array_map('intval', $matches[1]);
                if (count($ids) === 1) {
                    return $ids[0];
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $sites
     * @return array<string, mixed>|null
     */
    protected function pickWpSiteForDomain(array $sites, string $domain): ?array
    {
        if ($sites === []) {
            return null;
        }

        $needle = strtolower($domain);
        foreach ($sites as $site) {
            if (! is_array($site)) {
                continue;
            }
            $url = strtolower((string) ($site['url'] ?? $site['title'] ?? ''));
            if ($url === $needle || str_contains($url, $needle)) {
                return $site;
            }
        }

        return count($sites) === 1 ? $sites[0] : null;
    }

    protected function extractWpSiteIdFromListHtml(string $html, string $domain): ?int
    {
        $sites = null;

        if (preg_match('/\$scope\.wpSites\s*=\s*(\[[\s\S]*?\]);/s', $html, $matches)) {
            $sites = json_decode($matches[1], true);
        } elseif (preg_match('/"wpsite"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/s', $html, $matches)) {
            $sites = json_decode(stripcslashes($matches[1]), true);
        } elseif (preg_match('/"wpsite"\s*:\s*(\[[\s\S]*?\])\s*,/s', $html, $matches)) {
            $sites = json_decode($matches[1], true);
        } elseif (preg_match('/var\s+wpsite\s*=\s*(\[[\s\S]*?\]);/s', $html, $matches)) {
            $sites = json_decode($matches[1], true);
        }

        if (is_array($sites)) {
            $match = $this->pickWpSiteForDomain($sites, $domain);

            return isset($match['id']) ? (int) $match['id'] : null;
        }

        if (preg_match_all('/WPHome\?[^"\']*ID=(\d+)/i', $html, $idMatches)) {
            $ids = array_values(array_unique(array_map('intval', $idMatches[1])));
            if (count($ids) === 1) {
                return $ids[0];
            }
            foreach ($ids as $id) {
                if ($id > 0) {
                    return $id;
                }
            }
        }

        return null;
    }

    protected function makeWebsiteHttpClient(?CookieJar $jar = null): \Illuminate\Http\Client\PendingRequest
    {
        $options = [
            'verify' => $this->verifySsl,
            'allow_redirects' => true,
        ];

        if ($jar !== null) {
            $options['cookies'] = $jar;
        }

        return Http::timeout(max(180, $this->timeout))->withOptions($options);
    }

    /**
     * @param  list<string>  $selected
     */
    protected function wpBulkExtensionRequest(string $controller, string $domain, string $plugin, array $selected, bool $allChecked): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $payload = [
            'domain' => trim($domain),
            'plugin' => $plugin,
        ];

        if ($plugin === 'selected') {
            $payload['plugins'] = array_values($selected);
            $payload['allPluginsChecked'] = $allChecked ? 1 : 0;
        }

        return $this->cloudRequest($controller, $payload);
    }

    protected function wpListRequest(string $controller, string $domain): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'message' => 'النطاق مطلوب'];
        }

        $response = $this->cloudRequest($controller, ['domain' => $domain]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $raw = is_array($response['data'] ?? null) ? ($response['data']['data'] ?? null) : null;
        $items = $this->parseWpJsonList($raw);

        return [
            'success' => true,
            'message' => $response['message'] ?? 'OK',
            'data' => $items,
            'status' => $response['status'] ?? 200,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseWpJsonList(mixed $raw): array
    {
        if (is_array($raw)) {
            if (array_is_list($raw)) {
                return array_values(array_filter($raw, 'is_array'));
            }

            return $this->normalizeList($raw);
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

  /**
     * @return array{success: bool, message?: string, data?: mixed, status?: int, completed?: bool}
     */
    public function pollInstallStatus(string $statusFile): array
    {
        if ($guard = $this->cloudAuthGuard()) {
            return $guard;
        }

        $statusFile = trim($statusFile);
        if ($statusFile === '') {
            return ['success' => false, 'message' => 'مسار حالة التثبيت غير متوفر'];
        }

        $response = $this->cloudRequest('statusFunc', ['statusFile' => $statusFile]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $completed = (int) ($data['abort'] ?? 0) === 1;
        $ok = (int) ($data['status'] ?? 0) === 1;

        return [
            'success' => $completed ? $ok : true,
            'message' => $this->extractStatusMessage($data),
            'data' => $data,
            'status' => $response['status'] ?? 200,
            'completed' => $completed,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractStatusMessage(array $data): string
    {
        $current = trim((string) ($data['currentStatus'] ?? ''));
        if ($current !== '') {
            return $current;
        }

        $error = trim((string) ($data['error_message'] ?? ''));
        if ($error !== '' && strtolower($error) !== 'none') {
            return $error;
        }

        if ((int) ($data['abort'] ?? 0) === 1 && (int) ($data['status'] ?? 0) === 1) {
            return 'اكتمل التثبيت بنجاح';
        }

        return 'جاري التثبيت...';
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function cloudRequest(string $controller, array $extra = []): array
    {
        if ($this->apiStyle === 'legacy') {
            return $this->legacyRequest($controller, $extra);
        }

        $body = array_merge($this->authPayload(), [
            'controller' => $controller,
            'serverUserName' => $this->adminUser,
        ], $extra);

        return $this->httpPost($this->getBaseUrl().'/cloudAPI/', $body);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    public function legacyRequest(string $action, array $params = []): array
    {
        $body = array_merge($this->authPayload(), $params);

        return $this->httpPost($this->getBaseUrl().'/api/'.$action, $body);
    }

    /**
     * @return array<string, string>
     */
    protected function authPayload(): array
    {
        return array_filter([
            'adminUser' => $this->adminUser,
            'adminPass' => $this->adminPassword !== '' ? $this->adminPassword : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{success: bool, message?: string, data?: mixed, status?: int}
     */
    protected function httpPost(string $url, array $body, ?string $authHeaderOverride = null, bool $skipAuthDiscovery = false): array
    {
        if ($this->host === '') {
            return ['success' => false, 'message' => 'عنوان CyberPanel غير مضبوط', 'status' => 0];
        }

        try {
            $request = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson();

            if (! $this->verifySsl) {
                $request = $request->withoutVerifying();
            }

            if (str_contains($url, '/cloudAPI/')) {
                $authHeader = $authHeaderOverride;
                if ($authHeader === null && ! $skipAuthDiscovery) {
                    $authHeader = $this->discoverAuthorizationHeader();
                }
                if ($authHeader) {
                    $request = $request->withHeaders(['Authorization' => $authHeader]);
                }
            }

            $response = $request->post($url, $body);
            $status = $response->status();
            $json = $response->json();

            if (! is_array($json)) {
                $text = trim($response->body());
                if ($response->successful()) {
                    return ['success' => true, 'data' => $text, 'status' => $status];
                }

                return [
                    'success' => false,
                    'message' => $this->formatHttpError($text, $status),
                    'status' => $status,
                ];
            }

            $success = $this->responseIndicatesSuccess($json, $response->successful());

            if (! $success) {
                Log::warning('CyberPanel API error', [
                    'url' => $url,
                    'status' => $status,
                    'body' => $json,
                ]);
            }

            return [
                'success' => $success,
                'message' => $this->extractMessage($json),
                'data' => $json,
                'status' => $status,
            ];
        } catch (\Throwable $e) {
            Log::error('CyberPanel API exception', ['url' => $url, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage(), 'status' => 0];
        }
    }

    protected function formatHttpError(string $text, int $status): string
    {
        if (stripos($text, '<!doctype') !== false || stripos($text, '<html') !== false) {
            return match (true) {
                $status === 404 => 'المسار غير موجود على CyberPanel (404). تحقق من عنوان اللوحة ونمط API.',
                $status === 401, $status === 403 => 'رفض CyberPanel الطلب (HTTP '.$status.'). تحقق من API Token.',
                default => 'استجابة HTML غير متوقعة من CyberPanel (HTTP '.$status.')',
            };
        }

        if (strlen($text) > 400) {
            return mb_substr($text, 0, 400).'…';
        }

        return $text !== '' ? $text : 'استجابة غير متوقعة من CyberPanel';
    }

    /**
     * @param  array<string, mixed>  $json
     */
    protected function responseIndicatesSuccess(array $json, bool $httpOk): bool
    {
        if (isset($json['status'])) {
            $status = is_numeric($json['status']) ? (int) $json['status'] : null;
            if ($status === 1 || $status === 200) {
                return true;
            }
            if ($status === 0) {
                return false;
            }
        }

        if (isset($json['success'])) {
            return filter_var($json['success'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($json['error_message']) && $json['error_message'] !== '' && $json['error_message'] !== 'None') {
            return false;
        }

        if (isset($json['errorMessage']) && $json['errorMessage'] !== '' && $json['errorMessage'] !== 'None') {
            return false;
        }

        return $httpOk;
    }

    /**
     * @param  array<string, mixed>  $json
     */
    protected function extractMessage(array $json): string
    {
        foreach (['error_message', 'errorMessage', 'message', 'statusMessage'] as $key) {
            $val = trim((string) ($json[$key] ?? ''));
            if ($val !== '' && strtolower($val) !== 'none') {
                if (stripos($val, 'Invalid login information') !== false) {
                    return 'فشل مصادقة CloudAPI. تأكد من: (1) تفعيل API Access للمستخدم admin وحفظ التغييرات، (2) صحة كلمة مرور المدير في الإعدادات.';
                }

                if (stripos($val, 'dangerous characters') !== false) {
                    return 'كلمة مرور WordPress تحتوي رموزاً غير مسموحة في CyberPanel. أعد المحاولة.';
                }

                return $val;
            }
        }

        return $json['success'] ?? false ? 'OK' : 'فشل الطلب';
    }

  /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizeList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if ($data === []) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        foreach (['websites', 'websiteList', 'packages', 'packageList', 'data', 'list'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $this->normalizeList($data[$key]);
            }
        }

        return [$data];
    }

    public function clearPackagesCache(): void
    {
        Cache::forget('cyberpanel_packages_list');
    }
}
