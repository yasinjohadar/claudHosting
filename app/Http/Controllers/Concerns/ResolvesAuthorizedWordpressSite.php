<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\WordpressSiteAccess;

trait ResolvesAuthorizedWordpressSite
{
    protected function resolveAuthorizedWordpressSite(string $uuid): CoolifyWordpressSite
    {
        return app(WordpressSiteAccess::class)->resolveSite($uuid, auth()->user());
    }
}
