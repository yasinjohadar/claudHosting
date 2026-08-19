<?php

namespace App\Console\Commands;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhmAccount;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Support\InternationalPhoneDigits;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp reminders before a hosting subscription expires.
 *
 * New behaviour, not a rewrite: nothing in this application sent expiry reminders before, so
 * it is deliberately opt-in — it sends nothing unless the `subscription_expiring` template is
 * active, and --dry-run shows exactly who would be contacted.
 */
class SendSubscriptionExpiryRemindersCommand extends Command
{
    protected $signature = 'whatsapp:send-expiry-reminders
                            {--days=* : عدد الأيام قبل الانتهاء (الافتراضي: 7 و 1)}
                            {--dry-run : عرض المستلمين بلا إرسال}
                            {--force : إعادة الإرسال حتى لو أُرسل تنبيه اليوم لنفس الحساب}';

    protected $description = 'إرسال تنبيهات واتساب قبل انتهاء اشتراك الاستضافة (يستخدم قالب subscription_expiring)';

    /** One reminder per account per threshold per day, so a re-run does not spam customers. */
    private const SENT_CACHE_PREFIX = 'wa_expiry_reminder:';

    public function handle(SendWhatsAppMessage $sender, WhatsAppSettingsService $settings): int
    {
        if (! ($settings->getSettings()['whatsapp_enabled'] ?? false)) {
            $this->warn('الواتساب غير مفعّل في الإعدادات — لم يُرسل شيء.');

            return self::SUCCESS;
        }

        $template = WhatsAppMessageTemplate::findBySlug(WhatsAppMessageTemplate::SLUG_SUBSCRIPTION_EXPIRING);
        if ($template === null) {
            // No hardcoded fallback on purpose: this is a new outbound message, and inventing
            // wording nobody approved is not something a scheduled job should do silently.
            $this->warn('قالب «subscription_expiring» غير موجود أو معطّل — لم يُرسل شيء. فعّله من صفحة قوالب الواتساب.');

            return self::SUCCESS;
        }

        $thresholds = $this->thresholds();
        $this->line('الأيام المستهدفة: '.implode('، ', $thresholds));

        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->accountsExpiringIn($thresholds) as $account) {
            $days = $account->subscription_days_remaining;
            $client = $account->client;
            $phone = $client !== null ? InternationalPhoneDigits::forUser($client) : null;

            if ($phone === null) {
                $this->line(sprintf('  – %-28s (%2d يوم) — لا رقم صالح', $account->domain, $days));
                $skipped++;

                continue;
            }

            $cacheKey = self::SENT_CACHE_PREFIX.$account->id.':'.$days.':'.now()->toDateString();
            if (! $this->option('force') && Cache::has($cacheKey)) {
                $skipped++;

                continue;
            }

            $text = trim($template->render([], [
                'user' => $client,
                'customer' => $account->customer,
                'whmAccount' => $account,
            ]));

            if ($text === '') {
                $this->warn(sprintf('  – %s — القالب أنتج نصاً فارغاً، تم التخطي', $account->domain));
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('  – %-28s (%2d يوم) → %s', $account->domain, $days, InternationalPhoneDigits::toDisplay($phone)));
                $sent++;

                continue;
            }

            try {
                $sender->sendTextSync(InternationalPhoneDigits::toDisplay($phone), $text, false);
                Cache::put($cacheKey, true, now()->addDay());
                $sent++;
                $this->info(sprintf('  ✓ %-28s (%2d يوم)', $account->domain, $days));
            } catch (\Throwable $e) {
                // Keep going: one unreachable number must not stop the rest of the reminders.
                $failed++;
                $this->error(sprintf('  ✗ %-28s — %s', $account->domain, $e->getMessage()));
                Log::channel('whatsapp')->error('Expiry reminder failed.', [
                    'whm_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->line($dryRun
            ? sprintf('معاينة: %d سيُرسل لهم، %d تم تخطيهم. لم تُرسل أي رسالة.', $sent, $skipped)
            : sprintf('أُرسلت %d، تم تخطي %d، فشلت %d.', $sent, $skipped, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function thresholds(): array
    {
        $given = array_filter(array_map('intval', (array) $this->option('days')), static fn (int $d): bool => $d >= 0);

        $thresholds = $given !== [] ? $given : [7, 1];

        rsort($thresholds);

        return array_values(array_unique($thresholds));
    }

    /**
     * Accounts whose remaining days match one of the thresholds exactly.
     *
     * Exactly, not "less than": a threshold of 7 must fire once, seven days out, rather than
     * every day of the final week.
     *
     * @param  list<int>  $thresholds
     * @return \Illuminate\Support\Collection<int, WhmAccount>
     */
    private function accountsExpiringIn(array $thresholds)
    {
        $dates = array_map(static fn (int $days): string => now()->addDays($days)->toDateString(), $thresholds);

        return WhmAccount::query()
            ->whereNotNull('subscription_ends_at')
            ->whereNotNull('user_id')
            // Terminated accounts have no subscription left to renew.
            ->where('status', '!=', 'terminated')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(subscription_ends_at)'), $dates)
            ->with(['client', 'customer'])
            ->orderBy('subscription_ends_at')
            ->get();
    }
}
