<?php

namespace Tests\Unit\Whm\MailDns;

use App\Models\WhmAccount;
use App\Services\Cloudflare\CloudflareSettingsService;
use App\Services\CloudflareApiService;
use App\Services\Whm\MailDns\MailDnsSyncLogger;
use App\Services\Whm\MailDns\WhmMailDnsDiffer;
use App\Services\Whm\MailDns\WhmMailDnsPlanBuilder;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * The safety rails around a live DNS write. No DB (the logger is a seam), no real HTTP.
 */
class WhmMailDnsSyncServiceTest extends TestCase
{
    protected string $domain = 'docs.claudsoft.com';

    /** @var array<int, array<string, mixed>> */
    protected array $logged = [];

    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function account(string $status = 'active'): WhmAccount
    {
        $account = new WhmAccount;
        $account->id = 44;
        $account->username = 'docsclaudsoft';
        $account->domain = $this->domain;
        $account->status = $status;

        return $account;
    }

    /**
     * A cPanel zone with MX, SPF, DKIM and the mail hosts.
     *
     * @return list<array<string, mixed>>
     */
    protected function whmZone(): array
    {
        return [
            ['type' => 'SOA', 'name' => 'docs.claudsoft.com.'],
            ['type' => 'MX', 'name' => 'docs.claudsoft.com.', 'exchange' => 'docs.claudsoft.com.', 'preference' => 0],
            ['type' => 'TXT', 'name' => 'docs.claudsoft.com.', 'txtdata' => 'v=spf1 +mx +a +ip4:46.4.193.156 ~all'],
            ['type' => 'TXT', 'name' => 'default._domainkey.docs.claudsoft.com.', 'txtdata' => 'v=DKIM1; k=rsa; p=MIIBIjANBg'],
            ['type' => 'A', 'name' => 'mail.docs.claudsoft.com.', 'address' => '46.4.193.156'],
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function makeService(array $options = []): WhmMailDnsSyncService
    {
        $zoneResult = $options['whm_zone'] ?? ['success' => true, 'records' => $this->whmZone()];

        $accounts = Mockery::mock(WhmAccountService::class);
        $accounts->shouldReceive('dnsZoneForDomain')->andReturn($zoneResult);

        $deliverability = Mockery::mock(WhmEmailDeliverabilityService::class);
        $deliverability->shouldReceive('serverContextForAccount')->andReturn([
            'hostname' => 'main.claudsoft.com',
            'ip' => '46.4.193.156',
            'ptr' => 'static.156.193.4.46.clients.your-server.de',
            'ptr_state' => 'problem',
        ]);

        $settings = Mockery::mock(CloudflareSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
            'api_token' => $options['cf_token'] ?? 'TOKEN123',
            'account_id' => str_repeat('a', 32),
            'timeout' => 30,
            'cache_ttl' => 600,
            'token_configured' => ($options['cf_token'] ?? 'TOKEN123') !== '',
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        $logger = Mockery::mock(MailDnsSyncLogger::class);
        $logger->shouldReceive('record')->andReturnUsing(function (array $attributes): int {
            $this->logged[] = $attributes;

            return count($this->logged);
        });

        return new WhmMailDnsSyncService(
            $accounts,
            $deliverability,
            new CloudflareApiService($settings),
            new WhmMailDnsPlanBuilder,
            new WhmMailDnsDiffer,
            $logger
        );
    }

    /**
     * @param  list<array<string, mixed>>  $cfRecords
     * @param  array<string, mixed>  $writeBehaviour  ['status' => int] to fail writes
     */
    protected function fakeCloudflare(array $cfRecords = [], array $writeBehaviour = []): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/zone1/dns_records*' => function ($request) use ($cfRecords, $writeBehaviour) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'success' => true,
                        'result' => $cfRecords,
                        'result_info' => ['total_pages' => 1],
                    ], 200);
                }

                $status = (int) ($writeBehaviour['status'] ?? 200);
                if ($status === 200) {
                    return Http::response(['success' => true, 'result' => ['id' => 'new1']], 200);
                }

                return Http::response([
                    'success' => false,
                    'errors' => [['message' => 'refused']],
                ], $status);
            },
            'https://api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => [['id' => 'zone1', 'name' => 'claudsoft.com']],
                'result_info' => ['total_pages' => 1],
            ], 200),
        ]);
    }

    /**
     * @return list<\Illuminate\Http\Client\Request>
     */
    protected function writeRequests(): array
    {
        $writes = [];
        foreach (Http::recorded() as [$request]) {
            if (in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
                $writes[] = $request;
            }
        }

        return $writes;
    }

    protected function allAcks(array $preview): array
    {
        return array_column($preview['warnings'], 'key');
    }

    // ---------------------------------------------------------------- preview

    public function test_preview_resolves_the_parent_zone_for_a_subdomain_and_plans_creates(): void
    {
        $this->fakeCloudflare();

        $preview = $this->makeService()->preview($this->account());

        $this->assertTrue($preview['ok']);
        $this->assertTrue($preview['can_apply']);
        $this->assertSame(['id' => 'zone1', 'name' => 'claudsoft.com'], $preview['zone']);
        $this->assertSame([], $preview['blockers']);
        $this->assertGreaterThan(0, $preview['counts']['create']);
        $this->assertNotNull($preview['plan_hash']);
    }

    public function test_preview_never_writes(): void
    {
        $this->fakeCloudflare();

        $this->makeService()->preview($this->account());

        $this->assertSame([], $this->writeRequests());
        $this->assertSame([], $this->logged, 'preview must not write an audit row');
    }

    public function test_preview_reads_the_cloudflare_record_list_only_once(): void
    {
        $this->fakeCloudflare();

        $this->makeService()->preview($this->account());

        $listings = 0;
        foreach (Http::recorded() as [$request]) {
            if ($request->method() === 'GET' && str_contains($request->url(), 'dns_records')) {
                $listings++;
            }
        }

        $this->assertSame(1, $listings, 'the zone must be listed once, not once per record');
    }

    public function test_an_already_correct_zone_needs_no_changes(): void
    {
        $this->fakeCloudflare([
            ['id' => 'm1', 'type' => 'MX', 'name' => $this->domain, 'content' => $this->domain, 'priority' => 0],
            ['id' => 's1', 'type' => 'TXT', 'name' => $this->domain, 'content' => 'v=spf1 +mx +a +ip4:46.4.193.156 ~all'],
            ['id' => 'd1', 'type' => 'TXT', 'name' => 'default._domainkey.'.$this->domain, 'content' => 'v=DKIM1; k=rsa; p=MIIBIjANBg'],
            ['id' => 'a1', 'type' => 'A', 'name' => 'mail.'.$this->domain, 'content' => '46.4.193.156', 'proxied' => false],
            ['id' => 'x1', 'type' => 'TXT', 'name' => '_dmarc.'.$this->domain, 'content' => 'v=DMARC1; p=none;'],
        ]);

        $preview = $this->makeService()->preview($this->account());

        $this->assertFalse($preview['can_apply']);
        $this->assertSame(0, $preview['counts']['create']);
        $this->assertSame(0, $preview['counts']['update']);
        $this->assertStringContainsString('مطابقة', $preview['message']);
    }

    // ---------------------------------------------------------------- blockers

    public function test_a_terminated_account_is_refused(): void
    {
        Http::fake();

        $preview = $this->makeService()->preview($this->account('terminated'));

        $this->assertFalse($preview['ok']);
        $this->assertSame(WhmMailDnsSyncService::BLOCKER_TERMINATED, $preview['blockers'][0]['key']);
        Http::assertNothingSent();
    }

    public function test_an_unreadable_whm_zone_is_refused_because_there_is_no_source_of_truth(): void
    {
        Http::fake();

        $service = $this->makeService(['whm_zone' => ['success' => false, 'message' => 'فشل الاتصال بـ WHM']]);
        $preview = $service->preview($this->account());

        $this->assertFalse($preview['ok']);
        $this->assertSame(WhmMailDnsSyncService::BLOCKER_WHM_ZONE_UNREADABLE, $preview['blockers'][0]['key']);
    }

    public function test_a_third_party_mx_blocks_the_apply(): void
    {
        // The most dangerous case: silently taking over a working Google Workspace domain.
        $this->fakeCloudflare([
            ['id' => 'g1', 'type' => 'MX', 'name' => $this->domain, 'content' => 'aspmx.l.google.com', 'priority' => 1],
        ]);

        $preview = $this->makeService()->preview($this->account());

        $keys = array_column($preview['blockers'], 'key');
        $this->assertContains(WhmMailDnsSyncService::BLOCKER_THIRD_PARTY_MX, $keys);
        $this->assertStringContainsString('Google Workspace', $preview['blockers'][0]['message']);
    }

    public function test_a_blocked_preview_cannot_be_applied_and_writes_nothing(): void
    {
        $this->fakeCloudflare([
            ['id' => 'g1', 'type' => 'MX', 'name' => $this->domain, 'content' => 'aspmx.l.google.com', 'priority' => 1],
        ]);

        $result = $this->makeService()->apply($this->account(), acknowledged: [
            WhmMailDnsSyncService::ACK_DMARC_GENERATED,
            WhmMailDnsSyncService::ACK_EXTRA_RECORDS,
        ]);

        $this->assertFalse($result['ok']);
        $this->assertSame('blocked', $result['outcome']);
        $this->assertSame([], $this->writeRequests());
        $this->assertSame([], $this->logged);
    }

    public function test_a_zone_not_on_cloudflare_still_produces_a_manual_plan(): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true, 'result' => [], 'result_info' => ['total_pages' => 1],
            ], 200),
        ]);

        $preview = $this->makeService()->preview($this->account());

        // ok:true — the screen is useful even though we cannot apply.
        $this->assertTrue($preview['ok']);
        $this->assertFalse($preview['can_apply']);
        $this->assertNull($preview['zone']);
        $this->assertSame('zone_not_found', $preview['blockers'][0]['key']);
        $this->assertNotEmpty($preview['changes']);
        $this->assertSame('manual', $preview['changes'][0]['verdict']);
    }

    public function test_unconfigured_cloudflare_falls_back_to_manual_instructions(): void
    {
        Http::fake();

        $preview = $this->makeService(['cf_token' => ''])->preview($this->account());

        $this->assertTrue($preview['ok']);
        $this->assertFalse($preview['can_apply']);
        $this->assertStringContainsString('Cloudflare', $preview['blockers'][0]['message']);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- warnings

    public function test_a_generated_dmarc_must_be_acknowledged(): void
    {
        $this->fakeCloudflare();

        $preview = $this->makeService()->preview($this->account());

        $this->assertContains(WhmMailDnsSyncService::ACK_DMARC_GENERATED, $this->allAcks($preview));
    }

    public function test_applying_without_acknowledging_is_refused_and_writes_nothing(): void
    {
        $this->fakeCloudflare();

        $result = $this->makeService()->apply($this->account());

        $this->assertFalse($result['ok']);
        $this->assertSame('blocked', $result['outcome']);
        $this->assertNotEmpty($result['unacknowledged']);
        $this->assertSame([], $this->writeRequests());
    }

    public function test_a_suspended_account_warns_but_is_allowed(): void
    {
        $this->fakeCloudflare();

        $preview = $this->makeService()->preview($this->account('suspended'));

        // The cPanel zone is intact, so pre-provisioning DNS before unsuspending is fine.
        $this->assertTrue($preview['ok']);
        $this->assertSame([], $preview['blockers']);
        $this->assertContains(WhmMailDnsSyncService::ACK_ACCOUNT_SUSPENDED, $this->allAcks($preview));
    }

    public function test_an_orange_clouded_mail_record_warns_and_the_apply_turns_the_proxy_off(): void
    {
        $this->fakeCloudflare([
            ['id' => 'a1', 'type' => 'A', 'name' => 'mail.'.$this->domain, 'content' => '46.4.193.156', 'proxied' => true],
        ]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $this->assertContains(WhmMailDnsSyncService::ACK_MAIL_PROXIED, $this->allAcks($preview));

        $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $found = false;
        foreach ($this->writeRequests() as $request) {
            $data = $request->data();
            if (($data['name'] ?? '') === 'mail.'.$this->domain) {
                $found = true;
                $this->assertFalse($data['proxied'], 'the mail A record must be written grey-clouded');
            }
        }
        $this->assertTrue($found, 'the mail record was never written');
    }

    // ---------------------------------------------------------------- apply

    public function test_dry_run_makes_zero_write_calls(): void
    {
        $this->fakeCloudflare();

        $service = $this->makeService();
        $preview = $service->preview($this->account());

        $result = $service->apply(
            $this->account(),
            planHash: $preview['plan_hash'],
            acknowledged: $this->allAcks($preview),
            dryRun: true
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('dry_run', $result['outcome']);
        $this->assertSame([], $this->writeRequests());
        $this->assertSame([], $this->logged, 'a dry run must not write an audit row');
    }

    public function test_a_successful_apply_creates_records_and_logs_once(): void
    {
        $this->fakeCloudflare();

        $service = $this->makeService();
        $preview = $service->preview($this->account());

        $result = $service->apply(
            $this->account(),
            planHash: $preview['plan_hash'],
            acknowledged: $this->allAcks($preview)
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('applied', $result['outcome']);
        $this->assertGreaterThan(0, $result['created_count']);
        $this->assertSame(0, $result['failed_count']);

        $this->assertCount(1, $this->logged, 'exactly one audit row per apply');
        $this->assertSame('applied', $this->logged[0]['outcome']);
        $this->assertNotEmpty($this->logged[0]['meta']['records']);
        foreach ($this->logged[0]['meta']['records'] as $record) {
            $this->assertArrayHasKey('before', $record);
            $this->assertArrayHasKey('after', $record);
        }
    }

    public function test_the_put_payload_is_complete_for_mx_and_omits_proxied_for_txt(): void
    {
        $this->fakeCloudflare([
            ['id' => 'm1', 'type' => 'MX', 'name' => $this->domain, 'content' => $this->domain, 'priority' => 99],
            ['id' => 's1', 'type' => 'TXT', 'name' => $this->domain, 'content' => 'v=spf1 -all'],
        ]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $sawMx = false;
        $sawTxt = false;
        foreach ($this->writeRequests() as $request) {
            $data = $request->data();
            if (($data['type'] ?? '') === 'MX') {
                $sawMx = true;
                // updateDnsRecord is a PUT (full replace) — a missing priority would wipe it.
                $this->assertArrayHasKey('priority', $data);
                $this->assertArrayNotHasKey('proxied', $data);
            }
            if (($data['type'] ?? '') === 'TXT') {
                $sawTxt = true;
                $this->assertArrayNotHasKey('proxied', $data, 'Cloudflare rejects proxied on TXT');
            }
            $this->assertArrayHasKey('content', $data);
            $this->assertArrayHasKey('ttl', $data);
        }

        $this->assertTrue($sawMx && $sawTxt);
    }

    public function test_every_written_name_stays_inside_the_account_domain(): void
    {
        $this->fakeCloudflare();

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $writes = $this->writeRequests();
        $this->assertNotEmpty($writes);
        foreach ($writes as $request) {
            $this->assertStringEndsWith($this->domain, $request->data()['name']);
        }
    }

    public function test_nothing_is_ever_deleted(): void
    {
        $this->fakeCloudflare([
            ['id' => 'stale', 'type' => 'MX', 'name' => $this->domain, 'content' => 'stale.example.com', 'priority' => 50],
        ]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        foreach ($this->writeRequests() as $request) {
            $this->assertNotSame('DELETE', $request->method());
        }
    }

    public function test_a_stale_plan_hash_is_refused(): void
    {
        $this->fakeCloudflare();

        $result = $this->makeService()->apply(
            $this->account(),
            planHash: 'stale-hash-from-an-old-preview',
            acknowledged: [
                WhmMailDnsSyncService::ACK_DMARC_GENERATED,
                WhmMailDnsSyncService::ACK_ACCOUNT_SUSPENDED,
                WhmMailDnsSyncService::ACK_MAIL_PROXIED,
                WhmMailDnsSyncService::ACK_EXTRA_RECORDS,
            ]
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('blocked', $result['outcome']);
        $this->assertStringContainsString('أعد المعاينة', $result['message']);
        $this->assertSame([], $this->writeRequests());
    }

    public function test_the_plan_hash_is_stable_across_previews(): void
    {
        $this->fakeCloudflare();
        $service = $this->makeService();

        $first = $service->preview($this->account());
        $second = $service->preview($this->account());

        $this->assertSame($first['plan_hash'], $second['plan_hash']);
    }

    public function test_a_forbidden_write_aborts_immediately(): void
    {
        // 403 is a global signal — continuing would just pile up identical failures.
        $this->fakeCloudflare([], ['status' => 403]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $result = $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $this->assertSame('failed', $result['outcome']);
        $this->assertSame('token_lacks_dns_edit', $result['abort_reason']);
        $this->assertCount(1, $this->writeRequests(), 'must stop after the first 403');
        $this->assertStringContainsString('صلاحية تعديل DNS', $result['message']);
    }

    public function test_a_rate_limit_aborts_immediately(): void
    {
        $this->fakeCloudflare([], ['status' => 429]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $result = $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $this->assertSame('rate_limited', $result['abort_reason']);
        $this->assertCount(1, $this->writeRequests(), 'no retry loop inside a web request');
    }

    public function test_a_per_record_failure_continues_and_reports_a_mixed_state(): void
    {
        // 400 is record-specific, so the remaining records are still worth attempting —
        // stopping would leave more of the zone wrong, not less.
        $this->fakeCloudflare([], ['status' => 400]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $expected = count(array_filter(
            $preview['changes'],
            fn (array $c): bool => in_array($c['verdict'], ['create', 'update'], true)
        ));

        $result = $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $this->assertSame('failed', $result['outcome']);
        $this->assertCount($expected, $this->writeRequests(), 'every record must be attempted');
        $this->assertCount(1, $this->logged);
        $this->assertSame($expected, $this->logged[0]['failed_count']);
    }

    public function test_an_apply_with_nothing_to_do_succeeds_without_writing(): void
    {
        $this->fakeCloudflare([
            ['id' => 'm1', 'type' => 'MX', 'name' => $this->domain, 'content' => $this->domain, 'priority' => 0],
            ['id' => 's1', 'type' => 'TXT', 'name' => $this->domain, 'content' => 'v=spf1 +mx +a +ip4:46.4.193.156 ~all'],
            ['id' => 'd1', 'type' => 'TXT', 'name' => 'default._domainkey.'.$this->domain, 'content' => 'v=DKIM1; k=rsa; p=MIIBIjANBg'],
            ['id' => 'a1', 'type' => 'A', 'name' => 'mail.'.$this->domain, 'content' => '46.4.193.156', 'proxied' => false],
            ['id' => 'x1', 'type' => 'TXT', 'name' => '_dmarc.'.$this->domain, 'content' => 'v=DMARC1; p=none;'],
        ]);

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $result = $service->apply($this->account(), planHash: $preview['plan_hash'], acknowledged: $this->allAcks($preview));

        $this->assertTrue($result['ok']);
        $this->assertSame('applied', $result['outcome']);
        $this->assertSame([], $this->writeRequests());
        $this->assertSame([], $this->logged, 'no changes means no audit row');
    }

    public function test_the_command_source_is_recorded(): void
    {
        $this->fakeCloudflare();

        $service = $this->makeService();
        $preview = $service->preview($this->account());
        $service->apply(
            $this->account(),
            planHash: $preview['plan_hash'],
            acknowledged: $this->allAcks($preview),
            source: 'command'
        );

        $this->assertSame('command', $this->logged[0]['source']);
    }

    public function test_apply_re_derives_the_plan_rather_than_trusting_the_caller(): void
    {
        $this->fakeCloudflare();

        $service = $this->makeService();
        $preview = $service->preview($this->account());

        // Passing no hash still re-derives; the audit trail proves what was written.
        $result = $service->apply($this->account(), acknowledged: $this->allAcks($preview));

        $this->assertSame('applied', $result['outcome']);
        $written = array_column(array_map(fn ($r) => $r->data(), $this->writeRequests()), 'name');
        foreach ($written as $name) {
            $this->assertStringEndsWith($this->domain, $name);
        }
    }
}
