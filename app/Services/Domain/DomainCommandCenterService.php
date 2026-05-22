<?php

namespace App\Services\Domain;

use App\Http\Controllers\Admin\Namecom\NamecomDomainController;
use App\Models\ClientDomain;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\CloudflareApiService;
use App\Services\CoolifyApiService;
use App\Services\NamecomApiService;
use App\Services\Whm\WhmApiService;
use Carbon\Carbon;

class DomainCommandCenterService
{
    public function __construct(
        protected CloudflareApiService $cloudflare,
        protected NamecomApiService $namecom,
        protected CoolifyApiService $coolify,
        protected WhmApiService $whm
    ) {}

    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   stats: array<string, int|bool>,
     *   errors: array<string, ?string>,
     *   configured: array<string, bool>
     * }
     */
    public function build(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            $this->cloudflare->clearCaches();
            $this->namecom->clearCaches();
        }

        $configured = [
            'cloudflare' => $this->cloudflare->isConfigured(),
            'namecom' => $this->namecom->isConfigured(),
            'whm' => $this->whm->isConfigured(),
        ];

        $errors = [
            'cloudflare_zones' => null,
            'cloudflare_registrar' => null,
            'namecom' => null,
        ];

        /** @var array<string, array<string, mixed>> $registry */
        $registry = [];

        if ($configured['cloudflare']) {
            $zones = $this->cloudflare->listAllZones();
            if (isset($zones['_error'])) {
                $errors['cloudflare_zones'] = $zones['_error'];
                $zones = [];
            }

            foreach ($zones as $zone) {
                if (! is_array($zone)) {
                    continue;
                }
                $name = strtolower(trim((string) ($zone['name'] ?? '')));
                if ($name === '') {
                    continue;
                }
                $this->upsertRow($registry, $name, [
                    'display_name' => $zone['name'],
                    'source' => 'cf_zone',
                    'source_label' => 'Cloudflare Zone',
                    'source_badge' => 'bg-primary-transparent text-primary',
                    'status' => (string) ($zone['status'] ?? ''),
                    'status_label' => $this->cfZoneStatusLabel($zone['status'] ?? ''),
                    'expires_at' => null,
                    'registered_at' => $this->parseDate($zone['created_on'] ?? null),
                    'detail_url' => ! empty($zone['id'])
                        ? route('admin.cloudflare.zones.show', $zone['id'])
                        : null,
                    'extra' => $zone['plan']['name'] ?? null,
                ]);
            }

            $registrarMeta = $this->cloudflare->listRegistrarDomainsWithMeta($forceRefresh);
            $errors['cloudflare_registrar'] = $registrarMeta['error'];
            foreach ($registrarMeta['domains'] as $domain) {
                if (! is_array($domain)) {
                    continue;
                }
                $display = (string) ($domain['name'] ?? '');
                $name = strtolower(trim($display));
                if ($name === '') {
                    continue;
                }
                $expires = $domain['expires_at'] ?? $domain['payment_expires_at'] ?? null;
                $this->upsertRow($registry, $name, [
                    'display_name' => $display,
                    'source' => 'cf_registrar',
                    'source_label' => 'CF Registrar',
                    'source_badge' => 'bg-info-transparent text-info',
                    'status' => 'registrationActive',
                    'status_label' => 'مسجّل عند CF',
                    'expires_at' => $this->parseDate($expires),
                    'registered_at' => $this->parseDate($domain['registered_at'] ?? $domain['created_at'] ?? null),
                    'detail_url' => route('admin.cloudflare.registrar.index'),
                    'extra' => ! empty($domain['auto_renew']) ? 'تجديد تلقائي' : null,
                ]);
            }
        }

        if ($configured['namecom']) {
            $meta = $this->namecom->listAllDomainsWithMeta($forceRefresh);
            $errors['namecom'] = $meta['error'];
            foreach ($meta['domains'] as $domain) {
                if (! is_array($domain)) {
                    continue;
                }
                $display = NamecomDomainController::domainName($domain);
                $name = strtolower(trim($display));
                if ($name === '' || $name === '—') {
                    continue;
                }
                $status = NamecomDomainController::formatStatus($domain);
                $expiresRaw = $domain['expireDate'] ?? $domain['expires_at'] ?? null;
                $this->upsertRow($registry, $name, [
                    'display_name' => $display,
                    'source' => 'namecom',
                    'source_label' => 'name.com',
                    'source_badge' => 'bg-success-transparent text-success',
                    'status' => $status['is_active'] ? 'active' : 'expired',
                    'status_label' => $status['label'],
                    'expires_at' => $this->parseDate($expiresRaw),
                    'registered_at' => $this->parseDate($domain['createDate'] ?? $domain['registered_at'] ?? null),
                    'detail_url' => route('admin.namecom.domains.show', ['domain' => $display]),
                    'extra' => ! empty($domain['autorenewEnabled']) ? 'تجديد تلقائي' : null,
                ]);
            }
        }

        $this->mergeCoolifyDomains($registry);

        if ($forceRefresh && $configured['whm']) {
            app(\App\Services\Whm\WhmAccountService::class)->syncFromWhm();
        }

        $whmAccounts = WhmAccount::query()->orderBy('domain')->get();
        foreach ($whmAccounts as $account) {
            $display = (string) $account->domain;
            $name = strtolower(trim($display));
            if ($name === '') {
                continue;
            }
            $this->upsertRow($registry, $name, [
                'display_name' => $display,
                'source' => 'whm',
                'source_label' => 'WHM / cPanel',
                'source_badge' => 'bg-warning-transparent text-warning',
                'status' => strtolower((string) ($account->status ?? '')),
                'status_label' => $account->status_label,
                'expires_at' => null,
                'registered_at' => $account->created_at,
                'detail_url' => route('admin.whm.accounts.show', $account),
                'extra' => $account->username.($account->package ? ' · '.$account->package : ''),
            ]);
        }

        $rows = array_values($registry);
        foreach ($rows as &$row) {
            $row = $this->finalizeRow($row);
        }
        unset($row);

        usort($rows, fn ($a, $b) => strcmp($a['name'], $b['name']));

        $this->attachClientOwnership($rows);

        $stats = $this->computeStats($rows, $configured, $whmAccounts->count());

        return [
            'rows' => $rows,
            'stats' => $stats,
            'errors' => $errors,
            'configured' => $configured,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $registry
     * @param  array<string, mixed>  $entry
     */
    protected function upsertRow(array &$registry, string $name, array $entry): void
    {
        if (! isset($registry[$name])) {
            $registry[$name] = [
                'name' => $name,
                'display_name' => $entry['display_name'] ?? $name,
                'sources' => [],
                'expires_candidates' => [],
                'registered_candidates' => [],
            ];
        }

        if (! empty($entry['display_name'])) {
            $registry[$name]['display_name'] = $entry['display_name'];
        }

        $registry[$name]['sources'][$entry['source']] = [
            'key' => $entry['source'],
            'label' => $entry['source_label'],
            'badge' => $entry['source_badge'],
            'status' => $entry['status'] ?? '',
            'status_label' => $entry['status_label'] ?? '—',
            'expires_at' => $entry['expires_at'],
            'registered_at' => $entry['registered_at'],
            'detail_url' => $entry['detail_url'],
            'extra' => $entry['extra'] ?? null,
        ];

        if ($entry['expires_at'] instanceof Carbon) {
            $registry[$name]['expires_candidates'][] = $entry['expires_at'];
        }

        if ($entry['registered_at'] instanceof Carbon) {
            $registry[$name]['registered_candidates'][] = $entry['registered_at'];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function finalizeRow(array $row): array
    {
        $sources = array_values($row['sources'] ?? []);
        $expiresAt = $this->earliestDate($row['expires_candidates'] ?? []);
        $registeredAt = $this->earliestDate($row['registered_candidates'] ?? []);

        $aggregated = $this->aggregateStatus($sources, $expiresAt);

        $coolify = collect($sources)->firstWhere('key', 'coolify');

        return [
            'name' => $row['name'],
            'display_name' => $row['display_name'],
            'sources' => $sources,
            'source_keys' => array_column($sources, 'key'),
            'coolify_bound' => $coolify !== null,
            'coolify_url' => $coolify['detail_url'] ?? null,
            'coolify_label' => $coolify['extra'] ?? null,
            'expires_at' => $expiresAt,
            'expires_formatted' => $expiresAt ? $expiresAt->format('Y-m-d') : '—',
            'registered_at' => $registeredAt,
            'registered_formatted' => $registeredAt ? $registeredAt->format('Y-m-d') : '—',
            'expiring_soon' => $expiresAt
                && $expiresAt->isFuture()
                && $expiresAt->lte(now()->addDays(30)),
            'status' => $aggregated['status'],
            'status_label' => $aggregated['label'],
            'status_badge' => $aggregated['badge'],
            'primary_url' => $this->primaryDetailUrl($sources),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     * @return array{status: string, label: string, badge: string}
     */
    protected function aggregateStatus(array $sources, ?Carbon $expiresAt): array
    {
        if ($expiresAt && $expiresAt->isPast()) {
            return ['status' => 'expired', 'label' => 'منتهي', 'badge' => 'bg-danger-transparent text-danger'];
        }

        foreach ($sources as $src) {
            $st = strtolower((string) ($src['status'] ?? ''));
            if (in_array($st, ['pending', 'initializing'], true)) {
                return ['status' => 'pending', 'label' => 'قيد الانتظار', 'badge' => 'bg-warning-transparent text-warning'];
            }
        }

        if ($expiresAt && $expiresAt->isFuture() && $expiresAt->lte(now()->addDays(30))) {
            return ['status' => 'expiring', 'label' => 'ينتهي قريباً', 'badge' => 'bg-warning-transparent text-warning'];
        }

        return ['status' => 'active', 'label' => 'فعال', 'badge' => 'bg-success-transparent text-success'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     */
    protected function primaryDetailUrl(array $sources): ?string
    {
        $priority = ['namecom', 'cf_registrar', 'whm', 'cf_zone'];
        foreach ($priority as $key) {
            foreach ($sources as $src) {
                if (($src['key'] ?? '') === $key && ! empty($src['detail_url'])) {
                    return $src['detail_url'];
                }
            }
        }

        return $sources[0]['detail_url'] ?? null;
    }

    /**
     * @param  array<int, Carbon>  $dates
     */
    protected function earliestDate(array $dates): ?Carbon
    {
        $filtered = array_filter($dates, fn ($d) => $d instanceof Carbon);
        if ($filtered === []) {
            return null;
        }

        usort($filtered, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        return $filtered[0];
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $registry
     */
    protected function mergeCoolifyDomains(array &$registry): void
    {
        if (! $this->coolify->isConfigured() || ! $this->coolify->ping()) {
            return;
        }

        foreach ([
            ['list' => fn () => $this->coolify->listApplications(), 'type' => 'application'],
            ['list' => fn () => $this->coolify->listServices(), 'type' => 'service'],
        ] as $spec) {
            $response = $spec['list']();
            if (! ($response['success'] ?? false)) {
                continue;
            }
            foreach ($this->coolify->normalizeList($response['data'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach ($this->extractHostsFromResource($item) as $host) {
                    $name = strtolower($host);
                    $uuid = (string) ($item['uuid'] ?? '');
                    $detail = $spec['type'] === 'application'
                        ? route('admin.coolify.applications.show', $uuid)
                        : route('admin.coolify.services.show', $uuid);
                    $this->upsertRow($registry, $name, [
                        'display_name' => $host,
                        'source' => 'coolify',
                        'source_label' => 'Coolify',
                        'source_badge' => 'bg-secondary-transparent text-secondary',
                        'status' => (string) ($item['status'] ?? 'linked'),
                        'status_label' => $spec['type'] === 'application' ? 'تطبيق Coolify' : 'خدمة Coolify',
                        'expires_at' => null,
                        'registered_at' => null,
                        'detail_url' => $detail,
                        'extra' => ($item['name'] ?? $uuid).' ('.$spec['type'].')',
                    ]);
                }
            }
        }

        foreach ($this->coolify->normalizeList($this->coolify->listServers()['data'] ?? []) as $server) {
            if (! is_array($server)) {
                continue;
            }
            $serverUuid = (string) ($server['uuid'] ?? '');
            if ($serverUuid === '') {
                continue;
            }
            $domainsRes = $this->coolify->serverDomains($serverUuid);
            if (! ($domainsRes['success'] ?? false)) {
                continue;
            }
            foreach ($this->coolify->normalizeList($domainsRes['data'] ?? []) as $domain) {
                if (! is_array($domain)) {
                    continue;
                }
                $host = strtolower(trim((string) ($domain['name'] ?? $domain['domain'] ?? $domain['fqdn'] ?? '')));
                if ($host === '') {
                    continue;
                }
                $this->upsertRow($registry, $host, [
                    'display_name' => $host,
                    'source' => 'coolify',
                    'source_label' => 'Coolify DNS',
                    'source_badge' => 'bg-secondary-transparent text-secondary',
                    'status' => 'server_domain',
                    'status_label' => 'نطاق سيرفر',
                    'detail_url' => route('admin.coolify.servers.domains', $serverUuid),
                    'extra' => $server['name'] ?? $serverUuid,
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<int, string>
     */
    protected function extractHostsFromResource(array $item): array
    {
        $hosts = [];
        $candidates = [
            $item['fqdn'] ?? null,
            $item['domain'] ?? null,
        ];
        foreach ($item['domains'] ?? [] as $d) {
            if (is_string($d)) {
                $candidates[] = $d;
            } elseif (is_array($d)) {
                $candidates[] = $d['name'] ?? $d['domain'] ?? null;
            }
        }
        foreach ($item['urls'] ?? [] as $url) {
            if (is_string($url)) {
                $candidates[] = parse_url($url, PHP_URL_HOST);
            } elseif (is_array($url)) {
                $candidates[] = parse_url((string) ($url['url'] ?? ''), PHP_URL_HOST);
            }
        }
        if (! empty($item['public_url'])) {
            $candidates[] = parse_url((string) $item['public_url'], PHP_URL_HOST);
        }

        foreach ($candidates as $c) {
            $h = strtolower(trim((string) $c));
            if ($h !== '' && ! in_array($h, $hosts, true)) {
                $hosts[] = $h;
            }
        }

        return $hosts;
    }

    protected function cfZoneStatusLabel(mixed $status): string
    {
        return match (strtolower((string) $status)) {
            'active' => 'نشط (Zone)',
            'pending' => 'قيد الانتظار',
            'moved' => 'منقول',
            'deleted' => 'محذوف',
            default => (string) $status ?: '—',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, bool>  $configured
     * @return array<string, int|bool>
     */
    protected function computeStats(array $rows, array $configured, int $whmCount): array
    {
        $sourceCounts = [
            'cf_zone' => 0,
            'cf_registrar' => 0,
            'namecom' => 0,
            'whm' => 0,
            'coolify' => 0,
        ];

        $expiringSoon = 0;
        $multiSource = 0;

        foreach ($rows as $row) {
            if (count($row['sources'] ?? []) > 1) {
                $multiSource++;
            }
            if ($row['expiring_soon'] ?? false) {
                $expiringSoon++;
            }
            foreach ($row['source_keys'] ?? [] as $key) {
                if (isset($sourceCounts[$key])) {
                    $sourceCounts[$key]++;
                }
            }
        }

        return [
            'total_unique' => count($rows),
            'expiring_soon' => $expiringSoon,
            'multi_source' => $multiSource,
            'cf_zone' => $sourceCounts['cf_zone'],
            'cf_registrar' => $sourceCounts['cf_registrar'],
            'namecom' => $sourceCounts['namecom'],
            'whm' => $sourceCounts['whm'],
            'coolify' => $sourceCounts['coolify'],
            'whm_records' => $whmCount,
            'whm_configured' => $configured['whm'] ?? false,
            'cloudflare_configured' => $configured['cloudflare'],
            'namecom_configured' => $configured['namecom'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function attachClientOwnership(array &$rows): void
    {
        $domainOwners = ClientDomain::query()
            ->whereNotNull('user_id')
            ->pluck('user_id', 'domain_name')
            ->all();

        $whmOwners = [];
        foreach (WhmAccount::query()->whereNotNull('user_id')->get(['domain', 'user_id']) as $account) {
            $name = ClientDomain::normalizeName((string) $account->domain);
            if ($name !== '' && ! isset($domainOwners[$name])) {
                $whmOwners[$name] = $account->user_id;
            }
        }

        $userIds = collect($domainOwners)->merge($whmOwners)->unique()->filter()->values();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($rows as &$row) {
            $name = $row['name'] ?? '';
            $userId = $domainOwners[$name] ?? $whmOwners[$name] ?? null;
            $row['user_id'] = $userId;
            $row['client'] = $userId ? $users->get($userId) : null;
            $row['client_label'] = $row['client']
                ? trim($row['client']->name.' — '.$row['client']->email)
                : null;
        }
        unset($row);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function filterRows(array $rows, array $filters): array
    {
        $q = strtolower(trim((string) ($filters['q'] ?? '')));
        $source = (string) ($filters['source'] ?? 'all');
        $status = (string) ($filters['status'] ?? 'all');
        $userId = isset($filters['user_id']) && $filters['user_id'] !== '' && $filters['user_id'] !== null
            ? (int) $filters['user_id']
            : null;
        $sort = (string) ($filters['sort'] ?? 'name');
        $dir = strtolower((string) ($filters['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $filtered = array_values(array_filter($rows, function (array $row) use ($q, $source, $status, $userId) {
            if ($userId !== null && (int) ($row['user_id'] ?? 0) !== $userId) {
                return false;
            }

            if ($q !== '' && ! str_contains($row['name'], $q) && ! str_contains(strtolower($row['display_name']), $q)) {
                return false;
            }

            if ($source !== 'all' && ! in_array($source, $row['source_keys'] ?? [], true)) {
                return false;
            }

            if ($status !== 'all' && ($row['status'] ?? '') !== $status) {
                return false;
            }

            return true;
        }));

        usort($filtered, function (array $a, array $b) use ($sort, $dir): int {
            if ($sort === 'expires') {
                $tsA = $a['expires_at']?->timestamp;
                $tsB = $b['expires_at']?->timestamp;
                if ($tsA === null && $tsB === null) {
                    $cmp = 0;
                } elseif ($tsA === null) {
                    $cmp = 1;
                } elseif ($tsB === null) {
                    $cmp = -1;
                } else {
                    $cmp = $tsA <=> $tsB;
                }
            } else {
                $cmp = strcmp($a['name'], $b['name']);
            }

            return $dir === 'desc' ? -$cmp : $cmp;
        });

        return $filtered;
    }
}
