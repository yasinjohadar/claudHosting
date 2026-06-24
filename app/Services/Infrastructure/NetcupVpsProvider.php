<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsLifecycleContract;
use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Netcup\NetcupImageService;
use App\Services\Infrastructure\Netcup\NetcupPowerService;
use App\Services\Infrastructure\Netcup\NetcupScpClient;
use App\Services\Infrastructure\Netcup\NetcupServerService;

class NetcupVpsProvider implements VpsProviderContract, VpsLifecycleContract
{
    public function __construct(
        protected NetcupScpClient $client,
        protected NetcupServerService $servers,
        protected NetcupPowerService $power,
        protected NetcupImageService $images
    ) {}

    public function providerKey(): string
    {
        return 'netcup';
    }

    public function testConnection(): array
    {
        return $this->client->test();
    }

    public function listInstances(): array
    {
        $res = $this->servers->listInstances();

        return [
            'success' => $res['success'],
            'message' => $res['message'] ?? ($res['success'] ? 'OK' : 'فشل'),
            'instances' => $res['instances'] ?? [],
        ];
    }

    public function getInstance(string $externalId): array
    {
        return $this->servers->getInstance($externalId, true);
    }

    public function start(string $externalId): array
    {
        return $this->power->start($externalId);
    }

    public function stop(string $externalId): array
    {
        return $this->power->stop($externalId);
    }

    public function shutdown(string $externalId): array
    {
        return $this->power->shutdown($externalId);
    }

    public function restart(string $externalId): array
    {
        return $this->power->restart($externalId);
    }

    public function listImages(string $externalId): array
    {
        $res = $this->images->listFlavours($externalId);
        if (! $res['success']) {
            return ['success' => false, 'message' => $res['message']];
        }

        $images = $this->extractImageList($res['data']);

        return ['success' => true, 'message' => 'OK', 'images' => $images];
    }

    public function reinstall(string $externalId, string $imageId, array $options = []): array
    {
        $res = $this->images->setupImage($externalId, $imageId, $options);

        return [
            'success' => $res['success'],
            'message' => $res['message'],
            'task_uuid' => $res['task_uuid'] ?? null,
        ];
    }

    public function createInstance(array $order): array
    {
        return [
            'success' => false,
            'message' => 'إنشاء سيرفر جديد عبر Netcup API غير متاح في SCP — أنشئ السيرفر من واجهة Netcup ثم «مزامنة الكل»',
        ];
    }

    /**
     * @return list<mixed>
     */
    protected function extractImageList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        foreach (['data', 'imageFlavours', 'flavours', 'images'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        return array_is_list($data) ? $data : [];
    }
}
