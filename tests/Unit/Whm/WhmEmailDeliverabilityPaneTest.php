<?php

namespace Tests\Unit\Whm;

use App\Models\WhmAccount;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Locks the multi-instance contract of the deliverability pane shell: no ids, every
 * hook a data-attribute, styles emitted once no matter how many panes render.
 */
class WhmEmailDeliverabilityPaneTest extends TestCase
{
    protected function account(int $id = 14): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = $id;
        $account->username = 'rootdiplomas';
        $account->domain = 'diplomas.claudsoft.com';
        $account->status = 'active';

        return $account;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function render(array $params = []): string
    {
        return view('admin.whm.accounts.partials.email-deliverability-tab', array_merge([
            'account' => $this->account(),
            'embedded' => true,
        ], $params))->render();
    }

    public function test_pane_exposes_data_hooks_and_no_ids(): void
    {
        $html = $this->render(['url' => '/x/y']);

        $this->assertStringContainsString('data-whm-mail-pane', $html);
        $this->assertStringContainsString('data-whm-mail-url="/x/y"', $html);
        $this->assertStringContainsString('data-whm-mail-body', $html);
        $this->assertStringContainsString('data-whm-mail-refresh', $html);
        $this->assertStringContainsString('data-whm-mail-synced', $html);
        $this->assertStringContainsString('data-whm-mail-loading', $html);

        // The old singleton hooks must be gone, or N panes would collide.
        $this->assertStringNotContainsString('id="whm-mail-', $html);
    }

    public function test_two_panes_coexist_and_styles_are_emitted_once(): void
    {
        // Blade::render is one top-level render, which is what an accordion page (or any
        // page including the partial N times) actually does. @once state is per top-level
        // render, so this is the scenario that must collapse the styles.
        $both = Blade::render(
            '@include("admin.whm.accounts.partials.email-deliverability-tab", ["account" => $a, "embedded" => true, "url" => "/a"])'
            .'@include("admin.whm.accounts.partials.email-deliverability-tab", ["account" => $b, "embedded" => true, "url" => "/b"])',
            ['a' => $this->account(14), 'b' => $this->account(15)]
        );

        $this->assertSame(2, substr_count($both, 'data-whm-mail-pane'));
        $this->assertStringContainsString('data-whm-mail-url="/a"', $both);
        $this->assertStringContainsString('data-whm-mail-url="/b"', $both);

        // Exactly two <style> blocks for N panes: whm-panel-styles + copy-email-styles,
        // each emitted once. (.whm-mail-value appears twice *within* its block — base
        // rule plus the .dark override — so count a selector that is unique instead.)
        $this->assertSame(2, substr_count($both, '<style>'));
        $this->assertSame(1, substr_count($both, '.whm-mail-check-label {'));
        $this->assertSame(1, substr_count($both, '.whm-copy-email {'));
    }

    public function test_a_standalone_fragment_carries_its_own_styles(): void
    {
        // Counterpart to the test above: rendered on its own (not nested in a page), the
        // partial must still ship the CSS it needs — @once state is per top-level render.
        $html = $this->render();

        $this->assertSame(1, substr_count($html, '.whm-mail-check-label {'));
        $this->assertSame(1, substr_count($html, '.whm-copy-email {'));
    }

    public function test_url_defaults_to_the_admin_route(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('/admin/whm/accounts/14/email-deliverability', $html);
    }

    public function test_loading_indicator_starts_hidden(): void
    {
        $html = $this->render();

        // A visible spinner would sit forever in every never-expanded accordion card.
        $this->assertMatchesRegularExpression('/<div class="[^"]*\bd-none\b[^"]*" data-whm-mail-loading>/', $html);
    }

    public function test_auto_flag_is_surfaced_to_the_script(): void
    {
        $this->assertStringContainsString('data-whm-mail-auto="1"', $this->render(['auto' => true]));
        $this->assertStringNotContainsString('data-whm-mail-auto', $this->render());
    }

    public function test_title_is_optional_but_the_read_only_badge_is_not(): void
    {
        $withTitle = $this->render();
        $this->assertStringContainsString('قابلية تسليم البريد', $withTitle);

        $withoutTitle = $this->render(['showTitle' => false]);
        $this->assertStringContainsString('قراءة فقط', $withoutTitle);
    }
}
