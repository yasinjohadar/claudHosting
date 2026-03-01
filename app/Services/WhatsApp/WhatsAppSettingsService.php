<?php

namespace App\Services\WhatsApp;

use App\Models\SystemSetting;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class WhatsAppSettingsService
{
    /**
     * Get WhatsApp settings from database
     */
    public function getSettings(): array
    {
        $settings = SystemSetting::where('group', 'whatsapp')
            ->get()
            ->keyBy('key')
            ->map(function ($setting) {
                return $setting->value;
            })
            ->toArray();

        return [
            'whatsapp_enabled' => filter_var($settings['whatsapp_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'whatsapp_provider' => $settings['whatsapp_provider'] ?? 'meta',
            'api_version' => $settings['api_version'] ?? 'v20.0',
            'phone_number_id' => $settings['phone_number_id'] ?? '',
            'waba_id' => $settings['waba_id'] ?? '',
            'access_token' => $this->decryptIfEncrypted($settings['access_token'] ?? ''),
            'verify_token' => $settings['verify_token'] ?? '',
            'app_secret' => $this->decryptIfEncrypted($settings['app_secret'] ?? ''),
            'webhook_path' => $settings['webhook_path'] ?? '/api/webhooks/whatsapp',
            'default_from' => $settings['default_from'] ?? '',
            'strict_signature' => filter_var($settings['strict_signature'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'auto_reply' => filter_var($settings['auto_reply'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'auto_reply_message' => $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.',
            'timeout' => $settings['timeout'] ?? 30,
            // Custom API settings
            'custom_api_url' => $settings['custom_api_url'] ?? '',
            'custom_api_key' => $this->decryptIfEncrypted($settings['custom_api_key'] ?? ''),
            'custom_api_method' => $settings['custom_api_method'] ?? 'POST',
            'custom_api_headers' => $this->parseHeaders($settings['custom_api_headers'] ?? '{}'),
            // WhatsApp Web settings
            'whatsapp_web_service_url' => $settings['whatsapp_web_service_url'] ?? 'http://localhost:3000',
            'whatsapp_web_api_token' => $this->decryptIfEncrypted($settings['whatsapp_web_api_token'] ?? ''),
            // Delay settings
            'delay_between_messages' => (int) ($settings['delay_between_messages'] ?? 3),
            'delay_between_broadcasts' => (int) ($settings['delay_between_broadcasts'] ?? 5),
            'max_messages_per_minute' => (int) ($settings['max_messages_per_minute'] ?? 20),
            'random_delay_enabled' => filter_var($settings['random_delay_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'min_delay' => (int) ($settings['min_delay'] ?? 2),
            'max_delay' => (int) ($settings['max_delay'] ?? 5),
        ];
    }

    /**
     * Update WhatsApp settings in database
     */
    public function updateSettings(array $newSettings): void
    {
        foreach ($newSettings as $key => $value) {
            // Encrypt sensitive fields
            if (in_array($key, ['access_token', 'app_secret', 'custom_api_key', 'whatsapp_web_api_token']) && !empty($value)) {
                $value = Crypt::encryptString($value);
            }

            // Handle JSON fields - custom_api_headers comes as string from textarea
            if ($key === 'custom_api_headers') {
                if (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_string($value) && !empty(trim($value))) {
                    // Validate JSON if it's a non-empty string
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Invalid JSON, set to empty object
                        $value = '{}';
                    }
                    // If valid JSON, keep as is (already a string)
                } else {
                    // Empty or null, set to empty object
                    $value = '{}';
                }
            }

            SystemSetting::set(
                $key,
                $value,
                'string',
                'whatsapp'
            );
        }
    }

    /**
     * Initialize default settings if not exists
     */
    public function initializeDefaults(): void
    {
        $defaults = [
            'whatsapp_enabled' => false,
            'whatsapp_provider' => 'meta',
            'api_version' => 'v20.0',
            'phone_number_id' => '',
            'waba_id' => '',
            'access_token' => '',
            'verify_token' => '',
            'app_secret' => '',
            'webhook_path' => '/api/webhooks/whatsapp',
            'default_from' => '',
            'strict_signature' => true,
            'auto_reply' => false,
            'auto_reply_message' => 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.',
            'timeout' => 30,
            'custom_api_url' => '',
            'custom_api_key' => '',
            'custom_api_method' => 'POST',
            'custom_api_headers' => '{}',
            // WhatsApp Web settings
            'whatsapp_web_service_url' => 'http://localhost:3000',
            'whatsapp_web_api_token' => '',
            // Delay settings
            'delay_between_messages' => '3',
            'delay_between_broadcasts' => '5',
            'max_messages_per_minute' => '20',
            'random_delay_enabled' => 'true',
            'min_delay' => '2',
            'max_delay' => '5',
        ];

        foreach ($defaults as $key => $value) {
            if (!SystemSetting::byKey($key)->ofGroup('whatsapp')->exists()) {
                SystemSetting::set($key, $value, 'string', 'whatsapp');
            }
        }
    }

    /**
     * Get provider configuration based on selected provider
     */
    public function getProviderConfig(): array
    {
        $settings = $this->getSettings();
        $provider = $settings['whatsapp_provider'];

        if ($provider === 'custom_api') {
            return [
                'api_url' => $settings['custom_api_url'],
                'api_key' => $settings['custom_api_key'],
                'api_method' => $settings['custom_api_method'],
                'headers' => $settings['custom_api_headers'],
            ];
        }

        if ($provider === 'whatsapp_web') {
            return [
                'nodejs_service_url' => $settings['whatsapp_web_service_url'],
                'api_token' => $settings['whatsapp_web_api_token'],
            ];
        }

        // Default to Meta
        return [
            'api_version' => $settings['api_version'],
            'phone_number_id' => $settings['phone_number_id'],
            'access_token' => $settings['access_token'],
            'timeout' => $settings['timeout'] ?? 30,
        ];
    }

    /**
     * Get delay settings for message sending
     */
    public function getDelaySettings(): array
    {
        $settings = $this->getSettings();
        
        return [
            'delay_between_messages' => $settings['delay_between_messages'],
            'delay_between_broadcasts' => $settings['delay_between_broadcasts'],
            'max_messages_per_minute' => $settings['max_messages_per_minute'],
            'random_delay_enabled' => $settings['random_delay_enabled'],
            'min_delay' => $settings['min_delay'],
            'max_delay' => $settings['max_delay'],
        ];
    }

    /**
     * Calculate delay for message sending (with random variation if enabled)
     */
    public function calculateDelay(?int $customDelay = null): int
    {
        $delaySettings = $this->getDelaySettings();
        $baseDelay = $customDelay ?? $delaySettings['delay_between_messages'];
        
        if ($delaySettings['random_delay_enabled']) {
            $min = $delaySettings['min_delay'];
            $max = $delaySettings['max_delay'];
            // Add random variation between min and max
            $randomVariation = rand($min, $max);
            return $baseDelay + $randomVariation;
        }
        
        return $baseDelay;
    }

    /**
     * Decrypt value if it's encrypted
     */
    protected function decryptIfEncrypted(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (Exception $e) {
            // If decryption fails, it might be plain text (during first setup)
            return $value;
        }
    }

    /**
     * Parse headers JSON string to array
     */
    protected function parseHeaders(?string $headersJson): array
    {
        if (empty($headersJson)) {
            return [];
        }

        try {
            $decoded = json_decode($headersJson, true);
            return is_array($decoded) ? $decoded : [];
        } catch (Exception $e) {
            return [];
        }
    }
}




