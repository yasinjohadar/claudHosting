<?php

namespace App\Services\Domain;

use App\Services\CloudflareApiService;
use App\Services\NamecomApiService;

class DomainAvailabilitySearchService
{
    public function __construct(
        protected CloudflareApiService $cloudflare,
        protected NamecomApiService $namecom
    ) {}

    /**
     * @return array{
     *   query: string,
     *   configured: array{cloudflare: bool, namecom: bool},
     *   errors: array{cloudflare: ?string, namecom: ?string},
     *   rows: array<int, array<string, mixed>>
     * }
     */
    public function search(string $rawQuery): array
    {
        $query = $this->normalizeQuery($rawQuery);
        $configured = [
            'cloudflare' => $this->cloudflare->isConfigured(),
            'namecom' => $this->namecom->isConfigured(),
        ];

        $errors = ['cloudflare' => null, 'namecom' => null];
        /** @var array<string, array<string, mixed>> $registry */
        $registry = [];

        if ($query === '') {
            return [
                'query' => '',
                'configured' => $configured,
                'errors' => $errors,
                'rows' => [],
            ];
        }

        if ($configured['cloudflare']) {
            $cf = $this->fetchCloudflare($query);
            $errors['cloudflare'] = $cf['error'];
            foreach ($cf['items'] as $item) {
                $this->mergeProvider($registry, $item['domain'], 'cloudflare', $item);
            }
        }

        if ($configured['namecom']) {
            $nc = $this->fetchNamecom($query);
            $errors['namecom'] = $nc['error'];
            foreach ($nc['items'] as $item) {
                $this->mergeProvider($registry, $item['domain'], 'namecom', $item);
            }
        }

        $rows = array_values($registry);
        usort($rows, function (array $a, array $b) use ($query): int {
            $exactA = ($a['domain'] ?? '') === $query ? 0 : 1;
            $exactB = ($b['domain'] ?? '') === $query ? 0 : 1;
            if ($exactA !== $exactB) {
                return $exactA <=> $exactB;
            }

            $availA = ($a['any_available'] ?? false) ? 0 : 1;
            $availB = ($b['any_available'] ?? false) ? 0 : 1;
            if ($availA !== $availB) {
                return $availA <=> $availB;
            }

            return strcmp($a['domain'] ?? '', $b['domain'] ?? '');
        });

        return [
            'query' => $query,
            'configured' => $configured,
            'errors' => $errors,
            'rows' => $rows,
        ];
    }

    public function normalizeQuery(string $raw): string
    {
        $q = trim(mb_strtolower($raw));
        $q = preg_replace('#^https?://#', '', $q) ?? $q;
        $q = preg_replace('#^www\.#', '', $q) ?? $q;
        $q = trim($q, " \t\n\r\0\x0B/");

        return $q;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, error: ?string}
     */
    protected function fetchCloudflare(string $query): array
    {
        $search = $this->cloudflare->searchRegistrarDomains($query, 25);
        if ($search['error'] !== null) {
            return ['items' => [], 'error' => $search['error']];
        }

        $items = [];
        $toCheck = [];

        foreach ($search['suggestions'] as $row) {
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $pricing = $row['pricing'] ?? [];
            $registrable = (bool) ($row['registrable'] ?? false);
            $items[$name] = [
                'domain' => $name,
                'available' => $registrable,
                'price' => $this->moneyFromPricing($pricing, 'registration_cost'),
                'renewal' => $this->moneyFromPricing($pricing, 'renewal_cost'),
                'premium' => ($row['tier'] ?? '') === 'premium',
                'kind' => 'suggestion',
                'note' => $registrable ? null : ($row['unregistrable_reason'] ?? 'غير متاح'),
            ];

            if ($registrable) {
                $toCheck[] = $name;
            }
        }

        if (str_contains($query, '.') && ! isset($items[$query])) {
            $toCheck[] = $query;
        }

        if ($toCheck !== []) {
            $check = $this->cloudflare->checkRegistrarDomains($toCheck);
            if ($check['error'] === null) {
                foreach ($check['checks'] as $row) {
                    $name = strtolower(trim((string) ($row['name'] ?? '')));
                    if ($name === '') {
                        continue;
                    }
                    $pricing = $row['pricing'] ?? [];
                    $items[$name] = [
                        'domain' => $name,
                        'available' => (bool) ($row['registrable'] ?? false),
                        'price' => $this->moneyFromPricing($pricing, 'registration_cost'),
                        'renewal' => $this->moneyFromPricing($pricing, 'renewal_cost'),
                        'premium' => ($row['tier'] ?? '') === 'premium',
                        'kind' => $name === $query ? 'exact' : 'suggestion',
                        'note' => ($row['registrable'] ?? false) ? null : ($row['unregistrable_reason'] ?? 'غير متاح'),
                    ];
                }
            }
        }

        return ['items' => array_values($items), 'error' => null];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, error: ?string}
     */
    protected function fetchNamecom(string $query): array
    {
        $items = [];
        $search = $this->namecom->searchDomainSuggestions($query);
        if ($search['error'] !== null) {
            return ['items' => [], 'error' => $search['error']];
        }

        $names = [];
        foreach ($search['results'] as $row) {
            $name = strtolower(trim((string) ($row['domainName'] ?? '')));
            if ($name === '') {
                continue;
            }
            $names[] = $name;
            $items[$name] = $this->mapNamecomRow($row, $name === $query ? 'exact' : 'suggestion');
        }

        if (str_contains($query, '.') && ! in_array($query, $names, true)) {
            $names[] = $query;
        }

        if ($names !== []) {
            $check = $this->namecom->checkDomainsAvailability($names);
            if ($check['error'] === null) {
                foreach ($check['results'] as $row) {
                    $name = strtolower(trim((string) ($row['domainName'] ?? '')));
                    if ($name === '') {
                        continue;
                    }
                    $items[$name] = $this->mapNamecomRow($row, $name === $query ? 'exact' : 'suggestion');
                }
            } elseif ($search['error'] === null && empty($items)) {
                return ['items' => [], 'error' => $check['error']];
            }
        }

        return ['items' => array_values($items), 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapNamecomRow(array $row, string $kind): array
    {
        $purchasable = (bool) ($row['purchasable'] ?? false);

        return [
            'domain' => strtolower(trim((string) ($row['domainName'] ?? ''))),
            'available' => $purchasable,
            'price' => isset($row['purchasePrice']) ? (float) $row['purchasePrice'] : null,
            'renewal' => isset($row['renewalPrice']) ? (float) $row['renewalPrice'] : null,
            'premium' => (bool) ($row['premium'] ?? false),
            'kind' => $kind,
            'note' => $purchasable ? null : 'غير متاح للشراء',
        ];
    }

    /**
     * @param  array<string, mixed>  $pricing
     */
    protected function moneyFromPricing(array $pricing, string $key): ?float
    {
        if (! isset($pricing[$key])) {
            return null;
        }

        return is_numeric($pricing[$key]) ? (float) $pricing[$key] : null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $registry
     * @param  array<string, mixed>  $providerData
     */
    protected function mergeProvider(array &$registry, string $domain, string $provider, array $providerData): void
    {
        if ($domain === '') {
            return;
        }

        if (! isset($registry[$domain])) {
            $registry[$domain] = [
                'domain' => $domain,
                'display_name' => $domain,
                'cloudflare' => null,
                'namecom' => null,
                'any_available' => false,
                'kinds' => [],
            ];
        }

        $registry[$domain][$provider] = [
            'available' => (bool) ($providerData['available'] ?? false),
            'price' => $providerData['price'] ?? null,
            'renewal' => $providerData['renewal'] ?? null,
            'premium' => (bool) ($providerData['premium'] ?? false),
            'kind' => $providerData['kind'] ?? 'suggestion',
            'note' => $providerData['note'] ?? null,
        ];

        if ($providerData['available'] ?? false) {
            $registry[$domain]['any_available'] = true;
        }

        $kind = $providerData['kind'] ?? 'suggestion';
        if (! in_array($kind, $registry[$domain]['kinds'], true)) {
            $registry[$domain]['kinds'][] = $kind;
        }
    }
}
