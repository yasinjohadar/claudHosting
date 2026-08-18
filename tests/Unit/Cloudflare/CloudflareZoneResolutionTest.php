<?php

namespace Tests\Unit\Cloudflare;

use App\Services\Cloudflare\CloudflareSettingsService;
use App\Services\CloudflareApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Zone resolution for a SUBdomain — the case that was broken twice over:
 * resolveZoneIdForDomain matched only exactly, and it passed the hostname into
 * listAllZones() as a `name=contains:` filter that a parent zone can never satisfy.
 */
class CloudflareZoneResolutionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function makeService(string $token = 'TOKEN123'): CloudflareApiService
    {
        $settings = Mockery::mock(CloudflareSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
            'api_token' => $token,
            'account_id' => str_repeat('a', 32),
            'timeout' => 30,
            'cache_ttl' => 600,
            'token_configured' => $token !== '',
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        return new CloudflareApiService($settings);
    }

    /**
     * @param  list<array{id: string, name: string}>  $zones
     */
    protected function fakeZones(array $zones): void
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => $zones,
                'result_info' => ['total_pages' => 1],
            ], 200),
        ]);
    }

    /**
     * Http::fake() APPENDS stubs and the first match wins, so a test that needs two
     * different zone-list responses must use a sequence rather than faking twice.
     *
     * @param  list<list<array{id?: string, name: string}>>  $pages
     */
    protected function fakeZoneSequence(array $pages): void
    {
        $sequence = Http::fakeSequence('https://api.cloudflare.com/client/v4/zones*');
        foreach ($pages as $zones) {
            $sequence->push([
                'success' => true,
                'result' => $zones,
                'result_info' => ['total_pages' => 1],
            ], 200);
        }
    }

    public function test_resolves_the_parent_zone_for_a_subdomain(): void
    {
        $this->fakeZones([
            ['id' => 'zone-other', 'name' => 'example.com'],
            ['id' => 'zone-claud', 'name' => 'claudsoft.com'],
        ]);

        $zone = $this->makeService()->resolveZoneForHostname('docs.claudsoft.com');

        $this->assertSame(['id' => 'zone-claud', 'name' => 'claudsoft.com'], $zone);
    }

    public function test_prefers_the_most_specific_zone_when_both_exist(): void
    {
        // If the subdomain is itself delegated as a zone, it must win over the parent.
        $this->fakeZones([
            ['id' => 'zone-claud', 'name' => 'claudsoft.com'],
            ['id' => 'zone-docs', 'name' => 'docs.claudsoft.com'],
        ]);

        $zone = $this->makeService()->resolveZoneForHostname('docs.claudsoft.com');

        $this->assertSame('zone-docs', $zone['id']);
    }

    public function test_zone_list_is_requested_without_a_name_filter(): void
    {
        $this->fakeZones([['id' => 'zone-claud', 'name' => 'claudsoft.com']]);

        $this->makeService()->resolveZoneForHostname('docs.claudsoft.com');

        // The `contains:` filter was one of the two reasons the old code failed.
        Http::assertSent(function ($request) {
            return ! str_contains($request->url(), 'name=')
                && ! str_contains(urldecode($request->url()), 'contains:');
        });
    }

    public function test_the_zone_list_is_fetched_once_and_cached_across_lookups(): void
    {
        $this->fakeZones([['id' => 'zone-claud', 'name' => 'claudsoft.com']]);

        $service = $this->makeService();
        $service->resolveZoneForHostname('docs.claudsoft.com');
        $after = count(Http::recorded());

        $service->resolveZoneForHostname('mail.claudsoft.com');
        $this->assertCount($after, Http::recorded(), 'second lookup must reuse the cached zone list');
    }

    public function test_returns_null_when_no_candidate_is_a_zone(): void
    {
        $this->fakeZones([['id' => 'zone-other', 'name' => 'example.com']]);

        $this->assertNull($this->makeService()->resolveZoneForHostname('docs.claudsoft.com'));
    }

    public function test_apex_hostname_resolves_to_its_own_zone(): void
    {
        $this->fakeZones([['id' => 'zone-claud', 'name' => 'claudsoft.com']]);

        $zone = $this->makeService()->resolveZoneForHostname('claudsoft.com');

        $this->assertSame('zone-claud', $zone['id']);
    }

    public function test_multi_label_public_suffix_resolves_without_a_suffix_list(): void
    {
        // A naive apex guess would produce co.uk; asking Cloudflare gets it right.
        $this->fakeZones([['id' => 'zone-uk', 'name' => 'example.co.uk']]);

        $zone = $this->makeService()->resolveZoneForHostname('shop.example.co.uk');

        $this->assertSame(['id' => 'zone-uk', 'name' => 'example.co.uk'], $zone);
    }

    public function test_zones_without_an_id_are_ignored(): void
    {
        // The old code could return '' for a zone row missing its id.
        $this->fakeZones([
            ['name' => 'claudsoft.com'],
            ['id' => 'zone-claud', 'name' => 'claudsoft.com'],
        ]);

        $zone = $this->makeService()->resolveZoneForHostname('docs.claudsoft.com');

        $this->assertSame('zone-claud', $zone['id']);
    }

    public function test_resolve_zone_id_for_domain_delegates_to_the_label_walk(): void
    {
        $this->fakeZones([['id' => 'zone-claud', 'name' => 'claudsoft.com']]);

        $this->assertSame('zone-claud', $this->makeService()->resolveZoneIdForDomain('docs.claudsoft.com'));
    }

    public function test_resolve_zone_id_for_domain_returns_null_not_an_empty_string(): void
    {
        // The old implementation could hand callers '' for a zone row missing its id.
        $this->fakeZones([['name' => 'claudsoft.com']]);

        $this->assertNull($this->makeService()->resolveZoneIdForDomain('docs.claudsoft.com'));
    }

    public function test_unconfigured_token_resolves_to_null_without_any_request(): void
    {
        Http::fake();

        $this->assertNull($this->makeService('')->resolveZoneForHostname('docs.claudsoft.com'));
        Http::assertNothingSent();
    }

    public function test_forget_zone_list_cache_forces_a_refetch(): void
    {
        // listAllZones caches its own failures, so the escape hatch matters: an empty
        // first response would otherwise pin every lookup to null for the whole TTL.
        $this->fakeZoneSequence([
            [],
            [['id' => 'zone-claud', 'name' => 'claudsoft.com']],
        ]);

        $service = $this->makeService();
        $this->assertNull($service->resolveZoneForHostname('docs.claudsoft.com'));

        // Without forgetting, the cached empty list would still be in play.
        $service->forgetZoneListCache();

        $zone = $service->resolveZoneForHostname('docs.claudsoft.com');
        $this->assertNotNull($zone, 'forgetZoneListCache did not clear the cached empty list');
        $this->assertSame('zone-claud', $zone['id']);
    }

    public function test_the_cached_empty_list_persists_until_it_is_forgotten(): void
    {
        $this->fakeZoneSequence([
            [],
            [['id' => 'zone-claud', 'name' => 'claudsoft.com']],
        ]);

        $service = $this->makeService();
        $this->assertNull($service->resolveZoneForHostname('docs.claudsoft.com'));
        // No forget() here — the second response must not be reached.
        $this->assertNull($service->resolveZoneForHostname('docs.claudsoft.com'));
    }
}
