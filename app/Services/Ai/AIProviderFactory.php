<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use App\Services\Ai\ManusProviderService;
use App\Services\Ai\GroqProviderService;
use InvalidArgumentException;

class AIProviderFactory
{
    /**
     * إنشاء instance من Provider حسب نوع الموديل
     */
    public static function create(AIModel $model): AIProviderService
    {
        return match($model->provider) {
            'openai' => new OpenAIProviderService($model),
            'anthropic' => new AnthropicProviderService($model),
            'google' => new GoogleProviderService($model),
            'openrouter' => new OpenRouterProviderService($model),
            'zai' => new ZaiProviderService($model),
            'manus' => new ManusProviderService($model),
            'groq' => new GroqProviderService($model),
            'local' => new LocalLLMProviderService($model),
            'custom' => new OpenRouterProviderService($model), // Custom يستخدم نفس بنية OpenAI
            default => throw new InvalidArgumentException("Unsupported provider: {$model->provider}"),
        };
    }
}

