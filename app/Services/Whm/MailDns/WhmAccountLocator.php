<?php

namespace App\Services\Whm\MailDns;

use App\Models\WhmAccount;
use Illuminate\Support\Collection;

/**
 * Resolves a CLI account reference to a model.
 *
 * Kept as its own seam so the mail-DNS command can be unit-tested without a database —
 * the project's Feature suite cannot run (four migrations query MySQL's
 * information_schema, so RefreshDatabase fails on sqlite).
 */
class WhmAccountLocator
{
    /**
     * Find by numeric id, cPanel username, or domain.
     */
    public function find(string $reference): ?WhmAccount
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        if (ctype_digit($reference)) {
            $byId = WhmAccount::query()->find((int) $reference);
            if ($byId !== null) {
                return $byId;
            }
        }

        return WhmAccount::query()
            ->where('username', $reference)
            ->orWhere('domain', strtolower($reference))
            ->first();
    }

    /**
     * @return Collection<int, WhmAccount>
     */
    public function syncable(): Collection
    {
        return WhmAccount::query()
            ->whereIn('status', ['active', 'suspended'])
            ->orderBy('domain')
            ->get();
    }
}
