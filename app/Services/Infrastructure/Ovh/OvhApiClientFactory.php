<?php

namespace App\Services\Infrastructure\Ovh;

use App\Services\Infrastructure\InfrastructureSettingsService;
use Ovh\Api;

class OvhApiClientFactory
{
    public function __construct(protected InfrastructureSettingsService $settings) {}

    public function make(): ?Api
    {
        $creds = $this->settings->getCredentials();
        $key = $creds['ovh_application_key'] ?? '';
        $secret = $creds['ovh_application_secret'] ?? '';
        $consumer = $creds['ovh_consumer_key'] ?? '';
        $endpoint = $creds['ovh_endpoint'] ?? 'ovh-eu';

        if ($key === '' || $secret === '' || $consumer === '') {
            return null;
        }

        return new Api($key, $secret, $endpoint, $consumer);
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function test(): array
    {
        $api = $this->make();
        if ($api === null) {
            return ['success' => false, 'message' => 'أكمل مفاتيح OVH (Application Key, Secret, Consumer Key)'];
        }

        try {
            $api->get('/auth/time');

            return ['success' => true, 'message' => 'الاتصال بـ OVHcloud ناجح'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
