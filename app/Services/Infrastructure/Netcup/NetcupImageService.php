<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupImageService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected InfrastructureSettingsService $settings,
        protected NetcupTaskService $tasks
    ) {}

    public function listFlavours(string $externalId): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse($this->client->request('GET', '/servers/'.$serverId.'/imageflavours'));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function setupImage(string $externalId, string $imageFlavourId, array $options = []): array
    {
        $serverId = $this->parseServerId($externalId);
        $payload = array_filter([
            'imageFlavourId' => $imageFlavourId,
            'hostname' => $options['hostname'] ?? null,
            'disk' => $options['disk'] ?? null,
            'sshKeyIds' => $options['sshKeyIds'] ?? $options['ssh_key_ids'] ?? null,
            'locale' => $options['locale'] ?? null,
            'customScript' => $options['customScript'] ?? $options['custom_script'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $res = $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/image', [], $payload),
            'تم طلب إعداد الصورة'
        );

        if ($res['success'] && $res['task_uuid']) {
            $wait = $this->tasks->waitUntilDone($res['task_uuid']);
            if (! $wait['success']) {
                return array_merge($res, ['success' => false, 'message' => $wait['message']]);
            }
        }

        return $res;
    }

    public function setupUserImage(string $externalId, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/user-image', [], $payload),
            'تم طلب صورة المستخدم'
        );
    }

    public function listUserImages(): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ — أعد ربط Device Flow', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/images'));
    }

    public function createUserImage(string $key, array $payload = []): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('POST', '/users/'.$userId.'/images/'.$key, [], $payload)
        );
    }

    public function deleteUserImage(string $key): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('DELETE', '/users/'.$userId.'/images/'.$key),
            'تم حذف الصورة'
        );
    }
}
