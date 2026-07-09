<?php

namespace Tests\Unit;

use App\Models\EvolutionInstance;
use App\Services\WhatsApp\Evolution\EvolutionInstanceRotator;
use App\Services\WhatsApp\Evolution\EvolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EvolutionInstanceRotatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_round_robin_cycles_through_connected_instances(): void
    {
        EvolutionInstance::create([
            'instance_name' => 'inst-a',
            'connection_status' => 'open',
            'rotation_enabled' => true,
        ]);
        EvolutionInstance::create([
            'instance_name' => 'inst-b',
            'connection_status' => 'open',
            'rotation_enabled' => true,
        ]);
        EvolutionInstance::create([
            'instance_name' => 'inst-c',
            'connection_status' => 'close',
            'rotation_enabled' => true,
        ]);

        $rotator = app(EvolutionInstanceRotator::class);

        $first = $rotator->nextInstance()->instance_name;
        $rotator->markUsed(EvolutionInstance::where('instance_name', $first)->firstOrFail());
        $second = $rotator->nextInstance()->instance_name;
        $rotator->markUsed(EvolutionInstance::where('instance_name', $second)->firstOrFail());
        $third = $rotator->nextInstance()->instance_name;

        $this->assertContains($first, ['inst-a', 'inst-b']);
        $this->assertContains($second, ['inst-a', 'inst-b']);
        $this->assertNotSame($first, $second);
        $this->assertSame($first, $third);
    }

    public function test_rotation_disabled_instances_are_excluded(): void
    {
        EvolutionInstance::create([
            'instance_name' => 'inst-a',
            'connection_status' => 'open',
            'rotation_enabled' => false,
        ]);
        EvolutionInstance::create([
            'instance_name' => 'inst-b',
            'connection_status' => 'open',
            'rotation_enabled' => true,
        ]);

        $rotator = app(EvolutionInstanceRotator::class);

        $this->assertSame(1, $rotator->poolCount());
        $this->assertSame('inst-b', $rotator->nextInstance()->instance_name);
    }

    public function test_pool_refresh_promotes_manual_instance_when_api_reports_open(): void
    {
        Cache::flush();

        EvolutionInstance::create([
            'instance_name' => 'manual-inst',
            'connection_status' => 'pending',
            'is_manual' => true,
            'rotation_enabled' => true,
        ]);

        $evolutionMock = $this->mock(EvolutionService::class);
        $evolutionMock->shouldReceive('refreshRotationCandidates')
            ->andReturnUsing(function () {
                EvolutionInstance::where('instance_name', 'manual-inst')
                    ->update(['connection_status' => 'open']);

                return 1;
            });

        $rotator = app(EvolutionInstanceRotator::class);

        $this->assertSame(1, $rotator->poolCount(true));
        $this->assertSame('manual-inst', $rotator->nextInstance(true)->instance_name);
    }

    public function test_least_recently_used_prefers_unused_instance(): void
    {
        Cache::flush();

        $instanceA = EvolutionInstance::create([
            'instance_name' => 'inst-a',
            'connection_status' => 'open',
            'rotation_enabled' => true,
            'last_used_at' => now()->subMinute(),
        ]);
        EvolutionInstance::create([
            'instance_name' => 'inst-b',
            'connection_status' => 'open',
            'rotation_enabled' => true,
            'last_used_at' => null,
        ]);

        $rotator = app(EvolutionInstanceRotator::class);

        $this->assertSame('inst-b', $rotator->nextInstance()->instance_name);

        $rotator->markUsed($instanceA->fresh());

        $this->assertSame('inst-b', $rotator->nextInstance()->instance_name);
    }
}
