<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleProviderService extends AIProviderService
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function chat(array $messages, array $options = []): array
    {
        $url = $this->getBaseUrl() ?? self::BASE_URL;
        
        // إذا كان endpoint مخصص، استخدمه. وإلا استخدم الافتراضي
        $customEndpoint = $this->getApiEndpoint();
        if ($customEndpoint) {
            // تأكد من أن endpoint يبدأ بـ /
            $endpoint = str_starts_with($customEndpoint, '/') ? $customEndpoint : '/' . $customEndpoint;
        } else {
            // الافتراضي: /models/{model_key}:generateContent
            // ملاحظة: بعض الموديلات قد تحتاج مسار مختلف
            $modelKey = $this->model->model_key;
            
            // التحقق من الموديلات المدعومة (2024-2025)
            $supportedModels = ['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-flash-latest', 'gemini-pro-latest', 'gemini-2.0-flash-lite'];
            if (!in_array($modelKey, $supportedModels)) {
                Log::warning('Potentially unsupported Gemini model key', [
                    'model_key' => $modelKey,
                    'supported' => $supportedModels,
                ]);
            }
            
            $endpoint = '/models/' . $modelKey . ':generateContent';
        }

        // تحويل تنسيق الرسائل إلى Google Gemini
        $contents = [];
        foreach ($messages as $message) {
            if ($message['role'] !== 'system') {
                $contents[] = [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $message['content']]]
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $options['max_tokens'] ?? $this->model->max_tokens,
                'temperature' => $options['temperature'] ?? $this->model->temperature,
            ],
        ];

        try {
            $apiKey = $this->getApiKey();
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'API Key غير موجود. يرجى إدخال API Key في حقل "مفتاح API" وحفظ النموذج أولاً.',
                ];
            }

            // تنظيف API Key من المسافات
            $apiKey = trim($apiKey);
            
            $fullUrl = $url . $endpoint . '?key=' . urlencode($apiKey);
            
            Log::info('Google Gemini API Request', [
                'url' => $url,
                'endpoint' => $endpoint,
                'model_key' => $this->model->model_key,
                'api_key_length' => strlen($apiKey),
                'api_key_prefix' => substr($apiKey, 0, 10) . '...',
            ]);

            $response = Http::withoutVerifying()->timeout(180)->post($fullUrl, $payload);
            
            Log::info('Google Gemini API Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                return [
                    'success' => true,
                    'content' => $content,
                    'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                    'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                    'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            $errorCode = $errorData['error']['code'] ?? null;
            
            Log::error('Google Gemini API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'code' => $errorCode,
                'response' => $errorData,
            ]);

            // رسائل خطأ أكثر وضوحاً
            if ($response->status() === 401) {
                $errorMessage = 'API Key غير صحيح أو منتهي الصلاحية. يرجى التحقق من API Key من Google AI Studio.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Model Key غير صحيح. استخدم أحد الموديلات الجديدة: gemini-2.0-flash, gemini-2.5-flash, gemini-2.5-pro';
            } elseif ($response->status() === 400) {
                $errorMessage = 'طلب غير صحيح: ' . $errorMessage;
            } elseif ($response->status() === 429) {
                $errorMessage = '⏳ تم تجاوز حد الاستخدام المجاني. انتظر قليلاً ثم جرّب مرة أخرى. (الاتصال يعمل بشكل صحيح!)';
            } elseif ($response->status() === 403) {
                $errorMessage = 'تم رفض الوصول. قد يكون API Key غير مفعل أو لا يملك الصلاحيات المطلوبة.';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Google Gemini API Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ];
        }
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $result = $this->chat($messages, $options);
        
        if (!$result['success']) {
            $this->setLastError($result['error'] ?? 'خطأ غير معروف في توليد النص');
            return '';
        }
        
        return $result['content'] ?? '';
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

