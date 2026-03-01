<?php

namespace App\Services\WhatsApp\Providers;

use App\DTOs\WhatsApp\SendMessageResponseDTO;
use App\Services\WhatsApp\WhatsAppProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomApiProvider implements WhatsAppProviderService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $method;
    protected array $headers;

    public function __construct(array $config)
    {
        $this->apiUrl = $config['api_url'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->method = strtoupper($config['api_method'] ?? 'POST');
        $this->headers = $config['headers'] ?? [];
    }

    /**
     * Send text message via Custom API
     */
    public function sendText(string $to, string $text, bool $previewUrl = false): SendMessageResponseDTO
    {
        // Use 'text' field as per most API standards (wasenderapi.com uses 'text')
        $payload = [
            'to' => $to,
            'text' => $text,
        ];

        // Only add optional fields if needed
        // Some APIs might use 'message' instead of 'text', but 'text' is more standard
        // If your API requires 'message', you can add it via custom headers or modify this

        return $this->sendRequest($payload);
    }

    /**
     * Send template message via Custom API
     */
    public function sendTemplate(string $to, string $templateName, string $language = 'ar', array $components = []): SendMessageResponseDTO
    {
        $payload = [
            'to' => $to,
            'template' => $templateName,
            'language' => $language,
            'type' => 'template',
        ];

        if (!empty($components)) {
            $payload['components'] = $components;
        }

        return $this->sendRequest($payload);
    }

    /**
     * Send document via Custom API
     */
    public function sendDocument(string $to, string $documentUrl, string $filename, ?string $caption = null): SendMessageResponseDTO
    {
        $payload = [
            'to' => $to,
            'type' => 'document',
            'document' => $documentUrl,
            'filename' => $filename,
        ];

        if ($caption) {
            $payload['caption'] = $caption;
        }

        return $this->sendRequest($payload);
    }

    /**
     * Send request to Custom API
     */
    protected function sendRequest(array $payload): SendMessageResponseDTO
    {
        try {
            $headers = array_merge([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $this->headers);

            $request = Http::timeout(30)->withHeaders($headers);

            if ($this->method === 'GET') {
                $response = $request->get($this->apiUrl, $payload);
            } else {
                // Use asJson() to send JSON body (matching Guzzle's 'json' option)
                $response = $request->asJson()->post($this->apiUrl, $payload);
            }

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['message_id'] ?? $data['id'] ?? $data['sid'] ?? uniqid('wa_');

                Log::channel('whatsapp')->info('Custom API message sent successfully', [
                    'message_id' => $messageId,
                    'to' => $payload['to'] ?? '',
                ]);

                return new SendMessageResponseDTO(
                    metaMessageId: $messageId,
                    rawResponse: $data
                );
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Unknown error';
                $errorCode = $errorData['code'] ?? $response->status();

                Log::channel('whatsapp')->error('Custom API error', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'to' => $payload['to'] ?? '',
                ]);

                throw new \Exception("Custom API error: {$errorMessage}", (int) $errorCode);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Exception sending Custom API message', [
                'error' => $e->getMessage(),
                'to' => $payload['to'] ?? '',
            ]);

            throw $e;
        }
    }

    /**
     * Test connection to Custom API
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->apiUrl)) {
                return [
                    'success' => false,
                    'message' => 'API URL مطلوب',
                ];
            }

            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'API Key مطلوب',
                ];
            }

            $headers = array_merge([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $this->headers);

            $request = Http::timeout(10)->withHeaders($headers);

            // Use the configured method (POST or GET) to test connection
            // For POST endpoints, send a minimal test payload
            if ($this->method === 'POST') {
                // Send a test POST request with minimal payload
                // Many APIs will reject empty payloads, so we send a test message
                $testPayload = [
                    'to' => '0000000000', // Test number (will likely fail but shows connection works)
                    'text' => 'test',
                ];
                
                $response = $request->asJson()->post($this->apiUrl, $testPayload);
                
                // If we get 400/422 (validation error) or 401 (auth error), connection works
                // If we get 404, endpoint doesn't exist
                // If we get 405, method not allowed (shouldn't happen if method is POST)
                if ($response->status() === 401 || $response->status() === 400 || $response->status() === 422) {
                    // Connection successful, but validation/auth failed (expected for test)
                    return [
                        'success' => true,
                        'message' => 'تم الاتصال بنجاح (الخدمة متاحة)',
                    ];
                }
            } else {
                // For GET, try the URL directly
                $response = $request->get($this->apiUrl);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'تم الاتصال بنجاح',
                ];
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'فشل الاتصال';
                
                // If it's a validation/auth error with POST, connection works
                if ($this->method === 'POST' && in_array($response->status(), [401, 400, 422])) {
                    return [
                        'success' => true,
                        'message' => 'تم الاتصال بنجاح (الخدمة متاحة)',
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'فشل الاتصال: ' . $errorMessage,
                ];
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Custom API connection test error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ];
        }
    }
}

