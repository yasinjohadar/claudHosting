<?php

namespace Tests\Unit\Whm;

use App\Models\WhmAccount;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Locks the N-item accordion markup used on both admin client pages: unique paired ids,
 * nothing pre-expanded (a pre-expanded item fires no show.bs.collapse, so its pane would
 * never load), and one independent deliverability pane per eligible account.
 */
class WhmAccountsAccordionTest extends TestCase
{
    protected function account(int $id, string $status = 'active', ?string $endsAt = '2027-05-21'): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = $id;
        $account->username = 'user'.$id;
        $account->domain = 'site'.$id.'.com';
        $account->package = 'package1';
        $account->status = $status;
        $account->subscription_ends_at = $endsAt ? Carbon::parse($endsAt) : null;

        return $account;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function render(array $params = []): string
    {
        return view('admin.whm.accounts.partials.accounts-accordion', array_merge([
            'accounts' => collect([$this->account(14), $this->account(15), $this->account(16)]),
            'configured' => true,
        ], $params))->render();
    }

    public function test_one_item_per_account_with_paired_aria_and_shared_parent(): void
    {
        $html = $this->render();

        $this->assertSame(3, substr_count($html, 'accordion-item'));
        $this->assertSame(3, substr_count($html, 'data-bs-parent="#whm-accounts-accordion"'));
        $this->assertSame(3, substr_count($html, 'aria-expanded="false"'));

        // Nothing pre-expanded: no .show on any panel, every button starts collapsed.
        $this->assertSame(3, substr_count($html, 'class="accordion-collapse collapse"'));
        $this->assertSame(3, substr_count($html, 'accordion-button collapsed'));

        $this->assertStringContainsString('id="whm-accounts-accordion-14"', $html);
        $this->assertStringContainsString('aria-controls="whm-accounts-accordion-14"', $html);
        $this->assertStringContainsString('id="whm-accounts-accordion-14-heading"', $html);
        $this->assertStringContainsString('aria-labelledby="whm-accounts-accordion-14-heading"', $html);
    }

    public function test_accordion_id_is_parameterized(): void
    {
        $html = $this->render(['accordionId' => 'user-whm-accounts-accordion']);

        $this->assertSame(3, substr_count($html, 'data-bs-parent="#user-whm-accounts-accordion"'));
        $this->assertStringContainsString('id="user-whm-accounts-accordion-15"', $html);
        $this->assertStringNotContainsString('whm-accounts-accordion-15"', str_replace('user-whm-accounts-accordion-15"', '', $html));
    }

    public function test_one_mail_pane_per_eligible_account(): void
    {
        $html = $this->render();

        $this->assertSame(3, substr_count($html, 'data-whm-mail-pane'));
        foreach ([14, 15, 16] as $id) {
            $this->assertStringContainsString("/admin/whm/accounts/{$id}/email-deliverability", $html);
        }
    }

    public function test_terminated_and_unconfigured_accounts_get_no_mail_pane(): void
    {
        $withTerminated = $this->render([
            'accounts' => collect([$this->account(14), $this->account(15), $this->account(16, 'terminated')]),
        ]);
        $this->assertSame(2, substr_count($withTerminated, 'data-whm-mail-pane'));
        $this->assertStringContainsString('الحساب محذوف — لا بيانات بريد.', $withTerminated);

        $unconfigured = $this->render(['configured' => false]);
        $this->assertSame(0, substr_count($unconfigured, 'data-whm-mail-pane'));
        $this->assertStringContainsString('إعدادات WHM غير مكتملة', $unconfigured);
        // No live-API affordances either.
        $this->assertStringNotContainsString('/cpanel', $unconfigured);
    }

    public function test_header_shows_subscription_fields(): void
    {
        $html = $this->render();
        $this->assertStringContainsString('ينتهي 2027-05-21', $html);
        $this->assertStringContainsString('package1', $html);
        $this->assertStringContainsString('site14.com', $html);

        $noDate = $this->render(['accounts' => collect([$this->account(14, 'active', null)])]);
        $this->assertStringContainsString('لم يُضبط تاريخ نهاية الاشتراك', $noDate);
    }

    public function test_variant_switches_only_the_badge_and_button_classes(): void
    {
        // variant controls exactly two things: the ACCOUNT-STATUS badge class and the two
        // action-button classes. The SUBSCRIPTION badge (subscription_status_badge) is
        // deliberately Bootstrap in both variants, so assert on the status label markup
        // rather than on the bare colour class, which both badges share.
        $domain = $this->render(['variant' => 'domain']);
        $this->assertStringContainsString('domain-status-badge domain-status-badge--active">نشط</span>', $domain);
        $this->assertStringContainsString('domain-action-btn', $domain);
        $this->assertStringNotContainsString('badge bg-success-transparent">نشط', $domain);

        $plain = $this->render(['variant' => 'plain']);
        $this->assertStringContainsString('badge bg-success-transparent">نشط</span>', $plain);
        $this->assertStringNotContainsString('domain-status-badge', $plain);
        $this->assertStringNotContainsString('domain-action-btn', $plain);

        // The subscription badge is shared by design.
        $this->assertStringContainsString('ساري', $domain);
        $this->assertStringContainsString('ساري', $plain);

        // Structure is identical either way.
        $this->assertSame(
            substr_count($domain, 'accordion-item'),
            substr_count($plain, 'accordion-item')
        );
    }

    public function test_renew_form_is_off_by_default_in_the_accordion(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('/renew', $html);
        $this->assertStringNotContainsString('_token', $html);
    }

    public function test_empty_account_list_renders_the_empty_state(): void
    {
        $html = $this->render(['accounts' => collect()]);

        $this->assertStringContainsString('لا توجد حسابات مرتبطة', $html);
        $this->assertSame(0, substr_count($html, 'accordion-item'));
        $this->assertSame(0, substr_count($html, 'data-whm-mail-pane'));
    }
}
