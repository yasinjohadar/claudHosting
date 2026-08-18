<?php

namespace Tests\Unit\Whm;

use App\Models\WhmAccount;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use App\Services\Whm\WhmSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class WhmEmailDeliverabilityServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function makeService(): WhmEmailDeliverabilityService
    {
        $settings = Mockery::mock(WhmSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
            'host' => 'https://whm.example.com:2087',
            'username' => 'root',
            'api_token' => 'TOKEN123',
            'verify_ssl' => false,
            'default_package' => 'default',
            'default_domain_suffix' => '',
            'timeout' => 30,
            'token_configured' => true,
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        return new WhmEmailDeliverabilityService(new WhmApiService($settings), $settings);
    }

    protected function account(): WhmAccount
    {
        $account = new WhmAccount;
        $account->username = 'rootdiplomas';
        $account->domain = 'diplomas.claudsoft.com';
        $account->status = 'active';

        return $account;
    }

    /**
     * Wrap a UAPI payload the way WHM's `cpanel` proxy does.
     */
    protected function uapi(mixed $data, int $status = 1): array
    {
        return [
            'result' => [
                'data' => $data,
                'status' => $status,
                'errors' => $status === 1 ? [] : ['فشل'],
                'messages' => [],
            ],
        ];
    }

    protected function domainsDataResponse(): array
    {
        return $this->uapi([
            'main_domain' => ['domain' => 'diplomas.claudsoft.com', 'documentroot' => '/home/u/public_html'],
            'addon_domains' => [],
            'sub_domains' => [],
            'parked_domains' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function fakeAll(array $overrides = []): void
    {
        $base = [
            '*/json-api/gethostname*' => Http::response(['data' => ['hostname' => 'server.claudsoft.com']], 200),
            '*/json-api/accountsummary*' => Http::response(['data' => ['acct' => [['ip' => '159.195.108.28']]]], 200),
            '*/json-api/dumpzone*' => Http::response(['data' => ['zone' => []]], 200),
        ];

        Http::fake(array_merge($base, $overrides));
    }

    protected function findCheck(array $result, string $key): ?array
    {
        foreach ($result['domains'][0]['checks'] ?? [] as $check) {
            if ($check['key'] === $key) {
                return $check;
            }
        }

        return null;
    }

    public function test_canonical_list_shape_produces_full_contract(): void
    {
        // The `cpanel` proxy is used for domains_data AND validate_current_setup, so
        // one sequence on that endpoint serves both, in call order.
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'dkim' => [
                        'domain' => 'default._domainkey.diplomas.claudsoft.com',
                        'state' => 'VALID',
                        'expected' => 'v=DKIM1; k=rsa; p=MIIBIjANBg',
                        'current' => 'v=DKIM1; k=rsa; p=MIIBIjANBg',
                    ],
                    'spf' => [
                        'domain' => 'diplomas.claudsoft.com',
                        'state' => 'VALID',
                        'expected' => 'v=spf1 +mx +a +ip4:159.195.108.28 ~all',
                        'current' => 'v=spf1 +mx +a +ip4:159.195.108.28 ~all',
                    ],
                    'ptr' => [
                        'ip_address' => '159.195.108.28',
                        'state' => 'VALID',
                        'expected' => 'server.claudsoft.com',
                        'current' => 'server.claudsoft.com',
                    ],
                ]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $this->assertTrue($result['success']);
        $this->assertTrue($result['configured']);
        $this->assertTrue($result['available']);
        $this->assertCount(1, $result['domains']);

        $domain = $result['domains'][0];
        $this->assertSame('diplomas.claudsoft.com', $domain['domain']);
        $this->assertSame('main', $domain['type']);
        $this->assertSame('ok', $domain['overall']);

        foreach ($domain['checks'] as $check) {
            foreach ([
                'key', 'label', 'context', 'state', 'state_label', 'badge', 'raw_state',
                'record_type', 'expected_name', 'expected_value', 'current_value',
                'matches', 'message', 'source',
            ] as $key) {
                $this->assertArrayHasKey($key, $check, "check[{$check['key']}] is missing {$key}");
            }
        }

        $dkim = $this->findCheck($result, 'dkim');
        $this->assertSame('ok', $dkim['state']);
        $this->assertSame('سليم', $dkim['state_label']);
        $this->assertSame('TXT', $dkim['record_type']);
        $this->assertSame('default._domainkey.diplomas.claudsoft.com', $dkim['expected_name']);
        $this->assertTrue($dkim['matches']);

        $this->assertSame('ok', $this->findCheck($result, 'spf')['state']);
        $this->assertSame('159.195.108.28', $this->findCheck($result, 'ptr')['context']);
    }

    public function test_domain_keyed_map_shape_is_understood(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([
                    'diplomas.claudsoft.com' => [
                        'dkim' => ['state' => 'VALID', 'expected' => 'v=DKIM1; k=rsa; p=AAA', 'current' => 'v=DKIM1; k=rsa; p=AAA'],
                        'spf' => ['state' => 'VALID', 'expected' => 'v=spf1 ~all', 'current' => 'v=spf1 ~all'],
                    ],
                ]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $this->assertTrue($result['success']);
        $this->assertSame('ok', $result['domains'][0]['overall']);
        $this->assertSame('v=DKIM1; k=rsa; p=AAA', $this->findCheck($result, 'dkim')['current_value']);
    }

    public function test_alternate_key_spellings_and_missing_state_are_tolerated(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'dkim' => ['status' => 'MISSING', 'suggested_record' => 'v=DKIM1; k=rsa; p=BBB'],
                    'spf' => ['status' => 'MISSING', 'suggested_record' => 'v=spf1 +ip4:1.2.3.4 ~all'],
                    'reverse_dns' => ['status' => 'MISSING', 'ptr_domain' => 'server.claudsoft.com'],
                ]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $dkim = $this->findCheck($result, 'dkim');
        $this->assertSame('problem', $dkim['state']);
        $this->assertSame('MISSING', $dkim['raw_state']);
        $this->assertSame('v=DKIM1; k=rsa; p=BBB', $dkim['expected_value']);
        $this->assertSame('يحتاج إصلاح', $dkim['state_label']);
        $this->assertSame('bg-danger-transparent', $dkim['badge']);

        // reverse_dns must be recognised as the PTR section.
        $this->assertSame('problem', $this->findCheck($result, 'ptr')['state']);
        $this->assertSame('problem', $result['domains'][0]['overall']);
    }

    public function test_chunked_txt_value_is_joined_without_a_separator(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'dkim' => [
                        'state' => 'VALID',
                        'expected' => ['v=DKIM1; k=rsa; p=AAAABBBB', 'CCCCDDDD'],
                    ],
                ]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $this->assertSame(
            'v=DKIM1; k=rsa; p=AAAABBBBCCCCDDDD',
            $this->findCheck($result, 'dkim')['expected_value']
        );
    }

    public function test_two_distinct_records_are_not_joined_and_warn(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'spf' => ['state' => 'INVALID', 'current' => ['v=spf1 a ~all', 'v=spf1 mx ~all']],
                ]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $spf = $this->findCheck($result, 'spf');
        $this->assertSame('v=spf1 a ~all', $spf['current_value']);
        $this->assertSame('problem', $spf['state']);
    }

    public function test_emailauth_failure_falls_back_to_the_dns_zone(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi(null, 0), 200),
            '*/json-api/dumpzone*' => Http::response(['data' => ['zone' => [
                ['record' => ['name' => 'diplomas.claudsoft.com.', 'type' => 'TXT', 'txtdata' => 'v=spf1 +mx ~all', 'ttl' => 14400]],
                ['record' => ['name' => 'default._domainkey.diplomas.claudsoft.com.', 'type' => 'TXT', 'txtdata' => 'v=DKIM1; k=rsa; p=ZZZ', 'ttl' => 14400]],
            ]]], 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        // The current value came from the zone; the recommended value was derived locally.
        // The two provenances are tracked separately so neither is mislabelled.
        $spf = $this->findCheck($result, 'spf');
        $this->assertSame('zone', $spf['source']);
        $this->assertSame('derived', $spf['expected_source']);
        $this->assertSame('v=spf1 +mx ~all', $spf['current_value']);

        $dkim = $this->findCheck($result, 'dkim');
        $this->assertSame('zone', $dkim['source']);
        $this->assertSame('v=DKIM1; k=rsa; p=ZZZ', $dkim['current_value']);

        // EmailAuth answered with an error, yet the zone read still produced real data.
        $this->assertFalse($result['available']);
        $this->assertTrue($result['success']);
    }

    public function test_all_sections_missing_still_emits_a_check_per_kind(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([['domain' => 'diplomas.claudsoft.com']]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $keys = array_column($result['domains'][0]['checks'], 'key');
        $this->assertSame(['dkim', 'spf', 'ptr', 'helo'], $keys);

        // No API data at all, so the SPF suggestion is derived locally and labelled as such.
        $spf = $this->findCheck($result, 'spf');
        $this->assertSame('derived', $spf['expected_source']);
        $this->assertStringContainsString('ip4:159.195.108.28', $spf['expected_value']);
        $this->assertNull($spf['current_value']);

        foreach ($result['domains'][0]['checks'] as $check) {
            $this->assertArrayHasKey('expected_source', $check);
        }
    }

    public function test_terminated_account_short_circuits_without_any_api_call(): void
    {
        Http::fake();

        $account = $this->account();
        $account->status = 'terminated';

        $result = $this->makeService()->forAccount($account);

        $this->assertFalse($result['success']);
        $this->assertSame('الحساب محذوف', $result['message']);
        $this->assertSame([], $result['domains']);
        Http::assertNothingSent();
    }

    public function test_result_is_cached_until_fresh_is_requested(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'spf' => ['state' => 'VALID', 'expected' => 'v=spf1 ~all', 'current' => 'v=spf1 ~all'],
                ]]), 200)
                ->push($this->domainsDataResponse(), 200)
                ->push($this->uapi([[
                    'domain' => 'diplomas.claudsoft.com',
                    'spf' => ['state' => 'VALID', 'expected' => 'v=spf1 ~all', 'current' => 'v=spf1 ~all'],
                ]]), 200),
        ]);

        $service = $this->makeService();
        $account = $this->account();

        $service->forAccount($account);
        $afterFirst = count(Http::recorded());

        $service->forAccount($account);
        $this->assertCount($afterFirst, Http::recorded(), 'second call must be served from cache');

        $service->forAccount($account, fresh: true);
        $this->assertGreaterThan($afterFirst, count(Http::recorded()), 'fresh=true must bypass the cache');
    }

    public function test_failures_are_not_cached(): void
    {
        Http::fake([
            '*/json-api/*' => Http::response(['status' => 0, 'error' => 'connection refused'], 200),
        ]);

        $service = $this->makeService();
        $account = $this->account();

        $first = $service->forAccount($account);
        $afterFirst = count(Http::recorded());

        // Nothing was learned from the server, so this is not a cacheable success.
        $this->assertFalse($first['success']);
        $this->assertFalse($first['available']);

        $service->forAccount($account);
        $this->assertGreaterThan($afterFirst, count(Http::recorded()), 'a failed fetch must not be cached');
    }

    public function test_domain_cap_is_enforced_and_warned_about(): void
    {
        config(['whm.email_deliverability_max_domains' => 2]);

        $addons = [];
        foreach (['a.com', 'b.com', 'c.com', 'd.com'] as $domain) {
            $addons[] = ['domain' => $domain, 'documentroot' => '/home/u/'.$domain];
        }

        $sequence = Http::sequence()->push($this->uapi([
            'main_domain' => ['domain' => 'diplomas.claudsoft.com'],
            'addon_domains' => $addons,
        ]), 200);
        for ($i = 0; $i < 6; $i++) {
            $sequence->push($this->uapi([['domain' => 'x', 'spf' => ['state' => 'VALID']]]), 200);
        }

        $this->fakeAll(['*/json-api/cpanel*' => $sequence]);

        $result = $this->makeService()->forAccount($this->account());

        $this->assertCount(2, $result['domains']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('أول 2 نطاق', $result['warnings'][0]);
        // Main domain always sorts first.
        $this->assertSame('diplomas.claudsoft.com', $result['domains'][0]['domain']);
    }

    public function test_service_subdomains_are_filtered_out(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->uapi([
                    'main_domain' => ['domain' => 'diplomas.claudsoft.com'],
                    'sub_domains' => [
                        ['domain' => 'mail.diplomas.claudsoft.com'],
                        ['domain' => 'webmail.diplomas.claudsoft.com'],
                        ['domain' => 'shop.diplomas.claudsoft.com'],
                    ],
                ]), 200)
                ->push($this->uapi([['domain' => 'diplomas.claudsoft.com', 'spf' => ['state' => 'VALID']]]), 200)
                ->push($this->uapi([['domain' => 'shop.diplomas.claudsoft.com', 'spf' => ['state' => 'VALID']]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $domains = array_column($result['domains'], 'domain');
        $this->assertSame(['diplomas.claudsoft.com', 'shop.diplomas.claudsoft.com'], $domains);
    }

    public function test_domain_list_falls_back_to_the_account_domain(): void
    {
        $this->fakeAll([
            '*/json-api/cpanel*' => Http::sequence()
                ->push($this->uapi(null, 0), 200)   // domains_data fails
                ->push($this->uapi(null, 0), 200)   // domain-less validate_current_setup fails
                ->push($this->uapi([['domain' => 'diplomas.claudsoft.com', 'spf' => ['state' => 'VALID']]]), 200),
        ]);

        $result = $this->makeService()->forAccount($this->account());

        $this->assertCount(1, $result['domains']);
        $this->assertSame('diplomas.claudsoft.com', $result['domains'][0]['domain']);
    }

    public function test_missing_whm_settings_returns_an_unconfigured_result(): void
    {
        Http::fake();

        $settings = Mockery::mock(WhmSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
            'host' => '', 'username' => 'root', 'api_token' => '', 'verify_ssl' => true,
            'default_package' => 'default', 'default_domain_suffix' => '', 'timeout' => 30,
            'token_configured' => false,
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        $service = new WhmEmailDeliverabilityService(new WhmApiService($settings), $settings);
        $result = $service->forAccount($this->account());

        $this->assertFalse($result['success']);
        $this->assertFalse($result['configured']);
        $this->assertSame([], $result['domains']);
        Http::assertNothingSent();
    }
}
