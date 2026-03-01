<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalLLMProviderService extends AIProviderService
{
    public function chat(array $messages, array $options = []): array
    {
        $baseUrl = $this->getBaseUrl() ?? 'http://localhost:11434';
        $endpoint = $this->getApiEndpoint() ?? '/api/chat';

        // تحويل تنسيق الرسائل إلى Ollama
        $conversationMessages = [];
        foreach ($messages as $message) {
            if ($message['role'] !== 'system') {
                $conversationMessages[] = [
                    'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $message['content']
                ];
            }
        }

        $payload = [
            'model' => $this->model->model_key,
            'messages' => $conversationMessages,
            'options' => [
                'temperature' => $options['temperature'] ?? $this->model->temperature,
                'num_predict' => $options['max_tokens'] ?? $this->model->max_tokens,
            ],
        ];

        try {
            $response = Http::withoutVerifying()->timeout(120)->post($baseUrl . $endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'content' => $data['message']['content'] ?? '',
                    'tokens_used' => 0, // Ollama لا يعيد tokens count دائماً
                    'prompt_tokens' => 0,
                    'completion_tokens' => 0,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            Log::error('Local LLM API Error: ' . $e->getMessage());
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
            $baseUrl = $this->getBaseUrl() ?? 'http://localhost:11434';
            $response = Http::withoutVerifying()->timeout(5)->get($baseUrl . '/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}

