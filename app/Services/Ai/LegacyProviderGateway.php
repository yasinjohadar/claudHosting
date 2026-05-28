<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use RuntimeException;

class LegacyProviderGateway
{
    public function prompt(AIModel $model, string $prompt, array $options = []): string
    {
        return match ($model->provider) {
            'zai' => $this->promptViaZai($model, $prompt, $options),
            'manus' => $this->promptViaManus($model, $prompt, $options),
            default => throw new RuntimeException("Provider [{$model->provider}] is not configured as legacy."),
        };
    }

    protected function promptViaZai(AIModel $model, string $prompt, array $options): string
    {
        $provider = new ZaiProviderService($model);

        return $provider->generateText($prompt, $options);
    }

    protected function promptViaManus(AIModel $model, string $prompt, array $options): string
    {
        $provider = new ManusProviderService($model);

        return $provider->generateText($prompt, $options);
    }
}

