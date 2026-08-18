<?php

namespace App\Services\Whm\MailDns;

use App\Support\Dns\DnsValue;

/**
 * Turns a cPanel DNS zone (WHM `dumpzone`) into the list of records we want to exist in
 * Cloudflare so email works.
 *
 * The source of truth is deliberately cPanel's own zone, not a locally-derived "best
 * practice" value: cPanel generated those records, so mirroring them cannot invent a
 * wrong SPF or a stale DKIM key. It also means the feature works on servers where the
 * EmailAuth UAPI module is unavailable, which is the common case.
 *
 * DMARC is the one exception — cPanel does not create it — so it is synthesised and
 * flagged `generated` so the operator is never told a made-up value came from cPanel.
 *
 * Item = array{
 *   key: string,             // dkim | spf | dmarc | mx | mail_host | webmail | autodiscover | autoconfig
 *   label: string,
 *   type: string,            // TXT | MX | A | AAAA | CNAME
 *   name: string,            // always an FQDN
 *   content: string,
 *   priority: ?int,          // MX only
 *   proxy: 'off'|'inherit',  // 'off' = must never be orange-clouded (breaks SMTP)
 *   origin: 'mirrored'|'generated',
 *   note: ?string,
 * }
 *
 * Skipped = array{key: string, label: string, type: string, name: string, reason: string}
 */
class WhmMailDnsPlanBuilder
{
    /** Record types we never copy: zone infrastructure owned by the DNS provider. */
    protected const INFRASTRUCTURE_TYPES = ['SOA', 'NS', 'DNSKEY', 'DS', 'RRSIG', 'NSEC', 'NSEC3'];

    /** Control-panel service names — not mail, and pointing them at the host is wrong here. */
    protected const PANEL_PREFIXES = ['cpanel', 'whm', 'webdisk', 'cpcalendars', 'cpcontacts'];

    /** Mail-related hostnames we mirror, and whether Cloudflare proxying is acceptable. */
    protected const MAIL_HOSTS = [
        'mail' => ['key' => 'mail_host', 'label' => 'mail (A)', 'proxy' => 'off'],
        'webmail' => ['key' => 'webmail', 'label' => 'webmail (A)', 'proxy' => 'inherit'],
        'autodiscover' => ['key' => 'autodiscover', 'label' => 'autodiscover', 'proxy' => 'inherit'],
        'autoconfig' => ['key' => 'autoconfig', 'label' => 'autoconfig', 'proxy' => 'inherit'],
    ];

    /**
     * @param  list<array<string, mixed>>  $zoneRecords  rows from WhmApiService::dumpZone()
     * @return array{items: list<array<string, mixed>>, skipped: list<array<string, mixed>>, notes: list<string>}
     */
    public function build(string $domain, array $zoneRecords, array $options = []): array
    {
        $domain = DnsValue::host($domain);
        $items = [];
        $skipped = [];
        $notes = [];

        if ($domain === '') {
            return ['items' => [], 'skipped' => [], 'notes' => ['نطاق غير صالح']];
        }

        $rows = $this->normalizeRows($zoneRecords, $domain, $skipped);

        foreach ($this->buildMxItems($rows, $domain, $notes) as $item) {
            $items[] = $item;
        }
        foreach ($this->buildTxtItems($rows, $domain, $notes) as $item) {
            $items[] = $item;
        }
        foreach ($this->buildHostItems($rows, $domain, $skipped) as $item) {
            $items[] = $item;
        }

        $dmarc = $this->buildDmarcItem($rows, $domain, $options, $notes);
        if ($dmarc !== null) {
            $items[] = $dmarc;
        }

        return ['items' => $items, 'skipped' => $skipped, 'notes' => $notes];
    }

    /**
     * Flatten dumpzone rows into a comparable shape, recording what we deliberately drop.
     *
     * @param  list<array<string, mixed>>  $zoneRecords
     * @param  list<array<string, mixed>>  $skipped
     * @return list<array{type: string, name: string, content: string, priority: ?int, ttl: ?int}>
     */
    protected function normalizeRows(array $zoneRecords, string $domain, array &$skipped): array
    {
        $rows = [];

        foreach ($zoneRecords as $record) {
            if (! is_array($record)) {
                continue;
            }

            $type = strtoupper(trim((string) ($record['type'] ?? $record['record_type'] ?? '')));
            if ($type === '') {
                continue;
            }

            $name = DnsValue::host($record['name'] ?? $record['dname'] ?? '');
            if ($name === '' || $name === '@') {
                $name = $domain;
            }

            if (in_array($type, self::INFRASTRUCTURE_TYPES, true)) {
                $skipped[] = [
                    'key' => 'infrastructure',
                    'label' => $type,
                    'type' => $type,
                    'name' => $name,
                    'reason' => 'سجل بنية المنطقة — يملكه مزوّد DNS ولا يُنسخ',
                ];

                continue;
            }

            // Never plan a write outside the account's own domain.
            if (! DnsValue::isWithin($name, $domain)) {
                continue;
            }

            $content = $this->contentFor($type, $record);
            if ($content === '') {
                continue;
            }

            $rows[] = [
                'type' => $type,
                'name' => $name,
                'content' => $content,
                'priority' => $type === 'MX' ? DnsValue::priority($record) : null,
                'ttl' => isset($record['ttl']) && is_numeric($record['ttl']) ? (int) $record['ttl'] : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function contentFor(string $type, array $record): string
    {
        $raw = match ($type) {
            'TXT' => $record['txtdata'] ?? $record['txt'] ?? $record['value'] ?? null,
            'MX' => $record['exchange'] ?? $record['exchanger'] ?? $record['target'] ?? null,
            'CNAME' => $record['cname'] ?? $record['target'] ?? null,
            'A', 'AAAA' => $record['address'] ?? $record['ip'] ?? null,
            default => $record['address'] ?? $record['target'] ?? $record['value'] ?? null,
        };

        if (is_array($raw)) {
            // Chunked TXT arrives as an array; DnsValue joins with no separator.
            $raw = implode('', array_map('strval', $raw));
        }

        return $type === 'TXT'
            ? DnsValue::txt((string) $raw)
            : trim((string) $raw);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $notes
     * @return list<array<string, mixed>>
     */
    protected function buildMxItems(array $rows, string $domain, array &$notes): array
    {
        $items = [];

        foreach ($rows as $row) {
            if ($row['type'] !== 'MX' || DnsValue::host($row['name']) !== $domain) {
                continue;
            }

            $items[] = [
                'key' => 'mx',
                'label' => 'MX',
                'type' => 'MX',
                'name' => $domain,
                'content' => DnsValue::host($row['content']),
                'priority' => $row['priority'] ?? 0,
                'proxy' => 'off',
                'origin' => 'mirrored',
                'note' => null,
            ];
        }

        if ($items === []) {
            $notes[] = 'لا سجل MX في منطقة cPanel — لن يُركَّب سجل استقبال بريد';
        }

        return $items;
    }

    /**
     * SPF and DKIM, matched by content marker so an unrelated TXT is never treated as one.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $notes
     * @return list<array<string, mixed>>
     */
    protected function buildTxtItems(array $rows, string $domain, array &$notes): array
    {
        $items = [];

        $spf = [];
        $dkim = [];

        foreach ($rows as $row) {
            if ($row['type'] !== 'TXT') {
                continue;
            }

            $name = DnsValue::host($row['name']);

            if ($name === $domain && DnsValue::txtHasPrefix($row['content'], 'v=spf1')) {
                $spf[] = $row;

                continue;
            }

            if (str_starts_with($name, 'default._domainkey.') || $name === 'default._domainkey.'.$domain) {
                if (DnsValue::txtHasPrefix($row['content'], 'v=DKIM1')) {
                    $dkim[] = $row;
                }
            }
        }

        if (count($spf) > 1) {
            $notes[] = 'أكثر من سجل SPF في منطقة cPanel — لن يُركَّب SPF حتى تُوحَّد يدوياً';
        } elseif ($spf !== []) {
            $items[] = [
                'key' => 'spf',
                'label' => 'SPF',
                'type' => 'TXT',
                'name' => $domain,
                'content' => $spf[0]['content'],
                'priority' => null,
                'proxy' => 'off',
                'origin' => 'mirrored',
                'note' => null,
            ];
        } else {
            $notes[] = 'لا سجل SPF في منطقة cPanel';
        }

        if (count($dkim) > 1) {
            $notes[] = 'أكثر من مفتاح DKIM في منطقة cPanel — يُركَّب الأول فقط';
        }

        if ($dkim !== []) {
            $items[] = [
                'key' => 'dkim',
                'label' => 'DKIM',
                'type' => 'TXT',
                'name' => DnsValue::host($dkim[0]['name']),
                'content' => $dkim[0]['content'],
                'priority' => null,
                'proxy' => 'off',
                'origin' => 'mirrored',
                'note' => null,
            ];
        } else {
            $notes[] = 'لا مفتاح DKIM في منطقة cPanel — فعّله من cPanel أولاً';
        }

        return $items;
    }

    /**
     * mail / webmail / autodiscover / autoconfig, mirrored as cPanel has them.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $skipped
     * @return list<array<string, mixed>>
     */
    protected function buildHostItems(array $rows, string $domain, array &$skipped): array
    {
        $items = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! in_array($row['type'], ['A', 'AAAA', 'CNAME'], true)) {
                continue;
            }

            $name = DnsValue::host($row['name']);
            $prefix = $this->prefixWithin($name, $domain);

            if ($prefix === null) {
                continue;
            }

            if (in_array($prefix, self::PANEL_PREFIXES, true)) {
                $skipped[] = [
                    'key' => 'panel_service',
                    'label' => $prefix,
                    'type' => $row['type'],
                    'name' => $name,
                    'reason' => 'خدمة لوحة تحكم لا بريد — لا علاقة لها بتشغيل الإيميل',
                ];

                continue;
            }

            if (! isset(self::MAIL_HOSTS[$prefix])) {
                continue;
            }

            if ($row['type'] === 'AAAA' && ! config('whm.mail_dns_include_ipv6', false)) {
                $skipped[] = [
                    'key' => 'ipv6',
                    'label' => $prefix.' (AAAA)',
                    'type' => 'AAAA',
                    'name' => $name,
                    'reason' => 'IPv6 معطّل — يحتاج PTR سليماً على IPv6 قبل تفعيله',
                ];

                continue;
            }

            $meta = self::MAIL_HOSTS[$prefix];
            $dedupe = $meta['key'].'|'.$row['type'];
            if (isset($seen[$dedupe])) {
                continue;
            }
            $seen[$dedupe] = true;

            $items[] = [
                'key' => $meta['key'],
                'label' => $meta['label'],
                'type' => $row['type'],
                'name' => $name,
                'content' => $row['type'] === 'CNAME' ? DnsValue::host($row['content']) : $row['content'],
                'priority' => null,
                'proxy' => $meta['proxy'],
                'origin' => 'mirrored',
                'note' => $meta['proxy'] === 'off'
                    ? 'يجب ألا يكون عبر بروكسي Cloudflare — البروكسي لا يمرّر SMTP'
                    : null,
            ];
        }

        return $items;
    }

    /**
     * The label directly under $domain, or null when $name is not exactly one level down.
     */
    protected function prefixWithin(string $name, string $domain): ?string
    {
        if ($name === $domain || ! str_ends_with($name, '.'.$domain)) {
            return null;
        }

        $prefix = substr($name, 0, -strlen('.'.$domain));

        return str_contains($prefix, '.') ? null : $prefix;
    }

    /**
     * cPanel does not create DMARC, so this one is synthesised — and labelled as such.
     *
     * p=none is the only policy safe to publish unattended: it turns on reporting
     * without asking anyone to reject mail, so a wrong SPF/DKIM cannot start bouncing
     * legitimate messages.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $options
     * @param  list<string>  $notes
     * @return array<string, mixed>|null
     */
    protected function buildDmarcItem(array $rows, string $domain, array $options, array &$notes): ?array
    {
        $name = '_dmarc.'.$domain;

        foreach ($rows as $row) {
            if (DnsValue::host($row['name']) !== $name) {
                continue;
            }

            if ($row['type'] === 'TXT' && DnsValue::txtHasPrefix($row['content'], 'v=DMARC1')) {
                return [
                    'key' => 'dmarc',
                    'label' => 'DMARC',
                    'type' => 'TXT',
                    'name' => $name,
                    'content' => $row['content'],
                    'priority' => null,
                    'proxy' => 'off',
                    'origin' => 'mirrored',
                    'note' => null,
                ];
            }
        }

        $generate = $options['generate_dmarc'] ?? config('whm.mail_dns_generate_dmarc', true);
        if (! $generate) {
            return null;
        }

        $policy = strtolower(trim((string) ($options['dmarc_policy'] ?? config('whm.mail_dns_dmarc_policy', 'none'))));
        if (! in_array($policy, ['none', 'quarantine', 'reject'], true)) {
            $policy = 'none';
        }
        if ($policy !== 'none') {
            $notes[] = 'سياسة DMARC المضبوطة ('.$policy.') قد ترفض بريداً — تحقّق قبل التطبيق';
        }

        $content = 'v=DMARC1; p='.$policy.';';

        $rua = trim((string) ($options['dmarc_rua'] ?? config('whm.mail_dns_dmarc_rua', '')));
        if ($rua !== '' && filter_var($rua, FILTER_VALIDATE_EMAIL)) {
            $content .= ' rua=mailto:'.$rua.';';
        }

        return [
            'key' => 'dmarc',
            'label' => 'DMARC',
            'type' => 'TXT',
            'name' => $name,
            'content' => $content,
            'priority' => null,
            'proxy' => 'off',
            'origin' => 'generated',
            'note' => 'قيمة مُولَّدة لا منقولة من cPanel — cPanel لا يُنشئ DMARC',
        ];
    }
}
