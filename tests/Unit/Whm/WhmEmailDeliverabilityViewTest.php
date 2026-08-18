<?php

namespace Tests\Unit\Whm;

use App\Http\Controllers\Admin\Whm\WhmAccountController;
use App\Models\WhmAccount;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use App\Services\Whm\WhmServerStatusService;
use App\Services\Whm\WhmSettingsService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * Covers the Blade fragment and the controller's JSON contract without touching the
 * database. (The project's Feature suite cannot run here: four migrations query
 * MySQL's information_schema, so RefreshDatabase fails on sqlite for every test.)
 */
class WhmEmailDeliverabilityViewTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** A ~400-char DKIM value — the case that must wrap rather than be truncated. */
    protected function longDkim(): string
    {
        return 'v=DKIM1; k=rsa; p='.str_repeat('MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyw', 9);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function check(array $overrides = []): array
    {
        return array_merge([
            'key' => 'dkim',
            'label' => 'DKIM',
            'context' => null,
            'state' => 'problem',
            'state_label' => 'يحتاج إصلاح',
            'badge' => 'bg-danger-transparent',
            'raw_state' => 'MISSING',
            'record_type' => 'TXT',
            'expected_name' => 'default._domainkey.diplomas.claudsoft.com',
            'expected_value' => $this->longDkim(),
            'current_value' => null,
            'matches' => null,
            'message' => null,
            'source' => 'api',
            'expected_source' => 'api',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function data(array $overrides = []): array
    {
        return array_merge([
            'success' => true,
            'configured' => true,
            'available' => true,
            'message' => 'تم جلب بيانات البريد',
            'fetched_at' => '2026-08-18T11:01:00+00:00',
            'fetched_at_human' => '2026-08-18 11:01',
            'server' => [
                'hostname' => 'server.claudsoft.com',
                'ip' => '159.195.108.28',
                'ptr' => 'server.claudsoft.com',
                'ptr_state' => 'ok',
            ],
            'domains' => [[
                'domain' => 'diplomas.claudsoft.com',
                'type' => 'main',
                'is_main' => true,
                'helo' => 'server.claudsoft.com',
                'ip' => '159.195.108.28',
                'overall' => 'problem',
                'message' => null,
                'checks' => [$this->check()],
            ]],
            'warnings' => [],
        ], $overrides);
    }

    protected function account(): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 14;
        $account->username = 'rootdiplomas';
        $account->domain = 'diplomas.claudsoft.com';
        $account->status = 'active';

        return $account;
    }

    protected function render(array $data): string
    {
        return view('admin.whm.accounts.partials.email-deliverability-body', [
            'account' => $this->account(),
            'data' => $data,
        ])->render();
    }

    public function test_body_renders_copy_buttons_for_every_value(): void
    {
        $html = $this->render($this->data());

        // One copy button per rendered value: expected_name + expected_value,
        // plus the domain header and the three server tiles.
        $this->assertSame(6, substr_count($html, 'whm-copy-email'));
        $this->assertStringContainsString('data-copy="'.e($this->longDkim()).'"', $html);
        $this->assertStringContainsString('data-copy-msg=', $html);
        $this->assertStringContainsString('whm-mail-value', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function test_long_dkim_value_is_rendered_in_full_not_truncated(): void
    {
        $html = $this->render($this->data());
        $long = $this->longDkim();

        $this->assertGreaterThan(400, strlen($long));
        // The full value must reach both the visible span and the clipboard payload.
        $this->assertSame(2, substr_count($html, e($long)));
        // .whm-email-text is the 200px-ellipsis class from the index page; it must not be used here.
        $this->assertStringNotContainsString('whm-email-text', $html);
    }

    public function test_state_badges_and_labels_are_rendered(): void
    {
        $html = $this->render($this->data());

        $this->assertStringContainsString('bg-danger-transparent', $html);
        $this->assertStringContainsString('يحتاج إصلاح', $html);
        $this->assertStringContainsString('title="MISSING"', $html);
        $this->assertStringContainsString('TXT', $html);
        $this->assertStringContainsString('رئيسي', $html);
    }

    public function test_derived_and_zone_provenance_are_labelled_per_value(): void
    {
        $data = $this->data();
        $data['domains'][0]['checks'] = [$this->check([
            'key' => 'spf',
            'label' => 'SPF',
            'expected_value' => 'v=spf1 +mx +a +ip4:159.195.108.28 ~all',
            'expected_source' => 'derived',
            'current_value' => 'v=spf1 +mx ~all',
            'source' => 'zone',
            'matches' => false,
        ])];

        $html = $this->render($data);

        $this->assertStringContainsString('مقترح محلياً', $html);
        $this->assertStringContainsString('من منطقة DNS', $html);
        $this->assertStringContainsString('لا يطابق الموصى به', $html);
    }

    public function test_unconfigured_state_renders_a_warning_and_no_tiles(): void
    {
        $html = $this->render($this->data([
            'success' => false,
            'configured' => false,
            'message' => 'إعدادات WHM غير مكتملة',
            'domains' => [],
        ]));

        $this->assertStringContainsString('alert-warning', $html);
        $this->assertStringContainsString('إعدادات WHM غير مكتملة', $html);
        $this->assertStringNotContainsString('whm-mail-domain-card', $html);
    }

    public function test_empty_domain_list_renders_the_empty_state(): void
    {
        $html = $this->render($this->data([
            'success' => false,
            'message' => 'تعذّر جلب بيانات البريد من WHM',
            'domains' => [],
        ]));

        $this->assertStringContainsString('fe fe-mail', $html);
        $this->assertStringContainsString('تعذّر جلب بيانات البريد من WHM', $html);
    }

    public function test_warnings_are_rendered(): void
    {
        $html = $this->render($this->data([
            'warnings' => ['وحدة EmailAuth غير متاحة على هذا السيرفر — تم الاعتماد على قراءة منطقة DNS'],
        ]));

        $this->assertStringContainsString('وحدة EmailAuth غير متاحة', $html);
    }

    public function test_a_domain_with_no_checks_still_renders_its_message(): void
    {
        $data = $this->data();
        $data['domains'][0]['checks'] = [];
        $data['domains'][0]['message'] = 'انتهت المدة المخصصة للجلب — اضغط تحديث';

        $html = $this->render($data);

        $this->assertStringContainsString('انتهت المدة المخصصة للجلب', $html);
        $this->assertStringContainsString('لا فحوصات متاحة لهذا النطاق', $html);
    }

    public function test_values_are_html_escaped(): void
    {
        $data = $this->data();
        $data['domains'][0]['checks'] = [$this->check([
            'expected_value' => 'v=DKIM1; p="><script>alert(1)</script>',
        ])];

        $html = $this->render($data);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_controller_returns_the_expected_json_contract(): void
    {
        $data = $this->data();

        $deliverability = Mockery::mock(WhmEmailDeliverabilityService::class);
        $deliverability->shouldReceive('forAccount')
            ->once()
            ->with(Mockery::type(WhmAccount::class), true)
            ->andReturn($data);

        $controller = new WhmAccountController(
            Mockery::mock(WhmApiService::class),
            Mockery::mock(WhmAccountService::class),
            Mockery::mock(WhmServerStatusService::class),
            Mockery::mock(WhmSettingsService::class),
            $deliverability
        );

        $response = $controller->emailDeliverability(
            Request::create('/admin/whm/accounts/14/email-deliverability?fresh=1', 'GET'),
            $this->account()
        );

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(['success', 'message', 'fetched_at_human', 'html'], array_keys($payload));
        $this->assertTrue($payload['success']);
        $this->assertSame('2026-08-18 11:01', $payload['fetched_at_human']);
        $this->assertStringContainsString('whm-copy-email', $payload['html']);
    }

    public function test_controller_returns_200_even_when_the_fetch_failed(): void
    {
        $deliverability = Mockery::mock(WhmEmailDeliverabilityService::class);
        $deliverability->shouldReceive('forAccount')->once()->andReturn($this->data([
            'success' => false,
            'configured' => false,
            'message' => 'إعدادات WHM غير مكتملة',
            'fetched_at_human' => null,
            'domains' => [],
        ]));

        $controller = new WhmAccountController(
            Mockery::mock(WhmApiService::class),
            Mockery::mock(WhmAccountService::class),
            Mockery::mock(WhmServerStatusService::class),
            Mockery::mock(WhmSettingsService::class),
            $deliverability
        );

        $response = $controller->emailDeliverability(
            Request::create('/admin/whm/accounts/14/email-deliverability', 'GET'),
            $this->account()
        );

        // Not a validation failure — the panel always has meaningful Arabic HTML to show.
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertNull($payload['fetched_at_human']);
        $this->assertStringContainsString('إعدادات WHM غير مكتملة', $payload['html']);
    }
}
