<?php

if (! function_exists('frontend_asset')) {
    /**
     * رابط أصل الفرونت اند مع رقم إصدار (وقت تعديل الملف) لتفادي كاش قديم على السيرفر.
     */
    function frontend_asset(string $path): string
    {
        $normalized = ltrim($path, '/');
        $fullPath = public_path($normalized);
        $version = is_file($fullPath)
            ? (string) filemtime($fullPath)
            : (string) config('app.asset_version', '1');

        return asset($normalized).'?v='.$version;
    }
}
