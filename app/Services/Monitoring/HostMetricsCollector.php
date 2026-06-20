<?php

namespace App\Services\Monitoring;

use App\Services\Coolify\CoolifySshExecutor;

class HostMetricsCollector
{
    public function __construct(protected CoolifySshExecutor $ssh) {}

    /**
     * @return array<string, mixed>
     */
    public function collectHostMetrics(string $host, int $port = 22): array
    {
        $script = <<<'SH'
free -b 2>/dev/null | awk '/^Mem:/ {t=$2; u=$3; print "MEM_TOTAL=" t; print "MEM_USED=" u}'
free -b 2>/dev/null | awk '/^Swap:/ {t=$2; u=$3; print "SWAP_TOTAL=" t; print "SWAP_USED=" u}'
awk '{print "LOAD_1=" $1; print "LOAD_5=" $2; print "LOAD_15=" $3}' /proc/loadavg 2>/dev/null
awk '{print "UPTIME_SEC=" int($1)}' /proc/uptime 2>/dev/null
grep '^cpu ' /proc/stat 2>/dev/null | awk '{idle=$5+$6; total=0; for(i=2;i<=NF;i++) total+=$i; print "CPU_IDLE=" idle; print "CPU_TOTAL=" total}'
sleep 1
grep '^cpu ' /proc/stat 2>/dev/null | awk '{idle=$5+$6; total=0; for(i=2;i<=NF;i++) total+=$i; print "CPU_IDLE2=" idle; print "CPU_TOTAL2=" total}'
awk 'NR>2 && $1 !~ /^lo:/ {gsub(/:/,"",$1); rx+=$2; tx+=$10} END {print "NET_RX=" int(rx); print "NET_TX=" int(tx)}' /proc/net/dev 2>/dev/null
sleep 1
awk 'NR>2 && $1 !~ /^lo:/ {gsub(/:/,"",$1); rx+=$2; tx+=$10} END {print "NET_RX2=" int(rx); print "NET_TX2=" int(tx)}' /proc/net/dev 2>/dev/null
df -B1 --output=source,size,used,avail,pcent,target 2>/dev/null | tail -n +2
SH;

        $result = $this->ssh->run($host, $script, 45, $port);
        if (! ($result['success'] ?? false)) {
            return [
                'success' => false,
                'message' => 'فشل SSH: '.trim($result['output'] ?? ''),
            ];
        }

        return $this->parseHostOutput($result['output'] ?? '');
    }

    /**
     * @return array{containers: array<int, array<string, mixed>>}
     */
    public function collectContainerMetrics(string $host, int $port = 22): array
    {
        $cmd = 'docker stats --no-stream --format "{{json .}}" 2>/dev/null';
        $result = $this->ssh->run($host, $cmd, 60, $port);
        if (! ($result['success'] ?? false)) {
            return ['containers' => []];
        }

        $containers = [];
        foreach (preg_split('/\r\n|\r|\n/', $result['output'] ?? '') ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = json_decode($line, true);
            if (! is_array($row)) {
                continue;
            }
            $containers[] = [
                'name' => $row['Name'] ?? $row['name'] ?? '',
                'id' => $row['ID'] ?? $row['id'] ?? '',
                'cpu_percent' => $this->parsePercent($row['CPUPerc'] ?? $row['cpu'] ?? '0'),
                'mem_percent' => $this->parsePercent($row['MemPerc'] ?? $row['mem'] ?? '0'),
                'mem_usage' => $row['MemUsage'] ?? '',
                'net_io' => $row['NetIO'] ?? '',
                'block_io' => $row['BlockIO'] ?? '',
            ];
        }

        usort($containers, fn ($a, $b) => ($b['cpu_percent'] ?? 0) <=> ($a['cpu_percent'] ?? 0));

        return ['containers' => $containers];
    }

    /**
     * @return array<string, mixed>
     */
    public function parseHostOutput(string $output): array
    {
        $memTotal = 0;
        $memUsed = 0;
        $swapTotal = 0;
        $swapUsed = 0;
        $load1 = $load5 = $load15 = 0.0;
        $uptimeSec = 0;
        $cpuIdle1 = $cpuTotal1 = $cpuIdle2 = $cpuTotal2 = 0;
        $netRx1 = $netTx1 = $netRx2 = $netTx2 = 0;
        $disks = [];

        foreach (preg_split('/\r\n|\r|\n/', $output) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'MEM_TOTAL=')) {
                $memTotal = (int) substr($line, 10);
            } elseif (str_starts_with($line, 'MEM_USED=')) {
                $memUsed = (int) substr($line, 9);
            } elseif (str_starts_with($line, 'SWAP_TOTAL=')) {
                $swapTotal = (int) substr($line, 11);
            } elseif (str_starts_with($line, 'SWAP_USED=')) {
                $swapUsed = (int) substr($line, 10);
            } elseif (str_starts_with($line, 'LOAD_1=')) {
                $load1 = (float) substr($line, 7);
            } elseif (str_starts_with($line, 'LOAD_5=')) {
                $load5 = (float) substr($line, 7);
            } elseif (str_starts_with($line, 'LOAD_15=')) {
                $load15 = (float) substr($line, 8);
            } elseif (str_starts_with($line, 'UPTIME_SEC=')) {
                $uptimeSec = (int) substr($line, 11);
            } elseif (str_starts_with($line, 'CPU_IDLE=')) {
                $cpuIdle1 = (float) substr($line, 9);
            } elseif (str_starts_with($line, 'CPU_TOTAL=')) {
                $cpuTotal1 = (float) substr($line, 10);
            } elseif (str_starts_with($line, 'CPU_IDLE2=')) {
                $cpuIdle2 = (float) substr($line, 10);
            } elseif (str_starts_with($line, 'CPU_TOTAL2=')) {
                $cpuTotal2 = (float) substr($line, 11);
            } elseif (str_starts_with($line, 'NET_RX=')) {
                $netRx1 = (int) substr($line, 7);
            } elseif (str_starts_with($line, 'NET_TX=')) {
                $netTx1 = (int) substr($line, 7);
            } elseif (str_starts_with($line, 'NET_RX2=')) {
                $netRx2 = (int) substr($line, 8);
            } elseif (str_starts_with($line, 'NET_TX2=')) {
                $netTx2 = (int) substr($line, 8);
            } elseif ($line !== '' && ! str_starts_with($line, 'CPU_') && ! str_starts_with($line, 'MEM_')
                && ! str_starts_with($line, 'NET_') && ! str_starts_with($line, 'LOAD_')
                && ! str_starts_with($line, 'UPTIME_') && ! str_starts_with($line, 'SWAP_')) {
                $parts = preg_split('/\s+/', $line, 6);
                if (count($parts) >= 5) {
                    $disks[] = [
                        'source' => $parts[0],
                        'size_bytes' => (int) $parts[1],
                        'used_bytes' => (int) $parts[2],
                        'avail_bytes' => (int) $parts[3],
                        'percent' => $this->parsePercent($parts[4]),
                        'mount' => $parts[5] ?? '',
                    ];
                }
            }
        }

        $cpuPercent = $this->calcCpuPercent($cpuIdle1, $cpuTotal1, $cpuIdle2, $cpuTotal2);
        $ramPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 1) : 0;
        $swapPercent = $swapTotal > 0 ? round(($swapUsed / $swapTotal) * 100, 1) : 0;
        $netRxBps = max(0, $netRx2 - $netRx1);
        $netTxBps = max(0, $netTx2 - $netTx1);

        $rootDisk = collect($disks)->first(fn ($d) => ($d['mount'] ?? '') === '/')
            ?? ($disks[0] ?? null);

        return [
            'success' => true,
            'server' => [
                'cpu_percent' => $cpuPercent,
                'ram_percent' => $ramPercent,
                'ram_used_bytes' => $memUsed,
                'ram_total_bytes' => $memTotal,
                'swap_percent' => $swapPercent,
                'swap_used_bytes' => $swapUsed,
                'swap_total_bytes' => $swapTotal,
                'load_1' => round($load1, 2),
                'load_5' => round($load5, 2),
                'load_15' => round($load15, 2),
                'uptime_seconds' => $uptimeSec,
                'net_rx_bps' => $netRxBps,
                'net_tx_bps' => $netTxBps,
                'disk_percent' => $rootDisk['percent'] ?? 0,
                'disk_used_bytes' => $rootDisk['used_bytes'] ?? 0,
                'disk_total_bytes' => $rootDisk['size_bytes'] ?? 0,
                'disks' => $disks,
            ],
        ];
    }

    public function calcCpuPercent(float $idle1, float $total1, float $idle2, float $total2): float
    {
        $idleDelta = $idle2 - $idle1;
        $totalDelta = $total2 - $total1;
        if ($totalDelta <= 0) {
            return 0;
        }

        return round(max(0, min(100, (1 - ($idleDelta / $totalDelta)) * 100)), 1);
    }

    public function parsePercent(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 1);
        }

        return round((float) preg_replace('/[^0-9.]/', '', (string) $value), 1);
    }
}
