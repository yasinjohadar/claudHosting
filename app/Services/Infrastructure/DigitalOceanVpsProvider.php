<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Concerns\MakesHttpVpsRequests;

class DigitalOceanVpsProvider implements VpsProviderContract
{
    use MakesHttpVpsRequests;

    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function providerKey(): string
    {
        return 'digitalocean';
    }

    public function testConnection(): array
    {
        $res = $this->api('GET', '/account');

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'الاتصال بـ DigitalOcean ناجح' : ($res['message'] ?? 'فشل'),
        ];
    }

    public function listInstances(): array
    {
        $page = 1;
        $instances = [];

        do {
            $res = $this->api('GET', '/droplets', ['page' => $page, 'per_page' => 50]);
            if (! $res['success']) {
                return ['success' => false, 'message' => $res['message'], 'instances' => $instances];
            }

            $body = $res['body'] ?? [];
            foreach ($body['droplets'] ?? [] as $row) {
                if (is_array($row)) {
                    $instances[] = $this->mapInstance($row);
                }
            }

            $links = $body['links']['pages'] ?? [];
            $page++;
        } while (isset($links['next']) && $page <= 20);

        return ['success' => true, 'instances' => $instances];
    }

    public function getInstance(string $externalId): array
    {
        $res = $this->api('GET', '/droplets/'.$externalId);
        if (! $res['success']) {
            return ['success' => false, 'message' => $res['message']];
        }

        $row = $res['body']['droplet'] ?? null;
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'Droplet غير موجود'];
        }

        return ['success' => true, 'instance' => $this->mapInstance($row)];
    }

    public function start(string $externalId): array
    {
        return $this->dropletAction($externalId, 'power_on', 'تم تشغيل Droplet');
    }

    public function stop(string $externalId): array
    {
        return $this->dropletAction($externalId, 'power_off', 'تم إيقاف Droplet');
    }

    public function shutdown(string $externalId): array
    {
        return $this->dropletAction($externalId, 'shutdown', 'تم إرسال إيقاف آمن');
    }

    public function restart(string $externalId): array
    {
        return $this->dropletAction($externalId, 'reboot', 'تم إعادة تشغيل Droplet');
    }

    /**
     * @return array{success: bool, message: string}
     */
    protected function dropletAction(string $externalId, string $type, string $okMessage): array
    {
        $res = $this->api('POST', '/droplets/'.$externalId.'/actions', [], ['type' => $type]);

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
        foreach ($row['networks']['v4'] ?? [] as $net) {
            if (($net['type'] ?? '') === 'public' && ! empty($net['ip_address'])) {
                $ip = $net['ip_address'];
                break;
            }
        }

        $status = ($row['status'] ?? 'unknown') === 'active' ? 'running' : $this->normalizeStatus((string) ($row['status'] ?? 'unknown'));

        return [
            'external_id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'ip' => $ip,
            'region' => (string) ($row['region']['name'] ?? $row['region']['slug'] ?? ''),
            'status' => $status,
            'metadata' => [
                'size' => $row['size_slug'] ?? null,
            ],
        ];
    }

    /**
     * @return array{success: bool, status: int, body: mixed, message: string}
     */
    protected function api(string $method, string $path, array $query = [], array $body = []): array
    {
        $token = $this->settings->getCredentials()['digitalocean_api_token'] ?? '';
        if ($token === '') {
            return ['success' => false, 'status' => 0, 'body' => null, 'message' => 'توكن DigitalOcean غير مضبوط'];
        }

        $url = rtrim(config('infrastructure.digitalocean.api_base'), '/').$path;

        return $this->httpJson($method, $url, [
            'Authorization' => 'Bearer '.$token,
        ], $method === 'GET' ? $query : $body);
    }
}
