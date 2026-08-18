<?php

namespace App\Support\Dns;

/**
 * Normalisation helpers for comparing DNS values across providers.
 *
 * WHM's dumpzone and Cloudflare's API describe the same record differently: trailing
 * dots, quoting of TXT strings, chunked TXT payloads, and case. Every comparison in
 * the mail-DNS sync flow goes through this class so a cosmetic difference is never
 * mistaken for a real one — which would otherwise rewrite a correct live record.
 */
class DnsValue
{
    /** TXT strings longer than this are split into chunks by the DNS wire format. */
    public const TXT_CHUNK_LIMIT = 255;

    /**
     * Canonical form of a hostname: lowercase, no scheme/port/path, no trailing dot.
     */
    public static function host(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value) ?? $value;
        $value = explode('/', $value)[0];
        // Strip a :port suffix, but never an IPv6 literal's colons.
        if (! str_contains($value, ':') || substr_count($value, ':') === 1) {
            $value = explode(':', $value)[0];
        }

        return rtrim(trim($value), '.');
    }

    /**
     * Canonical form of a TXT value: unquoted, chunks joined, whitespace collapsed.
     *
     * Case is preserved — base64 DKIM keys and DMARC tags are case-sensitive in
     * content even though the record name is not.
     */
    public static function txt(?string $value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }

        $value = self::joinQuotedChunks($value);
        $value = str_replace('\\"', '"', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * A TXT payload may arrive as `"chunk1" "chunk2"` (Cloudflare, dig) or already
     * joined (WHM). Concatenate quoted runs with NO separator — inserting one would
     * corrupt a base64 DKIM key split across chunks.
     */
    protected static function joinQuotedChunks(string $value): string
    {
        $trimmed = trim($value);

        if (! str_starts_with($trimmed, '"')) {
            return $trimmed;
        }

        if (preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $trimmed, $matches) && $matches[1] !== []) {
            $joined = implode('', $matches[1]);
            // Only trust the reconstruction if quotes accounted for the whole string.
            $consumed = implode('', array_map(fn (string $m): string => '"'.$m.'"', $matches[1]));
            if (preg_replace('/\s+/', '', $consumed) === preg_replace('/\s+/', '', $trimmed)) {
                return $joined;
            }
        }

        return $trimmed;
    }

    /**
     * Split a TXT value into wire-format chunks. Cloudflare accepts a single long
     * string and chunks it itself, so this exists for display and for length checks.
     *
     * @return list<string>
     */
    public static function txtChunks(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return str_split($value, self::TXT_CHUNK_LIMIT);
    }

    /**
     * Does this TXT value start with the given policy marker (`v=spf1`, `v=DKIM1`,
     * `v=DMARC1`)? Used to match a desired TXT to the right existing row instead of
     * clobbering an unrelated one such as a domain-verification token.
     */
    public static function txtHasPrefix(?string $value, string $prefix): bool
    {
        $normalized = self::txt($value);
        if ($normalized === '') {
            return false;
        }

        return stripos($normalized, $prefix) === 0;
    }

    /**
     * Canonical content for a record type, for equality comparison only.
     */
    public static function content(string $type, ?string $value): string
    {
        return match (strtoupper($type)) {
            'TXT' => self::txt($value),
            'MX', 'CNAME', 'NS', 'PTR', 'SRV' => self::host($value),
            'A', 'AAAA' => self::ip($value),
            default => trim((string) $value),
        };
    }

    /**
     * Canonical IP form. IPv6 is lowercased and compacted by inet_pton/ntop when
     * valid; anything unparseable is returned trimmed so comparison still works.
     */
    public static function ip(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $packed = @inet_pton($value);
        if ($packed !== false) {
            $back = @inet_ntop($packed);
            if (is_string($back) && $back !== '') {
                return strtolower($back);
            }
        }

        return strtolower($value);
    }

    /**
     * Are two records equivalent? Name is compared case-insensitively without the
     * trailing dot; content per type; MX also compares priority.
     */
    public static function recordsEqual(array $a, array $b): bool
    {
        $typeA = strtoupper((string) ($a['type'] ?? ''));
        $typeB = strtoupper((string) ($b['type'] ?? ''));
        if ($typeA !== $typeB || $typeA === '') {
            return false;
        }

        if (self::host($a['name'] ?? null) !== self::host($b['name'] ?? null)) {
            return false;
        }

        if (self::content($typeA, $a['content'] ?? null) !== self::content($typeB, $b['content'] ?? null)) {
            return false;
        }

        if ($typeA === 'MX') {
            return self::priority($a) === self::priority($b);
        }

        return true;
    }

    /**
     * MX priority, defaulting to 0. Accepts Cloudflare's `priority` and WHM's
     * `preference`.
     */
    public static function priority(array $record): int
    {
        foreach (['priority', 'preference'] as $key) {
            if (isset($record[$key]) && is_numeric($record[$key])) {
                return (int) $record[$key];
            }
        }

        return 0;
    }

    /**
     * Is $hostname the given apex, or inside it? Used as the write allow-list guard.
     */
    public static function isWithin(?string $hostname, ?string $apex): bool
    {
        $hostname = self::host($hostname);
        $apex = self::host($apex);

        if ($hostname === '' || $apex === '') {
            return false;
        }

        return $hostname === $apex || str_ends_with($hostname, '.'.$apex);
    }

    /**
     * Labels of $hostname from the most specific candidate down to the TLD, for
     * walking up to find the registrable zone.
     *
     * docs.claudsoft.com → ['docs.claudsoft.com', 'claudsoft.com', 'com']
     *
     * @return list<string>
     */
    public static function zoneCandidates(?string $hostname): array
    {
        $hostname = self::host($hostname);
        if ($hostname === '') {
            return [];
        }

        $labels = array_values(array_filter(explode('.', $hostname), fn (string $l): bool => $l !== ''));
        $candidates = [];

        for ($i = 0; $i < count($labels); $i++) {
            $candidates[] = implode('.', array_slice($labels, $i));
        }

        return $candidates;
    }
}
