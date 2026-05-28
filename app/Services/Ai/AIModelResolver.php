<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Laravel\Ai\Enums\Lab;

class AIModelResolver
{
    public function resolveLab(AIModel $model): ?Lab
    {
        return match ($model->provider) {
            'openai' => Lab::OpenAI,
            'anthropic' => Lab::Anthropic,
            'google' => Lab::Gemini,
            'openrouter', 'custom' => Lab::OpenRouter,
            'groq' => Lab::Groq,
            'local' => Lab::Ollama,
            default => null,
        };
    }

    public function resolveDriver(AIModel $model): ?string
    {
        return match ($model->provider) {
            'openai' => 'openai',
            'anthropic' => 'anthropic',
            'google' => 'gemini',
            'openrouter', 'custom' => 'openrouter',
            'groq' => 'groq',
            'local' => 'ollama',
            default => null,
        };
    }

    public function isLegacy(AIModel $model): bool
    {
        return in_array($model->provider, ['zai', 'manus'], true);
    }

    public function supportsSdk(AIModel $model): bool
    {
        return $this->resolveLab($model) !== null;
    }
}

