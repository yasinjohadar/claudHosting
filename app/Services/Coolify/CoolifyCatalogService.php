<?php

namespace App\Services\Coolify;

use App\Models\CoolifyCatalogItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoolifyCatalogService
{
    public const CACHE_SERVICE_TYPES = 'coolify_catalog_service_types';

    public function __construct(
        protected CoolifyApiService $coolify
    ) {}

    /**
     * @return array<string, string>
     */
    public function categories(): array
    {
        return config('coolify_catalog.categories', []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCatalog(bool $enabledOnly = true, ?string $category = null, ?string $search = null): array
    {
        $merged = $this->buildMergedCatalog();
        $types = $this->getCachedServiceTypes();

        $items = [];
        foreach ($merged as $item) {
            if ($enabledOnly && ! ($item['enabled'] ?? false)) {
                continue;
            }
            if ($category !== null && $category !== '' && ($item['category'] ?? '') !== $category) {
                continue;
            }
            if ($search !== null && $search !== '') {
                $hay = mb_strtolower(($item['name_ar'] ?? '').' '.($item['description_ar'] ?? '').' '.($item['coolify_key'] ?? '').' '.($item['slug'] ?? ''));
                if (! str_contains($hay, mb_strtolower($search))) {
                    continue;
                }
            }

            if (($item['category'] ?? '') === 'service') {
                $key = strtolower((string) ($item['coolify_key'] ?? ''));
                $item['available_on_coolify'] = $key !== '' && in_array($key, $types, true);
            } elseif (($item['category'] ?? '') === 'database') {
                $item['available_on_coolify'] = true;
            } elseif (($item['category'] ?? '') === 'application') {
                $item['available_on_coolify'] = true;
            }

            $items[] = $item;
        }

        usort($items, function (array $a, array $b): int {
            $fa = ($a['featured'] ?? false) ? 0 : 1;
            $fb = ($b['featured'] ?? false) ? 0 : 1;
            if ($fa !== $fb) {
                return $fa <=> $fb;
            }

            return ($a['sort_order'] ?? 100) <=> ($b['sort_order'] ?? 100);
        });

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->buildMergedCatalog() as $item) {
            if (($item['slug'] ?? '') === $slug) {
                $types = $this->getCachedServiceTypes();
                if (($item['category'] ?? '') === 'service') {
                    $key = strtolower((string) ($item['coolify_key'] ?? ''));
                    $item['available_on_coolify'] = $key !== '' && in_array($key, $types, true);
                } elseif (in_array($item['category'] ?? '', ['database', 'application'], true)) {
                    $item['available_on_coolify'] = true;
                }

                return $item;
            }
        }

        return null;
    }

    /**
     * @return array{success: bool, message: string, discovered: int}
     */
    public function syncWithCoolify(): array
    {
        if (! $this->coolify->isConfigured()) {
            return ['success' => false, 'message' => 'Coolify غير مضبوط.', 'discovered' => 0];
        }

        $response = $this->coolify->getServiceTypes();
        if (! ($response['success'] ?? false)) {
            return ['success' => false, 'message' => $response['message'] ?? 'فشل جلب أنواع الخدمات', 'discovered' => 0];
        }

        $types = $this->extractServiceTypeKeys($response);
        Cache::put(self::CACHE_SERVICE_TYPES, $types, now()->addHours(6));

        $discovered = 0;
        foreach ($types as $typeKey) {
            $slug = 'svc-'.Str::slug($typeKey, '-');
            $existing = CoolifyCatalogItem::query()->where('slug', $slug)->orWhere('coolify_key', $typeKey)->where('category', 'service')->first();

            if ($existing) {
                $existing->update(['available_on_coolify' => true, 'coolify_key' => $typeKey]);

                continue;
            }

            $inConfig = collect(config('coolify_catalog.items', []))->contains(fn ($i) => ($i['coolify_key'] ?? '') === $typeKey);

            if (! $inConfig) {
                CoolifyCatalogItem::query()->create([
                    'slug' => $slug,
                    'category' => 'service',
                    'coolify_key' => $typeKey,
                    'name_ar' => ucfirst(str_replace(['-', '_'], ' ', $typeKey)),
                    'description_ar' => 'خدمة one-click مكتشفة من Coolify.',
                    'icon' => 'fe-grid',
                    'enabled' => false,
                    'featured' => false,
                    'sort_order' => 500,
                    'install_steps' => ['اختر المشروع والسيرفر.', 'أنشئ الخدمة وانتظر التشغيل.'],
                    'requirements' => ['سيرفر Coolify متصل'],
                    'available_on_coolify' => true,
                    'from_config' => false,
                ]);
                $discovered++;
            }
        }

        CoolifyCatalogItem::query()
            ->where('category', 'service')
            ->whereNotNull('coolify_key')
            ->each(function (CoolifyCatalogItem $row) use ($types): void {
                $key = strtolower((string) $row->coolify_key);
                $row->update(['available_on_coolify' => in_array($key, $types, true)]);
            });

        return [
            'success' => true,
            'message' => 'تمت المزامنة: '.count($types).' نوع خدمة.',
            'discovered' => $discovered,
        ];
    }

    /**
     * @return array{route: string, method: string, params: array<string, string>}
     */
    public function resolveInstallTarget(array $item): array
    {
        $category = $item['category'] ?? '';

        return match ($category) {
            'database' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
            'service' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
            'application' => ['route' => 'admin.coolify.applications.create', 'method' => 'GET', 'params' => []],
            'custom' => match ($item['install_mode'] ?? 'docs_only') {
                'service' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
                'link' => ['route' => 'admin.coolify.catalog.show', 'method' => 'GET', 'params' => ['slug' => $item['slug']]],
                default => ['route' => 'admin.coolify.catalog.show', 'method' => 'GET', 'params' => ['slug' => $item['slug']]],
            },
            default => ['route' => 'admin.coolify.catalog.index', 'method' => 'GET', 'params' => []],
        };
    }

    public function canInstall(array $item): bool
    {
        if (($item['install_mode'] ?? '') === 'docs_only') {
            return false;
        }
        if (($item['install_mode'] ?? '') === 'link' && ! empty($item['custom_install_url'])) {
            return false;
        }

        $needsServiceCheck = ($item['category'] ?? '') === 'service'
            || (($item['category'] ?? '') === 'custom' && ($item['install_mode'] ?? '') === 'service');

        if ($needsServiceCheck && ! ($item['available_on_coolify'] ?? false)) {
            return false;
        }

        return in_array($item['category'] ?? '', ['database', 'service', 'custom'], true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildMergedCatalog(): array
    {
        $bySlug = [];

        foreach (config('coolify_catalog.items', []) as $row) {
            $slug = $row['slug'] ?? '';
            if ($slug === '') {
                continue;
            }
            $bySlug[$slug] = array_merge($row, [
                'id' => null,
                'from_db' => false,
            ]);
        }

        foreach (CoolifyCatalogItem::query()->orderBy('sort_order')->get() as $model) {
            $slug = $model->slug;
            $bySlug[$slug] = [
                'id' => $model->id,
                'slug' => $model->slug,
                'category' => $model->category,
                'coolify_key' => $model->coolify_key,
                'name_ar' => $model->name_ar,
                'description_ar' => $model->description_ar,
                'icon' => $model->icon,
                'enabled' => $model->enabled,
                'featured' => $model->featured,
                'sort_order' => $model->sort_order,
                'install_steps' => $model->install_steps ?? [],
                'requirements' => $model->requirements ?? [],
                'docs_url' => $model->docs_url,
                'is_custom' => $model->is_custom,
                'install_mode' => $model->install_mode,
                'custom_install_url' => $model->custom_install_url,
                'available_on_coolify' => $model->available_on_coolify,
                'from_db' => true,
            ];
        }

        return array_values($bySlug);
    }

    /**
     * @return array<int, string>
     */
    protected function getCachedServiceTypes(): array
    {
        $cached = Cache::get(self::CACHE_SERVICE_TYPES);
        if (is_array($cached)) {
            return $cached;
        }

        if ($this->coolify->isConfigured()) {
            $this->syncWithCoolify();
            $cached = Cache::get(self::CACHE_SERVICE_TYPES);
            if (is_array($cached)) {
                return $cached;
            }
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function extractServiceTypeKeys(array $response): array
    {
        $data = $response['data'] ?? [];
        $list = $this->coolify->normalizeList($data);
        $keys = [];

        foreach ($list as $row) {
            if (is_string($row)) {
                $keys[] = strtolower(trim($row));

                continue;
            }
            if (is_array($row)) {
                $k = $row['type'] ?? $row['name'] ?? $row['slug'] ?? null;
                if (is_string($k) && $k !== '') {
                    $keys[] = strtolower(trim($k));
                }
            }
        }

        if ($keys === [] && is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_string($k) && $k !== 'result') {
                    $keys[] = strtolower(trim($k));
                }
            }
        }

        return array_values(array_unique(array_filter($keys)));
    }
}
