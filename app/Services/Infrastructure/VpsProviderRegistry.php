<?php

namespace App\Services\Infrastructure;

use App\Contracts\VpsLifecycleContract;
use App\Contracts\VpsProviderContract;
use InvalidArgumentException;

class VpsProviderRegistry
{
    public function __construct(
        protected ContaboVpsProvider $contabo,
        protected HetznerCloudVpsProvider $hetzner,
        protected DigitalOceanVpsProvider $digitalocean,
        protected OvhVpsProvider $ovh,
        protected NetcupVpsProvider $netcup,
        protected InfrastructureSettingsService $settings
    ) {}

    public function get(string $provider): VpsProviderContract
    {
        return match ($provider) {
            'contabo' => $this->contabo,
            'hetzner' => $this->hetzner,
            'digitalocean' => $this->digitalocean,
            'ovh' => $this->ovh,
            'netcup' => $this->netcup,
            default => throw new InvalidArgumentException('مزود غير مدعوم: '.$provider),
        };
    }

    public function lifecycle(string $provider): ?VpsLifecycleContract
    {
        $instance = $this->get($provider);

        return $instance instanceof VpsLifecycleContract ? $instance : null;
    }

    /**
     * @return array<string, VpsProviderContract>
     */
    public function configuredProviders(): array
    {
        $out = [];
        foreach (array_keys(\App\Models\VpsServer::PROVIDERS) as $key) {
            if ($this->settings->isProviderConfigured($key)) {
                $out[$key] = $this->get($key);
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public function configuredProviderKeys(): array
    {
        return array_keys($this->configuredProviders());
    }
}
