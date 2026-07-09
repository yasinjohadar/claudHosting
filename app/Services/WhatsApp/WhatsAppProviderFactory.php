<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Providers\EvolutionApiProvider;

class WhatsAppProviderFactory
{
    public static function create(string $provider, array $config): WhatsAppProviderService
    {
        if ($provider !== 'evolution') {
            $provider = 'evolution';
        }

        return new EvolutionApiProvider($config);
    }

    public static function getAvailableProviders(): array
    {
        return [
            'evolution' => 'Evolution API',
        ];
    }
}
