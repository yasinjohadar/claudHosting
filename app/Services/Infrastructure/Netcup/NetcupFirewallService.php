<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupFirewallService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected InfrastructureSettingsService $settings
    ) {}

    public function getInterfaceFirewall(string $externalId, string $mac): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('GET', '/servers/'.$serverId.'/interfaces/'.$mac.'/firewall')
        );
    }

    public function putInterfaceFirewall(string $externalId, string $mac, array $payload): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('PUT', '/servers/'.$serverId.'/interfaces/'.$mac.'/firewall', [], $payload),
            'تم تحديث الجدار الناري'
        );
    }

    public function reapplyInterfaceFirewall(string $externalId, string $mac): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/interfaces/'.$mac.'/firewall:reapply'),
            'تم إعادة تطبيق الجدار الناري'
        );
    }

    public function restoreCopiedPolicies(string $externalId, string $mac): array
    {
        $serverId = $this->parseServerId($externalId);

        return $this->wrapResponse(
            $this->client->request('POST', '/servers/'.$serverId.'/interfaces/'.$mac.'/firewall:restore-copied-policies'),
            'تم استعادة السياسات'
        );
    }

    public function listPolicies(): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/firewall-policies'));
    }

    public function createPolicy(array $payload): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('POST', '/users/'.$userId.'/firewall-policies', [], $payload),
            'تم إنشاء السياسة'
        );
    }

    public function getPolicy(string $id): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse($this->client->request('GET', '/users/'.$userId.'/firewall-policies/'.$id));
    }

    public function updatePolicy(string $id, array $payload): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('PUT', '/users/'.$userId.'/firewall-policies/'.$id, [], $payload),
            'تم تحديث السياسة'
        );
    }

    public function deletePolicy(string $id): array
    {
        $userId = $this->resolveUserId($this->settings);
        if ($userId === null) {
            return ['success' => false, 'message' => 'معرّف مستخدم SCP غير محفوظ', 'data' => null, 'task_uuid' => null];
        }

        return $this->wrapResponse(
            $this->client->request('DELETE', '/users/'.$userId.'/firewall-policies/'.$id),
            'تم حذف السياسة'
        );
    }
}
