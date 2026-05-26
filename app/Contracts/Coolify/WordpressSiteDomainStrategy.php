<?php

namespace App\Contracts\Coolify;

use App\Models\CoolifyWordpressSite;

interface WordpressSiteDomainStrategy
{
    public function type(): string;

    public function buildPublicUrl(CoolifyWordpressSite $site): string;

    public function buildFilebrowserPublicUrl(CoolifyWordpressSite $site): ?string;

    /**
     * @param  array<string, mixed>  $service
     * @return array{ok: bool, message?: string}
     */
    public function applyDns(
        CoolifyWordpressSite $site,
        array $service,
        ?string $preset,
        ?callable $log
    ): array;
}
