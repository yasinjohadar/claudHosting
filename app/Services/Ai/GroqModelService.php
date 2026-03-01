<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqModelService
{
    private const MODELS_ENDPOINT = 'https://api.groq.com/openai/v1/models';

    /**
     * جلب قائمة الموديلات المتاحة من Groq API
     */
    public function fetchAvailableModels(string $apiKey): array
    {
        if (trim($apiKey) === '') {
            return [
                'success' => false,
                'error' => 'Groq API Key غير موجود',
                'models' => [],
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(60)->get(self::MODELS_ENDPOINT);

            if (!$response->successful()) {
                $data = $response->json();
                $message = $data['error']['message'] ?? 'فشل في جلب الموديلات من Groq';

                Log::error('GroqModelService: Failed to fetch models', [
                    'status' => $response->status(),
                    'response' => $data,
                ]);

                return [
                    'success' => false,
                    'error' => $message,
                    'models' => [],
                ];
            }

            $data = $response->json();
            $models = $data['data'] ?? [];

            $normalized = [];
            foreach ($models as $model) {
                $id = $model['id'] ?? null;
                if (!$id) {
                    continue;
                }

                $normalized[] = [
                    'id' => $id,
                    'object' => $model['object'] ?? null,
                    'created' => $model['created'] ?? null,
                    'owned_by' => $model['owned_by'] ?? null,
                    'description' => $model['description'] ?? null,
                ];
            }

            return [
                'success' => true,
                'models' => $normalized,
            ];
        } catch (\Throwable $e) {
            Log::error('GroqModelService: Exception while fetching models', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'models' => [],
            ];
        }
    }

    /**
     * Fallback: قائمة موديلات ثابتة مرتبة حسب المزود
     */
    public function getStaticModelsByProvider(): array
    {
        return [
            'Alibaba Cloud' => [
                'qwen/qwen3-32b' => 'Qwen3 32B',
                'qwen/qwen-2.5-72b-instruct' => 'Qwen 2.5 72B Instruct',
            ],
            'Canopy Labs' => [
                'canopylabs/orpheus-arabic-saudi' => 'Orpheus Arabic Saudi',
                'canopylabs/orpheus-v1-english' => 'Orpheus v1 English',
            ],
            'Groq' => [
                'groq/compound' => 'Groq Compound',
                'groq/compound-mini' => 'Groq Compound Mini',
            ],
            'Meta' => [
                'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                'meta-llama/llama-guard-4-12b' => 'Llama Guard 4 12B',
            ],
            'Moonshot AI' => [
                'moonshotai/kimi-k2-instruct' => 'Kimi K2 Instruct',
                'moonshotai/kimi-k2-instruct-0905' => 'Kimi K2 Instruct 0905',
            ],
            'OpenAI' => [
                'openai/gpt-oss-120b' => 'GPT-OSS 120B',
                'openai/gpt-oss-20b' => 'GPT-OSS 20B',
                'whisper-large-v3' => 'Whisper Large v3',
                'whisper-large-v3-turbo' => 'Whisper Large v3 Turbo',
            ],
            'Other' => [
                'meta/llama-3.1-8b-instant' => 'Meta Llama 3.1 8B Instant',
            ],
        ];
    }
}


