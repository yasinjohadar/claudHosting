<?php

namespace App\Services\Infrastructure\Netcup\Concerns;

use App\Services\Infrastructure\InfrastructureSettingsService;

trait NetcupScpHelpers
{
    protected function parseServerId(string $externalId): string
    {
        return str_starts_with($externalId, 'scp:')
            ? substr($externalId, 4)
            : $externalId;
    }

    protected function extractTaskUuid(mixed $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        foreach (['taskUuid', 'task_uuid', 'taskId', 'uuid'] as $key) {
            if (! empty($body[$key]) && is_string($body[$key])) {
                return $body[$key];
            }
        }

        if (isset($body['task']) && is_array($body['task'])) {
            return $this->extractTaskUuid($body['task']);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $clientRes
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    protected function wrapResponse(array $clientRes, ?string $successMessage = null): array
    {
        if (! ($clientRes['success'] ?? false)) {
            return [
                'success' => false,
                'message' => (string) ($clientRes['message'] ?? 'فشل'),
                'data' => $clientRes['body'] ?? null,
                'task_uuid' => null,
            ];
        }

        $body = $clientRes['body'] ?? null;
        $taskUuid = $clientRes['task_uuid'] ?? $this->extractTaskUuid($body);

        return [
            'success' => true,
            'message' => $successMessage ?? 'OK',
            'data' => $body,
            'task_uuid' => $taskUuid,
        ];
    }

    protected function resolveUserId(InfrastructureSettingsService $settings): ?string
    {
        $id = trim((string) ($settings->getCredentials()['netcup_scp_user_id'] ?? ''));

        return $id !== '' ? $id : null;
    }

    protected function normalizeStatus(string $raw): string
    {
        $s = strtolower(trim($raw));

        return match (true) {
            in_array($s, ['running', 'on', 'active', 'poweron', 'started', 'run'], true) => 'running',
            in_array($s, ['stopped', 'off', 'inactive', 'poweroff', 'shutoff', 'shutdown', 'shut_off'], true) => 'stopped',
            str_contains($s, 'start') => 'starting',
            str_contains($s, 'stop') => 'stopping',
            str_contains($s, 'reboot'), str_contains($s, 'cycle') => 'rebooting',
            default => $s !== '' ? $s : 'unknown',
        };
    }

    protected function resolveServerName(array $row, string $id): string
    {
        foreach (['nickname', 'hostname', 'name'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'Netcup '.$id;
    }

    protected function resolveRegion(array $row): string
    {
        $site = $row['site'] ?? null;
        if (is_array($site)) {
            foreach (['name', 'key', 'location', 'datacenter'] as $key) {
                $value = trim((string) ($site[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return trim((string) ($row['location'] ?? $row['datacenter'] ?? ''));
    }

    protected function extractPrimaryIp(array $row): string
    {
        $ip = trim((string) ($row['ip'] ?? $row['primaryIp'] ?? ''));
        if ($ip !== '') {
            return $ip;
        }

        foreach ($row['ipv4Addresses'] ?? [] as $address) {
            if (is_string($address) && $address !== '') {
                return $address;
            }
            if (is_array($address)) {
                $candidate = trim((string) ($address['ip'] ?? $address['address'] ?? $address['ipv4'] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        $live = is_array($row['serverLiveInfo'] ?? null) ? $row['serverLiveInfo'] : [];
        foreach ($live['interfaces'] ?? [] as $interface) {
            if (! is_array($interface)) {
                continue;
            }
            foreach ($interface['ipv4Addresses'] ?? [] as $address) {
                if (is_string($address) && $address !== '') {
                    return $address;
                }
            }
        }

        if (isset($row['ips']) && is_array($row['ips'])) {
            $first = trim((string) ($row['ips'][0] ?? ''));
            if ($first !== '') {
                return $first;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $live
     * @return array<string, mixed>
     */
    protected function extractScpLiveMetrics(array $row, array $live): array
    {
        $currentMem = (int) ($live['currentServerMemoryInMiB'] ?? 0);
        $maxMem = (int) ($live['maxServerMemoryInMiB'] ?? 0);

        $diskPercentPlatform = null;
        $disks = $live['disks'] ?? [];
        if (is_array($disks)) {
            foreach ($disks as $disk) {
                if (! is_array($disk)) {
                    continue;
                }
                $capacity = (int) ($disk['capacityInMiB'] ?? 0);
                $allocation = (int) ($disk['allocationInMiB'] ?? 0);
                if ($capacity > 0) {
                    $diskPercentPlatform = min(100, round(($allocation / $capacity) * 100, 1));
                    break;
                }
            }
        }

        return array_filter([
            'current_memory_mib' => $currentMem > 0 ? $currentMem : null,
            'max_memory_mib' => $maxMem > 0 ? $maxMem : null,
            'disk_percent_platform' => $diskPercentPlatform,
            'cpu_count' => isset($live['cpuCount']) ? (int) $live['cpuCount'] : null,
            'uptime_seconds' => isset($live['uptimeInSeconds']) ? (int) $live['uptimeInSeconds'] : null,
            'disks_available_mib' => isset($row['disksAvailableSpaceInMiB'])
                ? (int) $row['disksAvailableSpaceInMiB']
                : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function mapServerRow(array $row): ?array
    {
        $id = (string) ($row['id'] ?? $row['serverId'] ?? '');
        if ($id === '') {
            return null;
        }

        $live = is_array($row['serverLiveInfo'] ?? null) ? $row['serverLiveInfo'] : [];
        $name = $this->resolveServerName($row, $id);
        $ip = $this->extractPrimaryIp($row);

        $state = strtolower((string) (
            $live['state'] ?? $row['state'] ?? $row['status'] ?? $row['powerState'] ?? ''
        ));

        return [
            'external_id' => 'scp:'.$id,
            'name' => $name,
            'ip' => $ip !== '' ? $ip : null,
            'region' => $this->resolveRegion($row),
            'status' => $this->normalizeStatus($state),
            'metadata' => [
                'product_line' => (string) ($row['type'] ?? 'netcup_server'),
                'server_id' => $id,
                'hostname' => $row['hostname'] ?? null,
                'nickname' => $row['nickname'] ?? null,
                'live_state' => $live['state'] ?? null,
                'scp_live' => $this->extractScpLiveMetrics($row, $live),
            ],
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function extractList(mixed $body, string ...$keys): array
    {
        if (! is_array($body)) {
            return [];
        }

        foreach ($keys as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                return $body[$key];
            }
        }

        return array_is_list($body) ? $body : [];
    }
}
