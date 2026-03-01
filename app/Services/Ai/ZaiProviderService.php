<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Z.ai Provider Service
 * 
 * يوفر وصولاً إلى Z.ai GLM-4.7 Model
 * متوافق مع OpenAI API format
 * 
 * @see https://z.ai/subscribe
 */
class ZaiProviderService extends AIProviderService
{
    private const BASE_URL = 'https://api.z.ai/api/coding/paas/v4';

    public function chat(array $messages, array $options = []): array
    {
        $baseUrl = $this->getBaseUrl() ?? self::BASE_URL;
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

        // تنظيف model_key من المسافات والحروف الكبيرة/الصغيرة
        $modelKey = trim($this->model->model_key);
        
        $payload = [
            'model' => $modelKey,
            'messages' => $messages,
            'max_tokens' => (int) ($options['max_tokens'] ?? $this->model->max_tokens),
            'temperature' => (float) ($options['temperature'] ?? $this->model->temperature),
        ];

        try {
            // بناء URL بشكل صحيح
            $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
            
            Log::info('Z.ai API Request', [
                'url' => $fullUrl,
                'base_url' => $baseUrl,
                'endpoint' => $endpoint,
                'model' => $modelKey,
                'max_tokens' => $payload['max_tokens'],
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(500)->post($fullUrl, $payload);

            // تحويل الـ response body إلى UTF-8 بشكل صحيح
            $rawBody = $response->body();
            
            // التحقق من الترميز وإصلاحه إذا لزم الأمر
            if (!mb_check_encoding($rawBody, 'UTF-8')) {
                // محاولة تحويل الترميز
                $body = mb_convert_encoding($rawBody, 'UTF-8', 'auto');
                // إذا فشل التحويل، استخدم utf8_encode كحل بديل
                if (!mb_check_encoding($body, 'UTF-8')) {
                    $body = mb_convert_encoding($rawBody, 'UTF-8', ['UTF-8', 'ISO-8859-1', 'Windows-1256']);
                }
            } else {
                $body = $rawBody;
            }
            
            // تنظيف النص من الأحرف غير الصالحة في UTF-8
            $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
            
            Log::info('Z.ai API Response', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'body_length' => strlen($body),
                'body_preview' => mb_substr($body, 0, 500),
                'encoding_valid' => mb_check_encoding($body, 'UTF-8'),
            ]);

            if ($response->successful()) {
                try {
                    $data = json_decode($body, true, 512, JSON_INVALID_UTF8_IGNORE);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Z.ai JSON decode error', [
                            'error' => json_last_error_msg(),
                            'error_code' => json_last_error(),
                            'body_preview' => mb_substr($body, 0, 500),
                        ]);
                        $this->setLastError('خطأ في تحليل رد Z.ai: ' . json_last_error_msg());
                        return [
                            'success' => false,
                            'error' => 'خطأ في تحليل رد Z.ai',
                        ];
                    }
                    
                    $content = $data['choices'][0]['message']['content'] ?? '';
                    
                    // التحقق من ترميز المحتوى المستخرج
                    if (!empty($content) && !mb_check_encoding($content, 'UTF-8')) {
                        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                    }
                    
                    Log::info('Z.ai content extracted', [
                        'content_length' => strlen($content),
                        'content_preview' => mb_substr($content, 0, 500),
                        'encoding_valid' => mb_check_encoding($content, 'UTF-8'),
                    ]);
                    
                    return [
                        'success' => true,
                        'content' => $content,
                        'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                        'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                        'model_used' => $data['model'] ?? $this->model->model_key,
                    ];
                } catch (\JsonException $e) {
                    Log::error('Z.ai JSON exception: ' . $e->getMessage(), [
                        'body_preview' => mb_substr($body, 0, 500),
                    ]);
                    $this->setLastError('خطأ في تحليل رد Z.ai: ' . $e->getMessage());
                    return [
                        'success' => false,
                        'error' => 'خطأ في تحليل رد Z.ai',
                    ];
                }
            }

            // معالجة الأخطاء
            $errorData = $response->json();
            
            // Z.ai قد يعيد أخطاء بصيغ مختلفة
            if (isset($errorData['error'])) {
                $errorMessage = $errorData['error']['message'] ?? ($errorData['error']['msg'] ?? 'خطأ غير معروف');
                $errorType = $errorData['error']['type'] ?? null;
                $errorCode = $errorData['error']['code'] ?? null;
            } elseif (isset($errorData['msg'])) {
                $errorMessage = $errorData['msg'];
                $errorType = null;
                $errorCode = $errorData['code'] ?? null;
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
                $errorType = null;
                $errorCode = null;
            } else {
                $errorMessage = $response->body() ?? 'خطأ غير معروف';
                $errorType = null;
                $errorCode = null;
            }
            
            Log::error('Z.ai API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'type' => $errorType,
                'code' => $errorCode,
                'response_body' => $response->body(),
                'error_data' => $errorData,
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
            Log::error('Z.ai API Exception: ' . $e->getMessage(), [
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
        // تنظيف رسالة الخطأ من النقاط الزائدة في البداية
        $errorMessage = ltrim($errorMessage, '. ');
        
        if ($statusCode === 401) {
            return 'API Key غير صحيح أو منتهي الصلاحية. يرجى التحقق من API Key من Z.ai Platform.';
        } elseif ($statusCode === 404) {
            return 'Model Key غير صحيح أو غير متاح. تأكد من أن Model Key صحيح (glm-4.7 أو GLM-4.7).';
        } elseif ($statusCode === 429) {
            return 'تم تجاوز حد الاستخدام. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى، أو التحقق من خطة Z.ai الخاصة بك.';
        } elseif ($statusCode === 500 || $statusCode === 502 || $statusCode === 503) {
            return 'خطأ في خادم Z.ai. يرجى المحاولة مرة أخرى لاحقاً.';
        } elseif ($errorType === 'insufficient_quota' || stripos($errorMessage, 'quota') !== false) {
            return 'رصيد Z.ai غير كافٍ. يرجى إضافة رصيد إلى حسابك من Z.ai Platform.';
        } elseif ($errorType === 'invalid_request_error' || stripos($errorMessage, 'invalid') !== false || stripos($errorMessage, 'payload') !== false) {
            $message = 'طلب غير صحيح: ' . $errorMessage;
            $message .= "\n\n💡 نصائح:";
            $message .= "\n- تأكد من أن Model Key صحيح (مثل: glm-4.7, GLM-4.7)";
            $message .= "\n- تأكد من أن API Key صحيح من: https://z.ai/subscribe";
            $message .= "\n- تأكد من أن Base URL صحيح: https://api.z.ai/api/coding/paas/v4";
            $message .= "\n- تأكد من أن API Endpoint صحيح: /chat/completions";
            return $message;
        }

        return 'خطأ من Z.ai: ' . $errorMessage;
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
        
        $content = $result['content'] ?? '';
        
        // تنظيف المحتوى من الأحرف غير الصالحة في UTF-8
        if (!empty($content)) {
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            }
            // إزالة الأحرف غير الصالحة
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
            // إزالة BOM إذا كان موجوداً
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        }
        
        return $content;
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
            Log::error('Z.ai test connection failed: ' . $e->getMessage());
            $this->setLastError('فشل اختبار الاتصال: ' . $e->getMessage());
            return false;
        }
    }
}

