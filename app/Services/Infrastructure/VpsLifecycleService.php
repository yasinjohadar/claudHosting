<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsLifecycleContract;
use App\Models\VpsActionLog;
use App\Models\VpsServer;
use App\Models\User;

class VpsLifecycleService
{
    public function __construct(protected VpsProviderRegistry $registry) {}

    /**
     * @return array{success: bool, message: string, images?: array<int, mixed>}
     */
    public function listImages(VpsServer $server): array
    {
        $lifecycle = $this->lifecycle($server);
        if ($lifecycle === null) {
            return ['success' => false, 'message' => 'المزود لا يدعم قائمة الصور'];
        }

        return $lifecycle->listImages($server->external_id);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function reinstall(VpsServer $server, string $imageId, ?User $user = null): array
    {
        $lifecycle = $this->lifecycle($server);
        if ($lifecycle === null) {
            return ['success' => false, 'message' => 'إعادة التثبيت غير مدعومة لهذا المزود'];
        }

        $result = $lifecycle->reinstall($server->external_id, $imageId);

        VpsActionLog::query()->create([
            'vps_server_id' => $server->id,
            'user_id' => $user?->id ?? auth()->id(),
            'action' => 'reinstall',
            'success' => (bool) ($result['success'] ?? false),
            'message' => $result['message'] ?? null,
            'meta' => ['image_id' => $imageId],
        ]);

        return $result;
    }

    protected function lifecycle(VpsServer $server): ?VpsLifecycleContract
    {
        return $this->registry->lifecycle($server->provider);
    }
}
