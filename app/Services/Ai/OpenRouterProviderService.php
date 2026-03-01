<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenRouter Provider Service
 * 
 * يوفر وصولاً موحداً لعدة موديلات AI من خلال OpenRouter API
 * بما في ذلك موديلات مجانية من Google, Meta, Microsoft, وغيرها
 * 
 * @see https://openrouter.ai/docs
 */
class OpenRouterProviderService extends AIProviderService
{
    private const BASE_URL = 'https://openrouter.ai/api/v1';
    
    /**
     * الموديلات المجانية المتاحة (محدثة)
     */
    public const FREE_MODELS = [
        'google/gemini-2.0-flash-exp:free',
        'allenai/olmo-3.1-32b-think:free',
        'xiaomi/mimo-v2-flash:free',
        'nvidia/nemotron-3-nano-30b-a3b:free',
        'mistralai/devstral-2512:free',
        'nex-agi/deepseek-v3.1-nex-n1:free',
        'google/gemma-3-27b-it:free',
        'microsoft/phi-4:free',
        'qwen/qwen-2.5-72b-instruct:free',
    ];

    /**
     * إرسال رسالة في محادثة
     */
    public function chat(array $messages, array $options = []): array
    {
        $url = $this->getBaseUrl() ?? self::BASE_URL;
        $endpoint = $this->getApiEndpoint() ?? '/chat/completions';

        $payload = [
            'model' => $this->model->model_key,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $this->model->max_tokens),
            'temperature' => (float) ($options['temperature'] ?? $this->model->temperature),
        ];

        try {
            $apiKey = $this->getApiKey();
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'API Key غير موجود. احصل على API Key مجاني من openrouter.ai',
                ];
            }

            Log::info('OpenRouter API Request', [
                'url' => $url . $endpoint,
                'model' => $this->model->model_key,
                'is_free' => $this->isFreeModel(),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url', 'http://localhost'),
                'X-Title' => config('app.name', 'Laravel App'),
            ])->withoutVerifying()->timeout(180)->post($url . $endpoint, $payload);

            Log::info('OpenRouter API Response', [
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

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
            $errorCode = $errorData['error']['code'] ?? $response->status();
            
            Log::error('OpenRouter API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'code' => $errorCode,
                'response' => $errorData,
            ]);

            // رسائل خطأ واضحة
            $friendlyMessage = $this->getFriendlyErrorMessage($response->status(), $errorMessage);

            return [
                'success' => false,
                'error' => $friendlyMessage,
                'status_code' => $response->status(),
                'raw_error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('OpenRouter API Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * توليد نص من prompt
     */
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

    /**
     * تقدير عدد الـ tokens
     */
    public function estimateTokens(string $text): int
    {
        // تقدير تقريبي: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection(): bool
    {
        try {
            $result = $this->chat([
                ['role' => 'user', 'content' => 'Say "OK" only.']
            ], ['max_tokens' => 10]);

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('OpenRouter test connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * التحقق مما إذا كان الموديل مجانياً
     */
    public function isFreeModel(): bool
    {
        return in_array($this->model->model_key, self::FREE_MODELS) 
            || str_ends_with($this->model->model_key, ':free');
    }

    /**
     * الحصول على قائمة الموديلات المتاحة من OpenRouter
     */
    public function getAvailableModels(): array
    {
        try {
            $apiKey = $this->getApiKey();
            if (!$apiKey) {
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
            ])->timeout(30)->get(self::BASE_URL . '/models');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get OpenRouter models: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * الحصول على معلومات الاستخدام
     */
    public function getUsageInfo(): array
    {
        try {
            $apiKey = $this->getApiKey();
            if (!$apiKey) {
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
            ])->timeout(30)->get(self::BASE_URL . '/auth/key');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to get OpenRouter usage info: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * تحويل رسائل الخطأ إلى رسائل صديقة للمستخدم
     */
    private function getFriendlyErrorMessage(int $statusCode, string $rawMessage): string
    {
        return match($statusCode) {
            400 => 'طلب غير صحيح: ' . $rawMessage,
            401 => 'API Key غير صحيح. تأكد من نسخ API Key بشكل صحيح من openrouter.ai',
            402 => 'رصيدك منتهي. أضف رصيداً في openrouter.ai أو استخدم موديل مجاني (:free)',
            403 => 'الوصول مرفوض. تحقق من صلاحيات API Key.',
            404 => 'الموديل غير موجود. تأكد من اسم الموديل.',
            408 => 'انتهت مهلة الطلب. جرّب مرة أخرى.',
            429 => 'تم تجاوز حد الطلبات. انتظر قليلاً ثم جرّب مرة أخرى.',
            500, 502, 503 => 'خطأ في خادم OpenRouter. جرّب مرة أخرى لاحقاً.',
            default => 'خطأ غير متوقع: ' . $rawMessage . " (رمز: {$statusCode})",
        };
    }
}

