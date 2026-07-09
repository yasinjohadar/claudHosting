<?php

namespace Tests\Unit;

use App\Services\WhatsApp\Evolution\EvolutionRotatingSendService;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EvolutionRotatingSendServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_global_delay_waits_between_consecutive_sends(): void
    {
        $this->bindDelaySettings(1);

        $service = app(EvolutionRotatingSendService::class);

        $start = microtime(true);
        $service->waitBeforeNextGlobalSend();
        $service->waitBeforeNextGlobalSend();
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.9, $elapsed);
    }

    public function test_global_delay_applies_before_each_send_with_rotation_disabled(): void
    {
        $this->bindDelaySettings(1, rotationEnabled: false);

        $service = app(EvolutionRotatingSendService::class);

        $timestamps = [];
        $service->sendWithRotation(function (string $instanceName) use (&$timestamps) {
            $timestamps[] = microtime(true);

            return $instanceName;
        });

        $service->sendWithRotation(function (string $instanceName) use (&$timestamps) {
            $timestamps[] = microtime(true);

            return $instanceName;
        });

        $this->assertCount(2, $timestamps);
        $this->assertGreaterThanOrEqual(0.9, $timestamps[1] - $timestamps[0]);
    }

    private function bindDelaySettings(int $delaySeconds, bool $rotationEnabled = true): void
    {
        $settings = $this->mock(WhatsAppSettingsService::class);
        $settings->shouldReceive('calculateDelay')->andReturn($delaySeconds);
        $settings->shouldReceive('getSettings')->andReturn([
            'whatsapp_provider' => 'evolution',
            'evolution_rotation_enabled' => $rotationEnabled,
            'evolution_instance_name' => 'inst-a',
        ]);
    }
}
