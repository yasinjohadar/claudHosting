<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Closure;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class LaravelAiConfigurator
{
    public function __construct(
        protected AIModelResolver $resolver
    ) {
    }

    public function using(AIModel $model, Closure $callback): mixed
    {
        $driver = $this->resolver->resolveDriver($model);

        if (! $driver) {
            throw new RuntimeException("Model provider [{$model->provider}] is not supported by Laravel AI SDK.");
        }

        $configKey = "ai.providers.{$driver}";
        $original = Config::get($configKey, []);

        $updated = $original;
        $apiKey = $model->getDecryptedApiKey();

        if ($apiKey) {
            $updated['key'] = $apiKey;
        }

        if (! empty($model->base_url)) {
            $updated['url'] = $model->base_url;
        }

        Config::set($configKey, $updated);

        try {
            return $callback();
        } finally {
            Config::set($configKey, $original);
        }
    }
}

