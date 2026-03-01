<?php

namespace App\Services\WhatsApp\Providers;

use App\DTOs\WhatsApp\SendMessageResponseDTO;
use App\Exceptions\WhatsAppApiException;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppProviderService;
use Illuminate\Support\Facades\Log;

class MetaProvider implements WhatsAppProviderService
{
    protected WhatsAppClient $client;
    protected array $config;

    protected int $timeout;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->client = new WhatsAppClient(
            apiVersion: $config['api_version'] ?? null,
            phoneNumberId: $config['phone_number_id'] ?? null,
            accessToken: $config['access_token'] ?? null,
            timeout: $this->timeout
        );
    }

    /**
     * Send text message via Meta WhatsApp Cloud API
     */
    public function sendText(string $to, string $text, bool $previewUrl = false): SendMessageResponseDTO
    {
        return $this->client->sendText($to, $text, $previewUrl);
    }

    /**
     * Send template message via Meta WhatsApp Cloud API
     */
    public function sendTemplate(string $to, string $templateName, string $language = 'ar', array $components = []): SendMessageResponseDTO
    {
        return $this->client->sendTemplate($to, $templateName, $language, $components);
    }

    /**
     * Send document via Meta WhatsApp Cloud API
     */
    public function sendDocument(string $to, string $documentUrl, string $filename, ?string $caption = null): SendMessageResponseDTO
    {
        return $this->client->sendDocument($to, $documentUrl, $filename, $caption);
    }

    /**
     * Test connection to Meta WhatsApp API
     */
    public function testConnection(): array
    {
        try {
            // Use config passed to constructor (from WhatsAppSettingsService)
            $apiVersion = $this->config['api_version'] ?? 'v20.0';
            $baseUrl = 'https://graph.facebook.com';
            $phoneNumberId = $this->config['phone_number_id'] ?? null;
            $accessToken = $this->config['access_token'] ?? null;

            if (empty($phoneNumberId) || empty($accessToken)) {
                return [
                    'success' => false,
                    'message' => 'Phone Number ID و Access Token مطلوبان',
                ];
            }

            $url = "{$baseUrl}/{$apiVersion}/{$phoneNumberId}";

            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withToken($accessToken)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'تم الاتصال بنجاح. ' . ($data['display_phone_number'] ?? ''),
                ];
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'فشل الاتصال';
                return [
                    'success' => false,
                    'message' => 'فشل الاتصال: ' . $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Meta Provider connection test error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ];
        }
    }
}

