<?php

namespace App\Services\WhatsApp;

use App\DTOs\WhatsApp\SendMessageResponseDTO;
use App\Exceptions\WhatsAppApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppClient
{
    protected string $apiVersion;
    protected string $baseUrl;
    protected ?string $phoneNumberId;
    protected ?string $accessToken;
    protected int $timeout;

    public function __construct(
        ?string $apiVersion = null,
        ?string $phoneNumberId = null,
        ?string $accessToken = null,
        ?int $timeout = null
    ) {
        $this->apiVersion = $apiVersion ?? 'v20.0';
        $this->baseUrl = 'https://graph.facebook.com';
        $this->phoneNumberId = $phoneNumberId;
        $this->accessToken = $accessToken;
        $this->timeout = $timeout ?? 30;
    }

    /**
     * Send text message
     */
    public function sendText(string $to, string $text, bool $previewUrl = false): SendMessageResponseDTO
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $text,
                'preview_url' => $previewUrl,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send template message
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $language = 'ar',
        array $components = []
    ): SendMessageResponseDTO {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendMessage($payload);
    }

    /**
     * Send document message
     */
    public function sendDocument(string $to, string $documentUrl, string $filename, ?string $caption = null): SendMessageResponseDTO
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => [
                'link' => $documentUrl,
                'filename' => $filename,
            ],
        ];

        if ($caption) {
            $payload['document']['caption'] = $caption;
        }

        return $this->sendMessage($payload);
    }

    /**
     * Send message via API
     */
    protected function sendMessage(array $payload): SendMessageResponseDTO
    {
        if (empty($this->phoneNumberId)) {
            throw new WhatsAppApiException('WhatsApp phone number ID is not configured');
        }

        if (empty($this->accessToken)) {
            throw new WhatsAppApiException('WhatsApp access token is not configured');
        }

        $url = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->accessToken)
                ->asJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['messages'][0]['id'] ?? '';

                if (empty($messageId)) {
                    throw new WhatsAppApiException('Message ID not found in API response', 500, null, [
                        'response' => $data,
                    ]);
                }

                Log::channel('whatsapp')->info('WhatsApp message sent successfully', [
                    'message_id' => $messageId,
                    'to' => $payload['to'] ?? '',
                ]);

                return new SendMessageResponseDTO(
                    metaMessageId: $messageId,
                    rawResponse: $data
                );
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
                $errorCode = $errorData['error']['code'] ?? $response->status();

                Log::channel('whatsapp')->error('WhatsApp API error', [
                    'status' => $response->status(),
                    'error' => $errorData,
                    'to' => $payload['to'] ?? '',
                ]);

                throw new WhatsAppApiException(
                    "WhatsApp API error: {$errorMessage}",
                    (int) $errorCode,
                    null,
                    $errorData
                );
            }
        } catch (WhatsAppApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Exception sending WhatsApp message', [
                'error' => $e->getMessage(),
                'to' => $payload['to'] ?? '',
            ]);

            throw new WhatsAppApiException(
                "Failed to send WhatsApp message: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}

