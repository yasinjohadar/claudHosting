<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupUserService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected InfrastructureSettingsService $settings
    ) {}

    protected function userIdOrFail(): array|string
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $userId;
    }

    public function profile(): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId));
    }

    public function updateProfile(array $payload): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse(
            $this->client->request('PUT', '/users/'.$userId, [], $payload),
            'تم تحديث الملف'
        );
    }

    public function logs(): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/logs'));
    }

    public function listSshKeys(): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/ssh-keys'));
    }

    public function createSshKey(array $payload): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse(
            $this->client->request('POST', '/users/'.$userId.'/ssh-keys', [], $payload),
            'تم إضافة مفتاح SSH'
        );
    }

    public function deleteSshKey(string $id): array
    {
        $userId = $this->userIdOrFail();
        if (is_array($userId)) {
            return $userId;
        }

        return $this->wrapResponse(
            $this->client->request('DELETE', '/users/'.$userId.'/ssh-keys/'.$id),
            'تم حذف مفتاح SSH'
        );
    }
}
