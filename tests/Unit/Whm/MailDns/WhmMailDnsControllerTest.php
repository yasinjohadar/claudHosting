<?php

namespace Tests\Unit\Whm\MailDns;

use App\Http\Controllers\Admin\Whm\WhmMailDnsController;
use App\Models\WhmAccount;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * The controller's JSON contract and its input guards. Called directly with a mocked
 * service — no DB, no HTTP (the project's Feature suite cannot run: four migrations query
 * MySQL's information_schema, so RefreshDatabase dies on sqlite).
 */
class WhmMailDnsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function account(): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 44;
        $account->username = 'docsclaudsoft';
        $account->domain = 'docs.claudsoft.com';
        $account->status = 'active';

        return $account;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function serviceResult(array $overrides = []): array
    {
        return array_merge([
            'ok' => true,
            'can_apply' => true,
            'domain' => 'docs.claudsoft.com',
            'zone' => ['id' => 'zone1', 'name' => 'claudsoft.com'],
            'plan' => ['items' => [], 'skipped' => [], 'notes' => []],
            'changes' => [],
            'extras' => [],
            'counts' => ['create' => 1, 'update' => 0, 'unchanged' => 0, 'conflict' => 0],
            'blockers' => [],
            'warnings' => [['key' => 'dmarc_generated', 'message' => 'قيمة مُولَّدة']],
            'plan_hash' => str_repeat('a', 64),
            'message' => 'مطلوب 1 تغيير',
        ], $overrides);
    }

    protected function controller(WhmMailDnsSyncService $service): WhmMailDnsController
    {
        return new WhmMailDnsController($service);
    }

    public function test_preview_returns_the_expected_json_shape_and_never_applies(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->once()->andReturn($this->serviceResult());
        $service->shouldNotReceive('apply');

        $response = $this->controller($service)->preview(
            Request::create('/admin/whm/accounts/44/mail-dns/preview', 'GET'),
            $this->account()
        );

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(
            ['ok', 'can_apply', 'outcome', 'message', 'plan_hash', 'acks', 'html'],
            array_keys($payload)
        );
        $this->assertTrue($payload['ok']);
        $this->assertSame(['dmarc_generated'], $payload['acks']);
        $this->assertNotSame('', $payload['html']);
    }

    public function test_preview_passes_the_fresh_flag_through(): void
    {
        $seen = [];

        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')
            ->twice()
            ->andReturnUsing(function ($account, $domain, $fresh) use (&$seen): array {
                $seen[] = $fresh;

                return $this->serviceResult();
            });

        $controller = $this->controller($service);
        $controller->preview(Request::create('/x?fresh=1', 'GET'), $this->account());
        $controller->preview(Request::create('/x', 'GET'), $this->account());

        $this->assertSame([true, false], $seen);
    }

    public function test_a_blocked_plan_is_still_http_200(): void
    {
        // A refusal is a renderable answer, not a transport error.
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('preview')->andReturn($this->serviceResult([
            'ok' => false,
            'can_apply' => false,
            'blockers' => [['key' => 'third_party_mx', 'message' => 'MX يشير إلى Google Workspace']],
            'warnings' => [],
            'plan_hash' => null,
        ]));

        $response = $this->controller($service)->preview(Request::create('/x', 'GET'), $this->account());

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['ok']);
        $this->assertFalse($payload['can_apply']);
        $this->assertStringContainsString('Google Workspace', $payload['html']);
    }

    public function test_apply_requires_a_plan_hash(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldNotReceive('apply');

        $this->expectException(ValidationException::class);

        $this->controller($service)->apply(
            Request::create('/x', 'POST', []),
            $this->account()
        );
    }

    public function test_apply_rejects_a_malformed_plan_hash(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldNotReceive('apply');

        $this->expectException(ValidationException::class);

        $this->controller($service)->apply(
            Request::create('/x', 'POST', ['plan_hash' => 'too-short']),
            $this->account()
        );
    }

    public function test_apply_forwards_the_hash_and_acknowledgements_and_is_never_a_dry_run(): void
    {
        $hash = str_repeat('b', 64);

        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('apply')
            ->once()
            ->withArgs(function ($account, $domain, $planHash, $acks, $dryRun, $source) use ($hash): bool {
                return $planHash === $hash
                    && $acks === ['dmarc_generated', 'mail_a_proxied']
                    && $dryRun === false
                    && $source === 'web';
            })
            ->andReturn($this->serviceResult(['outcome' => 'applied', 'message' => 'تم التطبيق']));

        $response = $this->controller($service)->apply(
            Request::create('/x', 'POST', [
                'plan_hash' => $hash,
                'ack' => ['dmarc_generated', 'mail_a_proxied'],
            ]),
            $this->account()
        );

        $payload = json_decode($response->getContent(), true);
        $this->assertSame('applied', $payload['outcome']);
    }

    public function test_apply_accepts_an_optional_domain(): void
    {
        $seen = null;

        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('apply')
            ->once()
            ->andReturnUsing(function ($account, $domain) use (&$seen): array {
                $seen = $domain;

                return $this->serviceResult(['outcome' => 'applied']);
            });

        $this->controller($service)->apply(
            Request::create('/x', 'POST', [
                'plan_hash' => str_repeat('c', 64),
                'domain' => 'shop.claudsoft.com',
            ]),
            $this->account()
        );

        $this->assertSame('shop.claudsoft.com', $seen);
    }

    public function test_a_partial_outcome_is_surfaced_with_ok_false(): void
    {
        $service = Mockery::mock(WhmMailDnsSyncService::class);
        $service->shouldReceive('apply')->andReturn($this->serviceResult([
            'ok' => false,
            'outcome' => 'partial',
            'message' => 'تم تطبيق 1 من 3 تغييرات. المنطقة الآن في حالة مختلطة',
            'results' => [],
        ]));

        $response = $this->controller($service)->apply(
            Request::create('/x', 'POST', ['plan_hash' => str_repeat('d', 64)]),
            $this->account()
        );

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['ok']);
        $this->assertSame('partial', $payload['outcome']);
        $this->assertStringContainsString('حالة مختلطة', $payload['message']);
    }
}
