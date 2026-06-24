<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupPowerService
{
    use NetcupScpHelpers;

    public function __construct(
        protected NetcupScpClient $client,
        protected NetcupTaskService $tasks
    ) {}

    /**
     * @return array{success: bool, message: string, task_uuid?: ?string}
     */
    public function start(string $externalId): array
    {
        return $this->setState($externalId, 'ON', null, 'تم إرسال أمر التشغيل');
    }

    /**
     * @return array{success: bool, message: string, task_uuid?: ?string}
     */
    public function stop(string $externalId): array
    {
        return $this->setState($externalId, 'OFF', 'POWEROFF', 'تم إرسال الإيقاف الفوري');
    }

    /**
     * @return array{success: bool, message: string, task_uuid?: ?string}
     */
    public function shutdown(string $externalId): array
    {
        return $this->setState($externalId, 'OFF', null, 'تم إرسال إيقاف ACPI');
    }

    /**
     * @return array{success: bool, message: string, task_uuid?: ?string}
     */
    public function restart(string $externalId): array
    {
        return $this->setState($externalId, 'OFF', 'POWERCYCLE', 'تم إرسال إعادة التشغيل');
    }

    /**
     * @return array{success: bool, message: string, task_uuid?: ?string}
     */
    protected function setState(string $externalId, string $state, ?string $stateOption, string $successMessage): array
    {
        $serverId = $this->parseServerId($externalId);
        $query = array_filter(['stateOption' => $stateOption]);
        $res = $this->client->request('PATCH', '/servers/'.$serverId, $query, ['state' => $state]);
        $wrapped = $this->wrapResponse($res, $successMessage);

        if (! $wrapped['success']) {
            return [
                'success' => false,
                'message' => $wrapped['message'],
                'task_uuid' => $wrapped['task_uuid'],
            ];
        }

        if ($wrapped['task_uuid']) {
            $wait = $this->tasks->waitUntilDone($wrapped['task_uuid']);
            if (! $wait['success']) {
                return [
                    'success' => false,
                    'message' => $wait['message'],
                    'task_uuid' => $wrapped['task_uuid'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => $successMessage,
            'task_uuid' => $wrapped['task_uuid'],
        ];
    }
}
