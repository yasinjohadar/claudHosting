<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsLifecycleContract;
use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Concerns\MakesHttpVpsRequests;
use App\Services\Infrastructure\Netcup\NetcupScpClient;

class NetcupVpsProvider implements VpsProviderContract, VpsLifecycleContract
{
    use MakesHttpVpsRequests;

    public function __construct(protected NetcupScpClient $client) {}

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
        $res = $this->client->request('GET', '/servers', ['limit' => 500]);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل', 'instances' => []];
        }

        $body = $res['body'] ?? [];
        $rows = $body['data'] ?? $body['servers'] ?? (is_array($body) && array_is_list($body) ? $body : []);

        $instances = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $mapped = $this->mapServer($row);
            if ($mapped !== null) {
                $instances[] = $mapped;
            }
        }

        return ['success' => true, 'instances' => $instances];
    }

    public function getInstance(string $externalId): array
    {
        $serverId = $this->parseExternalId($externalId);
        $res = $this->client->request('GET', '/servers/'.$serverId);
        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل'];
        }

        $row = $res['body']['data'] ?? $res['body'] ?? null;
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'السيرفر غير موجود'];
        }

        $mapped = $this->mapServer($row);

        return $mapped
            ? ['success' => true, 'instance' => $mapped]
            : ['success' => false, 'message' => 'تعذّر قراءة السيرفر'];
    }

    public function start(string $externalId): array
    {
        return $this->power($externalId, 'on', null, 'تم إرسال أمر التشغيل');
    }

    public function stop(string $externalId): array
    {
        return $this->power($externalId, 'off', 'POWEROFF', 'تم إرسال الإيقاف الفوري');
    }

    public function shutdown(string $externalId): array
    {
        return $this->power($externalId, 'off', null, 'تم إرسال إيقاف ACPI');
    }

    public function restart(string $externalId): array
    {
        return $this->power($externalId, 'off', 'POWERCYCLE', 'تم إرسال إعادة التشغيل (Powercycle)');
    }

    public function listImages(string $externalId): array
    {
        $serverId = $this->parseExternalId($externalId);
        $res = $this->client->request('GET', '/servers/'.$serverId.'/image/flavours');

        if (! ($res['success'] ?? false)) {
            return ['success' => false, 'message' => $res['message'] ?? 'فشل'];
        }

        $images = $res['body']['data'] ?? $res['body'] ?? [];

        return ['success' => true, 'message' => 'OK', 'images' => is_array($images) ? $images : []];
    }

    public function reinstall(string $externalId, string $imageId, array $options = []): array
    {
        $serverId = $this->parseExternalId($externalId);
        $res = $this->client->request('POST', '/servers/'.$serverId.'/image/setup', [], [
            'imageId' => $imageId,
            'hostname' => $options['hostname'] ?? null,
        ]);

        return [
            'success' => $res['success'] ?? false,
            'message' => $res['success'] ? 'تم طلب إعداد/إعادة تثبيت الصورة' : ($res['message'] ?? 'فشل'),
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
     * @return array{success: bool, message: string}
     */
    protected function power(string $externalId, string $state, ?string $option, string $successMessage): array
    {
        $serverId = $this->parseExternalId($externalId);
        $payload = array_filter([
            'power' => $state,
            'option' => $option,
        ]);

        $res = $this->client->request('POST', '/servers/'.$serverId.'/power', [], $payload);

        return [
            'success' => $res['success'] ?? false,
            'message' => $res['success'] ? $successMessage : ($res['message'] ?? 'فشل'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function mapServer(array $row): ?array
    {
        $id = (string) ($row['id'] ?? $row['serverId'] ?? '');
        if ($id === '') {
            return null;
        }

        $name = (string) ($row['nickname'] ?? $row['hostname'] ?? $row['name'] ?? 'Netcup '.$id);
        $ip = (string) ($row['ip'] ?? $row['primaryIp'] ?? '');
        if ($ip === '' && isset($row['ips']) && is_array($row['ips'])) {
            $ip = (string) ($row['ips'][0] ?? '');
        }

        $state = strtolower((string) ($row['state'] ?? $row['status'] ?? $row['powerState'] ?? ''));

        return [
            'external_id' => 'scp:'.$id,
            'name' => $name,
            'ip' => $ip,
            'region' => (string) ($row['location'] ?? $row['datacenter'] ?? ''),
            'status' => $this->normalizeStatus($state),
            'metadata' => [
                'product_line' => (string) ($row['type'] ?? 'netcup_server'),
                'server_id' => $id,
            ],
        ];
    }

    protected function parseExternalId(string $externalId): string
    {
        return str_starts_with($externalId, 'scp:')
            ? substr($externalId, 4)
            : $externalId;
    }
}
