<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsProviderContract;
use App\Services\Infrastructure\Concerns\MakesHttpVpsRequests;
use Illuminate\Support\Facades\Cache;

class ContaboVpsProvider implements VpsProviderContract
{
    use MakesHttpVpsRequests;

    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function providerKey(): string
    {
        return 'contabo';
    }

    public function testConnection(): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'message' => 'فشل المصادقة مع Contabo — تحقق من بيانات API'];
        }

        $res = $this->httpJson(
            'GET',
            config('infrastructure.contabo.api_base').'/compute/instances',
            $this->contaboRequestHeaders($token),
            ['size' => 1]
        );

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'الاتصال بـ Contabo ناجح' : ($res['message'] ?? 'فشل'),
        ];
    }

    public function listInstances(): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'message' => 'فشل المصادقة', 'instances' => []];
        }

        $page = 1;
        $instances = [];

        do {
            $res = $this->httpJson(
                'GET',
                config('infrastructure.contabo.api_base').'/compute/instances',
                $this->contaboRequestHeaders($token),
                ['page' => $page, 'size' => 100]
            );

            if (! $res['success']) {
                return ['success' => false, 'message' => $res['message'], 'instances' => $instances];
            }

            $body = $res['body'] ?? [];
            $rows = $body['data'] ?? [];
            if (! is_array($rows)) {
                break;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $productType = strtolower((string) ($row['productType'] ?? $row['product_type'] ?? ''));
                if (str_contains($productType, 'storage')) {
                    continue;
                }
                $instances[] = $this->mapInstance($row);
            }

            $pagination = $body['_pagination'] ?? [];
            $totalPages = (int) ($pagination['totalPages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 20);

        return ['success' => true, 'instances' => $instances];
    }

    public function getInstance(string $externalId): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        $res = $this->httpJson(
            'GET',
            config('infrastructure.contabo.api_base').'/compute/instances/'.$externalId,
            $this->contaboRequestHeaders($token)
        );

        if (! $res['success']) {
            return ['success' => false, 'message' => $res['message']];
        }

        $row = $res['body']['data'][0] ?? $res['body']['data'] ?? $res['body'] ?? null;
        if (! is_array($row)) {
            return ['success' => false, 'message' => 'السيرفر غير موجود'];
        }

        return ['success' => true, 'instance' => $this->mapInstance($row)];
    }

    public function start(string $externalId): array
    {
        return $this->action($externalId, 'start', 'تم إرسال أمر التشغيل');
    }

    public function stop(string $externalId): array
    {
        return $this->action($externalId, 'stop', 'تم إرسال أمر الإيقاف (قطع التيار)');
    }

    public function shutdown(string $externalId): array
    {
        return $this->action($externalId, 'shutdown', 'تم إرسال أمر الإيقاف الآمن');
    }

    public function restart(string $externalId): array
    {
        return $this->action($externalId, 'restart', 'تم إرسال أمر إعادة التشغيل');
    }

  /**
     * @return array{success: bool, message: string}
     */
    protected function action(string $externalId, string $action, string $successMessage): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['success' => false, 'message' => 'فشل المصادقة'];
        }

        $res = $this->httpJson(
            'POST',
            config('infrastructure.contabo.api_base').'/compute/instances/'.$externalId.'/actions/'.$action,
            $this->contaboRequestHeaders($token)
        );

        return [
            'success' => $res['success'],
            'message' => $res['success'] ? $successMessage : ($res['message'] ?? 'فشل الإجراء'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapInstance(array $row): array
    {
        $id = (string) ($row['instanceId'] ?? $row['instance_id'] ?? $row['id'] ?? '');
        $ips = $row['ipConfig'] ?? $row['ip_config'] ?? [];
        $ip = '';
        if (is_array($ips)) {
            $v4 = $ips['v4'] ?? $ips['ipv4'] ?? null;
            if (is_array($v4)) {
                $ip = (string) ($v4['ip'] ?? $v4[0]['ip'] ?? '');
            }
        }

        $status = $this->normalizeStatus((string) ($row['status'] ?? $row['state'] ?? 'unknown'));

        return [
            'external_id' => $id,
            'name' => (string) ($row['displayName'] ?? $row['name'] ?? 'Contabo '.$id),
            'ip' => $ip,
            'region' => (string) ($row['region'] ?? $row['dataCenter'] ?? ''),
            'status' => $status,
            'metadata' => [
                'product_type' => $row['productType'] ?? null,
                'image_id' => $row['imageId'] ?? null,
            ],
        ];
    }

    protected function accessToken(): ?string
    {
        $creds = $this->settings->getCredentials();
        if (! $this->settings->isProviderConfigured('contabo')) {
            return null;
        }

        $cacheKey = 'contabo_access_token_'.md5($creds['contabo_client_id'] ?? '');

        return Cache::remember($cacheKey, 840, function () use ($creds) {
            try {
                $response = \Illuminate\Support\Facades\Http::asForm()
                    ->timeout(30)
                    ->post(config('infrastructure.contabo.auth_url'), [
                        'grant_type' => 'password',
                        'client_id' => $creds['contabo_client_id'],
                        'client_secret' => $creds['contabo_client_secret'],
                        'username' => $creds['contabo_api_user'],
                        'password' => $creds['contabo_api_password'],
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json('access_token');
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
