<?php

namespace App\Services\Whm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmApiService
{
    protected string $host = '';

    protected string $username = '';

    protected string $apiToken = '';

    protected bool $verifySsl = true;

    protected int $timeout = 60;

    protected string $defaultPackage = 'default';

    public function __construct(protected WhmSettingsService $settings)
    {
        $this->loadConnectionConfig();
    }

    protected function loadConnectionConfig(): void
    {
        $config = $this->settings->getConnectionConfig();
        $this->host = $config['host'] ?? '';
        $this->username = $config['username'] ?? 'root';
        $this->apiToken = $config['api_token'] ?? '';
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? true);
        $this->timeout = (int) ($config['timeout'] ?? 60);
        $this->defaultPackage = $config['default_package'] ?? 'default';
    }

    public function refreshConnection(): void
    {
        $this->settings->clearCache();
        $this->loadConnectionConfig();
    }

    public function getDefaultPackage(): string
    {
        return $this->defaultPackage;
    }

    public function isConfigured(): bool
    {
        return $this->host !== '' && $this->apiToken !== '' && $this->username !== '';
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function ping(): array
    {
        return $this->request('version');
    }

    /**
     * @return array{success: bool, message?: string, accounts?: array<int, array<string, mixed>>}
     */
    public function listAccounts(): array
    {
        $response = $this->request('listaccts', [
            'want' => 'user,domain,email,plan,suspended,startdate,unix_startdate,suspendtime,outgoing_mail_suspended',
        ]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $accounts = $data['acct'] ?? $data['account'] ?? [];
        if (! is_array($accounts)) {
            $accounts = [];
        }
        if ($accounts !== [] && ! array_is_list($accounts)) {
            $accounts = [$accounts];
        }

        return ['success' => true, 'accounts' => $accounts];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function createAccount(array $params): array
    {
        $payload = array_merge([
            'username' => $params['username'] ?? '',
            'domain' => $params['domain'] ?? '',
            'password' => $params['password'] ?? '',
            'plan' => $params['plan'] ?? $this->defaultPackage,
            'contactemail' => $params['contactemail'] ?? $params['email'] ?? '',
        ], $params);

        return $this->request('createacct', $payload);
    }

    public function suspendAccount(string $username, ?string $reason = null): array
    {
        return $this->request('suspendacct', array_filter([
            'user' => $username,
            'reason' => $reason ?? 'Suspended from panel',
        ]));
    }

    public function unsuspendAccount(string $username): array
    {
        return $this->request('unsuspendacct', ['user' => $username]);
    }

    public function terminateAccount(string $username, bool $keepDns = false): array
    {
        return $this->request('removeacct', [
            'user' => $username,
            'keepdns' => $keepDns ? 1 : 0,
        ]);
    }

    /**
     * @return array{success: bool, message?: string, summary?: array<string, mixed>}
     */
    public function accountSummary(string $username): array
    {
        $response = $this->request('accountsummary', ['user' => $username]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $acct = $data['acct'] ?? [];
        if (is_array($acct) && isset($acct[0]) && is_array($acct[0])) {
            $acct = $acct[0];
        }
        if (! is_array($acct)) {
            return ['success' => false, 'message' => 'ملخص الحساب غير متوفر'];
        }

        return ['success' => true, 'summary' => $acct];
    }

    /**
     * @return array{success: bool, message?: string, packages?: array<int, array<string, mixed>>}
     */
    public function listPackages(string $want = 'creatable'): array
    {
        $cacheKey = 'whm_listpkgs_'.$want;

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $response = $this->request('listpkgs', ['want' => $want]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $pkgs = $data['pkg'] ?? $data['package'] ?? [];
        if (! is_array($pkgs)) {
            $pkgs = [];
        }
        if ($pkgs !== [] && ! array_is_list($pkgs)) {
            $pkgs = [$pkgs];
        }

        $result = ['success' => true, 'packages' => $pkgs];
        Cache::put($cacheKey, $result, 900);

        return $result;
    }

    /**
     * @return array{success: bool, message?: string, records?: array<int, array<string, mixed>>}
     */
    public function dumpZone(string $domain): array
    {
        $response = $this->request('dumpzone', ['domain' => $domain]);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $zone = $response['data']['zone'] ?? [];
        if (! is_array($zone)) {
            return ['success' => true, 'records' => []];
        }

        $records = [];
        foreach ($zone as $item) {
            if (! is_array($item)) {
                continue;
            }
            $record = $item['record'] ?? $item;
            if (is_array($record) && ! array_is_list($record)) {
                $records[] = $record;
            } elseif (is_array($record)) {
                foreach ($record as $r) {
                    if (is_array($r)) {
                        $records[] = $r;
                    }
                }
            }
        }

        return ['success' => true, 'records' => $records];
    }

    /**
     * Call cPanel UAPI for a user via WHM proxy.
     *
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function cpanelUapi(string $username, string $module, string $function, array $params = []): array
    {
        $payload = array_merge([
            'cpanel_jsonapi_user' => $username,
            'cpanel_jsonapi_apiversion' => 3,
            'cpanel_jsonapi_module' => $module,
            'cpanel_jsonapi_func' => $function,
        ], $params);

        $response = $this->request('cpanel', $payload);

        return $this->normalizeCpanelResponse($response);
    }

    /**
     * @param  array{success: bool, message?: string, data?: mixed}  $response
     * @return array{success: bool, message?: string, data?: mixed}
     */
    protected function normalizeCpanelResponse(array $response): array
    {
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $raw = $response['data'] ?? [];
        if (! is_array($raw)) {
            return $response;
        }

        $cpanel = $raw['cpanelresult'] ?? $raw;
        if (is_array($cpanel) && isset($cpanel['error'])) {
            $err = $cpanel['error'];
            if (is_array($err)) {
                $err = $err['message'] ?? json_encode($err);
            }

            return ['success' => false, 'message' => (string) $err, 'data' => $cpanel];
        }

        if (is_array($cpanel) && isset($cpanel['data'])) {
            $inner = $cpanel['data'];

            if (is_array($inner) && isset($inner['result']) && is_array($inner['result'])) {
                $result = $inner['result'];
                if (isset($result['status']) && (int) $result['status'] === 0) {
                    $errors = $result['errors'] ?? 'فشل UAPI';
                    if (is_array($errors)) {
                        $errors = implode('; ', array_map('strval', $errors));
                    }

                    return ['success' => false, 'message' => (string) $errors, 'data' => $inner];
                }

                return ['success' => true, 'data' => $inner];
            }

            if (is_array($inner) && isset($inner['result']['status']) && (int) $inner['result']['status'] === 0) {
                $errors = $inner['result']['errors'] ?? 'فشل UAPI';
                if (is_array($errors)) {
                    $errors = implode('; ', array_map('strval', $errors));
                }

                return ['success' => false, 'message' => (string) $errors, 'data' => $inner];
            }

            return ['success' => true, 'data' => $inner];
        }

        if (is_array($cpanel) && isset($cpanel['result']) && is_array($cpanel['result'])) {
            if (isset($cpanel['result']['status']) && (int) $cpanel['result']['status'] === 0) {
                $errors = $cpanel['result']['errors'] ?? 'فشل UAPI';
                if (is_array($errors)) {
                    $errors = implode('; ', array_map('strval', $errors));
                }

                return ['success' => false, 'message' => (string) $errors, 'data' => $cpanel];
            }

            return ['success' => true, 'data' => $cpanel];
        }

        return $response;
    }

    public function clearPackagesCache(): void
    {
        foreach (['creatable', 'all', 'viewable', 'editable'] as $want) {
            Cache::forget('whm_listpkgs_'.$want);
        }
    }

    /**
     * @return array{success: bool, message?: string, load?: array{one: float, five: float, fifteen: float}}
     */
    public function systemLoadAvg(): array
    {
        $response = $this->request('systemloadavg');
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        if (! is_array($data)) {
            return ['success' => false, 'message' => 'بيانات حمل السيرفر غير متوفرة'];
        }

        return [
            'success' => true,
            'load' => [
                'one' => (float) ($data['one'] ?? 0),
                'five' => (float) ($data['five'] ?? 0),
                'fifteen' => (float) ($data['fifteen'] ?? 0),
            ],
        ];
    }

    /**
     * @return array{success: bool, message?: string, partitions?: array<int, array<string, mixed>>}
     */
    public function getDiskUsage(): array
    {
        $response = $this->request('getdiskusage');
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $data = $response['data'] ?? [];
        $partitions = $data['partition'] ?? [];
        if (! is_array($partitions)) {
            $partitions = [];
        }
        if ($partitions !== [] && ! array_is_list($partitions)) {
            $partitions = [$partitions];
        }

        return ['success' => true, 'partitions' => $partitions];
    }

    /**
     * Server-wide status (load, memory, swap) via cPanel UAPI proxy.
     *
     * @return array{success: bool, message?: string, items?: array<int, array<string, mixed>>}
     */
    public function serverInformation(string $cpanelUser): array
    {
        $response = $this->cpanelUapi($cpanelUser, 'ServerInformation', 'get_information');
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $items = $this->extractUapiDataList($response['data'] ?? null);
        if ($items === []) {
            return ['success' => false, 'message' => 'بيانات الذاكرة والمعالج غير متوفرة من WHM'];
        }

        return ['success' => true, 'items' => $items];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractUapiDataList(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if ($payload !== [] && array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        if (isset($payload['result']) && is_array($payload['result'])) {
            $result = $payload['result'];
            if (isset($result['status']) && (int) $result['status'] === 0) {
                return [];
            }
            if (isset($result['data']) && is_array($result['data'])) {
                return $this->extractUapiDataList($result['data']);
            }
        }

        if (isset($payload['data'])) {
            return $this->extractUapiDataList($payload['data']);
        }

        if (isset($payload['cpanelresult']) && is_array($payload['cpanelresult'])) {
            return $this->extractUapiDataList($payload['cpanelresult']);
        }

        if (isset($payload['uapi']) && is_array($payload['uapi'])) {
            return $this->extractUapiDataList($payload['uapi']);
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $params  contactemail, newuser, domain, ...
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function modifyAccount(string $user, array $params = []): array
    {
        return $this->request('modifyacct', array_merge(['user' => $user], $params));
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function changePassword(string $user, string $password, bool $updateMysql = true): array
    {
        return $this->request('passwd', [
            'user' => $user,
            'password' => $password,
            'db_pass_update' => $updateMysql ? 1 : 0,
        ]);
    }

    /**
     * Create a one-time cPanel / Webmail login URL (WHM SSO).
     *
     * @return array{success: bool, message?: string, url?: string, data?: mixed}
     */
    public function createUserSession(string $user, string $service = 'cpaneld', ?string $app = null): array
    {
        $params = [
            'user' => $user,
            'service' => $service,
        ];
        if ($app !== null && $app !== '') {
            $params['app'] = $app;
        }

        $response = $this->request('create_user_session', $params);
        if (! ($response['success'] ?? false)) {
            return $response;
        }

        $url = $this->extractSessionUrl($response['data'] ?? []);
        if ($url === null || $url === '') {
            return [
                'success' => false,
                'message' => 'لم يُرجع WHM رابط جلسة cPanel — تحقق من صلاحيات توكن API',
                'data' => $response['data'] ?? null,
            ];
        }

        return ['success' => true, 'url' => $url, 'data' => $response['data'] ?? null];
    }

    /**
     * @param  array<string, mixed>|mixed  $data
     */
    protected function extractSessionUrl(mixed $data): ?string
    {
        if (is_string($data) && $data !== '') {
            return $data;
        }

        if (! is_array($data)) {
            return null;
        }

        if (! empty($data['url']) && is_string($data['url'])) {
            return $data['url'];
        }

        foreach (['cpanel_url', 'session_url', 'redirect'] as $key) {
            if (! empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    /**
     * @return array{success: bool, message?: string, data?: mixed}
     */
    public function request(string $function, array $params = []): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة — اضبطها من لوحة التحكم → WHM / cPanel → إعدادات WHM'];
        }

        $url = $this->host.'/json-api/'.$function;
        $params['api.version'] = $params['api.version'] ?? 1;

        try {
            $response = Http::withOptions([
                'verify' => $this->verifySsl,
            ])
                ->timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'whm '.$this->username.':'.$this->apiToken,
                ])
                ->get($url, $params);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'HTTP '.$response->status().': '.$response->body(),
                ];
            }

            $json = $response->json();
            if (! is_array($json)) {
                return ['success' => false, 'message' => 'استجابة غير صالحة من WHM'];
            }

            if (isset($json['status']) && (int) $json['status'] === 0) {
                $errors = $json['error'] ?? $json['errors'] ?? 'خطأ WHM';
                if (is_array($errors)) {
                    $errors = implode('; ', $errors);
                }

                return ['success' => false, 'message' => (string) $errors, 'data' => $json];
            }

            if (isset($json['metadata']['result']) && (int) $json['metadata']['result'] === 0) {
                return [
                    'success' => false,
                    'message' => (string) ($json['metadata']['reason'] ?? 'فشل WHM'),
                    'data' => $json,
                ];
            }

            return ['success' => true, 'data' => $json['data'] ?? $json];
        } catch (\Throwable $e) {
            Log::error('WHM API error', ['function' => $function, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
