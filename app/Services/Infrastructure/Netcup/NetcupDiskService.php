<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupDiskService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected NetcupTaskService $tasks
    ) {}

    public function list(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/disks'));
    }

    public function get(string $externalId, string $diskName): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/disks/'.$diskName));
    }

    public function supportedDrivers(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/disks/supported-drivers'));
    }

    public function patch(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('PATCH', '/servers/'.$serverId.'/disks', [], $payload),
            'تم تحديث الأقراص'
        );
    }

    public function format(string $externalId, string $diskName, array $payload = []): array
    {
        $serverId = $this->parseServerId($externalId);
        $res = $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/disks/'.$diskName.'/format', [], $payload),
            'تم طلب تهيئة القرص'
        );

        if ($res['success'] && $res['task_uuid']) {
            $wait = $this->tasks->waitUntilDone($res['task_uuid']);
            if (! $wait['success']) {
                return array_merge($res, ['success' => false, 'message' => $wait['message']]);
            }
        }

        return $res;
    }
}
