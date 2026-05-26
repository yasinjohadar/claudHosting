<?php

namespace App\Services\Coolify\Wordpress\Domain;

use App\Contracts\Coolify\WordpressSiteDomainStrategy;
use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\WordpressCloudflareService;

class PlatformSubdomainStrategy implements WordpressSiteDomainStrategy
{
    public function __construct(
        protected CoolifySettingsService $settings,
        protected WordpressCloudflareService $cloudflare
    ) {}

    public function type(): string
    {
        return CoolifyWordpressSite::DOMAIN_TYPE_PLATFORM;
    }

    public function buildPublicUrl(CoolifyWordpressSite $site): string
    {
        if (filled($site->public_url) && $site->isPlatformSubdomain()) {
            return rtrim((string) $site->public_url, '/');
        }

        return rtrim($this->settings->buildWordpressPublicUrl($site->slug), '/');
    }

    public function buildFilebrowserPublicUrl(CoolifyWordpressSite $site): ?string
    {
        if (! $this->settings->getWordpressFilebrowserEnabled()) {
            return null;
        }

        return rtrim($this->settings->buildWordpressFilebrowserPublicUrl($site->slug), '/');
    }

    public function applyDns(
        CoolifyWordpressSite $site,
        array $service,
        ?string $preset,
        ?callable $log
    ): array {
        $result = $this->cloudflare->applyForSite($site, $service, $preset, $log);

        if ($result['ok'] ?? false) {
            return ['ok' => true];
        }

        return [
            'ok' => false,
            'message' => $result['message'] ?? 'فشل ربط Cloudflare',
        ];
    }
}
