<?php

namespace Tests\Unit\Whm\MailDns;

use App\Http\Controllers\Client\ClientWhmAccountController;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\Client\ClientBillingService;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The client-facing mail-DNS endpoints.
 *
 * The load-bearing tests here are the domain ones. WhmMailDnsSyncService reads the zone of
 * whatever domain it is handed, using WHM root credentials, and then allow-lists record
 * names against THAT domain. That is fine for a trusted admin, but on the client path an
 * unvalidated domain would let one tenant write into another tenant's zone — so the
 * controller must pin the domain to the account before the service ever sees it.
 */
class ClientMailDnsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function account(string $status = 'active'): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 44;
        $account->user_id = 1;
        $account->username = 'docsclaudsoft';
        $account->domain = 'docs.claudsoft.com';
        $account->status = $status;

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

    /**
     * @param  callable|null  $capture  receives the domain the service was handed
     */
    protected function controller(?callable $capture = null, bool $expectNoCalls = false): ClientWhmAccountController
    {
        Auth::setUser(new User(['name' => 'client']));

        $accounts = Mockery::mock(WhmAccountService::class);
        $accounts->shouldReceive('userOwnsAccount')->andReturnTrue();

        $mailDns = Mockery::mock(WhmMailDnsSyncService::class);

        if ($expectNoCalls) {
            $mailDns->shouldNotReceive('preview');
            $mailDns->shouldNotReceive('apply');
        } else {
            $mailDns->shouldReceive('preview')->andReturnUsing(function ($account, $domain, $fresh = false) use ($capture): array {
                if ($capture) {
                    $capture($domain);
                }

                return $this->serviceResult();
            });
            $mailDns->shouldReceive('apply')->andReturnUsing(function ($account, $domain, $hash, $acks, $dryRun, $source) use ($capture): array {
                if ($capture) {
                    $capture($domain, $source, $dryRun);
                }

                return $this->serviceResult(['outcome' => 'applied', 'message' => 'تم التطبيق']);
            });
        }

        return new ClientWhmAccountController(
            $accounts,
            Mockery::mock(WhmApiService::class),
            Mockery::mock(WhmEmailDeliverabilityService::class),
            Mockery::mock(ClientBillingService::class),
            $mailDns
        );
    }

    // ------------------------------------------------- the escalation guard

    public function test_preview_defaults_to_the_accounts_own_domain(): void
    {
        $seen = null;
        $controller = $this->controller(function ($domain) use (&$seen) {
            $seen = $domain;
        });

        $controller->mailDnsPreview(Request::create('/x', 'GET'), $this->account());

        $this->assertSame('docs.claudsoft.com', $seen);
    }

    public function test_a_foreign_domain_is_forbidden_on_preview(): void
    {
        // Another tenant's domain — the service must never be reached.
        $controller = $this->controller(expectNoCalls: true);

        try {
            $controller->mailDnsPreview(
                Request::create('/x?domain=other-tenant.com', 'GET'),
                $this->account()
            );
            $this->fail('a foreign domain must be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_a_foreign_domain_is_forbidden_on_apply(): void
    {
        $controller = $this->controller(expectNoCalls: true);

        try {
            $controller->mailDnsApply(
                Request::create('/x', 'POST', [
                    'plan_hash' => str_repeat('a', 64),
                    'domain' => 'other-tenant.com',
                ]),
                $this->account()
            );
            $this->fail('a foreign domain must be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_a_suffix_lookalike_domain_is_forbidden(): void
    {
        // evildocs.claudsoft.com ends with "docs.claudsoft.com" only as a raw substring.
        $controller = $this->controller(expectNoCalls: true);

        try {
            $controller->mailDnsPreview(
                Request::create('/x?domain=evildocs.claudsoft.com', 'GET'),
                $this->account()
            );
            $this->fail('a suffix lookalike must be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_the_parent_zone_is_forbidden(): void
    {
        // The zone the records live in is not the client's to manage wholesale.
        $controller = $this->controller(expectNoCalls: true);

        try {
            $controller->mailDnsPreview(
                Request::create('/x?domain=claudsoft.com', 'GET'),
                $this->account()
            );
            $this->fail('the parent zone must be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_a_subdomain_of_the_account_domain_is_allowed(): void
    {
        $seen = null;
        $controller = $this->controller(function ($domain) use (&$seen) {
            $seen = $domain;
        });

        $controller->mailDnsPreview(
            Request::create('/x?domain=shop.docs.claudsoft.com', 'GET'),
            $this->account()
        );

        $this->assertSame('shop.docs.claudsoft.com', $seen);
    }

    public function test_the_domain_is_normalised_before_comparison(): void
    {
        $seen = null;
        $controller = $this->controller(function ($domain) use (&$seen) {
            $seen = $domain;
        });

        // Trailing dot + uppercase must not defeat the check, nor be rejected.
        $controller->mailDnsPreview(
            Request::create('/x?domain=DOCS.ClaudSoft.com.', 'GET'),
            $this->account()
        );

        $this->assertSame('docs.claudsoft.com', $seen);
    }

    // ------------------------------------------------- ownership and status

    public function test_a_non_owner_is_forbidden(): void
    {
        Auth::setUser(new User(['name' => 'other']));

        $accounts = Mockery::mock(WhmAccountService::class);
        $accounts->shouldReceive('userOwnsAccount')->andReturnFalse();

        $mailDns = Mockery::mock(WhmMailDnsSyncService::class);
        $mailDns->shouldNotReceive('preview');

        $controller = new ClientWhmAccountController(
            $accounts,
            Mockery::mock(WhmApiService::class),
            Mockery::mock(WhmEmailDeliverabilityService::class),
            Mockery::mock(ClientBillingService::class),
            $mailDns
        );

        try {
            $controller->mailDnsPreview(Request::create('/x', 'GET'), $this->account());
            $this->fail('a non-owner must be refused');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_a_terminated_account_is_not_found(): void
    {
        $controller = $this->controller(expectNoCalls: true);

        try {
            $controller->mailDnsPreview(Request::create('/x', 'GET'), $this->account('terminated'));
            $this->fail('a terminated account must 404');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    // ------------------------------------------------- contract

    public function test_apply_requires_a_well_formed_plan_hash(): void
    {
        $controller = $this->controller(expectNoCalls: true);

        $this->expectException(ValidationException::class);

        $controller->mailDnsApply(Request::create('/x', 'POST', ['plan_hash' => 'short']), $this->account());
    }

    public function test_apply_records_the_client_source_and_is_never_a_dry_run(): void
    {
        $source = null;
        $dryRun = null;
        $controller = $this->controller(function ($domain, $s = null, $d = null) use (&$source, &$dryRun) {
            $source = $s;
            $dryRun = $d;
        });

        $controller->mailDnsApply(
            Request::create('/x', 'POST', ['plan_hash' => str_repeat('a', 64), 'ack' => ['dmarc_generated']]),
            $this->account()
        );

        // A distinct source keeps client-initiated writes identifiable in the audit log.
        $this->assertSame('client', $source);
        $this->assertFalse($dryRun);
    }

    public function test_the_response_matches_the_admin_json_contract(): void
    {
        $response = $this->controller()->mailDnsPreview(Request::create('/x', 'GET'), $this->account());

        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(
            ['ok', 'can_apply', 'outcome', 'message', 'plan_hash', 'acks', 'html'],
            array_keys($payload)
        );
        $this->assertSame(['dmarc_generated'], $payload['acks']);
    }
}
