<?php

namespace App\Services\Coolify\Wordpress\Domain;

use App\Contracts\Coolify\WordpressSiteDomainStrategy;
use App\Models\CoolifyWordpressSite;

class WordpressSiteDomainResolver
{
    public function __construct(
        protected PlatformSubdomainStrategy $platform,
        protected CustomDomainStrategy $custom
    ) {}

    public function for(CoolifyWordpressSite $site): WordpressSiteDomainStrategy
    {
        if ($site->isCustomDomain()) {
            return $this->custom;
        }

        return $this->platform;
    }

    public function forType(string $domainType): WordpressSiteDomainStrategy
    {
        if ($domainType === CoolifyWordpressSite::DOMAIN_TYPE_CUSTOM) {
            return $this->custom;
        }

        return $this->platform;
    }
}
