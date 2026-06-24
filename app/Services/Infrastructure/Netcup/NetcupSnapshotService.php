<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupSnapshotService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected NetcupTaskService $tasks
    ) {}

    public function list(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/snapshots'));
    }

    public function get(string $externalId, string $name): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/snapshots/'.$name));
    }

    public function create(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/snapshots', [], $payload),
            'تم إنشاء اللقطة'
        );
    }

    public function delete(string $externalId, string $name): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('DELETE', '/servers/'.$serverId.'/snapshots/'.$name),
            'تم حذف اللقطة'
        );
    }

    public function revert(string $externalId, string $name): array
    {
        $serverId = $this->parseServerId($externalId);
        $res = $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/snapshots/'.$name.'/revert'),
            'تم طلب استعادة اللقطة'
        );

        if ($res['success'] && $res['task_uuid']) {
            $wait = $this->tasks->waitUntilDone($res['task_uuid']);
            if (! $wait['success']) {
                return array_merge($res, ['success' => false, 'message' => $wait['message']]);
            }
        }

        return $res;
    }

    public function export(string $externalId, string $name, array $payload = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/snapshots/'.$name.'/export', [], $payload),
            'تم طلب تصدير اللقطة'
        );
    }

    public function dryrun(string $externalId, array $payload = []): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/snapshots:dryrun', [], $payload)
        );
    }
}
