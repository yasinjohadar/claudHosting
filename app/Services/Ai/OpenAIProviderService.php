<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProviderService extends AIProviderService
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function chat(array $messages, array $options = []): array
    {
        $url = $this->getBaseUrl() ?? self::BASE_URL;
        $endpoint = $this->getApiEndpoint() ?? '/chat/completions';

        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $error = 'API Key غير موجود. يرجى إدخال API Key في حقل "مفتاح API" وحفظ النموذج أولاً.';
            $this->setLastError($error);
            return [
                'success' => false,
                'error' => $error,
            ];
        }

        $payload = [
            'model' => $this->model->model_key,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $this->model->max_tokens),
            'temperature' => (float) ($options['temperature'] ?? $this->model->temperature),
        ];

        try {
            $fullUrl = $url . $endpoint;
            
            Log::info('OpenAI API Request', [
                'url' => $fullUrl,
                'model' => $this->model->model_key,
                'max_tokens' => $payload['max_tokens'],
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(180)->post($fullUrl, $payload);

            Log::info('OpenAI API Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'model_used' => $data['model'] ?? $this->model->model_key,
                ];
            }

            // معالجة الأخطاء
            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'خطأ غير معروف';
            $errorType = $errorData['error']['type'] ?? null;
            $errorCode = $errorData['error']['code'] ?? null;
            
            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'type' => $errorType,
                'code' => $errorCode,
            ]);

            // رسائل خطأ واضحة بالعربية
            $friendlyMessage = $this->getFriendlyErrorMessage($response->status(), $errorMessage, $errorType);

            $this->setLastError($friendlyMessage);

            return [
                'success' => false,
                'error' => $friendlyMessage,
                'status_code' => $response->status(),
                'raw_error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            $error = 'خطأ في الاتصال: ' . $e->getMessage();
            $this->setLastError($error);
            
            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    /**
     * الحصول على رسالة خطأ واضحة
     */
    private function getFriendlyErrorMessage(int $statusCode, string $errorMessage, ?string $errorType = null): string
    {
        // رسائل خطأ شائعة من OpenAI
        if ($statusCode === 401) {
            return 'API Key غير صحيح أو منتهي الصلاحية. يرجى التحقق من API Key من OpenAI Platform.';
        } elseif ($statusCode === 404) {
            return 'Model Key غير صحيح أو غير متاح. تأكد من أن Model Key صحيح (مثل: gpt-4, gpt-3.5-turbo).';
        } elseif ($statusCode === 429) {
            return 'تم تجاوز حد الاستخدام. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى، أو التحقق من خطة OpenAI الخاصة بك.';
        } elseif ($statusCode === 500 || $statusCode === 502 || $statusCode === 503) {
            return 'خطأ في خادم OpenAI. يرجى المحاولة مرة أخرى لاحقاً.';
        } elseif ($errorType === 'insufficient_quota') {
            return 'رصيد OpenAI غير كافٍ. يرجى إضافة رصيد إلى حسابك من OpenAI Platform.';
        } elseif ($errorType === 'invalid_request_error') {
            return 'طلب غير صحيح: ' . $errorMessage;
        }

        return 'خطأ من OpenAI: ' . $errorMessage;
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
        // يمكن استخدام مكتبة tiktoken للحصول على تقدير أدق
        return (int) ceil(strlen($text) / 4);
    }

    public function testConnection(): bool
    {
        try {
            $result = $this->chat([
                ['role' => 'user', 'content' => 'Say "OK" only.']
            ], ['max_tokens' => 10]);

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('OpenAI test connection failed: ' . $e->getMessage());
            $this->setLastError('فشل اختبار الاتصال: ' . $e->getMessage());
            return false;
        }
    }
}

