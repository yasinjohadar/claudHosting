<?php

namespace App\Services\Whm\MailDns;

use App\Support\Dns\DnsValue;

/**
 * Compares a desired mail-DNS plan against what a Cloudflare zone actually holds.
 *
 * The existing record list is passed in once and indexed in memory. CloudflareApiService
 * has findDnsRecordByName(), but it re-lists the whole zone per call, matches only the
 * first row, and compares raw names against Cloudflare's FQDNs — all three are wrong
 * here: MX legitimately has several rows at one name, and the apex TXT holds SPF
 * alongside unrelated verification tokens that must never be clobbered.
 *
 * Change = array{
 *   ...plan item fields,
 *   verdict: 'create'|'update'|'unchanged'|'conflict',
 *   record_id: ?string,
 *   old_content: ?string,
 *   old_priority: ?int,
 *   old_proxied: ?bool,
 *   reason: ?string,
 * }
 */
class WhmMailDnsDiffer
{
    /** TXT content markers, so each desired TXT matches its own row and no other. */
    protected const TXT_MARKERS = [
        'spf' => 'v=spf1',
        'dkim' => 'v=DKIM1',
        'dmarc' => 'v=DMARC1',
    ];

    /**
     * Well-known third-party mail providers. If the zone's MX already points at one of
     * these, pointing it at cPanel would silently break a working mailbox.
     */
    protected const THIRD_PARTY_MX = [
        'google.com' => 'Google Workspace',
        'googlemail.com' => 'Google Workspace',
        'outlook.com' => 'Microsoft 365',
        'protection.outlook.com' => 'Microsoft 365',
        'zoho.com' => 'Zoho Mail',
        'zoho.eu' => 'Zoho Mail',
        'yandex.net' => 'Yandex Mail',
        'mail.ru' => 'Mail.ru',
        'messagingengine.com' => 'Fastmail',
        'improvmx.com' => 'ImprovMX',
        'mimecast.com' => 'Mimecast',
        'pphosted.com' => 'Proofpoint',
        'titan.email' => 'Titan',
        'secureserver.net' => 'GoDaddy Email',
    ];

    /**
     * @param  list<array<string, mixed>>  $planItems
     * @param  list<array<string, mixed>>  $cloudflareRecords
     * @return array{
     *     changes: list<array<string, mixed>>,
     *     extras: list<array<string, mixed>>,
     *     third_party_mx: list<array{provider: string, content: string}>,
     *     counts: array<string, int>
     * }
     */
    public function diff(array $planItems, array $cloudflareRecords): array
    {
        $index = $this->indexRecords($cloudflareRecords);
        $changes = [];
        $claimed = [];

        // MX is a set at one name, so it cannot be compared row by row.
        $mxItems = array_values(array_filter($planItems, fn (array $i): bool => strtoupper($i['type']) === 'MX'));
        $others = array_values(array_filter($planItems, fn (array $i): bool => strtoupper($i['type']) !== 'MX'));

        foreach ($this->diffMxSet($mxItems, $index, $claimed) as $change) {
            $changes[] = $change;
        }

        foreach ($others as $item) {
            $changes[] = $this->diffSingle($item, $index, $claimed);
        }

        return [
            'changes' => $changes,
            'extras' => $this->collectExtras($planItems, $index, $claimed),
            'third_party_mx' => $this->detectThirdPartyMx($mxItems, $index),
            'counts' => $this->countVerdicts($changes),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, list<array<string, mixed>>> keyed "name|TYPE"
     */
    protected function indexRecords(array $records): array
    {
        $index = [];

        foreach ($records as $record) {
            if (! is_array($record) || isset($record['_error'])) {
                continue;
            }

            $name = DnsValue::host($record['name'] ?? null);
            $type = strtoupper((string) ($record['type'] ?? ''));
            if ($name === '' || $type === '') {
                continue;
            }

            $index[$name.'|'.$type][] = [
                'id' => (string) ($record['id'] ?? ''),
                'name' => $name,
                'type' => $type,
                'content' => (string) ($record['content'] ?? ''),
                'priority' => $type === 'MX' ? DnsValue::priority($record) : null,
                'proxied' => array_key_exists('proxied', $record) ? (bool) $record['proxied'] : null,
                'ttl' => isset($record['ttl']) && is_numeric($record['ttl']) ? (int) $record['ttl'] : null,
                'comment' => $record['comment'] ?? null,
            ];
        }

        return $index;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $index
     * @return list<array<string, mixed>>
     */
    protected function at(array $index, string $name, string $type): array
    {
        return $index[DnsValue::host($name).'|'.strtoupper($type)] ?? [];
    }

    /**
     * MX: pair each desired exchange with an existing row of the same content, so a
     * priority-only change becomes an update rather than a delete-and-create.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, list<array<string, mixed>>>  $index
     * @param  array<string, true>  $claimed
     * @return list<array<string, mixed>>
     */
    protected function diffMxSet(array $items, array $index, array &$claimed): array
    {
        if ($items === []) {
            return [];
        }

        $changes = [];
        $name = $items[0]['name'];
        $existing = $this->at($index, $name, 'MX');

        // A CNAME at the mail name would make MX illegal (RFC 1034).
        $cname = $this->at($index, $name, 'CNAME');
        if ($cname !== []) {
            foreach ($items as $item) {
                $changes[] = $this->verdict($item, 'conflict', null, [
                    'reason' => 'يوجد CNAME على نفس الاسم — لا يمكن أن يتجاور مع MX. أزِله يدوياً أولاً',
                ]);
            }

            return $changes;
        }

        $unmatched = $existing;

        foreach ($items as $item) {
            $wantContent = DnsValue::host($item['content']);
            $matchIndex = null;

            foreach ($unmatched as $i => $row) {
                if (DnsValue::host($row['content']) === $wantContent) {
                    $matchIndex = $i;
                    break;
                }
            }

            if ($matchIndex === null) {
                $changes[] = $this->verdict($item, 'create');

                continue;
            }

            $row = $unmatched[$matchIndex];
            unset($unmatched[$matchIndex]);
            $claimed[$row['id']] = true;

            $samePriority = (int) ($item['priority'] ?? 0) === (int) ($row['priority'] ?? 0);
            $changes[] = $this->verdict($item, $samePriority ? 'unchanged' : 'update', $row);
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, list<array<string, mixed>>>  $index
     * @param  array<string, true>  $claimed
     * @return array<string, mixed>
     */
    protected function diffSingle(array $item, array $index, array &$claimed): array
    {
        $type = strtoupper($item['type']);
        $name = $item['name'];

        // A CNAME cannot coexist with other data at the same name.
        if ($type === 'TXT') {
            $cname = $this->at($index, $name, 'CNAME');
            if ($cname !== []) {
                return $this->verdict($item, 'conflict', null, [
                    'reason' => 'يوجد CNAME على '.$name.' — لا يمكن أن يتجاور مع TXT. عادةً تفويض متعمّد، فلن نحذفه',
                ]);
            }
        }

        $candidates = $this->at($index, $name, $type);

        // The other direction: we want an A/CNAME but a different type already sits there.
        if ($candidates === [] && in_array($type, ['A', 'AAAA', 'CNAME'], true)) {
            foreach (['A', 'AAAA', 'CNAME'] as $otherType) {
                if ($otherType === $type) {
                    continue;
                }
                $other = $this->at($index, $name, $otherType);
                if ($other !== []) {
                    return $this->verdict($item, 'conflict', $other[0], [
                        'reason' => 'يوجد سجل '.$otherType.' على نفس الاسم بينما المطلوب '.$type.' — يحتاج قراراً يدوياً',
                    ]);
                }
            }
        }

        $match = $type === 'TXT'
            ? $this->matchTxt($item, $candidates)
            : ($candidates[0] ?? null);

        if ($match === null) {
            return $this->verdict($item, 'create');
        }

        $claimed[$match['id']] = true;

        return $this->verdict($item, $this->isSatisfied($item, $match) ? 'unchanged' : 'update', $match);
    }

    /**
     * Find the TXT row that carries the same policy marker. Without this, an apex TXT
     * verification token would be overwritten with an SPF value.
     *
     * @param  array<string, mixed>  $item
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, mixed>|null
     */
    protected function matchTxt(array $item, array $candidates): ?array
    {
        $marker = self::TXT_MARKERS[$item['key']] ?? null;

        foreach ($candidates as $row) {
            if ($marker === null) {
                if (DnsValue::txt($row['content']) === DnsValue::txt($item['content'])) {
                    return $row;
                }

                continue;
            }

            if (DnsValue::txtHasPrefix($row['content'], $marker)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Is the existing record already what we want? Content, priority — and for the mail
     * host also the proxy flag, because an orange-clouded mail A resolves to a Cloudflare
     * address that never answers on SMTP ports, so it is broken even when the content
     * matches.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $existing
     */
    protected function isSatisfied(array $item, array $existing): bool
    {
        $type = strtoupper($item['type']);

        if (DnsValue::content($type, $existing['content']) !== DnsValue::content($type, $item['content'])) {
            return false;
        }

        if ($type === 'MX' && (int) ($item['priority'] ?? 0) !== (int) ($existing['priority'] ?? 0)) {
            return false;
        }

        if (($item['proxy'] ?? 'inherit') === 'off' && ($existing['proxied'] ?? false) === true) {
            return false;
        }

        return true;
    }

    /**
     * Records at a planned name that we are deliberately leaving alone. We never delete
     * what we did not plan, but a leftover MX or a second SPF still needs a human, so it
     * is surfaced rather than silently ignored.
     *
     * @param  list<array<string, mixed>>  $planItems
     * @param  array<string, list<array<string, mixed>>>  $index
     * @param  array<string, true>  $claimed
     * @return list<array<string, mixed>>
     */
    protected function collectExtras(array $planItems, array $index, array $claimed): array
    {
        $extras = [];

        foreach ($planItems as $item) {
            $type = strtoupper($item['type']);
            if (! in_array($type, ['MX', 'TXT'], true)) {
                continue;
            }

            $marker = self::TXT_MARKERS[$item['key']] ?? null;

            foreach ($this->at($index, $item['name'], $type) as $row) {
                if (isset($claimed[$row['id']]) || $row['id'] === '') {
                    continue;
                }

                if ($type === 'TXT' && ($marker === null || ! DnsValue::txtHasPrefix($row['content'], $marker))) {
                    // A different TXT at the same name is none of our business.
                    continue;
                }

                $extras[$row['id']] = [
                    'type' => $type,
                    'name' => $row['name'],
                    'content' => $row['content'],
                    'priority' => $row['priority'],
                    'reason' => $type === 'MX'
                        ? 'سجل MX إضافي لم نخطّط له — لن يُحذف، راجعه يدوياً'
                        : 'سجل '.strtoupper($item['key']).' إضافي لم نخطّط له — لن يُحذف، راجعه يدوياً',
                ];
            }
        }

        return array_values($extras);
    }

    /**
     * Is the zone's current MX pointing at a known third-party mail provider that we
     * would be taking over? This is the single most dangerous thing the feature could do.
     *
     * @param  list<array<string, mixed>>  $mxItems
     * @param  array<string, list<array<string, mixed>>>  $index
     * @return list<array{provider: string, content: string}>
     */
    protected function detectThirdPartyMx(array $mxItems, array $index): array
    {
        if ($mxItems === []) {
            return [];
        }

        $found = [];

        foreach ($this->at($index, $mxItems[0]['name'], 'MX') as $row) {
            $content = DnsValue::host($row['content']);
            if ($content === '') {
                continue;
            }

            foreach (self::THIRD_PARTY_MX as $needle => $provider) {
                if ($content === $needle || str_ends_with($content, '.'.$needle)) {
                    $found[$provider.'|'.$content] = ['provider' => $provider, 'content' => $content];
                    break;
                }
            }
        }

        return array_values($found);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>|null  $existing
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function verdict(array $item, string $verdict, ?array $existing = null, array $extra = []): array
    {
        return array_merge($item, [
            'verdict' => $verdict,
            'record_id' => $existing['id'] ?? null,
            'old_content' => $existing['content'] ?? null,
            'old_priority' => $existing['priority'] ?? null,
            'old_proxied' => $existing['proxied'] ?? null,
            'old_comment' => $existing['comment'] ?? null,
            'reason' => null,
        ], $extra);
    }

    /**
     * @param  list<array<string, mixed>>  $changes
     * @return array<string, int>
     */
    protected function countVerdicts(array $changes): array
    {
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0, 'conflict' => 0];

        foreach ($changes as $change) {
            $verdict = $change['verdict'];
            $counts[$verdict] = ($counts[$verdict] ?? 0) + 1;
        }

        return $counts;
    }
}
