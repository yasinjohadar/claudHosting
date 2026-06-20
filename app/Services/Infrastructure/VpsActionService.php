<?php

namespace App\Services\Infrastructure;

use App\Models\VpsActionLog;
use App\Models\VpsServer;
use App\Models\User;

class VpsActionService
{
    public function __construct(protected VpsProviderRegistry $registry) {}

    /**
     * @return array{success: bool, message: string}
     */
    public function execute(VpsServer $server, string $action, ?User $user = null): array
    {
        $allowed = ['start', 'stop', 'shutdown', 'restart'];
        if (! in_array($action, $allowed, true)) {
            return ['success' => false, 'message' => 'إجراء غير مسموح'];
        }

        $provider = $this->registry->get($server->provider);
        $result = match ($action) {
            'start' => $provider->start($server->external_id),
            'stop' => $provider->stop($server->external_id),
            'shutdown' => $provider->shutdown($server->external_id),
            'restart' => $provider->restart($server->external_id),
        };

        VpsActionLog::query()->create([
            'vps_server_id' => $server->id,
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? null,
            'meta' => ['provider' => $server->provider, 'external_id' => $server->external_id],
        ]);

        if ($result['success'] ?? false) {
            $server->update(['status' => match ($action) {
                'start' => 'starting',
                'stop', 'shutdown' => 'stopping',
                'restart' => 'rebooting',
                default => $server->status,
            }]);
        }

        return $result;
    }
}
