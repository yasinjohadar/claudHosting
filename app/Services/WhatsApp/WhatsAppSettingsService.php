<?php

namespace App\Services\WhatsApp;

use App\Models\SystemSetting;
use Exception;
use Illuminate\Support\Facades\Crypt;

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
            'whatsapp_provider' => 'evolution',
            'api_version' => $settings['api_version'] ?? 'v20.0',
            'phone_number_id' => $settings['phone_number_id'] ?? '',
            'waba_id' => $settings['waba_id'] ?? '',
            'access_token' => $this->decryptIfEncrypted($settings['access_token'] ?? ''),
            'verify_token' => $settings['verify_token'] ?? '',
            'app_secret' => $this->decryptIfEncrypted($settings['app_secret'] ?? ''),
            'webhook_path' => $settings['webhook_path'] ?? '/api/webhooks/evolution',
            'default_from' => $settings['default_from'] ?? '',
            'strict_signature' => filter_var($settings['strict_signature'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'auto_reply' => filter_var($settings['auto_reply'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'auto_reply_message' => $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.',
            'auto_reply_use_ai' => filter_var($settings['auto_reply_use_ai'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'auto_reply_ai_model_id' => $settings['auto_reply_ai_model_id'] ?? null,
            'auto_reply_ai_system_prompt' => $settings['auto_reply_ai_system_prompt'] ?? '',
            'auto_reply_evolution_instance' => $settings['auto_reply_evolution_instance'] ?? '',
            'auto_reply_faq_context' => $settings['auto_reply_faq_context'] ?? '',
            'auto_reply_initial_delay_min' => (int) ($settings['auto_reply_initial_delay_min'] ?? 2),
            'auto_reply_initial_delay_max' => (int) ($settings['auto_reply_initial_delay_max'] ?? 5),
            'auto_reply_typing_duration' => (int) ($settings['auto_reply_typing_duration'] ?? 3),
            'auto_reply_max_chunks' => (int) ($settings['auto_reply_max_chunks'] ?? 3),
            'auto_reply_chunk_max_chars' => (int) ($settings['auto_reply_chunk_max_chars'] ?? 350),
            'auto_reply_contact_cooldown' => (int) ($settings['auto_reply_contact_cooldown'] ?? 45),
            'auto_reply_debounce_seconds' => (int) ($settings['auto_reply_debounce_seconds'] ?? 8),
            'auto_reply_test_phone' => $settings['auto_reply_test_phone'] ?? '',
            'timeout' => $settings['timeout'] ?? 30,
            // Custom API settings
            'custom_api_url' => $settings['custom_api_url'] ?? '',
            'custom_api_key' => $this->decryptIfEncrypted($settings['custom_api_key'] ?? ''),
            'custom_api_method' => $settings['custom_api_method'] ?? 'POST',
            'custom_api_headers' => $this->parseHeaders($settings['custom_api_headers'] ?? '{}'),
            'custom_api_preflight_enabled' => filter_var($settings['custom_api_preflight_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'custom_api_preflight_url' => $settings['custom_api_preflight_url'] ?? '',
            // WhatsApp Web settings (normalize URL: no leading/trailing slashes)
            'whatsapp_web_service_url' => $this->normalizeServiceUrl($settings['whatsapp_web_service_url'] ?? 'http://localhost:3000'),
            'whatsapp_web_api_token' => $this->decryptIfEncrypted($settings['whatsapp_web_api_token'] ?? ''),
            // Delay settings
            'delay_between_messages' => (int) ($settings['delay_between_messages'] ?? 3),
            'delay_between_broadcasts' => (int) ($settings['delay_between_broadcasts'] ?? 5),
            'max_messages_per_minute' => (int) ($settings['max_messages_per_minute'] ?? 20),
            'random_delay_enabled' => filter_var($settings['random_delay_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'min_delay' => (int) ($settings['min_delay'] ?? 2),
            'max_delay' => (int) ($settings['max_delay'] ?? 5),
            // إشعار تقرير الدراسة: email | whatsapp | both
            'study_report_delivery' => $settings['study_report_delivery'] ?? 'both',
            // Flaxxa WAPI (لوحة الإدارة — يُفضّل على .env عند التعيين)
            'wapi_token' => $this->decryptIfEncrypted($settings['wapi_token'] ?? ''),
            'wapi_base_url' => $settings['wapi_base_url'] ?? config('services.whatsapp.base_url', 'https://wapi.flaxxa.com/api/v1'),
            // Evolution API settings
            'evolution_base_url' => $this->normalizeServiceUrl($settings['evolution_base_url'] ?? ''),
            'evolution_api_key' => $this->decryptIfEncrypted($settings['evolution_api_key'] ?? ''),
            'evolution_instance_name' => $settings['evolution_instance_name'] ?? '',
            'evolution_rotation_enabled' => filter_var($settings['evolution_rotation_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'evolution_webhook_secret' => $this->decryptIfEncrypted($settings['evolution_webhook_secret'] ?? ''),
            'evolution_webhook_base_url' => rtrim(trim($settings['evolution_webhook_base_url'] ?? ''), '/'),
        ];
    }

    /**
     * Update WhatsApp settings in database
     */
    public function updateSettings(array $newSettings): void
    {
        foreach ($newSettings as $key => $value) {
            // Encrypt sensitive fields
            if (in_array($key, ['access_token', 'app_secret', 'custom_api_key', 'whatsapp_web_api_token', 'wapi_token', 'evolution_api_key', 'evolution_webhook_secret']) && ! empty($value)) {
                $value = Crypt::encryptString($value);
            }

            // Normalize WhatsApp Web service URL when saving (remove leading slash that causes "غير متاح على: /https://...")
            if ($key === 'whatsapp_web_service_url' && is_string($value)) {
                $value = $this->normalizeServiceUrl($value);
            }

            if ($key === 'evolution_base_url' && is_string($value)) {
                $value = $this->normalizeServiceUrl($value);
            }

            if ($key === 'evolution_webhook_base_url' && is_string($value)) {
                $value = rtrim(trim($value), '/');
            }

            // Handle JSON fields - custom_api_headers comes as string from textarea
            if ($key === 'custom_api_headers') {
                if (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_string($value) && ! empty(trim($value))) {
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
            'whatsapp_provider' => 'evolution',
            'api_version' => 'v20.0',
            'phone_number_id' => '',
            'waba_id' => '',
            'access_token' => '',
            'verify_token' => '',
            'app_secret' => '',
            'webhook_path' => '/api/webhooks/evolution',
            'default_from' => '',
            'strict_signature' => true,
            'auto_reply' => false,
            'auto_reply_message' => 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.',
            'auto_reply_use_ai' => false,
            'auto_reply_ai_model_id' => '',
            'auto_reply_ai_system_prompt' => '',
            'auto_reply_evolution_instance' => '',
            'auto_reply_faq_context' => '',
            'auto_reply_initial_delay_min' => '2',
            'auto_reply_initial_delay_max' => '5',
            'auto_reply_typing_duration' => '3',
            'auto_reply_max_chunks' => '3',
            'auto_reply_chunk_max_chars' => '350',
            'auto_reply_contact_cooldown' => '45',
            'auto_reply_debounce_seconds' => '8',
            'auto_reply_test_phone' => '',
            'timeout' => 30,
            'custom_api_url' => '',
            'custom_api_key' => '',
            'custom_api_method' => 'POST',
            'custom_api_headers' => '{}',
            'custom_api_preflight_enabled' => false,
            'custom_api_preflight_url' => '',
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
            'study_report_delivery' => 'both',
            'wapi_token' => '',
            'wapi_base_url' => config('services.whatsapp.base_url', 'https://wapi.flaxxa.com/api/v1'),
            'evolution_base_url' => '',
            'evolution_api_key' => '',
            'evolution_instance_name' => '',
            'evolution_rotation_enabled' => 'true',
            'evolution_webhook_secret' => '',
            'evolution_webhook_base_url' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (! SystemSetting::byKey($key)->ofGroup('whatsapp')->exists()) {
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

        return [
            'base_url' => $settings['evolution_base_url'],
            'api_key' => $settings['evolution_api_key'],
            'instance_name' => $settings['evolution_instance_name'],
        ];
    }

    /**
     * Auto-reply specific settings (Evolution support instance + humanization).
     */
    public function getAutoReplySettings(): array
    {
        $settings = $this->getSettings();

        return [
            'auto_reply' => $settings['auto_reply'],
            'auto_reply_use_ai' => $settings['auto_reply_use_ai'],
            'auto_reply_message' => $settings['auto_reply_message'],
            'auto_reply_ai_model_id' => $settings['auto_reply_ai_model_id'],
            'auto_reply_ai_system_prompt' => $settings['auto_reply_ai_system_prompt'],
            'auto_reply_evolution_instance' => $settings['auto_reply_evolution_instance'],
            'auto_reply_faq_context' => $settings['auto_reply_faq_context'],
            'auto_reply_initial_delay_min' => $settings['auto_reply_initial_delay_min'],
            'auto_reply_initial_delay_max' => $settings['auto_reply_initial_delay_max'],
            'auto_reply_typing_duration' => $settings['auto_reply_typing_duration'],
            'auto_reply_max_chunks' => $settings['auto_reply_max_chunks'],
            'auto_reply_chunk_max_chars' => $settings['auto_reply_chunk_max_chars'],
            'auto_reply_contact_cooldown' => $settings['auto_reply_contact_cooldown'],
            'auto_reply_debounce_seconds' => $settings['auto_reply_debounce_seconds'],
            'auto_reply_test_phone' => $settings['auto_reply_test_phone'],
            'whatsapp_provider' => $settings['whatsapp_provider'],
            'whatsapp_enabled' => $settings['whatsapp_enabled'],
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
     * Normalize Node.js service base URL (no leading slash, no trailing slash)
     */
    protected function normalizeServiceUrl(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, '/')) {
            $url = substr($url, 1);
        }

        return rtrim($url, '/') ?: 'http://localhost:3000';
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
