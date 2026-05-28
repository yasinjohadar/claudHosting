<?php

namespace Tests\Unit;

use App\Models\AIModel;
use App\Services\Ai\AIModelResolver;
use Laravel\Ai\Enums\Lab;
use Tests\TestCase;

class AIModelResolverTest extends TestCase
{
    public function test_it_maps_openrouter_provider_to_sdk_lab(): void
    {
        $resolver = new AIModelResolver;
        $model = new AIModel(['provider' => 'openrouter']);

        $this->assertSame(Lab::OpenRouter, $resolver->resolveLab($model));
        $this->assertSame('openrouter', $resolver->resolveDriver($model));
    }

    public function test_it_marks_zai_and_manus_as_legacy(): void
    {
        $resolver = new AIModelResolver;

        $zai = new AIModel(['provider' => 'zai']);
        $manus = new AIModel(['provider' => 'manus']);

        $this->assertTrue($resolver->isLegacy($zai));
        $this->assertTrue($resolver->isLegacy($manus));
        $this->assertNull($resolver->resolveLab($zai));
    }
}

