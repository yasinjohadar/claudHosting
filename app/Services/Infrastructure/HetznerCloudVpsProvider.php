<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Concerns\MakesHttpVpsRequests;

class HetznerCloudVpsProvider implements VpsProviderContract
{
    use MakesHttpVpsRequests;

    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function providerKey(): string
    {
        return 'hetzner';
    }

    public function testConnection(): array
    {
        $res = $this->api('GET', '/servers', ['per_page' => 1]);

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'الاتصال بـ Hetzner Cloud ناجح' : ($res['message'] ?? 'فشل'),
        ];
    }

    public function listInstances(): array
    {
        $page = 1;
        $instances = [];

        do {
            $res = $this->api('GET', '/servers', ['page' => $page, 'per_page' => 50]);
            if (! $res['success']) {
                return ['success' => false, 'message' => $res['message'], 'instances' => $instances];
            }

            $body = $res['body'] ?? [];
            $rows = $body['servers'] ?? [];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $instances[] = $this->mapInstance($row);
                }
            }

            $meta = $body['meta']['pagination'] ?? [];
            $lastPage = (int) ($meta['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && $page <= 20);

        return ['success' => true, 'instances' => $instances];
    }

    public function getInstance(string $externalId): array
    {
        $res = $this->api('GET', '/servers/'.$externalId);
        if (! $res['success']) {
            return ['success' => false, 'message' => $res['message']];
        }

        $row = $res['body']['server'] ?? null;
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'السيرفر غير موجود'];
        }

        return ['success' => true, 'instance' => $this->mapInstance($row)];
    }

    public function start(string $externalId): array
    {
        return $this->powerAction($externalId, 'poweron', 'تم تشغيل السيرفر');
    }

    public function stop(string $externalId): array
    {
        return $this->powerAction($externalId, 'poweroff', 'تم إيقاف السيرفر (قطع التيار)');
    }

    public function shutdown(string $externalId): array
    {
        return $this->powerAction($externalId, 'shutdown', 'تم إرسال إيقاف آمن');
    }

    public function restart(string $externalId): array
    {
        return $this->powerAction($externalId, 'reboot', 'تم إعادة تشغيل السيرفر');
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function powerAction(string $externalId, string $type, string $okMessage): array
    {
        $res = $this->api('POST', '/servers/'.$externalId.'/actions', [], ['type' => $type]);

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? $okMessage : ($res['message'] ?? 'فشل الإجراء'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapInstance(array $row): array
    {
        $ip = '';
        $pub = $row['public_net']['ipv4']['ip'] ?? null;
        if (is_string($pub)) {
            $ip = $pub;
        }

        return [
            'external_id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'ip' => $ip,
            'region' => (string) ($row['datacenter']['location']['name'] ?? $row['datacenter']['name'] ?? ''),
            'status' => $this->normalizeStatus((string) ($row['status'] ?? 'unknown')),
            'metadata' => [
                'server_type' => $row['server_type']['name'] ?? null,
            ],
        ];
    }

    /**
     * @return array{success: bool, status: int, body: mixed, message: string}
     */
    protected function api(string $method, string $path, array $query = [], array $body = []): array
    {
        $token = $this->settings->getCredentials()['hetzner_api_token'] ?? '';
        if ($token === '') {
            return ['success' => false, 'status' => 0, 'body' => null, 'message' => 'توكن Hetzner غير مضبوط'];
        }

        $url = rtrim(config('infrastructure.hetzner.api_base'), '/').$path;

        return $this->httpJson($method, $url, [
            'Authorization' => 'Bearer '.$token,
        ], $method === 'GET' ? $query : $body);
    }
}
