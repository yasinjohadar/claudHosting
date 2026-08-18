<?php

namespace Tests\Unit\Whm;

use App\Http\Controllers\Client\ClientWhmAccountController;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\Client\ClientBillingService;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

/**
 * The client-facing account page and its deliverability endpoint. Renders the view
 * directly and calls the controller with mocked services — no DB, because the project's
 * Feature suite cannot run here (four migrations query MySQL's information_schema, so
 * RefreshDatabase dies on sqlite).
 */
class ClientWhmAccountPageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function account(string $status = 'active'): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 14;
        $account->username = 'rootdiplomas';
        $account->domain = 'diplomas.claudsoft.com';
        $account->package = 'package1';
        $account->email = 'yasinjohadar@gmail.com';
        $account->status = $status;
        $account->subscription_ends_at = Carbon::parse('2027-05-21');

        return $account;
    }

    /**
     * Renders only the page's own `content` section. The client layout re-includes the
     * admin header/sidebar partials, whose markup would swamp these assertions (and does
     * legitimately contain /admin/ links).
     *
     * @param  array<string, mixed>  $params
     */
    protected function page(array $params = []): string
    {
        // The layout's header reads Auth::user(), so a user must be present even though
        // only the content section is rendered.
        Auth::setUser(new User(['name' => 'client', 'email' => 'c@example.com']));

        return view('client.pages.hosting.show', array_merge([
            'account' => $this->account(),
            'configured' => true,
            'summary' => ['diskused' => '120M', 'disklimit' => 'unlimited', 'ip' => '159.195.108.28'],
            'summarySyncedAt' => '2026-08-18 11:01',
            'sslBadge' => ['label' => 'صالحة', 'badge' => 'bg-success-transparent'],
            'invoices' => collect(),
        ], $params))->renderSections()['content'];
    }

    public function test_page_renders_the_three_tabs(): void
    {
        $html = $this->page();

        foreach (['#client-whm-tab-overview', '#client-whm-tab-mail', '#client-whm-tab-resources'] as $target) {
            $this->assertStringContainsString('data-bs-target="'.$target.'"', $html);
        }
        $this->assertStringContainsString('نظرة عامة', $html);
        $this->assertStringContainsString('البريد', $html);
        $this->assertStringContainsString('موارد', $html);
    }

    public function test_mail_pane_points_at_the_client_endpoint_not_the_admin_one(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('data-whm-mail-url="'.url('/client/hosting/14/email-deliverability').'"', $html);
        $this->assertStringNotContainsString('/admin/', $html);
    }

    public function test_the_client_can_install_its_own_records_through_client_routes_only(): void
    {
        $html = $this->page();

        // The client is allowed to write DNS, but only ever via its own endpoints — the
        // admin routes sit behind admin.panel and must not appear here at all.
        $this->assertStringContainsString('data-whm-dns-open', $html);
        $this->assertStringContainsString('/client/hosting/14/mail-dns/preview', $html);
        $this->assertStringContainsString('/client/hosting/14/mail-dns/apply', $html);
        $this->assertStringNotContainsString('/admin/whm/accounts', $html);
        $this->assertStringNotContainsString('قراءة فقط', $html);
        $this->assertStringContainsString('قابل للتركيب', $html);
    }

    public function test_page_exposes_no_admin_only_affordances(): void
    {
        $html = $this->page();

        // No renew, no package change, no terminate, no WHM resource refresh, no status toggle.
        $this->assertStringNotContainsString('/renew', $html);
        $this->assertStringNotContainsString('refresh-summary', $html);
        $this->assertStringNotContainsString('change-package', $html);
        $this->assertStringNotContainsString('toggle-status', $html);
        $this->assertStringNotContainsString('_token', $html);
        $this->assertStringNotContainsString('مبلغ الفاتورة الافتراضي', $html);
    }

    public function test_mail_tab_is_hidden_when_whm_is_unconfigured(): void
    {
        $html = $this->page(['configured' => false]);

        $this->assertStringNotContainsString('data-bs-target="#client-whm-tab-mail"', $html);
        $this->assertStringNotContainsString('data-whm-mail-pane', $html);
        // An inline notice replaces the empty tab.
        $this->assertStringContainsString('خدمة الاستضافة غير متصلة حاليًا', $html);
    }

    public function test_suspended_account_gets_a_banner_and_no_cpanel_button(): void
    {
        $html = $this->page(['account' => $this->account('suspended')]);

        $this->assertStringContainsString('الحساب معلّق حاليًا', $html);
        $this->assertStringNotContainsString('/client/hosting/14/cpanel', $html);
        // Deliverability is still offered — DNS records stay valid and actionable.
        $this->assertStringContainsString('data-whm-mail-pane', $html);
    }

    public function test_active_account_gets_the_cpanel_button(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('/client/hosting/14/cpanel', $html);
        $this->assertStringContainsString('فتح cPanel', $html);
    }

    protected function controller(WhmEmailDeliverabilityService $deliverability): ClientWhmAccountController
    {
        $accounts = Mockery::mock(WhmAccountService::class);
        $accounts->shouldReceive('userOwnsAccount')->andReturnTrue();

        return new ClientWhmAccountController(
            $accounts,
            Mockery::mock(WhmApiService::class),
            $deliverability,
            Mockery::mock(ClientBillingService::class),
            Mockery::mock(WhmMailDnsSyncService::class)
        );
    }

    public function test_endpoint_matches_the_admin_json_contract(): void
    {
        $deliverability = Mockery::mock(WhmEmailDeliverabilityService::class);
        $deliverability->shouldReceive('forAccount')
            ->once()
            ->with(Mockery::type(WhmAccount::class), true)
            ->andReturn([
                'success' => true,
                'configured' => true,
                'available' => true,
                'message' => 'تم جلب بيانات البريد',
                'fetched_at' => '2026-08-18T11:01:00+00:00',
                'fetched_at_human' => '2026-08-18 11:01',
                'server' => ['hostname' => 'server.claudsoft.com', 'ip' => '1.2.3.4', 'ptr' => null, 'ptr_state' => 'unknown'],
                'domains' => [],
                'warnings' => [],
            ]);

        // The ownership guard needs an authenticated user; a terminated-account 404 and a
        // 403 for a non-owner are enforced by authorizeAccount(), which is exercised in
        // the browser QA pass (it needs a real session + DB).
        Auth::setUser(new User(['name' => 'client']));
        $account = $this->account();
        $account->user_id = 1;

        $response = $this->controller($deliverability)->emailDeliverability(
            Request::create('/client/hosting/14/email-deliverability?fresh=1', 'GET'),
            $account
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(['success', 'message', 'fetched_at_human', 'html'], array_keys($payload));
        $this->assertTrue($payload['success']);
        $this->assertSame('2026-08-18 11:01', $payload['fetched_at_human']);
    }
}
