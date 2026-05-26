<?php

namespace App\Services\Coolify\Wordpress\Domain;

use App\Contracts\Coolify\WordpressSiteDomainStrategy;
use App\Models\CoolifyWordpressSite;
use App\Support\WordpressDomainHelper;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\WordpressCustomDomainCloudflareService;

class CustomDomainStrategy implements WordpressSiteDomainStrategy
{
    public function __construct(
        protected CoolifySettingsService $settings,
        protected WordpressCustomDomainCloudflareService $cloudflare
    ) {}

    public function type(): string
    {
        return CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM;
    }

    public function buildPublicUrl(CoolifyWordpressSite $site): string
    {
        if (filled($site->public_url)) {
            return rtrim((string) $site->public_url, '/');
        }

        if (filled($site->primary_hostname)) {
            return WordpressDomainHelper::buildPublicUrl((string) $site->primary_hostname);
        }

        return '';
    }

    public function buildFilebrowserPublicUrl(CoolifyWordpressSite $site): ?string
    {
        if (! $this->settings->getWordpressFilebrowserEnabled()) {
            return null;
        }

        $apex = (string) ($site->custom_domain_apex ?? '');
        if ($apex === '') {
            return null;
        }

        return rtrim(WordpressDomainHelper::filebrowserPublicUrl($apex), '/');
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
            'message' => $result['message'] ?? 'فشل ربط DNS للدومين المستقل',
        ];
    }
}
