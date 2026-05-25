<?php

namespace App\Services\Wordpress;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WordpressOrgDirectoryService
{
    public function searchPlugins(string $query = '', int $page = 1, string $browse = ''): array
    {
        return $this->query('plugin', $query, $page, $browse);
    }

    public function searchThemes(string $query = '', int $page = 1, string $browse = ''): array
    {
        return $this->query('theme', $query, $page, $browse);
    }

    public function getPluginInfo(string $slug): array
    {
        return $this->itemInfo('plugin', $slug);
    }

    public function getThemeInfo(string $slug): array
    {
        return $this->itemInfo('theme', $slug);
    }

    public function isValidSlug(string $slug): bool
    {
        return (bool) preg_match((string) config('wordpress_directory.slug_pattern', '/^[a-z0-9\-]+$/'), $slug);
    }

    /**
     * @return array{success: bool, message?: string, items: array<int, array<string, mixed>>, page: int, pages: int, total: int}
     */
    protected function query(string $type, string $query, int $page, string $browse): array
    {
        $perPage = max(6, min(48, (int) config('wordpress_directory.per_page', 24)));
        $page = max(1, min((int) config('wordpress_directory.max_page', 20), $page));
        $browse = $this->normalizeBrowse($browse);
        $query = trim($query);
        if (strlen($query) > 80) {
            $query = Str::limit($query, 80, '');
        }

        $cacheKey = sprintf(
            'wp_org_dir_%s_%s_%s_%d_%d',
            $type,
            md5($query),
            $browse ?: 'search',
            $page,
            $perPage
        );

        return Cache::remember($cacheKey, (int) config('wordpress_directory.cache_ttl', 1800), function () use ($type, $query, $page, $perPage, $browse) {
            $action = $type === 'plugin' ? 'query_plugins' : 'query_themes';
            $request = [
                'per_page' => $perPage,
                'page' => $page,
                'fields' => [
                    'icons' => true,
                    'short_description' => true,
                    'description' => false,
                    'sections' => false,
                    'rating' => true,
                    'active_installs' => $type === 'plugin',
                    'downloaded' => true,
                    'last_updated' => true,
                    'homepage' => true,
                    'tags' => false,
                ],
            ];

            if ($browse !== '') {
                $request['browse'] = $browse;
            } elseif ($query !== '') {
                $request['search'] = $query;
            } else {
                $request['browse'] = 'popular';
            }

            $response = $this->apiRequest($type, $action, $request);
            if (! ($response['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'فشل جلب البيانات',
                    'items' => [],
                    'page' => $page,
                    'pages' => 0,
                    'total' => 0,
                ];
            }

            $data = $response['data'] ?? [];
            $rawItems = $data['plugins'] ?? $data['themes'] ?? [];
            $items = [];
            foreach ($rawItems as $slug => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $normalized = $this->normalizeItem($type, (string) $slug, $item);
                if ($normalized !== null) {
                    $items[] = $normalized;
                }
            }

            $info = $data['info'] ?? [];

            return [
                'success' => true,
                'items' => $items,
                'page' => (int) ($info['page'] ?? $page),
                'pages' => (int) ($info['pages'] ?? 1),
                'total' => (int) ($info['results'] ?? count($items)),
            ];
        });
    }

    /**
     * @return array{success: bool, message?: string, item?: array<string, mixed>}
     */
    protected function itemInfo(string $type, string $slug): array
    {
        $slug = strtolower(trim($slug));
        if (! $this->isValidSlug($slug)) {
            return ['success' => false, 'message' => 'معرّف غير صالح'];
        }

        $action = $type === 'plugin' ? 'plugin_information' : 'theme_information';
        $request = [
            'slug' => $slug,
            'fields' => [
                'icons' => true,
                'short_description' => true,
                'sections' => false,
                'rating' => true,
                'active_installs' => $type === 'plugin',
                'downloaded' => true,
                'last_updated' => true,
                'homepage' => true,
            ],
        ];

        $response = $this->apiRequest($type, $action, $request);
        if (! ($response['success'] ?? false)) {
            return ['success' => false, 'message' => $response['message'] ?? 'غير موجود'];
        }

        $item = $this->normalizeItem($type, $slug, $response['data'] ?? []);

        return $item !== null
            ? ['success' => true, 'item' => $item]
            : ['success' => false, 'message' => 'تعذّر قراءة البيانات'];
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array{success: bool, message?: string, data?: array<string, mixed>}
     */
    protected function apiRequest(string $type, string $action, array $request): array
    {
        $baseUrl = $type === 'plugin'
            ? config('wordpress_directory.plugins_api_url')
            : config('wordpress_directory.themes_api_url');

        try {
            $http = Http::timeout((int) config('wordpress_directory.timeout', 25))
                ->acceptJson()
                ->get($baseUrl, [
                    'action' => $action,
                    'request' => $request,
                ]);

            if (! $http->successful()) {
                return ['success' => false, 'message' => 'wordpress.org API: HTTP '.$http->status()];
            }

            $data = $http->json();
            if (! is_array($data)) {
                return ['success' => false, 'message' => 'استجابة غير صالحة من wordpress.org'];
            }

            if (isset($data['error'])) {
                return ['success' => false, 'message' => (string) $data['error']];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'تعذّر الاتصال بـ wordpress.org: '.$e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function normalizeItem(string $type, string $slug, array $item): ?array
    {
        if ($slug === '') {
            $slug = strtolower((string) ($item['slug'] ?? ''));
        }
        if ($slug === '' || ! $this->isValidSlug($slug)) {
            return null;
        }

        $icons = $item['icons'] ?? [];
        $icon = '';
        if (is_array($icons)) {
            $icon = (string) ($icons['2x'] ?? $icons['1x'] ?? $icons['default'] ?? '');
        }

        $rating = (int) round((float) ($item['rating'] ?? 0));
        $activeInstalls = (int) ($item['active_installs'] ?? 0);

        return [
            'type' => $type,
            'slug' => $slug,
            'name' => (string) ($item['name'] ?? $slug),
            'short_description' => Str::limit(strip_tags((string) ($item['short_description'] ?? '')), 160),
            'version' => (string) ($item['version'] ?? ''),
            'rating' => min(100, max(0, $rating)),
            'rating_label' => $this->formatRating($rating),
            'active_installs' => $activeInstalls,
            'active_installs_label' => $this->formatInstalls($activeInstalls),
            'downloaded' => (int) ($item['downloaded'] ?? 0),
            'last_updated' => (string) ($item['last_updated'] ?? ''),
            'icon' => $icon,
            'homepage' => (string) ($item['homepage'] ?? ''),
            'org_url' => $type === 'plugin'
                ? 'https://wordpress.org/plugins/'.$slug.'/'
                : 'https://wordpress.org/themes/'.$slug.'/',
        ];
    }

    protected function formatRating(int $percent): string
    {
        if ($percent <= 0) {
            return '—';
        }

        return number_format($percent / 20, 1).'/5';
    }

    protected function formatInstalls(int $count): string
    {
        if ($count <= 0) {
            return '—';
        }
        if ($count >= 1000000) {
            return round($count / 1000000, 1).'M+';
        }
        if ($count >= 1000) {
            return round($count / 1000).'K+';
        }

        return (string) $count;
    }

    protected function normalizeBrowse(string $browse): string
    {
        $browse = strtolower(trim($browse));
        $allowed = ['popular', 'new', 'updated', 'featured'];

        return in_array($browse, $allowed, true) ? $browse : '';
    }
}
