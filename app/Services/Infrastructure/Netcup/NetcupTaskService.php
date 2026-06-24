<?php

namespace App\Services\Infrastructure\Netcup;

use App\Services\Infrastructure\Netcup\Concerns\NetcupScpHelpers;

class NetcupTaskService
{
    use NetcupScpHelpers;

    public function __construct(protected NetcupScpClient $client) {}

    /**
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    public function list(array $query = []): array
    {
        return $this->wrapResponse($this->client->request('GET', '/tasks', $query));
    }

    /**
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    public function get(string $taskUuid): array
    {
        return $this->wrapResponse($this->client->request('GET', '/tasks/'.$taskUuid));
    }

    /**
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    public function cancel(string $taskUuid): array
    {
        return $this->wrapResponse(
            $this->client->request('PUT', '/tasks/'.$taskUuid.':cancel'),
            'تم إلغاء المهمة'
        );
    }

    /**
     * @return array{success: bool, message: string, data: mixed, task_uuid: ?string}
     */
    public function waitUntilDone(?string $taskUuid, ?int $timeoutSeconds = null): array
    {
        if ($taskUuid === null || $taskUuid === '') {
            return ['success' => true, 'message' => 'لا توجد مهمة للمتابعة', 'data' => null, 'task_uuid' => null];
        }

        $timeout = $timeoutSeconds ?? (int) config('infrastructure.netcup.task_wait_timeout', 300);
        $interval = (int) config('infrastructure.netcup.task_poll_interval', 3);
        $deadline = time() + $timeout;

        while (time() < $deadline) {
            $res = $this->get($taskUuid);
            if (! $res['success']) {
                return $res;
            }

            $data = is_array($res['data']) ? $res['data'] : [];
            $status = strtoupper((string) ($data['status'] ?? $data['state'] ?? ''));

            if (in_array($status, ['DONE', 'COMPLETED', 'SUCCESS', 'FINISHED'], true)) {
                return [
                    'success' => true,
                    'message' => 'اكتملت المهمة',
                    'data' => $data,
                    'task_uuid' => $taskUuid,
                ];
            }

            if (in_array($status, ['FAILED', 'ERROR', 'CANCELLED', 'CANCELED'], true)) {
                return [
                    'success' => false,
                    'message' => (string) ($data['message'] ?? $data['error'] ?? 'فشلت المهمة'),
                    'data' => $data,
                    'task_uuid' => $taskUuid,
                ];
            }

            sleep(max(1, $interval));
        }

        return [
            'success' => false,
            'message' => 'انتهت مهلة انتظار المهمة',
            'data' => null,
            'task_uuid' => $taskUuid,
        ];
    }
}
