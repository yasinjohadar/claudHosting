<?php

namespace App\Services;

use App\Services\Namecom\NamecomSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NamecomApiService
{
    protected string $username = '';

    protected string $token = '';

    protected string $apiBase = '';

    protected int $timeout = 30;

    protected int $cacheTtl = 600;

    public function __construct(protected NamecomSettingsService $settings)
    {
        $this->loadConnectionConfig();
    }

    protected function loadConnectionConfig(): void
    {
        $config = $this->settings->getConnectionConfig();
        $this->username = $config['username'] ?? '';
        $this->token = $config['api_token'] ?? '';
        $this->apiBase = $config['api_base'] ?? config('namecom.defaults.api_base');
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? 600);
    }

    public function refreshConnection(): void
    {
        $this->settings->clearCache();
        $this->loadConnectionConfig();
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->token !== '';
    }

    /**
     * @return array{success: bool, data?: mixed, message?: string, status?: int}
     */
    protected function request(string $method, string $path, array $query = [], array $body = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'يرجى ضبط اسم المستخدم وتوكن name.com من صفحة الإعدادات',
            ];
        }

        $url = $this->apiBase.'/'.ltrim($path, '/');

        try {
            $pending = Http::withBasicAuth($this->username, $this->token)
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

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $json,
                    'status' => $response->status(),
                ];
            }

            $message = $this->extractErrorMessage($json, $response->status());

            Log::warning('Name.com API error', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $json,
            ]);

            return [
                'success' => false,
                'message' => $message,
                'data' => $json,
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Name.com API exception', ['path' => $path, 'message' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'خطأ في الاتصال: '.$e->getMessage(),
            ];
        }
    }

    protected function extractErrorMessage(array $json, int $status): string
    {
        if (! empty($json['message'])) {
            return (string) $json['message'];
        }

        if (! empty($json['details'])) {
            return (string) $json['details'];
        }

        return match ($status) {
            401 => 'بيانات الاعتماد غير صحيحة (401)',
            403 => 'لا توجد صلاحية (403)',
            404 => 'غير موجود (404)',
            429 => 'تم تجاوز حد الطلبات (429)',
            default => 'فشل الطلب: HTTP '.$status,
        };
    }

    public function ping(): bool
    {
        $response = $this->request('GET', 'domains', ['perPage' => 1]);

        return $response['success'] ?? false;
    }

    /**
     * @return array{domains: array<int, array<string, mixed>>, error: ?string}
     */
    public function listAllDomainsWithMeta(bool $forceRefresh = false): array
    {
        $cacheKey = 'namecom_domains_v1_'.md5($this->username);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['domains'])) {
                return $cached;
            }
        }

        $all = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = $this->request('GET', 'domains', [
                'page' => $page,
                'perPage' => $perPage,
            ]);

            if (! ($response['success'] ?? false)) {
                return [
                    'domains' => $all,
                    'error' => $response['message'] ?? 'فشل جلب النطاقات',
                ];
            }

            $data = $response['data'] ?? [];
            $batch = $data['domains'] ?? [];
            if (! is_array($batch)) {
                break;
            }

            foreach ($batch as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }

            $lastPage = (int) ($data['lastPage'] ?? $page);
            $page++;
        } while ($page <= $lastPage && $page <= 100);

        $payload = ['domains' => $all, 'error' => null];
        if (! empty($all)) {
            Cache::put($cacheKey, $payload, $this->cacheTtl);
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllDomains(bool $forceRefresh = false): array
    {
        return $this->listAllDomainsWithMeta($forceRefresh)['domains'];
    }

    /**
     * @return array{domain: array<string, mixed>|null, error: ?string}
     */
    public function getDomain(string $domainName): array
    {
        $encoded = rawurlencode($domainName);
        $response = $this->request('GET', 'domains/'.$encoded);

        if (! ($response['success'] ?? false)) {
            return [
                'domain' => null,
                'error' => $response['message'] ?? 'فشل جلب تفاصيل النطاق',
            ];
        }

        $data = $response['data'] ?? [];

        return [
            'domain' => is_array($data) ? $data : null,
            'error' => null,
        ];
    }

    /**
     * @return array{records: array<int, array<string, mixed>>, error: ?string}
     */
    public function listDnsRecords(string $domainName): array
    {
        $encoded = rawurlencode($domainName);
        $response = $this->request('GET', 'domains/'.$encoded.'/records');

        if (! ($response['success'] ?? false)) {
            return [
                'records' => [],
                'error' => $response['message'] ?? 'فشل جلب سجلات DNS',
            ];
        }

        $data = $response['data'] ?? [];
        $records = $data['records'] ?? $data ?? [];
        if (! is_array($records)) {
            $records = [];
        }

        $normalized = [];
        foreach ($records as $row) {
            if (is_array($row)) {
                $normalized[] = $row;
            }
        }

        return ['records' => $normalized, 'error' => null];
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function searchDomainSuggestions(string $keyword): array
    {
        $response = $this->request('POST', 'domains:search', [], [
            'keyword' => $keyword,
        ]);

        if (! ($response['success'] ?? false)) {
            return [
                'results' => [],
                'error' => $response['message'] ?? 'فشل بحث name.com',
            ];
        }

        $data = $response['data'] ?? [];
        $results = $data['results'] ?? $data['searchResults'] ?? [];

        return [
            'results' => is_array($results) ? array_values(array_filter($results, 'is_array')) : [],
            'error' => null,
        ];
    }

    /**
     * @param  array<int, string>  $domainNames
     * @return array{results: array<int, array<string, mixed>>, error: ?string}
     */
    public function checkDomainsAvailability(array $domainNames): array
    {
        $names = array_values(array_unique(array_filter(array_map(
            fn ($n) => strtolower(trim((string) $n)),
            $domainNames
        ))));

        if ($names === []) {
            return ['results' => [], 'error' => null];
        }

        $names = array_slice($names, 0, 50);
        $response = $this->request('POST', 'domains:checkAvailability', [], [
            'domainNames' => $names,
        ]);

        if (! ($response['success'] ?? false)) {
            return [
                'results' => [],
                'error' => $response['message'] ?? 'فشل التحقق من name.com',
            ];
        }

        $data = $response['data'] ?? [];
        $results = $data['results'] ?? [];

        return [
            'results' => is_array($results) ? array_values(array_filter($results, 'is_array')) : [],
            'error' => null,
        ];
    }

    public function clearCaches(): void
    {
        $this->settings->clearCache();
    }
}
