<?php

namespace App\Support;

use App\Models\CoolifyWordpressSite;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the optional `visible` condition on a client-portal menu item.
 *
 * The conditions are named with strings rather than closures on purpose: `config/*.php`
 * has to survive `php artisan config:cache`, which serialises the array — and a Closure
 * cannot be serialised. So the config declares WHAT to check and this class knows HOW.
 *
 * Results are memoised per instance, so a caller that resolves this once and reuses it
 * across the menu loop pays for each distinct check only once per request.
 */
class ClientMenuVisibility
{
    /** @var array<string, bool> */
    protected array $memo = [];

    /**
     * An item with no condition is always visible.
     */
    public function passes(?string $condition): bool
    {
        $condition = trim((string) $condition);

        if ($condition === '') {
            return true;
        }

        if (! array_key_exists($condition, $this->memo)) {
            $this->memo[$condition] = $this->evaluate($condition);
        }

        return $this->memo[$condition];
    }

    protected function evaluate(string $condition): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return match ($condition) {
            // The sidebar's WordPress link opens the Coolify-managed sites list, so that
            // is what has to exist. cPanel/WHM WordPress installs are reached from the
            // hosting account page instead and are deliberately not counted here.
            'has_wordpress_sites' => $this->hasCoolifyWordpressSites($user->getKey()),

            // An unrecognised condition leaves the item visible: a typo in the config
            // should not silently remove navigation the user needs.
            default => true,
        };
    }

    /**
     * Every condition that touches the database goes through a guard like this.
     *
     * The sidebar renders on every single page, so an unavailable database here would
     * throw out of the layout and take down the whole portal — not just hide a link.
     * Both failure modes therefore fail OPEN: showing a link to an empty page is a far
     * smaller problem than a blank navigation or a 500.
     */
    protected function hasCoolifyWordpressSites(int|string|null $userId): bool
    {
        if ($userId === null) {
            return true;
        }

        try {
            return CoolifyWordpressSite::query()->where('user_id', $userId)->exists();
        } catch (\Throwable $e) {
            Log::warning('Client menu visibility check failed', [
                'condition' => 'has_wordpress_sites',
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }
}
