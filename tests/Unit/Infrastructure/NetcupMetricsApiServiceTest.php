<?php

namespace Tests\Unit\Infrastructure;

use App\Services\Infrastructure\Netcup\NetcupMetricsApiService;
use App\Services\Infrastructure\Netcup\NetcupScpClient;
use Mockery;
use Tests\TestCase;

class NetcupMetricsApiServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_metrics_query_defaults_and_clamps_hours(): void
    {
        config(['infrastructure.netcup.metrics_default_hours' => 6]);
        $client = Mockery::mock(NetcupScpClient::class);
        $service = new NetcupMetricsApiService($client);

        $this->assertSame(['hours' => 6], $service->metricsQuery([]));
        $this->assertSame(['hours' => 24], $service->metricsQuery(['hours' => 24]));
        $this->assertSame(['hours' => 1], $service->metricsQuery(['hours' => 0]));
        $this->assertSame(['hours' => 1440], $service->metricsQuery(['hours' => 9999]));
    }

    public function test_is_empty_payload(): void
    {
        $client = Mockery::mock(NetcupScpClient::class);
        $service = new NetcupMetricsApiService($client);

        $this->assertTrue($service->isEmptyPayload(null));
        $this->assertTrue($service->isEmptyPayload([]));
        $this->assertFalse($service->isEmptyPayload(['2024-01-01T00:00:00Z' => ['cpu0' => 1]]));
    }
}
