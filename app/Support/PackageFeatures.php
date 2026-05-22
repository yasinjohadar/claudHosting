<?php

namespace App\Support;

class PackageFeatures
{
    /**
     * @return array<string, array{class: string, label: string, brand?: bool}>
     */
    public static function iconCatalog(): array
    {
        return config('package_features.icons', []);
    }

    public static function isValidIcon(string $icon): bool
    {
        return isset(self::iconCatalog()[$icon]);
    }

    /**
     * @return array{class: string, prefix: string}
     */
    public static function iconClasses(string $icon): array
    {
        $meta = self::iconCatalog()[$icon] ?? self::iconCatalog()['check'];

        return [
            'class' => $meta['class'],
            'prefix' => ! empty($meta['brand']) ? 'fab' : 'fas',
        ];
    }

    /**
     * @param  mixed  $input
     * @return array<int, array{icon: string, text: string}>
     */
    public static function normalize(mixed $input): array
    {
        if (is_string($input) && $input !== '') {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($input)) {
            return [];
        }

        $max = (int) config('package_features.max_items', 20);
        $maxLen = (int) config('package_features.max_text_length', 500);
        $out = [];

        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }
            $text = trim(strip_tags((string) ($row['text'] ?? '')));
            if ($text === '') {
                continue;
            }
            $icon = (string) ($row['icon'] ?? 'check');
            if (! self::isValidIcon($icon)) {
                $icon = 'check';
            }
            $out[] = [
                'icon' => $icon,
                'text' => mb_substr($text, 0, $maxLen),
            ];
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }

    /**
     * استخراج بنود من وصف HTML/نصي قديم (للعرض حتى يُحدَّث المنتج).
     *
     * @return array<int, array{icon: string, text: string}>
     */
    public static function parseFromDescription(?string $html): array
    {
        if ($html === null || trim($html) === '') {
            return [];
        }

        $items = [];
        if (preg_match_all('#<li[^>]*>(.*?)</li>#isu', $html, $matches)) {
            foreach ($matches[1] as $chunk) {
                $text = trim(html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text !== '') {
                    $items[] = ['icon' => 'check', 'text' => $text];
                }
            }
        }

        if ($items !== []) {
            return $items;
        }

        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        foreach (preg_split('/\r\n|\r|\n|•|·/u', $plain) ?: [] as $line) {
            $line = trim(preg_replace('/^[\-\*]\s*/u', '', $line) ?? $line);
            if ($line !== '' && mb_strlen($line) > 2) {
                $items[] = ['icon' => 'check', 'text' => $line];
            }
        }

        return array_slice($items, 0, (int) config('package_features.max_items', 20));
    }
}
