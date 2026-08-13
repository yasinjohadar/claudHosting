<?php

namespace App\Services\Whm\Wordpress\Adapters;

use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\Wordpress\Contracts\WordpressDiscoveryAdapter;
use Illuminate\Support\Facades\Log;

class WpToolkitAdapter implements WordpressDiscoveryAdapter
{
    public function __construct(protected WhmApiService $api) {}

    public function source(): string
    {
        return WhmWordpressSite::SOURCE_WP_TOOLKIT;
    }

    public function discover(WhmAccount $account): array
    {
        $username = trim((string) $account->username);
        if ($username === '') {
            return ['success' => false, 'available' => false, 'message' => 'اسم مستخدم cPanel فارغ', 'sites' => []];
        }

        if (! $this->api->isConfigured()) {
            return ['success' => false, 'available' => false, 'message' => 'إعدادات WHM غير مكتملة', 'sites' => []];
        }

        // WP Toolkit WHM API (cPanel servers with WP Toolkit installed)
        $response = $this->api->request('wp-toolkit', [
            'method' => 'get-installations',
        ]);

        if (! ($response['success'] ?? false)) {
            Log::info('WP Toolkit unavailable', [
                'username' => $username,
                'message' => $response['message'] ?? null,
            ]);

            return [
                'success' => false,
                'available' => false,
                'message' => $response['message'] ?? 'WP Toolkit غير متاح على السيرفر',
                'sites' => [],
            ];
        }

        $installations = $this->extractInstallations($response['data'] ?? []);
        $sites = [];

        foreach ($installations as $row) {
            if (! is_array($row)) {
                continue;
            }

            $owner = (string) ($row['username'] ?? $row['owner'] ?? $row['user'] ?? '');
            if ($owner !== '' && strcasecmp($owner, $username) !== 0) {
                continue;
            }

            $id = (string) ($row['id'] ?? $row['installationId'] ?? $row['guid'] ?? '');
            $path = trim((string) ($row['path'] ?? $row['fullPath'] ?? $row['documentRoot'] ?? ''));
            $url = trim((string) ($row['url'] ?? $row['siteUrl'] ?? $row['site_url'] ?? ''));
            $domain = trim((string) ($row['domain'] ?? ''));

            if ($id === '') {
                $id = $path !== '' ? 'path:'.md5($path) : 'url:'.md5($url);
            }

            if ($domain === '' && $url !== '') {
                $host = parse_url(preg_match('#^https?://#i', $url) ? $url : 'https://'.$url, PHP_URL_HOST);
                $domain = is_string($host) ? $host : '';
            }

            if ($path === '' && $url === '' && $domain === '') {
                continue;
            }

            $sites[] = [
                'external_id' => $id,
                'domain' => $domain !== '' ? $domain : null,
                'path' => $path !== '' ? $path : null,
                'url' => $url !== '' ? $url : null,
                'wp_version' => isset($row['version']) ? (string) $row['version'] : (isset($row['wpVersion']) ? (string) $row['wpVersion'] : null),
                'title' => isset($row['name']) ? (string) $row['name'] : (isset($row['title']) ? (string) $row['title'] : null),
                'metadata' => ['wp_toolkit' => ['id' => $id]],
            ];
        }

        return [
            'success' => true,
            'available' => true,
            'message' => count($sites) > 0 ? 'تم جلب تثبيتات WP Toolkit' : 'لا توجد تثبيتات في WP Toolkit لهذا المستخدم',
            'sites' => $sites,
        ];
    }

    /**
     * @param  mixed  $data
     * @return list<array<string, mixed>>
     */
    protected function extractInstallations(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        foreach (['installations', 'result', 'data', 'items'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $inner = $data[$key];
                if (array_is_list($inner)) {
                    return $inner;
                }
                if (isset($inner['installations']) && is_array($inner['installations'])) {
                    return array_values($inner['installations']);
                }
            }
        }

        if (array_is_list($data)) {
            return $data;
        }

        return [];
    }
}
