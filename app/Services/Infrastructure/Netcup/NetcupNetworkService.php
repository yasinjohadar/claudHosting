<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupNetworkService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected InfrastructureSettingsService $settings
    ) {}

    public function listInterfaces(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/interfaces'));
    }

    public function getInterface(string $externalId, string $mac): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/interfaces/'.$mac));
    }

    public function createInterface(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/interfaces', [], $payload),
            'تم إنشاء الواجهة'
        );
    }

    public function updateInterface(string $externalId, string $mac, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('PUT', '/servers/'.$serverId.'/interfaces/'.$mac, [], $payload),
            'تم تحديث الواجهة'
        );
    }

    public function deleteInterface(string $externalId, string $mac): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('DELETE', '/servers/'.$serverId.'/interfaces/'.$mac),
            'تم حذف الواجهة'
        );
    }

    public function getRdnsIpv4(string $ip): array
    {
        return $this->wrapResponse($this->client->request('GET', '/rdns/ipv4/'.$ip));
    }

    public function setRdnsIpv4(array $payload): array
    {
        return $this->wrapResponse(
            $this->client->request('POST', '/rdns/ipv4', [], $payload),
            'تم تحديث rDNS IPv4'
        );
    }

    public function deleteRdnsIpv4(string $ip): array
    {
        return $this->wrapResponse(
            $this->client->request('DELETE', '/rdns/ipv4/'.$ip),
            'تم حذف rDNS IPv4'
        );
    }

    public function getRdnsIpv6(string $ip): array
    {
        return $this->wrapResponse($this->client->request('GET', '/rdns/ipv6/'.$ip));
    }

    public function setRdnsIpv6(array $payload): array
    {
        return $this->wrapResponse(
            $this->client->request('POST', '/rdns/ipv6', [], $payload),
            'تم تحديث rDNS IPv6'
        );
    }

    public function deleteRdnsIpv6(string $ip): array
    {
        return $this->wrapResponse(
            $this->client->request('DELETE', '/rdns/ipv6/'.$ip),
            'تم حذف rDNS IPv6'
        );
    }

    public function listFailoverIpv4(): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/failoverips/ipv4'));
    }

    public function patchFailoverIpv4(string $id, array $payload): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('PATCH', '/users/'.$userId.'/failoverips/ipv4/'.$id, [], $payload),
            'تم تحديث Failover IPv4'
        );
    }

    public function listFailoverIpv6(): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/failoverips/ipv6'));
    }

    public function patchFailoverIpv6(string $id, array $payload): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('PATCH', '/users/'.$userId.'/failoverips/ipv6/'.$id, [], $payload),
            'تم تحديث Failover IPv6'
        );
    }
}
