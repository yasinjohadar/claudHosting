<?php

namespace App\Services;

use App\Services\Cloudflare\CloudflareSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareApiService
{
    protected string $token = '';

    protected string $accountId = '';

    protected int $timeout = 30;

    protected int $cacheTtl = 600;

    public function __construct(protected CloudflareSettingsService $settings)
    {
        $this->loadConnectionConfig();
    }

    protected function loadConnectionConfig(): void
    {
        $config = $this->settings->getConnectionConfig();
        $this->token = $config['api_token'] ?? '';
        $this->accountId = self::sanitizeAccountId($config['account_id'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? 600);
    }

    public static function isValidAccountId(string $accountId): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', trim($accountId)) === 1;
    }

    public static function sanitizeAccountId(string $accountId): string
    {
        $accountId = trim($accountId);

        return self::isValidAccountId($accountId) ? $accountId : '';
    }

    public function refreshConnection(): void
    {
        $this->settings->clearCache();
        $this->loadConnectionConfig();
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * @return array{success: bool, data?: mixed, message?: string, status?: int, errors?: array}
     */
    protected function request(string $method, string $path, array $query = [], array $body = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'يرجى ضبط رمز Cloudflare API من صفحة إعدادات Cloudflare في لوحة التحكم',
            ];
        }

        $url = rtrim(config('cloudflare.api_base'), '/').'/'.ltrim($path, '/');

        try {
            $pending = Http::withToken($this->token)
                ->acceptJson()
                ->timeout($this->timeout);

            $response = match (strtoupper($method)) {
                'POST' => $pending->post($url, $body),
                'PUT' => $pending->put($url, $body),
                'PATCH' => $pending->patch($url, $body),
                'DELETE' => $pending->delete($url, $body),
                default => $pending->get($url, $query),
            };

            $json = $response->json();
            if (! is_array($json)) {
                $json = [];
            }

            if ($response->successful() && ($json['success'] ?? true)) {
                return [
                    'success' => true,
                    'data' => $json,
                    'status' => $response->status(),
                ];
            }

            $message = $this->extractErrorMessage($json, $response->status());

            Log::warning('Cloudflare API error', [
                'path' => $path,
                'status' => $response->status(),
                'errors' => $json['errors'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => $message,
                'data' => $json,
                'status' => $response->status(),
                'errors' => $json['errors'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Cloudflare API exception', ['path' => $path, 'message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'خطأ في الاتصال: '.$e->getMessage(),
            ];
        }
    }

    protected function extractErrorMessage(array $json, int $status): string
    {
        if (! empty($json['errors'][0]['message'])) {
            return $json['errors'][0]['message'];
        }

        return match ($status) {
            401 => 'رمز API غير صالح أو منتهٍ (401)',
            403 => 'لا توجد صلاحية لهذا الإجراء (403)',
            429 => 'تم تجاوز حد الطلبات — حاول لاحقاً (429)',
            default => 'فشل الطلب: HTTP '.$status,
        };
    }

    public function ping(): bool
    {
        $response = $this->request('GET', 'zones', ['per_page' => 1]);

        return $response['success'] ?? false;
    }

    public function verifyToken(): array
    {
        return $this->request('GET', 'user/tokens/verify');
    }

    public function getTokenDetails(string $tokenId): array
    {
        return $this->request('GET', 'user/tokens/'.$tokenId);
    }

    /**
     * ملخص التوكن: التحقق + السياسات + ما تدعمه اللوحة فعلياً.
     *
     * @return array{
     *   verified: bool,
     *   status: ?string,
     *   expires_on: ?string,
     *   not_before: ?string,
     *   token_id: ?string,
     *   policies: array<int, array{effect: string, permissions: array<int, string>, resources: string}>,
     *   details_error: ?string,
     *   panel_capabilities: array<int, array{key: string, label: string, description: string, allowed: bool, hint: ?string}>
     * }
     */
    public function getTokenPermissionsSummary(): array
    {
        $summary = [
            'verified' => false,
            'api_connected' => false,
            'status' => null,
            'expires_on' => null,
            'not_before' => null,
            'token_id' => null,
            'policies' => [],
            'verify_error' => null,
            'details_error' => null,
            'panel_capabilities' => $this->panelCapabilityDefinitions(false),
        ];

        if (! $this->isConfigured()) {
            return $summary;
        }

        $summary['api_connected'] = $this->ping();

        $verify = $this->verifyToken();
        if ($verify['success'] ?? false) {
            $result = $verify['data']['result'] ?? [];
            if (! is_array($result)) {
                $result = [];
            }

            $summary['verified'] = true;
            $summary['status'] = $result['status'] ?? null;
            $summary['expires_on'] = $result['expires_on'] ?? null;
            $summary['not_before'] = $result['not_before'] ?? null;
            $summary['token_id'] = $result['id'] ?? null;

            $tokenId = $summary['token_id'];
            if (is_string($tokenId) && $tokenId !== '') {
                $details = $this->getTokenDetails($tokenId);
                if ($details['success'] ?? false) {
                    $summary['policies'] = $this->parseTokenPolicies($details['data']['result'] ?? []);
                } else {
                    $summary['details_error'] = $details['message'] ?? 'تعذر جلب تفاصيل السياسات (قد يحتاج التوكن صلاحية API Tokens Read)';
                }
            }
        } else {
            $summary['verify_error'] = $verify['message'] ?? 'تعذر استدعاء /user/tokens/verify';
        }

        $probes = $this->probePanelCapabilities();
        $summary['panel_capabilities'] = $this->panelCapabilityDefinitions(true, $probes);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $tokenResult
     * @return array<int, array{effect: string, permissions: array<int, string>, resources: string}>
     */
    protected function parseTokenPolicies(array $tokenResult): array
    {
        $policies = $tokenResult['policies'] ?? [];
        if (! is_array($policies)) {
            return [];
        }

        $parsed = [];
        foreach ($policies as $policy) {
            if (! is_array($policy)) {
                continue;
            }

            $permissions = [];
            foreach ($policy['permission_groups'] ?? [] as $group) {
                if (is_array($group) && ! empty($group['name'])) {
                    $permissions[] = (string) $group['name'];
                }
            }

            $parsed[] = [
                'effect' => (string) ($policy['effect'] ?? 'allow'),
                'permissions' => $permissions,
                'resources' => $this->formatPolicyResources($policy['resources'] ?? null),
            ];
        }

        return $parsed;
    }

    /**
     * @param  mixed  $resources
     */
    protected function formatPolicyResources($resources): string
    {
        if ($resources === null || $resources === '') {
            return 'جميع الموارد المسموح بها في التوكن';
        }

        if (is_string($resources)) {
            return $resources;
        }

        if (! is_array($resources)) {
            return '—';
        }

        $parts = [];
        foreach ($resources as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $parts[] = is_int($key) ? (string) $value : $key.': '.(string) $value;
        }

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    /**
     * @return array<string, bool>
     */
    protected function probePanelCapabilities(): array
    {
        $probes = [
            'accounts' => false,
            'zones' => false,
            'dns' => false,
            'ssl' => false,
            'registrar' => false,
        ];

        $accounts = $this->request('GET', 'accounts', ['per_page' => 1]);
        $probes['accounts'] = $accounts['success'] ?? false;

        $zones = $this->request('GET', 'zones', ['per_page' => 1]);
        $probes['zones'] = $zones['success'] ?? false;

        $zoneId = null;
        if ($probes['zones']) {
            $first = $zones['data']['result'][0] ?? null;
            if (is_array($first) && ! empty($first['id'])) {
                $zoneId = (string) $first['id'];
            }
        }

        if ($zoneId) {
            $dns = $this->request('GET', "zones/{$zoneId}/dns_records", ['per_page' => 1]);
            $probes['dns'] = $dns['success'] ?? false;

            $ssl = $this->request('GET', "zones/{$zoneId}/settings/ssl");
            $probes['ssl'] = $ssl['success'] ?? false;
        }

        $accountId = $this->getAccountId();
        if ($accountId) {
            $registrar = $this->request('GET', "accounts/{$accountId}/registrar/domains", ['per_page' => 1]);
            $probes['registrar'] = $registrar['success'] ?? false;
        }

        return $probes;
    }

    /**
     * @param  array<string, bool>|null  $probes
     * @return array<int, array{key: string, label: string, description: string, allowed: bool, hint: ?string}>
     */
    protected function panelCapabilityDefinitions(bool $withProbeResults, ?array $probes = null): array
    {
        $defs = [
            [
                'key' => 'zones',
                'label' => 'نطاقات DNS (Zones)',
                'description' => 'قائمة الـ zones، تفاصيل النطاق، الحالة',
                'hint' => 'Zone · Read (أو أعلى)',
            ],
            [
                'key' => 'dns',
                'label' => 'سجلات DNS',
                'description' => 'عرض سجلات DNS داخل كل zone',
                'hint' => 'DNS · Read',
            ],
            [
                'key' => 'ssl',
                'label' => 'إعدادات SSL',
                'description' => 'وضع SSL للنطاق',
                'hint' => 'SSL and Certificates · Read أو Zone Settings · Read',
            ],
            [
                'key' => 'registrar',
                'label' => 'نطاقات Registrar',
                'description' => 'النطاقات المسجّلة عند Cloudflare كمسجّل',
                'hint' => 'Registrar · Read على الحساب',
            ],
            [
                'key' => 'accounts',
                'label' => 'الحساب (Account ID)',
                'description' => 'جلب Account ID تلقائياً عند تركه فارغاً',
                'hint' => 'Account · Read',
            ],
        ];

        if (! $withProbeResults || $probes === null) {
            foreach ($defs as &$def) {
                $def['allowed'] = false;
            }

            return $defs;
        }

        foreach ($defs as &$def) {
            $def['allowed'] = $probes[$def['key']] ?? false;
        }

        return $defs;
    }

    public function getAccountId(): ?string
    {
        if ($this->accountId !== '' && self::isValidAccountId($this->accountId)) {
            return $this->accountId;
        }

        return Cache::remember('cloudflare_account_id', $this->cacheTtl, function () {
            $response = $this->request('GET', 'accounts', ['per_page' => 1]);
            if (! ($response['success'] ?? false)) {
                return null;
            }
            $result = $response['data']['result'] ?? [];
            $first = is_array($result) && isset($result[0]) ? $result[0] : null;

            return is_array($first) ? ($first['id'] ?? null) : null;
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllZones(?string $nameFilter = null, ?string $statusFilter = null): array
    {
        $cacheKey = 'cloudflare_zones_v2_'.md5(($nameFilter ?? '').'|'.($statusFilter ?? ''));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($nameFilter, $statusFilter) {
            $all = [];
            $page = 1;

            do {
                $query = ['page' => $page, 'per_page' => 50];
                if ($nameFilter) {
                    $query['name'] = 'contains:'.ltrim($nameFilter);
                }
                if ($statusFilter) {
                    $query['status'] = $statusFilter;
                }

                $response = $this->request('GET', 'zones', $query);
                if (! ($response['success'] ?? false)) {
                    break;
                }

                $result = $response['data']['result'] ?? [];
                if (! is_array($result)) {
                    break;
                }

                foreach ($result as $zone) {
                    if (is_array($zone)) {
                        $all[] = $zone;
                    }
                }

                $info = $response['data']['result_info'] ?? [];
                $totalPages = (int) ($info['total_pages'] ?? 1);
                $page++;
            } while ($page <= $totalPages && $page <= 50);

            return $all;
        });
    }

    public function getZone(string $zoneId): array
    {
        return $this->request('GET', "zones/{$zoneId}");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDnsRecords(string $zoneId, int $perPage = 100): array
    {
        $all = [];
        $page = 1;

        do {
            $response = $this->request('GET', "zones/{$zoneId}/dns_records", [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if (! ($response['success'] ?? false)) {
                return ['_error' => $response['message'] ?? 'فشل جلب السجلات'];
            }

            $result = $response['data']['result'] ?? [];
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (is_array($row)) {
                        $all[] = $row;
                    }
                }
            }

            $info = $response['data']['result_info'] ?? [];
            $totalPages = (int) ($info['total_pages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 100);

        return $all;
    }

    public function getZoneSsl(string $zoneId): array
    {
        return $this->request('GET', "zones/{$zoneId}/settings/ssl");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createDnsRecord(string $zoneId, array $payload): array
    {
        return $this->request('POST', "zones/{$zoneId}/dns_records", [], $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateDnsRecord(string $zoneId, string $recordId, array $payload): array
    {
        return $this->request('PUT', "zones/{$zoneId}/dns_records/{$recordId}", [], $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDnsRecordByName(string $zoneId, string $name, ?string $type = null): ?array
    {
        $target = strtolower(rtrim($name, '.'));

        foreach ($this->listDnsRecords($zoneId) as $record) {
            if (! is_array($record) || isset($record['_error'])) {
                continue;
            }
            $recordName = strtolower(rtrim((string) ($record['name'] ?? ''), '.'));
            if ($recordName !== $target) {
                continue;
            }
            if ($type !== null && strtoupper((string) ($record['type'] ?? '')) !== strtoupper($type)) {
                continue;
            }

            return $record;
        }

        return null;
    }

    /**
     * @param  mixed  $value
     */
    public function updateZoneSetting(string $zoneId, string $settingId, $value): array
    {
        return $this->request('PATCH', "zones/{$zoneId}/settings/{$settingId}", [], [
            'value' => $value,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveZoneIdForDomain(string $domain): ?string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain ?? '');
        $domain = rtrim($domain ?? '', '/');

        if ($domain === '') {
            return null;
        }

        $zones = $this->listAllZones($domain);
        if (isset($zones['_error'])) {
            return null;
        }

        foreach ($zones as $zone) {
            if (! is_array($zone)) {
                continue;
            }
            $name = strtolower(rtrim((string) ($zone['name'] ?? ''), '.'));
            if ($name === $domain) {
                return (string) ($zone['id'] ?? '');
            }
        }

        return null;
    }

    /**
     * @return array{domains: array<int, array<string, mixed>>, error: ?string}
     */
    public function listRegistrarDomainsWithMeta(bool $forceRefresh = false): array
    {
        $accountId = $this->getAccountId();
        if (! $accountId) {
            return ['domains' => [], 'error' => 'تعذر تحديد Account ID', 'total_count' => 0];
        }

        $cacheKey = 'cloudflare_registrar_v2_'.$accountId;

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && ! empty($cached['domains'])) {
                return $cached;
            }
        }

        $fetch = $this->fetchRegistrarDomainsFromApi($accountId);
        $payload = [
            'domains' => $fetch['domains'],
            'error' => $fetch['error'],
            'total_count' => $fetch['total_count'],
        ];

        if (! empty($payload['domains'])) {
            Cache::put($cacheKey, $payload, $this->cacheTtl);
        }

        return $payload;
    }

    /**
     * @return array{domains: array<int, array<string, mixed>>, error: ?string, total_count: int}
     */
    protected function fetchRegistrarDomainsFromApi(string $accountId): array
    {
        $strategies = [
            ['per_page' => 50],
            ['per_page' => 100],
            [],
        ];

        foreach ($strategies as $baseQuery) {
            $all = [];
            $page = 1;
            $totalCount = 0;

            do {
                $query = $baseQuery;
                if ($page > 1) {
                    $query['page'] = $page;
                }

                $response = $this->request('GET', "accounts/{$accountId}/registrar/domains", $query);

                if (! ($response['success'] ?? false)) {
                    return [
                        'domains' => $all,
                        'error' => $response['message'] ?? 'فشل جلب نطاقات Registrar',
                        'total_count' => $totalCount,
                    ];
                }

                $result = $response['data']['result'] ?? [];
                if (is_array($result)) {
                    foreach ($result as $row) {
                        if (is_array($row)) {
                            $all[] = $row;
                        }
                    }
                }

                $info = $response['data']['result_info'] ?? [];
                $totalPages = (int) ($info['total_pages'] ?? 1);
                $totalCount = (int) ($info['total_count'] ?? count($all));
                $page++;
            } while ($page <= $totalPages && $page <= 50);

            if (! empty($all)) {
                return ['domains' => $all, 'error' => null, 'total_count' => $totalCount];
            }

            if ($totalCount > 0) {
                continue;
            }

            return ['domains' => [], 'error' => null, 'total_count' => 0];
        }

        return [
            'domains' => [],
            'error' => 'تعذر قراءة قائمة Registrar — اضغط تحديث أو راجع صلاحية Registrar Read',
            'total_count' => 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRegistrarDomains(): array
    {
        return $this->listRegistrarDomainsWithMeta()['domains'];
    }

    /**
     * @return array{suggestions: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchRegistrarDomains(string $query, int $limit = 20): array
    {
        $accountId = $this->getAccountId();
        if (! $accountId) {
            return ['suggestions' => [], 'error' => 'تعذر تحديد Account ID لـ Cloudflare'];
        }

        $response = $this->request('GET', "accounts/{$accountId}/registrar/domain-search", [
            'q' => $query,
            'limit' => max(1, min(50, $limit)),
        ]);

        if (! ($response['success'] ?? false)) {
            return [
                'suggestions' => [],
                'error' => $response['message'] ?? 'فشل بحث Cloudflare Registrar',
            ];
        }

        $domains = $response['data']['result']['domains'] ?? [];

        return [
            'suggestions' => is_array($domains) ? array_values(array_filter($domains, 'is_array')) : [],
            'error' => null,
        ];
    }

    /**
     * @param  array<int, string>  $domainNames
     * @return array{checks: array<int, array<string, mixed>>, error: ?string}
     */
    public function checkRegistrarDomains(array $domainNames): array
    {
        $accountId = $this->getAccountId();
        if (! $accountId) {
            return ['checks' => [], 'error' => 'تعذر تحديد Account ID لـ Cloudflare'];
        }

        $names = array_values(array_unique(array_filter(array_map(
            fn ($n) => strtolower(trim((string) $n)),
            $domainNames
        ))));

        if ($names === []) {
            return ['checks' => [], 'error' => null];
        }

        $names = array_slice($names, 0, 20);
        $response = $this->request('POST', "accounts/{$accountId}/registrar/domain-check", [], [
            'domains' => $names,
        ]);

        if (! ($response['success'] ?? false)) {
            return [
                'checks' => [],
                'error' => $response['message'] ?? 'فشل التحقق من Cloudflare',
            ];
        }

        $checks = $response['data']['result']['domains'] ?? $response['data']['result'] ?? [];

        return [
            'checks' => is_array($checks) ? array_values(array_filter($checks, 'is_array')) : [],
            'error' => null,
        ];
    }

    public function clearCaches(): void
    {
        $this->settings->clearCache();
    }
}
