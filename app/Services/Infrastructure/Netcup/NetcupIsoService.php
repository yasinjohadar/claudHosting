<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupIsoService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected InfrastructureSettingsService $settings
    ) {}

    public function getServerIso(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/iso'));
    }

    public function attachIso(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/iso', [], $payload),
            'تم ربط ISO'
        );
    }

    public function detachIso(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('DELETE', '/servers/'.$serverId.'/iso'),
            'تم فصل ISO'
        );
    }

    public function listIsoImages(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/isoimages'));
    }

    public function listUserIsos(): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/isos'));
    }

    public function getUserIso(string $key): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/isos/'.$key));
    }

    public function createUserIso(string $key, array $payload = []): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('POST', '/users/'.$userId.'/isos/'.$key, [], $payload)
        );
    }

    public function deleteUserIso(string $key): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('DELETE', '/users/'.$userId.'/isos/'.$key),
            'تم حذف ISO'
        );
    }
}
