<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Storage\StorageHelperService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class HeroSettingsService
{
    public const SETTING_KEY = 'homepage_hero';

    public const SETTING_GROUP = 'homepage';

    protected const CACHE_KEY = 'homepage_hero_resolved';

    protected const CACHE_TTL = 600;

    public function __construct(
        protected StorageHelperService $storageHelper
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return [
            'enabled' => true,
            'content' => [
                'title_prefix' => 'مرحباً بك في',
                'typing_texts' => [
                    'استضافة كلاودسوفت',
                    'خوادم سحابية موثوقة',
                    'باقات استضافة تناسب مشروعك',
                ],
                'subtitle' => 'استضافة كلاودسوفت تمنحك بنية سحابية مستقرة، سريعة وآمنة لموقعك أو متجرك الإلكتروني، مع خطط مرنة تبدأ من المواقع الشخصية وحتى مشاريع الشركات. اختر باقتك وابدأ خلال دقائق مع لوحة تحكم سهلة ودعم فني مستمر.',
                'image_alt' => 'خوادم استضافة سحابية موثوقة - استضافة كلاودسوفت',
                'buttons' => [
                    [
                        'label' => 'تصفّح الباقات',
                        'url' => '/packages',
                        'style' => 'primary',
                        'icon' => 'fas fa-server',
                        'enabled' => true,
                    ],
                    [
                        'label' => 'تواصل معنا',
                        'url' => '/contact',
                        'style' => 'outline',
                        'icon' => 'fas fa-paper-plane',
                        'enabled' => true,
                    ],
                ],
                'stats' => [
                    ['value' => 200, 'suffix' => '+', 'label' => 'موقع مستضاف', 'enabled' => true],
                    ['value' => 500, 'suffix' => '+', 'label' => 'عميل نشط', 'enabled' => true],
                    ['value' => 5, 'suffix' => '+', 'label' => 'سنوات خبرة في الاستضافة', 'enabled' => true],
                    ['value' => 99, 'suffix' => '+', 'label' => 'نسبة توفر %', 'enabled' => true],
                ],
            ],
            'light' => [
                'hero_image' => null,
                'background' => $this->defaultBackground(),
            ],
            'dark' => [
                'hero_image' => null,
                'background' => $this->defaultBackground(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $defaults = $this->getDefaults();
        $raw = Setting::getByKey(self::SETTING_KEY);

        if ($raw === null || $raw === '') {
            return $defaults;
        }

        $stored = json_decode($raw, true);
        if (! is_array($stored)) {
            return $defaults;
        }

        return $this->mergeRecursive($defaults, $stored);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForFrontend(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $data = $this->get();

            if (! ($data['enabled'] ?? true)) {
                return ['enabled' => false];
            }

            $content = $data['content'] ?? [];
            $typing = $content['typing_texts'] ?? [];
            if (! is_array($typing)) {
                $typing = array_filter(array_map('trim', explode('|', (string) $typing)));
            }

            $buttons = array_values(array_filter(
                $content['buttons'] ?? [],
                fn ($b) => ($b['enabled'] ?? true) && trim((string) ($b['label'] ?? '')) !== ''
            ));

            $stats = array_values(array_filter(
                $content['stats'] ?? [],
                fn ($s) => ($s['enabled'] ?? true) && trim((string) ($s['label'] ?? '')) !== ''
            ));

            $lightBg = $this->buildBackgroundCss($data['light']['background'] ?? []);
            $darkBg = $this->buildBackgroundCss($data['dark']['background'] ?? []);

            return [
                'enabled' => true,
                'content' => [
                    'title_prefix' => $content['title_prefix'] ?? '',
                    'typing_texts' => $typing,
                    'typing_texts_pipe' => implode('|', $typing),
                    'typing_texts_initial' => $typing[0] ?? '',
                    'subtitle' => $content['subtitle'] ?? '',
                    'image_alt' => $content['image_alt'] ?? '',
                    'buttons' => array_map(fn ($b) => $this->normalizeButton($b), $buttons),
                    'stats' => array_map(fn ($s) => $this->normalizeStat($s), $stats),
                ],
                'hero_image_light_url' => $this->resolveHeroImageUrl(
                    $data['light']['hero_image'] ?? null,
                    'frontend/assets/images/hero-light.webp'
                ),
                'hero_image_dark_url' => $this->resolveHeroImageUrl(
                    $data['dark']['hero_image'] ?? null,
                    'frontend/assets/images/hero-dark.webp'
                ),
                'bg_light_css' => $lightBg,
                'bg_dark_css' => $darkBg,
            ];
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function resetToDefaults(): void
    {
        Setting::set(
            self::SETTING_KEY,
            json_encode($this->getDefaults(), JSON_UNESCAPED_UNICODE),
            self::SETTING_GROUP
        );
        $this->clearCache();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload, Request $request): void
    {
        $current = $this->get();

        foreach (['light', 'dark'] as $theme) {
            $heroKey = "hero_image_{$theme}";
            if ($request->hasFile($heroKey)) {
                $this->deleteStoredFile($current[$theme]['hero_image'] ?? null);
                $current[$theme]['hero_image'] = $this->storeImage(
                    $request->file($heroKey),
                    'hero/images'
                );
            } elseif ($request->boolean("remove_{$heroKey}")) {
                $this->deleteStoredFile($current[$theme]['hero_image'] ?? null);
                $current[$theme]['hero_image'] = null;
            }

            $bgKey = "background_image_{$theme}";
            if ($request->hasFile($bgKey)) {
                $this->deleteStoredFile($current[$theme]['background']['image'] ?? null);
                $current[$theme]['background']['image'] = $this->storeImage(
                    $request->file($bgKey),
                    'hero/backgrounds'
                );
            } elseif ($request->boolean("remove_{$bgKey}")) {
                $this->deleteStoredFile($current[$theme]['background']['image'] ?? null);
                $current[$theme]['background']['image'] = null;
            }

            $current[$theme]['background'] = $this->normalizeBackground(
                $payload[$theme]['background'] ?? [],
                $current[$theme]['background'] ?? $this->defaultBackground()
            );
        }

        $current['enabled'] = (bool) ($payload['enabled'] ?? true);
        $current['content'] = [
            'title_prefix' => $payload['content']['title_prefix'] ?? '',
            'typing_texts' => $this->normalizeTypingTexts($payload['content']['typing_texts'] ?? ''),
            'subtitle' => $payload['content']['subtitle'] ?? '',
            'image_alt' => $payload['content']['image_alt'] ?? '',
            'buttons' => $this->normalizeButtonsInput($payload['content']['buttons'] ?? []),
            'stats' => $this->normalizeStatsInput($payload['content']['stats'] ?? []),
        ];

        Setting::set(self::SETTING_KEY, json_encode($current, JSON_UNESCAPED_UNICODE), self::SETTING_GROUP);
        $this->clearCache();
    }

    /**
     * @param  array<string, mixed>  $background
     */
    public function buildBackgroundCss(array $background): ?string
    {
        $mode = $background['mode'] ?? 'inherit';

        if ($mode === 'inherit' || $mode === '') {
            return null;
        }

        if ($mode === 'color') {
            $color = $this->sanitizeColor($background['color'] ?? '');
            return $color !== '' ? $color : null;
        }

        if ($mode === 'gradient') {
            $from = $this->sanitizeColor($background['gradient_from'] ?? '#ffffff');
            $to = $this->sanitizeColor($background['gradient_to'] ?? '#e8f0fa');
            $angle = max(0, min(360, (int) ($background['gradient_angle'] ?? 180)));

            return "linear-gradient({$angle}deg, {$from} 0%, {$to} 100%)";
        }

        if ($mode === 'image') {
            $path = $background['image'] ?? null;
            if (! $path) {
                return null;
            }
            $url = hero_asset_url($path);

            return $url ? "url('{$url}') center / cover no-repeat" : null;
        }

        return null;
    }

    public function resolveHeroImageUrl(?string $path, string $fallbackAsset): string
    {
        if ($path) {
            $url = hero_asset_url($path);
            if ($url) {
                return $url;
            }
        }

        return asset($fallbackAsset);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBackground(): array
    {
        return [
            'mode' => 'inherit',
            'color' => '#f0f2f5',
            'gradient_from' => '#ffffff',
            'gradient_to' => '#e8f0fa',
            'gradient_angle' => 180,
            'image' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    protected function mergeRecursive(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = $this->mergeRecursive($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    protected function normalizeBackground(array $input, array $existing): array
    {
        $mode = $input['mode'] ?? $existing['mode'] ?? 'inherit';

        return [
            'mode' => in_array($mode, ['inherit', 'color', 'gradient', 'image'], true) ? $mode : 'inherit',
            'color' => $this->sanitizeColor($input['color'] ?? $existing['color'] ?? '#f0f2f5'),
            'gradient_from' => $this->sanitizeColor($input['gradient_from'] ?? $existing['gradient_from'] ?? '#ffffff'),
            'gradient_to' => $this->sanitizeColor($input['gradient_to'] ?? $existing['gradient_to'] ?? '#e8f0fa'),
            'gradient_angle' => max(0, min(360, (int) ($input['gradient_angle'] ?? $existing['gradient_angle'] ?? 180))),
            'image' => $existing['image'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $button
     * @return array<string, mixed>
     */
    protected function normalizeButton(array $button): array
    {
        $url = trim((string) ($button['url'] ?? '/'));

        return [
            'label' => $button['label'] ?? '',
            'url' => $this->resolveButtonUrl($url),
            'style' => in_array($button['style'] ?? '', ['primary', 'outline'], true) ? $button['style'] : 'primary',
            'icon' => $button['icon'] ?? 'fas fa-link',
            'enabled' => (bool) ($button['enabled'] ?? true),
        ];
    }

    protected function resolveButtonUrl(string $url): string
    {
        if ($url === '') {
            return url('/');
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }

    /**
     * @param  array<string, mixed>  $stat
     * @return array<string, mixed>
     */
    protected function normalizeStat(array $stat): array
    {
        return [
            'value' => (int) ($stat['value'] ?? 0),
            'suffix' => (string) ($stat['suffix'] ?? '+'),
            'label' => $stat['label'] ?? '',
            'enabled' => (bool) ($stat['enabled'] ?? true),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|mixed  $input
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeButtonsInput($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $out = [];
        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'label' => trim((string) ($row['label'] ?? '')),
                'url' => trim((string) ($row['url'] ?? '')),
                'style' => in_array($row['style'] ?? '', ['primary', 'outline'], true) ? $row['style'] : 'primary',
                'icon' => trim((string) ($row['icon'] ?? 'fas fa-link')),
                'enabled' => filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>|mixed  $input
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeStatsInput($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $out = [];
        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'value' => (int) ($row['value'] ?? 0),
                'suffix' => (string) ($row['suffix'] ?? '+'),
                'label' => trim((string) ($row['label'] ?? '')),
                'enabled' => filter_var($row['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeTypingTexts(mixed $input): array
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map('trim', $input), fn ($t) => $t !== ''));
        }

        $text = (string) $input;

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|\|/', $text) ?: []), fn ($t) => $t !== ''));
    }

    protected function sanitizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            return $color;
        }

        return '';
    }

    protected function storeImage(UploadedFile $file, string $directory): string
    {
        return (string) $this->storageHelper->storeUploadedFile('public', $directory, $file, 'image');
    }

    protected function deleteStoredFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if ($this->storageHelper->fileExists('public', $path)) {
            $this->storageHelper->deleteFile('public', $path);
        }
    }

}
