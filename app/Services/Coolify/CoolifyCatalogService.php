<?php

namespace App\Services\Coolify;

use App\Models\CoolifyCatalogItem;
use App\Services\CoolifyApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoolifyCatalogService
{
    public const CACHE_SERVICE_TYPES = 'coolify_catalog_service_types';

    public const CACHE_SERVICE_TEMPLATES_META = 'coolify_service_templates_meta';

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
        if ($enabledOnly) {
            $this->ensureDiscoveredServicesVisible();
        }

        $merged = $this->buildMergedCatalog();
        $types = $this->getCachedServiceTypes();
        $templateMeta = $this->getCachedServiceTemplatesMeta();

        $items = [];
        foreach ($merged as $item) {
            $item = $this->enrichItemFromTemplates($item, $templateMeta);

            if (($item['category'] ?? '') === 'service') {
                $key = strtolower((string) ($item['coolify_key'] ?? ''));
                $item['available_on_coolify'] = $key !== '' && in_array($key, $types, true);
            } elseif (($item['category'] ?? '') === 'database') {
                $item['available_on_coolify'] = true;
            } elseif (($item['category'] ?? '') === 'application') {
                $item['available_on_coolify'] = true;
            }

            if ($enabledOnly && ! $this->isVisibleInPublicCatalog($item)) {
                continue;
            }
            if ($category !== null && $category !== '' && ($item['category'] ?? '') !== $category) {
                continue;
            }
            if ($search !== null && $search !== '') {
                $hay = mb_strtolower(implode(' ', [
                    (string) ($item['name_ar'] ?? ''),
                    (string) ($item['description_ar'] ?? ''),
                    (string) ($item['coolify_key'] ?? ''),
                    (string) ($item['slug'] ?? ''),
                    (string) ($item['template_slogan'] ?? ''),
                    implode(' ', $item['template_tags'] ?? []),
                ]));
                if (! str_contains($hay, mb_strtolower($search))) {
                    continue;
                }
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
        $templateMeta = $this->getCachedServiceTemplatesMeta();

        foreach ($this->buildMergedCatalog() as $item) {
            if (($item['slug'] ?? '') === $slug) {
                $item = $this->enrichItemFromTemplates($item, $templateMeta);
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
        if ($types === []) {
            return ['success' => false, 'message' => 'لم يُعثر على أنواع خدمات في الاستجابة.', 'discovered' => 0];
        }

        Cache::put(self::CACHE_SERVICE_TYPES, $types, now()->addHours(6));
        Cache::forget('coolify_service_types_remote');

        $templatesMeta = $this->coolify->fetchRemoteServiceTemplatesMeta();
        if ($templatesMeta !== []) {
            Cache::put(self::CACHE_SERVICE_TEMPLATES_META, $templatesMeta, now()->addHours(24));
        }

        CoolifyCatalogItem::query()
            ->where('category', 'service')
            ->where('available_on_coolify', true)
            ->where('enabled', false)
            ->where('from_config', false)
            ->update(['enabled' => true]);

        $discovered = 0;
        foreach ($types as $typeKey) {
            $slug = 'svc-'.Str::slug($typeKey, '-');
            $existing = CoolifyCatalogItem::query()
                ->where('category', 'service')
                ->where(function ($query) use ($slug, $typeKey) {
                    $query->where('slug', $slug)->orWhere('coolify_key', $typeKey);
                })
                ->first();

            if ($existing) {
                $existing->update(['available_on_coolify' => true, 'coolify_key' => $typeKey]);

                continue;
            }

            $inConfig = collect(config('coolify_catalog.items', []))->contains(fn ($i) => ($i['coolify_key'] ?? '') === $typeKey);

            if (! $inConfig) {
                $meta = $templatesMeta[$typeKey] ?? [];
                CoolifyCatalogItem::query()->create([
                    'slug' => $slug,
                    'category' => 'service',
                    'coolify_key' => $typeKey,
                    'name_ar' => $this->displayNameForServiceKey($typeKey),
                    'description_ar' => ($meta['slogan'] ?? '') !== ''
                        ? (string) $meta['slogan']
                        : 'خدمة one-click من قوالب Coolify الرسمية.',
                    'icon' => 'fe-grid',
                    'enabled' => true,
                    'featured' => false,
                    'sort_order' => 500,
                    'install_steps' => ['اختر المشروع والسيرفر.', 'أنشئ الخدمة وانتظر التشغيل.'],
                    'requirements' => ['سيرفر Coolify متصل'],
                    'docs_url' => ($meta['documentation'] ?? '') !== '' ? (string) $meta['documentation'] : null,
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

        foreach (config('coolify_catalog.items', []) as $row) {
            if (($row['category'] ?? '') !== 'service') {
                continue;
            }
            $slug = (string) ($row['slug'] ?? '');
            $key = strtolower((string) ($row['coolify_key'] ?? ''));
            if ($slug === '' || $key === '') {
                continue;
            }
            CoolifyCatalogItem::query()->where('slug', $slug)->update([
                'coolify_key' => $row['coolify_key'],
                'available_on_coolify' => in_array($key, $types, true),
            ]);
        }

        $discovered += $this->ensureApplicationInstallers();

        $source = ($response['source'] ?? '') === 'templates'
            ? ' (من قوالب Coolify الرسمية)'
            : '';

        return [
            'success' => true,
            'message' => 'تمت المزامنة: '.count($types).' نوع خدمة'.$source.' + '.count($this->applicationTypeKeys()).' نوع تطبيق.',
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
            'application' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
            'custom' => match ($item['install_mode'] ?? 'docs_only') {
                'service' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
                'application' => ['route' => 'admin.coolify.catalog.install.store', 'method' => 'POST', 'params' => ['slug' => $item['slug']]],
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

        return in_array($item['category'] ?? '', ['database', 'service', 'application', 'custom'], true);
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
                'from_config' => $model->from_config,
                'from_db' => true,
            ];
        }

        return array_values($bySlug);
    }

    protected function ensureDiscoveredServicesVisible(): void
    {
        Cache::remember('coolify_catalog_discovered_services_enabled', now()->addDay(), function (): bool {
            CoolifyCatalogItem::query()
                ->where('category', 'service')
                ->where('available_on_coolify', true)
                ->where('enabled', false)
                ->where('from_config', false)
                ->update(['enabled' => true]);

            return true;
        });
    }

    public function isVisibleInPublicCatalog(array $item): bool
    {
        $category = $item['category'] ?? '';

        if ($category === 'service') {
            if (! ($item['available_on_coolify'] ?? false)) {
                return false;
            }

            if (($item['from_db'] ?? false) && ! ($item['enabled'] ?? false)) {
                return false;
            }

            if (! ($item['from_db'] ?? false) && ! ($item['enabled'] ?? false)) {
                return false;
            }

            return true;
        }

        return (bool) ($item['enabled'] ?? false);
    }

    /**
     * @param  array<string, array{slogan: string, documentation: string, tags: array<int, string>, category: string, logo: string}>  $templateMeta
     * @return array<string, mixed>
     */
    protected function enrichItemFromTemplates(array $item, array $templateMeta): array
    {
        if (($item['category'] ?? '') !== 'service') {
            return $item;
        }

        $key = strtolower((string) ($item['coolify_key'] ?? ''));
        if ($key === '' || ! isset($templateMeta[$key])) {
            return $item;
        }

        $meta = $templateMeta[$key];
        $item['template_slogan'] = $meta['slogan'] ?? '';
        $item['template_tags'] = $meta['tags'] ?? [];
        $item['template_category'] = $meta['category'] ?? '';

        $genericDescription = in_array(
            (string) ($item['description_ar'] ?? ''),
            ['خدمة one-click مكتشفة من Coolify.', 'خدمة one-click من قوالب Coolify الرسمية.'],
            true
        );

        if ($genericDescription && ($meta['slogan'] ?? '') !== '') {
            $item['description_ar'] = (string) $meta['slogan'];
        }

        if (empty($item['docs_url']) && ($meta['documentation'] ?? '') !== '') {
            $item['docs_url'] = (string) $meta['documentation'];
        }

        $displayName = $this->displayNameForServiceKey($key);
        $currentName = trim((string) ($item['name_ar'] ?? ''));
        $legacyName = ucfirst(str_replace(['-', '_'], ' ', $key));
        if ($currentName === '' || $currentName === $displayName || $currentName === $legacyName) {
            $item['name_ar'] = $displayName;
        }

        return $item;
    }

    protected function displayNameForServiceKey(string $key): string
    {
        $parts = preg_split('/[-_]+/', $key) ?: [$key];

        return implode(' ', array_map(
            static fn (string $part): string => $part === '' ? '' : ucfirst($part),
            $parts
        ));
    }

    /**
     * @return array<string, array{slogan: string, documentation: string, tags: array<int, string>, category: string, logo: string}>
     */
    protected function getCachedServiceTemplatesMeta(): array
    {
        $cached = Cache::get(self::CACHE_SERVICE_TEMPLATES_META);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $meta = $this->coolify->fetchRemoteServiceTemplatesMeta();
        if ($meta !== []) {
            Cache::put(self::CACHE_SERVICE_TEMPLATES_META, $meta, now()->addHours(24));
        }

        return $meta;
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

        if (array_is_list($data)) {
            return array_values(array_unique(array_filter(array_map(
                static fn ($row) => is_string($row) ? strtolower(trim($row)) : '',
                $data
            ), static fn (string $key): bool => $key !== '')));
        }

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

    protected function ensureApplicationInstallers(): int
    {
        $discovered = 0;

        foreach ($this->applicationTypeKeys() as $typeKey) {
            $slug = 'app-'.Str::slug($typeKey, '-');
            $existing = CoolifyCatalogItem::query()
                ->where('slug', $slug)
                ->orWhere(function ($query) use ($typeKey) {
                    $query->where('category', 'application')
                        ->where('coolify_key', $typeKey);
                })
                ->first();

            if ($existing) {
                $existing->update([
                    'category' => 'application',
                    'coolify_key' => $typeKey,
                    'available_on_coolify' => true,
                ]);

                continue;
            }

            $inConfig = collect(config('coolify_catalog.items', []))
                ->contains(fn ($i) => ($i['category'] ?? '') === 'application' && ($i['coolify_key'] ?? '') === $typeKey);

            if ($inConfig) {
                continue;
            }

            CoolifyCatalogItem::query()->create([
                'slug' => $slug,
                'category' => 'application',
                'coolify_key' => $typeKey,
                'name_ar' => 'Application: '.str_replace(['-', '_'], ' ', $typeKey),
                'description_ar' => 'تثبيت تطبيق عبر المسار الديناميكي العام.',
                'icon' => 'fe-layers',
                'enabled' => false,
                'featured' => false,
                'sort_order' => 550,
                'install_steps' => ['اختر المشروع والسيرفر والبيئة.', 'أدخل اسم المورد.', 'أضف JSON إضافي عند الحاجة ثم أنشئ المورد.'],
                'requirements' => ['سيرفر Coolify متصل'],
                'available_on_coolify' => true,
                'from_config' => false,
            ]);

            $discovered++;
        }

        return $discovered;
    }

    /**
     * @return array<int, string>
     */
    protected function applicationTypeKeys(): array
    {
        return ['public', 'private-github-app', 'private-deploy-key', 'dockerfile', 'dockerimage', 'dockercompose'];
    }
}
