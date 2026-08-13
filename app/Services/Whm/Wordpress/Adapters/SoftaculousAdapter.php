<?php

namespace App\Services\Whm\Wordpress\Adapters;

use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\Wordpress\Contracts\WordpressDiscoveryAdapter;
use App\Services\Whm\Wordpress\SoftaculousApiClient;
use Illuminate\Support\Facades\Log;

class SoftaculousAdapter implements WordpressDiscoveryAdapter
{
    public function __construct(
        protected WhmApiService $api,
        protected SoftaculousApiClient $softaculous
    ) {}

    public function source(): string
    {
        return WhmWordpressSite::SOURCE_SOFTACULOUS;
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

        $session = $this->api->createUserSession($username, 'cpaneld');
        if (! ($session['success'] ?? false) || empty($session['url'])) {
            return [
                'success' => false,
                'available' => false,
                'message' => $session['message'] ?? 'فشل إنشاء جلسة cPanel لـ Softaculous',
                'sites' => [],
            ];
        }

        $result = $this->softaculous->call($session['url'], 'act=installations&api=json');
        if (! ($result['success'] ?? false)) {
            Log::info('Softaculous unavailable for account', [
                'username' => $username,
                'message' => $result['message'] ?? null,
            ]);

            return [
                'success' => false,
                'available' => false,
                'message' => $result['message'] ?? 'Softaculous غير متاح على هذا الحساب',
                'sites' => [],
            ];
        }

        $sites = $this->parseInstallations($result['data'] ?? []);

        return [
            'success' => true,
            'available' => true,
            'message' => count($sites) > 0 ? 'تم جلب تثبيتات Softaculous' : 'لا توجد تثبيتات WordPress في Softaculous',
            'sites' => $sites,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    protected function parseInstallations(array $data): array
    {
        $installations = $data['installations'] ?? $data;
        if (! is_array($installations)) {
            return [];
        }

        // Softaculous groups by script id: installations[26][insid] = {...}
        $wordpressBucket = $installations['26'] ?? null;
        $rows = [];

        if (is_array($wordpressBucket)) {
            foreach ($wordpressBucket as $insid => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $parsed = $this->mapRow((string) ($row['insid'] ?? $insid), $row);
                if ($parsed !== null) {
                    $rows[] = $parsed;
                }
            }

            return $rows;
        }

        // Flat list fallback
        foreach ($installations as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $sid = (string) ($row['sid'] ?? $row['soft'] ?? '');
            if ($sid !== '' && $sid !== '26') {
                continue;
            }
            $insid = (string) ($row['insid'] ?? $key);
            $parsed = $this->mapRow($insid, $row);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function mapRow(string $insid, array $row): ?array
    {
        $insid = trim($insid);
        if ($insid === '') {
            return null;
        }

        $url = trim((string) ($row['softdurl'] ?? $row['url'] ?? $row['site_url'] ?? ''));
        $path = trim((string) ($row['softpath'] ?? $row['path'] ?? $row['dir'] ?? ''));
        $domain = trim((string) ($row['softdomain'] ?? $row['domain'] ?? ''));
        if ($domain === '' && $url !== '') {
            $host = parse_url(preg_match('#^https?://#i', $url) ? $url : 'https://'.$url, PHP_URL_HOST);
            $domain = is_string($host) ? $host : '';
        }

        return [
            'external_id' => $insid,
            'domain' => $domain !== '' ? $domain : null,
            'path' => $path !== '' ? $path : null,
            'url' => $url !== '' ? $url : null,
            'wp_version' => isset($row['ver']) ? (string) $row['ver'] : (isset($row['version']) ? (string) $row['version'] : null),
            'title' => isset($row['site_name']) ? (string) $row['site_name'] : (isset($row['name']) ? (string) $row['name'] : null),
            'metadata' => [
                'softaculous' => [
                    'insid' => $insid,
                    'raw_keys' => array_keys($row),
                ],
            ],
        ];
    }
}
