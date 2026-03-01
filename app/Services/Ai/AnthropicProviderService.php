<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProviderService extends AIProviderService
{
    private const BASE_URL = 'https://api.anthropic.com/v1';

    public function chat(array $messages, array $options = []): array
    {
        $url = $this->getBaseUrl() ?? self::BASE_URL;
        $endpoint = $this->getApiEndpoint() ?? '/messages';

        // تحويل تنسيق الرسائل من OpenAI إلى Anthropic
        $systemMessage = '';
        $conversationMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMessage = $message['content'];
            } else {
                $conversationMessages[] = $message;
            }
        }

        $payload = [
            'model' => $this->model->model_key,
            'max_tokens' => $options['max_tokens'] ?? $this->model->max_tokens,
            'temperature' => $options['temperature'] ?? $this->model->temperature,
            'messages' => $conversationMessages,
        ];

        if ($systemMessage) {
            $payload['system'] = $systemMessage;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->getApiKey(),
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(180)->post($url . $endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'content' => $data['content'][0]['text'] ?? '',
                    'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                    'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Anthropic API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $result = $this->chat($messages, $options);
        return $result['success'] ? $result['content'] : '';
    }

    public function estimateTokens(string $text): int
    {
        // تقدير تقريبي: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->chat([
                ['role' => 'user', 'content' => 'Hello']
            ], ['max_tokens' => 5]);

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

