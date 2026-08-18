<?php

namespace Tests\Unit\Whm;

use App\Models\Invoice;
use App\Models\WhmAccount;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Locks the admin/client gating of the two shared panels. Every admin-only affordance
 * (renew form, admin invoice links, WHM refresh) must be absent by default so a client
 * page cannot leak it.
 */
class WhmPanelPartialsTest extends TestCase
{
    protected function account(): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 14;
        $account->username = 'rootdiplomas';
        $account->domain = 'diplomas.claudsoft.com';
        $account->package = 'package1';
        $account->status = 'active';
        $account->subscription_ends_at = Carbon::parse('2027-05-21');
        $account->last_renewed_at = Carbon::parse('2026-05-21 10:00:00');

        return $account;
    }

    protected function invoice(): Invoice
    {
        $invoice = new Invoice;
        $invoice->id = 7;
        $invoice->total = 100;
        $invoice->status = 'Unpaid';

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function subscription(array $params = []): string
    {
        return view('admin.whm.accounts.partials.subscription-panel', array_merge([
            'account' => $this->account(),
            'invoices' => collect(),
        ], $params))->render();
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function summary(array $params = []): string
    {
        return view('admin.whm.accounts.partials.account-summary', array_merge([
            'account' => $this->account(),
            'summary' => ['diskused' => '120M', 'disklimit' => 'unlimited', 'ip' => '159.195.108.28'],
            'summarySyncedAt' => '2026-08-18 11:01',
            'configured' => true,
            'sslBadge' => ['label' => 'صالحة', 'badge' => 'bg-success-transparent'],
        ], $params))->render();
    }

    public function test_subscription_panel_is_read_only_by_default(): void
    {
        $html = $this->subscription();

        $this->assertStringNotContainsString('renew', $html);
        $this->assertStringNotContainsString('_token', $html);
        $this->assertStringNotContainsString('/admin/invoices/', $html);
        $this->assertStringNotContainsString('<form', $html);

        // The read-only data is still there.
        $this->assertStringContainsString('2027-05-21', $html);
        $this->assertStringContainsString('package1', $html);
        $this->assertStringContainsString('نهاية الاشتراك', $html);
    }

    public function test_subscription_panel_renders_the_renew_form_when_allowed(): void
    {
        $html = $this->subscription([
            'canRenew' => true,
            'billing' => ['renewal_amount' => 150, 'subscription_years' => 1],
        ]);

        $this->assertStringContainsString('/admin/whm/accounts/14/renew', $html);
        $this->assertStringContainsString('_token', $html);
        $this->assertStringContainsString('150.00', $html);
        $this->assertStringContainsString('تجديد الاشتراك', $html);
    }

    public function test_terminated_accounts_never_get_the_renew_form(): void
    {
        $account = $this->account();
        $account->status = 'terminated';

        $html = $this->subscription(['account' => $account, 'canRenew' => true]);

        $this->assertStringNotContainsString('/renew', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_subscription_panel_links_invoices_through_the_given_route_name(): void
    {
        $html = $this->subscription([
            'invoices' => collect([$this->invoice()]),
            'invoiceRoute' => 'client.invoices.show',
        ]);

        $this->assertStringContainsString('/client/invoices/7', $html);
        $this->assertStringNotContainsString('/admin/invoices/', $html);
    }

    public function test_subscription_panel_omits_links_when_no_route_is_given(): void
    {
        $invoice = $this->invoice();
        $html = $this->subscription(['invoices' => collect([$invoice])]);

        $this->assertStringContainsString($invoice->invoice_number, $html);
        $this->assertStringNotContainsString('<a href', $html);
    }

    public function test_subscription_panel_hides_the_default_amount_when_no_billing_config(): void
    {
        $this->assertStringNotContainsString('مبلغ الفاتورة الافتراضي', $this->subscription());
        $this->assertStringContainsString(
            'مبلغ الفاتورة الافتراضي',
            $this->subscription(['billing' => ['renewal_amount' => 150]])
        );
    }

    /**
     * Markup only — the partial's inlined <style> block would otherwise match class-name
     * assertions and let a test pass on CSS instead of on rendered content.
     */
    protected function subscriptionMarkup(array $params = []): string
    {
        $html = $this->subscription($params);

        return substr($html, strpos($html, '</style>') + 8);
    }

    public function test_the_subscription_state_drives_the_tile_tone(): void
    {
        // Tone comes from the model's own badge, so colour cannot contradict the label.
        $expired = $this->account();
        $expired->subscription_ends_at = Carbon::parse('2020-01-01');
        $this->assertStringContainsString('whm-sub-tile--danger', $this->subscriptionMarkup(['account' => $expired]));

        $soon = $this->account();
        $soon->subscription_ends_at = Carbon::now()->addDays(10);
        $this->assertStringContainsString('whm-sub-tile--warning', $this->subscriptionMarkup(['account' => $soon]));

        // The default fixture ends in 2027.
        $this->assertStringContainsString('whm-sub-tile--success', $this->subscriptionMarkup());

        $undated = $this->account();
        $undated->subscription_ends_at = null;
        $this->assertStringContainsString('whm-sub-tile--secondary', $this->subscriptionMarkup(['account' => $undated]));
    }

    public function test_expired_and_remaining_days_read_differently(): void
    {
        $expired = $this->account();
        $expired->subscription_ends_at = Carbon::now()->subDays(425);
        $markup = $this->subscriptionMarkup(['account' => $expired]);

        $this->assertStringContainsString('425 يوم', $markup);
        $this->assertStringContainsString('مضت على انتهائه', $markup);
        $this->assertStringNotContainsString('حتى نهاية الاشتراك', $markup);

        $live = $this->subscriptionMarkup();
        $this->assertStringContainsString('حتى نهاية الاشتراك', $live);
        $this->assertStringNotContainsString('مضت على انتهائه', $live);
    }

    public function test_the_progress_bar_needs_a_real_start_date(): void
    {
        // The default fixture has last_renewed_at, so a bar is justified.
        $this->assertStringContainsString('whm-sub-progress__bar', $this->subscriptionMarkup());

        // No renewal and no join date means no honest start — so no bar rather than a guess.
        $unknownStart = $this->account();
        $unknownStart->last_renewed_at = null;
        $unknownStart->joined_at = null;
        $this->assertStringNotContainsString('whm-sub-progress__bar', $this->subscriptionMarkup(['account' => $unknownStart]));
    }

    public function test_the_progress_bar_is_clamped_to_100(): void
    {
        $expired = $this->account();
        $expired->last_renewed_at = Carbon::parse('2024-01-01');
        $expired->subscription_ends_at = Carbon::parse('2025-01-01');

        $this->assertStringContainsString('width: 100%', $this->subscriptionMarkup(['account' => $expired]));
    }

    public function test_the_paid_total_chip_only_appears_when_something_was_paid(): void
    {
        $paid = $this->invoice();
        $paid->status = 'Paid';
        $paid->total = 250;

        $withPaid = $this->subscriptionMarkup(['invoices' => collect([$paid])]);
        $this->assertStringContainsString('المدفوع', $withPaid);
        $this->assertStringContainsString('250.00', $withPaid);

        $unpaidOnly = $this->subscriptionMarkup(['invoices' => collect([$this->invoice()])]);
        $this->assertStringNotContainsString('المدفوع', $unpaidOnly);
    }

    public function test_each_invoice_renders_as_its_own_row(): void
    {
        $a = $this->invoice();
        $b = $this->invoice();
        $b->id = 8;

        $markup = $this->subscriptionMarkup(['invoices' => collect([$a, $b])]);

        $this->assertSame(2, substr_count($markup, 'whm-sub-invoice"'));
    }

    public function test_account_summary_omits_the_admin_refresh_form_when_can_refresh_is_false(): void
    {
        $html = $this->summary(['canRefresh' => false]);

        $this->assertStringNotContainsString('refresh-summary', $html);
        $this->assertStringNotContainsString('<form', $html);

        // The tiles still render.
        $this->assertStringContainsString('whm-stat-tile', $html);
        $this->assertStringContainsString('159.195.108.28', $html);
    }

    public function test_account_summary_keeps_the_refresh_form_by_default(): void
    {
        // Regression guard for the admin account page include.
        $html = $this->summary();

        $this->assertStringContainsString('/admin/whm/accounts/14/refresh-summary', $html);
        $this->assertStringContainsString('موارد WHM', $html);
    }

    public function test_account_summary_id_is_opt_in(): void
    {
        $this->assertStringContainsString('id="whm-summary-card"', $this->summary(['panelId' => 'whm-summary-card']));
        $this->assertStringNotContainsString('id=', $this->summary(['showTitle' => false]));
    }
}
