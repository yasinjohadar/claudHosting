<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsLifecycleContract;
use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Concerns\MakesHttpVpsRequests;
use App\Services\Infrastructure\Ovh\OvhApiClientFactory;
use Ovh\Api;

class OvhVpsProvider implements VpsProviderContract, VpsLifecycleContract
{
    use MakesHttpVpsRequests;

    public function __construct(protected OvhApiClientFactory $factory) {}

    public function providerKey(): string
    {
        return 'ovh';
    }

    public function testConnection(): array
    {
        return $this->factory->test();
    }

    public function listInstances(): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة OVH', 'instances' => []];
        }

        $instances = [];
        $errors = [];

        foreach ($this->listVpsServices($api) as $serviceName) {
            try {
                $instances[] = $this->mapVps($api, $serviceName);
            } catch (\Throwable $e) {
                $errors[] = 'vps '.$serviceName.': '.$e->getMessage();
            }
        }

        foreach ($this->listDedicatedServers($api) as $serverName) {
            try {
                $instances[] = $this->mapDedicated($api, $serverName);
            } catch (\Throwable $e) {
                $errors[] = 'dedicated '.$serverName.': '.$e->getMessage();
            }
        }

        foreach ($this->listCloudInstances($api) as $row) {
            try {
                $instances[] = $this->mapCloudInstance($row);
            } catch (\Throwable $e) {
                $errors[] = 'cloud: '.$e->getMessage();
            }
        }

        return [
            'success' => true,
            'instances' => $instances,
            'message' => $errors !== [] ? implode('; ', $errors) : '',
        ];
    }

    public function getInstance(string $externalId): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        try {
            $parsed = $this->parseExternalId($externalId);

            return match ($parsed['type']) {
                'vps' => ['success' => true, 'instance' => $this->mapVps($api, $parsed['id'])],
                'dedicated' => ['success' => true, 'instance' => $this->mapDedicated($api, $parsed['id'])],
                'cloud' => $this->getCloudInstance($api, $parsed['project'], $parsed['id']),
                default => ['success' => false, 'message' => 'معرّف غير معروف'],
            };
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function start(string $externalId): array
    {
        return $this->power($externalId, 'start', 'تم إرسال أمر التشغيل');
    }

    public function stop(string $externalId): array
    {
        return $this->power($externalId, 'stop', 'تم إرسال أمر الإيقاف');
    }

    public function shutdown(string $externalId): array
    {
        return $this->power($externalId, 'shutdown', 'تم إرسال أمر الإيقاف الآمن');
    }

    public function restart(string $externalId): array
    {
        return $this->power($externalId, 'restart', 'تم إرسال أمر إعادة التشغيل');
    }

    public function listImages(string $externalId): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        try {
            $parsed = $this->parseExternalId($externalId);
            if ($parsed['type'] !== 'vps') {
                return ['success' => false, 'message' => 'قائمة الصور متاحة لـ VPS فقط حالياً'];
            }

            $images = $api->get('/vps/'.$parsed['id'].'/images/available');

            return ['success' => true, 'message' => 'OK', 'images' => is_array($images) ? $images : []];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function reinstall(string $externalId, string $imageId, array $options = []): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        try {
            $parsed = $this->parseExternalId($externalId);
            if ($parsed['type'] === 'vps') {
                $body = array_filter([
                    'imageId' => $imageId,
                    'doNotSendPassword' => $options['do_not_send_password'] ?? false,
                ]);
                $api->post('/vps/'.$parsed['id'].'/rebuild', $body);

                return ['success' => true, 'message' => 'تم طلب إعادة تثبيت VPS'];
            }

            if ($parsed['type'] === 'dedicated') {
                $api->post('/dedicated/server/'.$parsed['id'].'/reinstall', array_filter([
                    'templateName' => $imageId,
                ]));

                return ['success' => true, 'message' => 'تم طلب إعادة تثبيت السيرفر المخصص'];
            }

            return ['success' => false, 'message' => 'إعادة التثبيت غير مدعومة لهذا النوع'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createInstance(array $order): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        $productLine = (string) ($order['product_line'] ?? 'vps');

        try {
            if ($productLine === 'vps') {
                $cart = $api->post('/order/cart', ['ovhSubsidiary' => $order['subsidiary'] ?? 'FR']);
                $cartId = $cart['cartId'] ?? null;
                if (! $cartId) {
                    return ['success' => false, 'message' => 'فشل إنشاء سلة الطلب'];
                }

                $api->post('/order/cart/'.$cartId.'/vps', [
                    'duration' => $order['duration'] ?? 'P1M',
                    'planCode' => $order['plan_code'] ?? '',
                    'pricingMode' => $order['pricing_mode'] ?? 'default',
                    'quantity' => 1,
                ]);

                $checkout = $api->post('/order/cart/'.$cartId.'/checkout', [
                    'autoPayWithPreferredPaymentMethod' => $order['auto_pay'] ?? true,
                    'waiveRetractationPeriod' => true,
                ]);

                return [
                    'success' => true,
                    'message' => 'تم إرسال طلب الشراء — راجع OVH للتأكيد',
                    'external_id' => null,
                    'order' => $checkout,
                ];
            }

            return ['success' => false, 'message' => 'شراء هذا النوع يتطلب معاملات إضافية — استخدم لوحة OVH أو وسّع الطلب'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function power(string $externalId, string $action, string $successMessage): array
    {
        $api = $this->api();
        if ($api === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        try {
            $parsed = $this->parseExternalId($externalId);

            match ($parsed['type']) {
                'vps' => $this->vpsPower($api, $parsed['id'], $action),
                'dedicated' => $this->dedicatedPower($api, $parsed['id'], $action),
                'cloud' => $this->cloudPower($api, $parsed['project'], $parsed['id'], $action),
                default => throw new \InvalidArgumentException('نوع مورد غير مدعوم'),
            };

            return ['success' => true, 'message' => $successMessage];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function vpsPower(Api $api, string $serviceName, string $action): void
    {
        match ($action) {
            'start' => $api->post('/vps/'.$serviceName.'/start'),
            'stop' => $api->post('/vps/'.$serviceName.'/stop'),
            'shutdown' => $api->post('/vps/'.$serviceName.'/stop'), // OVH VPS: stop is ACPI
            'restart' => $api->post('/vps/'.$serviceName.'/reboot'),
            default => throw new \InvalidArgumentException('إجراء غير مدعوم'),
        };
    }

    protected function dedicatedPower(Api $api, string $serverName, string $action): void
    {
        match ($action) {
            'start' => $api->post('/dedicated/server/'.$serverName.'/start'),
            'stop' => $api->post('/dedicated/server/'.$serverName.'/stop'),
            'shutdown' => $api->post('/dedicated/server/'.$serverName.'/shutdown'),
            'restart' => $api->post('/dedicated/server/'.$serverName.'/reboot'),
            default => throw new \InvalidArgumentException('إجراء غير مدعوم'),
        };
    }

    protected function cloudPower(Api $api, string $projectId, string $instanceId, string $action): void
    {
        $base = '/cloud/project/'.$projectId.'/instance/'.$instanceId;
        match ($action) {
            'start' => $api->post($base.'/start'),
            'stop' => $api->post($base.'/stop'),
            'shutdown' => $api->post($base.'/stop'),
            'restart' => $api->post($base.'/reboot'),
            default => throw new \InvalidArgumentException('إجراء غير مدعوم'),
        };
    }

    /**
     * @return list<string>
     */
    protected function listVpsServices(Api $api): array
    {
        $list = $api->get('/vps');

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /**
     * @return list<string>
     */
    protected function listDedicatedServers(Api $api): array
    {
        $list = $api->get('/dedicated/server');

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /**
     * @return list<array{project: string, id: string, api: Api}>
     */
    protected function listCloudInstances(Api $api): array
    {
        $out = [];
        $projects = $api->get('/cloud/project');
        if (! is_array($projects)) {
            return [];
        }

        foreach ($projects as $projectId) {
            if (! is_string($projectId)) {
                continue;
            }
            try {
                $instances = $api->get('/cloud/project/'.$projectId.'/instance');
                if (! is_array($instances)) {
                    continue;
                }
                foreach ($instances as $instance) {
                    if (! is_array($instance)) {
                        continue;
                    }
                    $id = (string) ($instance['id'] ?? $instance['name'] ?? '');
                    if ($id !== '') {
                        $out[] = ['project' => $projectId, 'id' => $id, 'row' => $instance];
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapVps(Api $api, string $serviceName): array
    {
        $info = $api->get('/vps/'.$serviceName);
        $ips = $api->get('/vps/'.$serviceName.'/ips') ?? [];
        $ip = '';
        if (is_array($ips)) {
            $ip = (string) ($ips[0] ?? '');
        }

        $state = '';
        try {
            $state = (string) ($api->get('/vps/'.$serviceName.'/status') ?? '');
        } catch (\Throwable) {
            $state = (string) ($info['state'] ?? '');
        }

        return [
            'external_id' => 'vps:'.$serviceName,
            'name' => (string) ($info['displayName'] ?? $serviceName),
            'ip' => $ip,
            'region' => (string) ($info['zone'] ?? $info['datacenter'] ?? ''),
            'status' => $this->normalizeStatus($state),
            'metadata' => [
                'product_line' => 'ovh_vps',
                'service_name' => $serviceName,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapDedicated(Api $api, string $serverName): array
    {
        $info = $api->get('/dedicated/server/'.$serverName);
        $ip = (string) ($info['ip'] ?? '');
        $state = (string) ($info['state'] ?? $info['powerState'] ?? '');

        return [
            'external_id' => 'dedicated:'.$serverName,
            'name' => (string) ($info['commercialRange'] ?? $serverName),
            'ip' => $ip,
            'region' => (string) ($info['datacenter'] ?? ''),
            'status' => $this->normalizeStatus($state),
            'metadata' => [
                'product_line' => 'ovh_dedicated',
                'service_name' => $serverName,
            ],
        ];
    }

    /**
     * @param  array{project?: string, id?: string, row?: array<string, mixed>, api?: Api}  $payload
     * @return array<string, mixed>
     */
    protected function mapCloudInstance(array $payload): array
    {
        $project = (string) ($payload['project'] ?? '');
        $id = (string) ($payload['id'] ?? '');
        $row = $payload['row'] ?? [];
        if ($project === '' || $id === '') {
            throw new \InvalidArgumentException('بيانات instance ناقصة');
        }

        $ip = '';
        if (isset($row['ipAddresses']) && is_array($row['ipAddresses'])) {
            foreach ($row['ipAddresses'] as $addr) {
                if (is_array($addr) && ($addr['version'] ?? 4) == 4) {
                    $ip = (string) ($addr['ip'] ?? '');
                    break;
                }
            }
        }

        return [
            'external_id' => 'cloud:'.$project.':'.$id,
            'name' => (string) ($row['name'] ?? $id),
            'ip' => $ip,
            'region' => (string) ($row['region'] ?? ''),
            'status' => $this->normalizeStatus((string) ($row['status'] ?? '')),
            'metadata' => [
                'product_line' => 'ovh_public_cloud',
                'project_id' => $project,
                'instance_id' => $id,
            ],
        ];
    }

    /**
     * @return array{type: string, id: string, project?: string}
     */
    protected function parseExternalId(string $externalId): array
    {
        if (str_starts_with($externalId, 'vps:')) {
            return ['type' => 'vps', 'id' => substr($externalId, 4)];
        }
        if (str_starts_with($externalId, 'dedicated:')) {
            return ['type' => 'dedicated', 'id' => substr($externalId, 10)];
        }
        if (str_starts_with($externalId, 'cloud:')) {
            $rest = substr($externalId, 6);
            $parts = explode(':', $rest, 2);
            if (count($parts) === 2) {
                return ['type' => 'cloud', 'project' => $parts[0], 'id' => $parts[1]];
            }
        }

        // Legacy: bare service name treated as VPS
        return ['type' => 'vps', 'id' => $externalId];
    }

    /**
     * @return array{success: bool, message?: string, instance?: array<string, mixed>}
     */
    protected function getCloudInstance(Api $api, string $projectId, string $instanceId): array
    {
        $row = $api->get('/cloud/project/'.$projectId.'/instance/'.$instanceId);
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'السيرفر غير موجود'];
        }

        return [
            'success' => true,
            'instance' => $this->mapCloudInstance([
                'project' => $projectId,
                'id' => $instanceId,
                'row' => $row,
            ]),
        ];
    }

    protected function api(): ?Api
    {
        return $this->factory->make();
    }
}
