<?php

namespace Tests\Unit\Infrastructure;

use App\Services\Infrastructure\InfrastructureSettingsService;
use App\Services\Infrastructure\Netcup\NetcupScpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class NetcupScpClientTokenCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::forget(NetcupScpClient::TOKEN_CACHE_KEY);
        parent::tearDown();
    }

    public function test_access_token_is_reused_across_calls_even_after_refresh_token_rotation(): void
    {
        // Only one token-endpoint response is faked on purpose: if the caching bug
        // regresses (cache key derived from the rotating refresh token), the second
        // request() call below would need a second token exchange and this test
        // would fail with an "out of responses" exception instead of a clean assertion.
        Http::fake([
            '*/protocol/openid-connect/token' => Http::response([
                'access_token' => 'tok-1',
                'refresh_token' => 'rot-1',
                'expires_in' => 300,
            ], 200),
            '*/scp-core/api/v1/servers*' => Http::response(['servers' => []], 200),
        ]);

        $settings = Mockery::mock(InfrastructureSettingsService::class);
        $settings->shouldReceive('getCredentials')->andReturn([
            'netcup_refresh_token' => 'seed-refresh-token',
            'netcup_customer_number' => '',
            'netcup_client_id' => '',
            'netcup_api_password' => '',
            'netcup_client_secret' => '',
        ]);
        $settings->shouldReceive('save')->andReturnNull();

        $client = new NetcupScpClient($settings);

        $first = $client->request('GET', '/servers', ['limit' => 1]);
        $second = $client->request('GET', '/servers', ['limit' => 1]);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);

        Http::assertSentCount(3);
    }
}
