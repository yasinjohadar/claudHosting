<?php

namespace App\Services\Whm;

use App\Models\WhmAccount;
use Illuminate\Support\Facades\Cache;

class WhmServerStatusService
{
    public function __construct(
        protected WhmApiService $api,
        protected WhmSettingsService $settings
    ) {}

    /**
     * @return array{
     *   success: bool,
     *   message?: string,
     *   fetched_at?: string,
     *   system?: array<int, array<string, mixed>>,
     *   disks?: array<int, array<string, mixed>>
     * }
     */
    public function getStatus(?string $cpanelProxyUser = null, bool $fresh = false): array
    {
        if (! $this->api->isConfigured()) {
            return ['success' => false, 'message' => 'إعدادات WHM غير مكتملة'];
        }

        $proxyUser = $this->resolveProxyUser($cpanelProxyUser);
        if ($proxyUser === '') {
            return ['success' => false, 'message' => 'لا يوجد حساب cPanel نشط للاستعلام — زامن الحسابات من WHM أولاً'];
        }

        $cacheKey = 'whm_server_status_'.md5($this->settings->getConnectionConfig()['host'] ?? 'default').'_'.$proxyUser;
        $ttl = max(15, (int) config('whm.server_status_cache_seconds', 60));

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $ttl, fn () => $this->fetchStatus($proxyUser));
    }

    public function resolveProxyUser(?string $explicit = null): string
    {
        $explicit = trim((string) $explicit);
        if ($explicit !== '') {
            return $explicit;
        }

        $accountUser = WhmAccount::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->value('username');

        if (is_string($accountUser) && $accountUser !== '') {
            return $accountUser;
        }

        return trim((string) ($this->settings->getConnectionConfig()['username'] ?? ''));
    }

    /**
     * @return array{
     *   success: bool,
     *   message?: string,
     *   fetched_at?: string,
     *   system?: array<int, array<string, mixed>>,
     *   disks?: array<int, array<string, mixed>>
     * }
     */
    protected function fetchStatus(string $proxyUser): array
    {
        $loadResult = $this->api->systemLoadAvg();
        $diskResult = $this->api->getDiskUsage();
        $infoResult = $this->fetchServerInformation($proxyUser);

        $errors = [];
        if (! ($loadResult['success'] ?? false)) {
            $errors[] = $loadResult['message'] ?? 'حمل السيرفر';
        }
        if (! ($diskResult['success'] ?? false)) {
            $errors[] = $diskResult['message'] ?? 'أقراص السيرفر';
        }
        if (! ($infoResult['success'] ?? false)) {
            $errors[] = $infoResult['message'] ?? 'الذاكرة والمعالج (ServerInformation)';
        }

        if ($errors !== [] && ! ($diskResult['success'] ?? false) && ! ($loadResult['success'] ?? false)) {
            return ['success' => false, 'message' => implode(' — ', $errors)];
        }

        $metrics = $this->parseServerInformation($infoResult['items'] ?? []);
        $cpuCount = (int) ($metrics['cpu_count'] ?? 0);
        $load = $loadResult['load'] ?? [];
        $loadOne = (float) ($load['one'] ?? $metrics['load'] ?? 0);
        $loadFive = (float) ($load['five'] ?? 0);
        $loadFifteen = (float) ($load['fifteen'] ?? 0);

        $system = [];

        if ($cpuCount > 0) {
            $system[] = [
                'key' => 'cpu',
                'label' => 'المعالج',
                'value' => $cpuCount.' CPU',
                'detail' => 'عدد الأنوية المتاحة',
                'percent' => 0,
                'status' => 'success',
                'icon' => 'fe-cpu',
                'hide_bar' => true,
            ];
        }

        $loadPercent = $cpuCount > 0
            ? min(100, round(($loadOne / $cpuCount) * 100, 1))
            : min(100, round($loadOne * 10, 1));

        $loadDetail = [];
        if ($loadOne > 0 || $loadFive > 0 || $loadFifteen > 0) {
            $loadDetail[] = '1 د: '.number_format($loadOne, 2);
            $loadDetail[] = '5 د: '.number_format($loadFive, 2);
            $loadDetail[] = '15 د: '.number_format($loadFifteen, 2);
        }
        if ($cpuCount > 0) {
            $loadDetail[] = "{$cpuCount} CPUs";
        }

        $system[] = [
            'key' => 'load',
            'label' => 'حمل السيرفر',
            'value' => number_format($loadOne, 2, '.', ''),
            'detail' => $loadDetail !== [] ? implode(' · ', $loadDetail) : null,
            'percent' => $loadPercent,
            'status' => $this->usageStatus($loadPercent),
            'icon' => 'fe-activity',
        ];

        if (isset($metrics['memory_percent'])) {
            $system[] = [
                'key' => 'memory',
                'label' => 'الذاكرة (RAM)',
                'value' => $metrics['memory_percent'].'%',
                'detail' => $metrics['memory_detail'] ?? null,
                'percent' => (float) $metrics['memory_percent'],
                'status' => $this->usageStatus((float) $metrics['memory_percent']),
                'icon' => 'fe-hard-drive',
            ];
        }

        if (isset($metrics['swap_percent'])) {
            $system[] = [
                'key' => 'swap',
                'label' => 'Swap',
                'value' => $metrics['swap_percent'].'%',
                'detail' => $metrics['swap_detail'] ?? null,
                'percent' => (float) $metrics['swap_percent'],
                'status' => $this->usageStatus((float) $metrics['swap_percent']),
                'icon' => 'fe-layers',
            ];
        }

        $disks = $this->formatDiskPartitions($diskResult['partitions'] ?? [], $metrics['disk_percentages'] ?? []);

        return [
            'success' => true,
            'fetched_at' => now()->format('Y-m-d H:i:s'),
            'system' => $system,
            'disks' => $disks,
            'warnings' => $errors !== [] ? $errors : null,
            'info_proxy_user' => $infoResult['proxy_user'] ?? $proxyUser,
        ];
    }

    /**
     * @return array{success: bool, message?: string, items?: array<int, array<string, mixed>>, proxy_user?: string}
     */
    protected function fetchServerInformation(string $preferredUser): array
    {
        $candidates = array_values(array_unique(array_filter([
            $preferredUser,
            ...WhmAccount::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->limit(5)
                ->pluck('username')
                ->all(),
            trim((string) ($this->settings->getConnectionConfig()['username'] ?? '')),
        ])));

        $lastMessage = 'فشل جلب معلومات السيرفر';

        foreach ($candidates as $user) {
            $user = trim((string) $user);
            if ($user === '') {
                continue;
            }

            $result = $this->api->serverInformation($user);
            if ($result['success'] ?? false) {
                return [
                    'success' => true,
                    'items' => $result['items'] ?? [],
                    'proxy_user' => $user,
                ];
            }

            $lastMessage = $result['message'] ?? $lastMessage;
        }

        return ['success' => false, 'message' => $lastMessage];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    protected function parseServerInformation(array $items): array
    {
        $out = [
            'load' => null,
            'cpu_count' => null,
            'memory_percent' => null,
            'memory_detail' => null,
            'swap_percent' => null,
            'swap_detail' => null,
            'disk_percentages' => [],
        ];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));
            $type = (string) ($item['type'] ?? '');

            $nameLower = strtolower($name);

            if ($name === 'Server Load' || str_contains($nameLower, 'server load')) {
                $out['load'] = (float) preg_replace('/[^0-9.]/', '', $value);
            } elseif ($name === 'CPU Count' || str_contains($nameLower, 'cpu count')) {
                $out['cpu_count'] = (int) preg_replace('/\D/', '', $value);
            } elseif ($name === 'Memory Used' || str_contains($nameLower, 'memory')) {
                $parsed = $this->parsePercentValue($value);
                $out['memory_percent'] = $parsed['percent'];
                $out['memory_detail'] = $parsed['detail'] ?? $value;
            } elseif ($name === 'Swap' || $nameLower === 'swap') {
                $parsed = $this->parsePercentValue($value);
                $out['swap_percent'] = $parsed['percent'];
                $out['swap_detail'] = $parsed['detail'];
            } elseif (str_starts_with($name, 'Disk ') && $type === 'device') {
                $mount = $this->extractMountFromDiskName($name);
                $parsed = $this->parsePercentValue($value);
                if ($mount !== '') {
                    $out['disk_percentages'][$mount] = $parsed['percent'];
                }
            }
        }

        return $out;
    }

    /**
     * @return array{percent: ?float, detail: ?string}
     */
    protected function parsePercentValue(string $value): array
    {
        $percent = null;
        $detail = null;

        if (preg_match('/([\d.]+)\s*%/', $value, $m)) {
            $percent = round((float) $m[1], 2);
        } elseif (preg_match('/^([\d.]+)$/', trim($value), $m)) {
            $percent = round((float) $m[1], 2);
        }

        if (preg_match('/\(([^)]+)\)/', $value, $m)) {
            $detail = trim($m[1]);
        } elseif ($percent === null && $value !== '') {
            $detail = $value;
        }

        if ($detail !== null && $detail !== '') {
            $detail = preg_replace('/\s*of\s*/i', ' / ', $detail);
        }

        return ['percent' => $percent, 'detail' => $detail];
    }

    protected function extractMountFromDiskName(string $name): string
    {
        if (preg_match('/\(([^)]+)\)\s*$/', $name, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $partitions
     * @param  array<string, float|null>  $fallbackPercents
     * @return array<int, array<string, mixed>>
     */
    protected function formatDiskPartitions(array $partitions, array $fallbackPercents): array
    {
        $disks = [];

        foreach ($partitions as $part) {
            if (! is_array($part)) {
                continue;
            }
            $mount = (string) ($part['mount'] ?? $part['filesystem'] ?? '');
            $device = (string) ($part['device'] ?? $part['disk'] ?? '—');
            $percent = (int) ($part['percentage'] ?? 0);
            if ($percent === 0 && isset($fallbackPercents[$mount])) {
                $percent = (int) round((float) $fallbackPercents[$mount]);
            }

            $usedKb = (int) ($part['used'] ?? 0);
            $totalKb = (int) ($part['total'] ?? 0);
            $detail = $totalKb > 0
                ? number_format($usedKb).' / '.number_format($totalKb).' KB'
                : null;

            $disks[] = [
                'device' => $device,
                'mount' => $mount !== '' ? $mount : $device,
                'percent' => $percent,
                'detail' => $detail,
                'status' => $this->usageStatus((float) $percent),
            ];
        }

        usort($disks, fn ($a, $b) => ($b['percent'] ?? 0) <=> ($a['percent'] ?? 0));

        return $disks;
    }

    protected function usageStatus(float $percent): string
    {
        if ($percent >= 90) {
            return 'danger';
        }
        if ($percent >= 75) {
            return 'warning';
        }

        return 'success';
    }
}
