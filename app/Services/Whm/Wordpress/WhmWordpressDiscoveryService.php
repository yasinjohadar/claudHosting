<?php

namespace App\Services\Whm\Wordpress;

use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\Wordpress\Adapters\ManualScanAdapter;
use App\Services\Whm\Wordpress\Adapters\SoftaculousAdapter;
use App\Services\Whm\Wordpress\Adapters\WpToolkitAdapter;
use App\Services\Whm\Wordpress\Contracts\WordpressDiscoveryAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WhmWordpressDiscoveryService
{
    /** @var list<WordpressDiscoveryAdapter> */
    protected array $adapters;

    public function __construct(
        SoftaculousAdapter $softaculous,
        WpToolkitAdapter $wpToolkit,
        ManualScanAdapter $manual
    ) {
        $this->adapters = [$softaculous, $wpToolkit, $manual];
    }

    /**
     * @return array{
     *   success: bool,
     *   message: string,
     *   sites: Collection<int, WhmWordpressSite>,
     *   warnings: list<string>,
     *   sources: array<string, array{success: bool, available?: bool, message?: string, count: int}>
     * }
     */
    public function discover(WhmAccount $account, bool $force = true): array
    {
        $warnings = [];
        $sources = [];
        $seenExternal = [];
        $seenPaths = [];
        $now = now();
        $activeIds = [];

        foreach ($this->adapters as $adapter) {
            $source = $adapter->source();
            try {
                $result = $adapter->discover($account);
            } catch (\Throwable $e) {
                Log::error('WP discovery adapter failed', [
                    'source' => $source,
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
                $result = [
                    'success' => false,
                    'available' => false,
                    'message' => $e->getMessage(),
                    'sites' => [],
                ];
            }

            $sites = $result['sites'] ?? [];
            $count = 0;

            foreach ($sites as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $externalId = trim((string) ($row['external_id'] ?? ''));
                if ($externalId === '') {
                    continue;
                }

                $path = isset($row['path']) ? rtrim((string) $row['path'], '/') : null;

                // Prefer Softaculous / WP Toolkit over manual for the same path
                if ($source === WhmWordpressSite::SOURCE_MANUAL && $path && isset($seenPaths[$path])) {
                    continue;
                }

                $key = $source.'|'.$externalId;
                if (isset($seenExternal[$key])) {
                    continue;
                }
                $seenExternal[$key] = true;
                if ($path) {
                    $seenPaths[$path] = $source;
                }

                $site = WhmWordpressSite::query()->updateOrCreate(
                    [
                        'whm_account_id' => $account->id,
                        'source' => $source,
                        'external_id' => $externalId,
                    ],
                    [
                        'domain' => $row['domain'] ?? null,
                        'path' => $path,
                        'url' => $row['url'] ?? null,
                        'wp_version' => $row['wp_version'] ?? null,
                        'title' => $row['title'] ?? null,
                        'status' => WhmWordpressSite::STATUS_ACTIVE,
                        'metadata' => $row['metadata'] ?? [],
                        'last_seen_at' => $now,
                    ]
                );

                $activeIds[] = $site->id;
                $count++;
            }

            $sources[$source] = [
                'success' => (bool) ($result['success'] ?? false),
                'available' => (bool) ($result['available'] ?? false),
                'message' => $result['message'] ?? null,
                'count' => $count,
            ];

            if (! ($result['success'] ?? false) && ! empty($result['message'])) {
                $warnings[] = ($this->sourceLabel($source)).': '.$result['message'];
            }
            foreach ($result['warnings'] ?? [] as $w) {
                if (is_string($w) && $w !== '') {
                    $warnings[] = $w;
                }
            }
        }

        // Mark previously active sites not seen in this scan as missing
        WhmWordpressSite::query()
            ->where('whm_account_id', $account->id)
            ->where('status', WhmWordpressSite::STATUS_ACTIVE)
            ->when($activeIds !== [], fn ($q) => $q->whereNotIn('id', $activeIds))
            ->when($activeIds === [], fn ($q) => $q)
            ->update(['status' => WhmWordpressSite::STATUS_MISSING]);

        $sites = WhmWordpressSite::query()
            ->where('whm_account_id', $account->id)
            ->where('status', '!=', WhmWordpressSite::STATUS_MISSING)
            ->orderBy('domain')
            ->get();

        $total = $sites->count();

        return [
            'success' => true,
            'message' => $total > 0
                ? "تم اكتشاف {$total} موقع WordPress"
                : 'لم يُعثر على مواقع WordPress — جرّب البحث مجدداً أو تحقق من Softaculous',
            'sites' => $sites,
            'warnings' => array_values(array_unique($warnings)),
            'sources' => $sources,
        ];
    }

    /**
     * @return Collection<int, WhmWordpressSite>
     */
    public function sitesForAccount(WhmAccount $account, bool $includeMissing = false): Collection
    {
        $q = WhmWordpressSite::query()->where('whm_account_id', $account->id);
        if (! $includeMissing) {
            $q->where('status', '!=', WhmWordpressSite::STATUS_MISSING);
        }

        return $q->orderByDesc('last_seen_at')->orderBy('domain')->get();
    }

    /**
     * @return Collection<int, WhmWordpressSite>
     */
    public function sitesForUser(int $userId): Collection
    {
        return WhmWordpressSite::query()
            ->whereHas('account', function ($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', '!=', 'terminated');
            })
            ->where('status', '!=', WhmWordpressSite::STATUS_MISSING)
            ->with('account')
            ->orderByDesc('last_seen_at')
            ->get();
    }

    public function needsRefresh(WhmAccount $account, int $hours = 6): bool
    {
        $latest = WhmWordpressSite::query()
            ->where('whm_account_id', $account->id)
            ->max('last_seen_at');

        if ($latest === null) {
            return true;
        }

        return now()->subHours($hours)->greaterThan($latest);
    }

    protected function sourceLabel(string $source): string
    {
        return WhmWordpressSite::SOURCES[$source] ?? $source;
    }
}
