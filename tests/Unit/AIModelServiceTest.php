<?php

namespace Tests\Unit;

use App\Ai\Agents\ConnectionTestAgent;
use App\Models\AIModel;
use App\Services\Ai\AIModelResolver;
use App\Services\Ai\AIModelService;
use App\Services\Ai\LaravelAiConfigurator;
use App\Services\Ai\LegacyProviderGateway;
use ReflectionMethod;
use Tests\TestCase;

class AIModelServiceTest extends TestCase
{
    public function test_it_tests_sdk_connection_with_faked_agent(): void
    {
        ConnectionTestAgent::fake(['OK']);

        $model = new AIModel([
            'provider' => 'openrouter',
            'model_key' => 'openai/gpt-4o-mini',
            'base_url' => 'https://openrouter.ai/api/v1',
        ]);
        $model->setRawApiKeyForTesting('test-key');

        $service = new AIModelService(
            new AIModelResolver,
            new LaravelAiConfigurator(new AIModelResolver),
            new LegacyProviderGateway,
        );

        $method = new ReflectionMethod($service, 'testConnectionByModel');
        $method->setAccessible(true);
        $result = $method->invoke($service, $model);

        $this->assertTrue($result['success']);
    }
}

