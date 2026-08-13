<?php

namespace App\Support;

class Html
{
    /**
     * يسمح بعرض HTML آمن من المحرر مع إزالة الوسوم الخطرة.
     */
    public static function safe(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // نص عادي قديم بدون وسوم
        if ($html === strip_tags($html)) {
            return nl2br(e($html), false);
        }

        $allowed = '<p><br><br/><strong><b><em><i><u><s><ul><ol><li><a><h1><h2><h3><h4><h5><h6>'
            .'<table><thead><tbody><tfoot><tr><th><td><img><blockquote><code><pre><span><div>'
            .'<hr><sub><sup><figure><figcaption>';

        $clean = strip_tags($html, $allowed);

        // إزالة سمات الأحداث وروابط javascript
        $clean = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/\shref\s*=\s*("|\')\s*javascript:[^"\']*\1/iu', ' href="#"', $clean) ?? $clean;
        $clean = preg_replace('/\ssrc\s*=\s*("|\')\s*javascript:[^"\']*\1/iu', '', $clean) ?? $clean;

        return $clean;
    }

    public static function isEmpty(?string $html): bool
    {
        return trim(html_entity_decode(strip_tags((string) $html))) === '';
    }
}
