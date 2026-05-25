<?php

namespace App\Services\Coolify;

use App\Models\CoolifyWordpressSite;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class WordpressSiteAccess
{
    public function isAdmin(?User $user): bool
    {
        return $user !== null && $user->isAdminPanelUser();
    }

    public function canAccess(?User $user, CoolifyWordpressSite $site): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return (int) $site->user_id === (int) $user->id;
    }

    public function assertCanAccess(?User $user, CoolifyWordpressSite $site): void
    {
        if (! $this->canAccess($user, $site)) {
            throw new AuthorizationException('لا يمكنك الوصول إلى هذا الموقع.');
        }
    }

    public function resolveSite(string $uuid, ?User $user = null): CoolifyWordpressSite
    {
        $site = CoolifyWordpressSite::query()->where('uuid', $uuid)->firstOrFail();
        $this->assertCanAccess($user ?? auth()->user(), $site);

        return $site;
    }
}
