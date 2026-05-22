<?php

namespace App\Console\Commands;

use App\Models\WhmAccount;
use App\Services\Whm\WhmSubscriptionBillingService;
use Illuminate\Console\Command;

class BackfillWhmSubscriptions extends Command
{
    protected $signature = 'whm:backfill-subscriptions';

    protected $description = 'Set subscription_ends_at from joined_at (+1 year) for WHM accounts missing end date';

    public function handle(WhmSubscriptionBillingService $billing): int
    {
        $count = 0;

        WhmAccount::query()
            ->whereNotNull('joined_at')
            ->whereNull('subscription_ends_at')
            ->chunkById(100, function ($accounts) use ($billing, &$count) {
                foreach ($accounts as $account) {
                    $billing->ensureSubscriptionDates($account);
                    $count++;
                }
            });

        $this->info("Updated subscription end dates for {$count} account(s).");

        return self::SUCCESS;
    }
}
