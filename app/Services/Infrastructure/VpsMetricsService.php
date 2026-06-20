<?php

namespace App\Services\Infrastructure;

use App\Models\VpsMetricSnapshot;
use App\Models\VpsServer;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Monitoring\HostMetricsCollector;
use Illuminate\Support\Facades\Cache;

class VpsMetricsService
{
    public function __construct(
        protected HostMetricsCollector $collector,
        protected CoolifySettingsService $coolifySettings
    ) {}

    public function refreshSeconds(): int
    {
        return max(5, min(60, (int) config('infrastructure.metrics_refresh_seconds', 10)));
    }

    public function cacheTtl(): int
    {
        return max(3, min(30, (int) config('infrastructure.metrics_cache_seconds', 8)));
    }

    /**
     * @return array{success: bool, host?: string, port?: int, message?: string}
     */
    public function resolveEndpoint(VpsServer $server): array
    {
        $host = trim((string) $server->ip);
        if ($host === '') {
            return [
                'success' => false,
                'message' => 'لا يوجد IP للسيرفر. نفّذ المزامنة أو أضف العنوان من صفحة التعديل.',
            ];
        }

        $ssh = $this->coolifySettings->getSshConfig();
        if (! ($ssh['ssh_key_configured'] ?? false)) {
            return [
                'success' => false,
                'message' => 'إعدادات SSH غير مكتملة. اضبط المفتاح والمستخدم من إعدادات Coolify → SSH.',
                'settings_url' => route('admin.coolify.settings.section', 'ssh'),
            ];
        }

        return [
            'success' => true,
            'host' => $host,
            'port' => (int) ($ssh['ssh_port'] ?? $this->coolifySettings->getSshPort()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLiveMetrics(VpsServer $server, bool $refresh = false): array
    {
        if (! $server->isRunning()) {
            return [
                'success' => false,
                'stopped' => true,
                'message' => 'السيرفر متوقف — المقاييس اللحظية غير متاحة.',
                'server' => $this->emptyServerMetrics(),
                'containers' => [],
                'fetched_at' => now()->toIso8601String(),
            ];
        }

        $endpoint = $this->resolveEndpoint($server);
        if (! ($endpoint['success'] ?? false)) {
            return array_merge($this->failure($endpoint['message'] ?? 'تعذّر الاتصال'), [
                'settings_url' => $endpoint['settings_url'] ?? route('admin.coolify.settings.index', ['tab' => 'ssh']),
            ]);
        }

        $host = (string) $endpoint['host'];
        $port = (int) $endpoint['port'];
        $cacheKey = 'vps_metrics_live_'.$server->uuid;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($server, $host, $port) {
            $hostMetrics = $this->collector->collectHostMetrics($host, $port);
            if (! ($hostMetrics['success'] ?? false)) {
                return array_merge($this->failure(
                    'فشل SSH: '.($hostMetrics['message'] ?? 'تحقق من المفتاح في إعدادات Coolify وفتح المنفذ 22 من IP لوحة التحكم.')
                ), [
                    'settings_url' => route('admin.coolify.settings.section', 'ssh'),
                ]);
            }

            $containers = $this->collector->collectContainerMetrics($host, $port);

            return [
                'success' => true,
                'scope' => 'vps',
                'vps_uuid' => $server->uuid,
                'host' => $host,
                'server' => $hostMetrics['server'] ?? [],
                'containers' => $containers['containers'] ?? [],
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getHistory(VpsServer $server, string $range = '24h'): array
    {
        $range = in_array($range, ['24h', '7d'], true) ? $range : '24h';
        $since = $range === '7d' ? now()->subDays(7) : now()->subHours(24);

        $rows = VpsMetricSnapshot::query()
            ->where('vps_server_id', $server->id)
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at')
            ->get(['recorded_at', 'cpu_percent', 'ram_percent', 'disk_percent', 'load_1', 'net_rx_bps', 'net_tx_bps']);

        return [
            'success' => true,
            'range' => $range,
            'points' => $rows->map(fn (VpsMetricSnapshot $s) => [
                't' => $s->recorded_at?->toIso8601String(),
                'cpu' => (float) $s->cpu_percent,
                'ram' => (float) $s->ram_percent,
                'disk' => (float) $s->disk_percent,
                'load_1' => $s->load_1 !== null ? (float) $s->load_1 : null,
                'net_rx_bps' => $s->net_rx_bps !== null ? (float) $s->net_rx_bps : null,
                'net_tx_bps' => $s->net_tx_bps !== null ? (float) $s->net_tx_bps : null,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function recordSnapshot(VpsServer $server): array
    {
        if (! $server->isRunning() || trim((string) $server->ip) === '') {
            return ['success' => false, 'message' => 'تخطي: السيرفر غير متاح'];
        }

        $live = $this->getLiveMetrics($server, true);
        if (! ($live['success'] ?? false)) {
            return ['success' => false, 'message' => $live['message'] ?? 'فشل'];
        }

        $srv = $live['server'] ?? [];
        $containers = $live['containers'] ?? [];

        VpsMetricSnapshot::query()->create([
            'vps_server_id' => $server->id,
            'cpu_percent' => $srv['cpu_percent'] ?? 0,
            'ram_percent' => $srv['ram_percent'] ?? 0,
            'disk_percent' => $srv['disk_percent'] ?? 0,
            'load_1' => $srv['load_1'] ?? null,
            'net_rx_bps' => $srv['net_rx_bps'] ?? null,
            'net_tx_bps' => $srv['net_tx_bps'] ?? null,
            'containers_count' => count($containers),
            'payload' => [
                'swap_percent' => $srv['swap_percent'] ?? null,
                'uptime_seconds' => $srv['uptime_seconds'] ?? null,
                'disks' => $srv['disks'] ?? [],
            ],
            'recorded_at' => now(),
        ]);

        return ['success' => true];
    }

    public function pruneOldSnapshots(): int
    {
        $days = max(1, (int) config('infrastructure.metrics_retention_days', 7));

        return VpsMetricSnapshot::query()
            ->where('recorded_at', '<', now()->subDays($days))
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    protected function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'server' => $this->emptyServerMetrics(),
            'containers' => [],
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyServerMetrics(): array
    {
        return [
            'cpu_percent' => 0,
            'ram_percent' => 0,
            'disk_percent' => 0,
        ];
    }
}
