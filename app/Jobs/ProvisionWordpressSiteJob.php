<?php

namespace App\Jobs;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\WordpressSiteProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProvisionWordpressSiteJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $siteId)
    {
        $this->onQueue(app(CoolifySettingsService::class)->getWordpressProvisionQueue());
    }

    public function handle(WordpressSiteProvisioningService $provisioning): void
    {
        $site = CoolifyWordpressSite::query()->find($this->siteId);

        if (! $site) {
            return;
        }

        $settings = app(CoolifySettingsService::class);
        Log::info('WordPress site provisioning started', [
            'site_id' => $this->siteId,
            'slug' => $site->slug,
            'service_type' => $settings->getWordpressServiceType(),
        ]);

        try {
            $provisioning->provision($site);
        } catch (\Throwable $e) {
            Log::error('WordPress site provisioning failed', [
                'site_id' => $this->siteId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
