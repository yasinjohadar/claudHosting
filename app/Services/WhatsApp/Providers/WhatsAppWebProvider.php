<?php

namespace App\Services\WhatsApp\Providers;

use App\DTOs\WhatsApp\SendMessageResponseDTO;
use App\Services\WhatsApp\WhatsAppProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebProvider implements WhatsAppProviderService
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['nodejs_service_url'] ?? 'http://localhost:3000';
    }

    /**
     * Send text message
     */
    public function sendText(string $to, string $text, bool $previewUrl = false): SendMessageResponseDTO
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
                ])
                ->post("{$this->baseUrl}/api/whatsapp/send", [
                    'to' => $to,
                    'message' => $text,
                    'type' => 'text',
                    'preview_url' => $previewUrl,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return new SendMessageResponseDTO(
                    success: true,
                    metaMessageId: $data['message_id'] ?? null,
                    rawResponse: $data
                );
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Unknown error';
            
            Log::error('WhatsApp Web API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            return new SendMessageResponseDTO(
                success: false,
                error: $errorMessage,
                rawResponse: $errorData
            );
        } catch (\Exception $e) {
            Log::error('WhatsApp Web Provider Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new SendMessageResponseDTO(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send template message
     */
    public function sendTemplate(string $to, string $templateName, string $language = 'ar', array $components = []): SendMessageResponseDTO
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
                ])
                ->post("{$this->baseUrl}/api/whatsapp/send", [
                    'to' => $to,
                    'type' => 'template',
                    'template_name' => $templateName,
                    'language' => $language,
                    'components' => $components,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return new SendMessageResponseDTO(
                    success: true,
                    metaMessageId: $data['message_id'] ?? null,
                    rawResponse: $data
                );
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Unknown error';

            return new SendMessageResponseDTO(
                success: false,
                error: $errorMessage,
                rawResponse: $errorData
            );
        } catch (\Exception $e) {
            Log::error('WhatsApp Web Template Exception', [
                'error' => $e->getMessage(),
            ]);

            return new SendMessageResponseDTO(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send document
     */
    public function sendDocument(string $to, string $documentUrl, string $filename, ?string $caption = null): SendMessageResponseDTO
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
                ])
                ->post("{$this->baseUrl}/api/whatsapp/send", [
                    'to' => $to,
                    'type' => 'document',
                    'document_url' => $documentUrl,
                    'filename' => $filename,
                    'caption' => $caption,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return new SendMessageResponseDTO(
                    success: true,
                    metaMessageId: $data['message_id'] ?? null,
                    rawResponse: $data
                );
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error'] ?? $errorData['message'] ?? 'Unknown error';

            return new SendMessageResponseDTO(
                success: false,
                error: $errorMessage,
                rawResponse: $errorData
            );
        } catch (\Exception $e) {
            Log::error('WhatsApp Web Document Exception', [
                'error' => $e->getMessage(),
            ]);

            return new SendMessageResponseDTO(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Test connection
     */
    public function testConnection(): array
    {
        try {
            // First, check if there's a connected session in database
            $session = \App\Models\WhatsAppWebSession::where('status', 'connected')
                ->latest()
                ->first();

            if ($session) {
                // Check status of this specific session
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
                    ])
                    ->get("{$this->baseUrl}/api/whatsapp/status/{$session->session_id}");

                if ($response->successful()) {
                    $data = $response->json();
                    $isConnected = $data['connected'] ?? false;
                    
                    if ($isConnected) {
                        return [
                            'success' => true,
                            'message' => 'WhatsApp Web متصل بنجاح - ' . ($session->name ?? $session->phone_number ?? 'جهاز مربوط'),
                            'data' => $data,
                        ];
                    }
                }
            }

            // If no connected session, check general service status
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . ($this->config['api_token'] ?? ''),
                ])
                ->get("{$this->baseUrl}/api/whatsapp/status");

            if ($response->successful()) {
                $data = $response->json();
                $isConnected = $data['connected'] ?? false;
                
                return [
                    'success' => $isConnected,
                    'message' => $isConnected 
                        ? 'WhatsApp Web متصل بنجاح' 
                        : 'Node.js service يعمل لكن لا يوجد جهاز مربوط. يرجى الربط أولاً.',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل الاتصال بخدمة WhatsApp Web. تأكد من أن Node.js service يعمل.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ];
        }
    }
}

