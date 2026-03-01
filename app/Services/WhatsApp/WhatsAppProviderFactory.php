<?php

namespace App\Services\WhatsApp;

use App\Services\WhatsApp\Providers\CustomApiProvider;
use App\Services\WhatsApp\Providers\MetaProvider;
use App\Services\WhatsApp\Providers\WhatsAppWebProvider;
use InvalidArgumentException;

class WhatsAppProviderFactory
{
    /**
     * Create WhatsApp provider instance
     *
     * @param string $provider Provider type (meta, custom_api, whatsapp_web)
     * @param array $config Provider configuration
     * @return WhatsAppProviderService
     */
    public static function create(string $provider, array $config): WhatsAppProviderService
    {
        return match ($provider) {
            'meta' => new MetaProvider($config),
            'custom_api' => new CustomApiProvider($config),
            'whatsapp_web' => new WhatsAppWebProvider($config),
            default => throw new InvalidArgumentException("Unsupported WhatsApp provider: {$provider}"),
        };
    }

    /**
     * Get available providers
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        return [
            'meta' => 'Meta WhatsApp Cloud API',
            'custom_api' => 'Custom API Provider',
            'whatsapp_web' => 'WhatsApp Web (QR Code)',
        ];
    }
}

