<?php

namespace App\Services\Whm\Wordpress\Adapters;

use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\Wordpress\Contracts\WordpressDiscoveryAdapter;
use Illuminate\Support\Facades\Log;

class ManualScanAdapter implements WordpressDiscoveryAdapter
{
    protected int $maxDepth = 2;

    /** @var list<string> */
    protected array $skipDirs = [
        'wp-content', 'wp-includes', 'wp-admin', 'node_modules', 'vendor',
        'cache', 'tmp', 'temp', '.git', '.trash', 'cgi-bin', 'mail', 'etc', 'logs',
    ];

    public function __construct(protected WhmApiService $api) {}

    public function source(): string
    {
        return WhmWordpressSite::SOURCE_MANUAL;
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

        $roots = $this->documentRoots($username, $account);
        if ($roots === []) {
            return [
                'success' => false,
                'available' => true,
                'message' => 'تعذر جلب مسارات المستندات للحساب',
                'sites' => [],
            ];
        }

        $found = [];
        $errors = [];

        foreach ($roots as $root) {
            try {
                $this->scanPath($username, $root['path'], $root['domain'], $root['url'], 0, $found);
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                Log::warning('Manual WP scan path failed', [
                    'username' => $username,
                    'path' => $root['path'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $sites = array_values($found);

        return [
            'success' => true,
            'available' => true,
            'message' => count($sites) > 0
                ? 'تم اكتشاف '.count($sites).' موقع عبر المسح اليدوي'
                : (count($errors) > 0 ? 'المسح اكتمل مع تحذيرات' : 'لم يُعثر على wp-config.php'),
            'sites' => $sites,
            'warnings' => $errors,
        ];
    }

    /**
     * @return list<array{path: string, domain: ?string, url: ?string}>
     */
    protected function documentRoots(string $username, WhmAccount $account): array
    {
        $roots = [];

        $domainsData = $this->api->cpanelUapi($username, 'DomainInfo', 'domains_data', [
            'return_https_redirect_status' => 0,
        ]);

        if ($domainsData['success'] ?? false) {
            $payload = $domainsData['data'] ?? [];
            $list = $payload['data'] ?? $payload;
            if (isset($list['main_domain']) || isset($list['addon_domains']) || isset($list['sub_domains'])) {
                foreach ($this->flattenDomainData($list) as $item) {
                    $roots[] = $item;
                }
            } elseif (is_array($list)) {
                foreach ($list as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $path = trim((string) ($item['documentroot'] ?? $item['dir'] ?? $item['homedir'] ?? ''));
                    $domain = trim((string) ($item['domain'] ?? $item['servername'] ?? ''));
                    if ($path === '') {
                        continue;
                    }
                    $roots[] = [
                        'path' => rtrim($path, '/'),
                        'domain' => $domain !== '' ? $domain : null,
                        'url' => $domain !== '' ? 'https://'.$domain : null,
                    ];
                }
            }
        }

        if ($roots === []) {
            $listFiles = $this->api->cpanelUapi($username, 'Fileman', 'list_files', [
                'dir' => '/home/'.$username,
                'types' => 'dir',
            ]);
            // Fallback to common public_html
            $homeGuess = '/home/'.$username.'/public_html';
            $domain = trim((string) ($account->domain ?? ''));
            $roots[] = [
                'path' => $homeGuess,
                'domain' => $domain !== '' ? $domain : null,
                'url' => $domain !== '' ? 'https://'.$domain : null,
            ];
            unset($listFiles);
        }

        // Deduplicate by path
        $unique = [];
        foreach ($roots as $root) {
            $key = $root['path'];
            $unique[$key] = $root;
        }

        return array_values($unique);
    }

    /**
     * @param  array<string, mixed>  $list
     * @return list<array{path: string, domain: ?string, url: ?string}>
     */
    protected function flattenDomainData(array $list): array
    {
        $out = [];

        $main = $list['main_domain'] ?? null;
        if (is_array($main)) {
            $path = trim((string) ($main['documentroot'] ?? $main['dir'] ?? ''));
            $domain = trim((string) ($main['domain'] ?? ''));
            if ($path !== '') {
                $out[] = [
                    'path' => rtrim($path, '/'),
                    'domain' => $domain !== '' ? $domain : null,
                    'url' => $domain !== '' ? 'https://'.$domain : null,
                ];
            }
        }

        foreach (['addon_domains', 'sub_domains', 'parked_domains'] as $group) {
            $items = $list[$group] ?? [];
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $path = trim((string) ($item['documentroot'] ?? $item['dir'] ?? ''));
                $domain = trim((string) ($item['domain'] ?? $item['servername'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $out[] = [
                    'path' => rtrim($path, '/'),
                    'domain' => $domain !== '' ? $domain : null,
                    'url' => $domain !== '' ? 'https://'.$domain : null,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, mixed>>  $found
     */
    protected function scanPath(
        string $username,
        string $path,
        ?string $domain,
        ?string $url,
        int $depth,
        array &$found
    ): void {
        $path = rtrim($path, '/');
        if ($path === '' || $depth > $this->maxDepth) {
            return;
        }

        if ($this->hasWpConfig($username, $path)) {
            $externalId = 'path:'.md5($path);
            $found[$externalId] = [
                'external_id' => $externalId,
                'domain' => $domain,
                'path' => $path,
                'url' => $url,
                'wp_version' => null,
                'title' => $domain,
                'metadata' => ['manual' => ['detected_via' => 'wp-config.php']],
            ];

            return; // do not scan inside WP tree
        }

        if ($depth >= $this->maxDepth) {
            return;
        }

        $listing = $this->api->cpanelUapi($username, 'Fileman', 'list_files', [
            'dir' => $path,
            'types' => 'dir',
            'include_mime' => 0,
            'include_hash' => 0,
            'include_permissions' => 0,
        ]);

        if (! ($listing['success'] ?? false)) {
            return;
        }

        $entries = $this->normalizeFileList($listing['data'] ?? []);
        foreach ($entries as $entry) {
            $name = trim((string) ($entry['file'] ?? $entry['name'] ?? ''));
            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }
            if (in_array(strtolower($name), $this->skipDirs, true)) {
                continue;
            }
            if (($entry['type'] ?? $entry['file_type'] ?? 'dir') === 'file') {
                continue;
            }

            $childPath = $path.'/'.$name;
            $this->scanPath($username, $childPath, $domain, $url ? rtrim($url, '/').'/'.$name : null, $depth + 1, $found);
        }
    }

    protected function hasWpConfig(string $username, string $path): bool
    {
        // Prefer get_file_information when available
        $info = $this->api->cpanelUapi($username, 'Fileman', 'get_file_information', [
            'path' => $path.'/wp-config.php',
        ]);

        if ($info['success'] ?? false) {
            $data = $info['data'] ?? [];
            if (is_array($data) && $data !== []) {
                return true;
            }
        }

        $list = $this->api->cpanelUapi($username, 'Fileman', 'list_files', [
            'dir' => $path,
            'types' => 'file',
            'include_mime' => 0,
            'include_hash' => 0,
            'include_permissions' => 0,
        ]);

        if (! ($list['success'] ?? false)) {
            return false;
        }

        foreach ($this->normalizeFileList($list['data'] ?? []) as $entry) {
            $name = strtolower((string) ($entry['file'] ?? $entry['name'] ?? ''));
            if ($name === 'wp-config.php') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $data
     * @return list<array<string, mixed>>
     */
    protected function normalizeFileList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        return [];
    }
}
