<?php

namespace Tests\Unit\Cloudflare;

use App\Services\Cloudflare\CloudflareSettingsService;
use App\Services\CloudflareApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * The settings page used to show a green "DNS" check for a read-only token, which then
 * failed on the first write. These lock the new DNS·Edit capability — and that probing
 * it never creates anything.
 */
class CloudflareTokenCapabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Cache::flush();
        parent::tearDown();
    }

    protected function makeService(): CloudflareApiService
    {
        $settings = Mockery::mock(CloudflareSettingsService::class);
        $settings->shouldReceive('getConnectionConfig')->andReturn([
            'api_token' => 'TOKEN123',
            'account_id' => str_repeat('a', 32),
            'timeout' => 30,
            'cache_ttl' => 600,
            'token_configured' => true,
        ]);
        $settings->shouldReceive('clearCache')->andReturnNull();

        return new CloudflareApiService($settings);
    }

    /**
     * One fake covering every call the capability probe makes. Http::fake() APPENDS
     * stubs and the FIRST match wins, so all of this has to be registered in a single
     * call — and the dns_records stub must branch on the HTTP method, otherwise a
     * blanket success would make the write probe pass for the wrong reason.
     *
     * @param  list<string>  $permissionNames  permission groups on the token, [] = verify fails
     * @param  int|null  $dnsWriteStatus  status for POST /dns_records (null = 200 success)
     */
    protected function fakeAll(array $permissionNames, ?int $dnsWriteStatus = null): void
    {
        $verify = $permissionNames === []
            ? Http::response(['success' => false, 'errors' => [['message' => 'nope']]], 403)
            : Http::response([
                'success' => true,
                'result' => [
                    'id' => 'tok1',
                    'status' => 'active',
                    'policies' => [[
                        'effect' => 'allow',
                        'permission_groups' => array_map(fn (string $n): array => ['name' => $n], $permissionNames),
                        'resources' => ['com.cloudflare.api.account.zone.*' => '*'],
                    ]],
                ],
            ], 200);

        Http::fake([
            '*/user/tokens/verify' => $verify,
            '*/accounts*' => Http::response(['success' => true, 'result' => [['id' => str_repeat('a', 32)]]], 200),
            // GET = the read probe (always allowed here), POST = the write probe.
            '*/zones/zone1/dns_records*' => function ($request) use ($dnsWriteStatus) {
                if ($request->method() !== 'POST') {
                    return Http::response(['success' => true, 'result' => [], 'result_info' => ['total_pages' => 1]], 200);
                }

                if ($dnsWriteStatus === null) {
                    return Http::response(['success' => true, 'result' => ['id' => 'rec1']], 200);
                }

                return Http::response([
                    'success' => false,
                    'errors' => [['message' => $dnsWriteStatus === 400 ? 'content is required' : 'Authentication error']],
                ], $dnsWriteStatus);
            },
            '*/zones/zone1/settings/ssl' => Http::response(['success' => true, 'result' => ['value' => 'full']], 200),
            '*/zones*' => Http::response([
                'success' => true,
                'result' => [['id' => 'zone1', 'name' => 'claudsoft.com']],
                'result_info' => ['total_pages' => 1],
            ], 200),
        ]);
    }

    protected function capability(array $summary, string $key): ?array
    {
        foreach ($summary['panel_capabilities'] ?? [] as $capability) {
            if (($capability['key'] ?? null) === $key) {
                return $capability;
            }
        }

        return null;
    }

    public function test_dns_edit_is_inferred_from_the_token_policies_without_any_write_call(): void
    {
        $this->fakeAll(['Zone Read', 'DNS Write']);

        $summary = $this->makeService()->getTokenPermissionsSummary();
        $capability = $this->capability($summary, 'dns_edit');

        $this->assertNotNull($capability, 'dns_edit capability is missing from the summary');
        $this->assertTrue($capability['allowed']);
        $this->assertSame('DNS · Edit', $capability['hint']);

        // Nothing was POSTed to dns_records — the inference is free and non-destructive.
        foreach (Http::recorded() as [$request]) {
            if (str_contains($request->url(), 'dns_records')) {
                $this->assertNotSame('POST', $request->method(), 'the policy path must not POST');
            }
        }
    }

    public function test_a_read_only_dns_token_reports_no_write_capability(): void
    {
        $this->fakeAll(['Zone Read', 'DNS Read']);

        $summary = $this->makeService()->getTokenPermissionsSummary();

        // Read still green, write explicitly not — that distinction is the whole point.
        $this->assertTrue($this->capability($summary, 'dns')['allowed']);
        $this->assertFalse($this->capability($summary, 'dns_edit')['allowed']);
    }

    public function test_fallback_probe_treats_a_validation_error_as_permitted(): void
    {
        // Policies unreadable → probe. Cloudflare authorises before it validates, so a
        // 400 on a deliberately incomplete body means the write would have been allowed.
        $this->fakeAll([], dnsWriteStatus: 400);

        $summary = $this->makeService()->getTokenPermissionsSummary();

        $this->assertTrue($this->capability($summary, 'dns_edit')['allowed']);
    }

    public function test_fallback_probe_sends_nothing_creatable(): void
    {
        $this->fakeAll([], dnsWriteStatus: 400);

        $this->makeService()->getTokenPermissionsSummary();

        $posted = 0;
        foreach (Http::recorded() as [$request]) {
            if ($request->method() === 'POST' && str_contains($request->url(), 'dns_records')) {
                $posted++;
                // A body with only a type can never create a record.
                $this->assertSame(['type' => 'A'], $request->data());
            }
        }
        $this->assertSame(1, $posted, 'the probe must POST exactly once');
    }

    public function test_fallback_probe_treats_forbidden_as_not_permitted(): void
    {
        $this->fakeAll([], dnsWriteStatus: 403);

        $summary = $this->makeService()->getTokenPermissionsSummary();

        $this->assertFalse($this->capability($summary, 'dns_edit')['allowed']);
    }

    public function test_capability_list_defaults_dns_edit_to_not_allowed_before_probing(): void
    {
        Http::fake();

        $service = $this->makeService();
        $reflection = new \ReflectionMethod($service, 'panelCapabilityDefinitions');
        $defs = $reflection->invoke($service, false, null);

        $dnsEdit = null;
        foreach ($defs as $def) {
            if ($def['key'] === 'dns_edit') {
                $dnsEdit = $def;
            }
        }

        $this->assertNotNull($dnsEdit);
        $this->assertFalse($dnsEdit['allowed'], 'never show a green write check before probing');
        Http::assertNothingSent();
    }
}
