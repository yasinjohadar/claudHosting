<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manus AI Provider Service
 * 
 * يوفر وصولاً لخدمات Manus AI من خلال Manus API
 * 
 * @see https://manus.im
 */
class ManusProviderService extends AIProviderService
{
    private const BASE_URL = 'https://api.manus.ai/v1';

    /**
     * إرسال رسالة في محادثة
     */
    public function chat(array $messages, array $options = []): array
    {
        $url = $this->getBaseUrl() ?? self::BASE_URL;
        $endpoint = $this->getApiEndpoint() ?? '/tasks';

        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            $error = 'API Key غير موجود. يرجى إدخال API Key في حقل "مفتاح API" وحفظ النموذج أولاً.';
            $this->setLastError($error);
            return [
                'success' => false,
                'error' => $error,
            ];
        }

        // تحويل messages إلى prompt (Manus يستخدم prompt وليس messages)
        $prompt = $this->convertMessagesToPrompt($messages);

        // بناء payload حسب بنية Manus API
        $payload = [
            'prompt' => $prompt,
        ];

        // إضافة معاملات إضافية إذا كانت موجودة
        if (isset($options['max_tokens']) || $this->model->max_tokens) {
            $payload['max_tokens'] = (int) ($options['max_tokens'] ?? $this->model->max_tokens);
        }
        if (isset($options['temperature']) || $this->model->temperature) {
            $payload['temperature'] = (float) ($options['temperature'] ?? $this->model->temperature);
        }
        if (isset($options['top_p'])) {
            $payload['top_p'] = (float) $options['top_p'];
        }

        try {
            $fullUrl = $url . $endpoint;
            
            Log::info('Manus API Request', [
                'url' => $fullUrl,
                'model' => $this->model->model_key,
                'payload' => $payload,
            ]);

            // Manus يستخدم API_KEY header وليس Authorization: Bearer
            $response = Http::withHeaders([
                'API_KEY' => trim($apiKey),
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(300)->post($fullUrl, $payload);

            // تطبيق نفس منطق تنظيف encoding من Zai
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
            
            Log::info('Manus API Response', [
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
                        Log::error('Manus JSON decode error', [
                            'error' => json_last_error_msg(),
                            'error_code' => json_last_error(),
                            'body_preview' => mb_substr($body, 0, 500),
                        ]);
                        $this->setLastError('خطأ في تحليل رد Manus: ' . json_last_error_msg());
                        return [
                            'success' => false,
                            'error' => 'خطأ في تحليل رد Manus',
                        ];
                    }
                    
                    Log::info('Manus API chat Response Data', [
                        'data' => $data,
                    ]);
                    
                    // Manus API يعيد task_id - نحتاج إلى استعلام عن حالة الـ task مع polling
                    if (isset($data['task_id'])) {
                        // محاولة الحصول على النتيجة من task مع polling
                        // القيم الافتراضية: 210 محاولة × 2 ثانية = 7 دقائق
                        // يمكن تخصيصها من خلال options
                        $maxAttempts = $options['polling_max_attempts'] ?? 210;
                        $delaySeconds = $options['polling_delay_seconds'] ?? 2;
                        $taskResult = $this->getTaskResult($data['task_id'], $apiKey, $url, $maxAttempts, $delaySeconds);
                        if ($taskResult !== null && !empty($taskResult)) {
                            // تطبيق تنظيف encoding على taskResult
                            if (!mb_check_encoding($taskResult, 'UTF-8')) {
                                $taskResult = mb_convert_encoding($taskResult, 'UTF-8', 'auto');
                            }
                            $taskResult = mb_convert_encoding($taskResult, 'UTF-8', 'UTF-8');
                            $taskResult = preg_replace('/^\xEF\xBB\xBF/', '', $taskResult);
                            
                            return [
                                'success' => true,
                                'content' => $taskResult,
                                'tokens_used' => $data['tokens_used'] ?? 0,
                                'prompt_tokens' => $data['prompt_tokens'] ?? 0,
                                'completion_tokens' => $data['completion_tokens'] ?? 0,
                                'model_used' => $this->model->model_key,
                            ];
                        }
                        
                        // إذا لم نتمكن من الحصول على النتيجة بعد polling
                        $this->setLastError('فشل في الحصول على نتيجة المهمة. Task ID: ' . $data['task_id']);
                        return [
                            'success' => false,
                            'error' => 'فشل في الحصول على نتيجة المهمة من Manus API. يرجى المحاولة مرة أخرى.',
                            'task_id' => $data['task_id'],
                        ];
                    }
                    
                    // محاولة استخراج النص من response مباشرة
                    $content = '';
                    if (isset($data['response'])) {
                        $content = $data['response'];
                    } elseif (isset($data['text'])) {
                        $content = $data['text'];
                    } elseif (isset($data['content'])) {
                        $content = $data['content'];
                    } elseif (isset($data['result'])) {
                        $content = is_string($data['result']) ? $data['result'] : json_encode($data['result'], JSON_UNESCAPED_UNICODE);
                    } elseif (isset($data['message'])) {
                        $content = $data['message'];
                    } elseif (isset($data['output'])) {
                        $content = $data['output'];
                    } elseif (is_string($data)) {
                        $content = $data;
                    }
                    
                    // تطبيق تنظيف encoding على المحتوى المستخرج (مثل Zai)
                    if (!empty($content)) {
                        // التحقق من ترميز المحتوى المستخرج
                        if (!mb_check_encoding($content, 'UTF-8')) {
                            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                        }
                        
                        Log::info('Manus content extracted', [
                            'content_length' => strlen($content),
                            'content_preview' => mb_substr($content, 0, 500),
                            'encoding_valid' => mb_check_encoding($content, 'UTF-8'),
                        ]);
                        
                        return [
                            'success' => true,
                            'content' => $content,
                            'tokens_used' => $data['tokens_used'] ?? $data['usage']['total_tokens'] ?? 0,
                            'prompt_tokens' => $data['prompt_tokens'] ?? $data['usage']['prompt_tokens'] ?? 0,
                            'completion_tokens' => $data['completion_tokens'] ?? $data['usage']['completion_tokens'] ?? 0,
                            'model_used' => $data['model'] ?? $this->model->model_key,
                        ];
                    }
                } catch (\JsonException $e) {
                    Log::error('Manus JSON exception: ' . $e->getMessage(), [
                        'body_preview' => mb_substr($body, 0, 500),
                    ]);
                    $this->setLastError('خطأ في تحليل رد Manus: ' . $e->getMessage());
                    return [
                        'success' => false,
                        'error' => 'خطأ في تحليل رد Manus',
                    ];
                }
            }

            // معالجة الأخطاء
            $errorData = $response->json();
            $errorMessage = 'خطأ غير معروف';
            
            // محاولة استخراج رسالة الخطأ من بنيات مختلفة
            if (isset($errorData['error'])) {
                if (is_string($errorData['error'])) {
                    $errorMessage = $errorData['error'];
                } elseif (isset($errorData['error']['message'])) {
                    $errorMessage = $errorData['error']['message'];
                }
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (is_string($errorData)) {
                $errorMessage = $errorData;
            }
            
            $errorType = $errorData['error']['type'] ?? $errorData['type'] ?? null;
            $errorCode = $errorData['error']['code'] ?? $errorData['code'] ?? null;
            
            Log::error('Manus API Error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'type' => $errorType,
                'code' => $errorCode,
                'response' => $errorData,
                'response_body' => $response->body(),
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
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Manus API Connection Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            $error = 'خطأ في الاتصال بخادم Manus. يرجى التحقق من الاتصال بالإنترنت والمحاولة مرة أخرى.';
            $this->setLastError($error);
            
            return [
                'success' => false,
                'error' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('Manus API Exception: ' . $e->getMessage(), [
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
        return match($statusCode) {
            400 => 'طلب غير صحيح: ' . $errorMessage,
            401 => 'API Key غير صحيح أو منتهي الصلاحية. يرجى التحقق من API Key من لوحة تحكم Manus.',
            403 => 'الوصول مرفوض. تحقق من صلاحيات API Key.',
            404 => 'Model Key غير صحيح أو غير متاح. تأكد من أن Model Key صحيح.',
            408 => 'انتهت مهلة الطلب. جرّب مرة أخرى.',
            429 => 'تم تجاوز حد الطلبات. انتظر قليلاً ثم جرّب مرة أخرى.',
            500, 502, 503 => 'خطأ في خادم Manus. يرجى المحاولة مرة أخرى لاحقاً.',
            default => match($errorType) {
                'insufficient_quota' => 'رصيد Manus غير كافٍ. يرجى إضافة رصيد إلى حسابك من لوحة تحكم Manus.',
                'invalid_request_error' => 'طلب غير صحيح: ' . $errorMessage,
                'rate_limit_error' => 'تم تجاوز حد الطلبات. يرجى الانتظار قليلاً ثم المحاولة مرة أخرى.',
                default => 'خطأ من Manus: ' . $errorMessage . " (رمز: {$statusCode})",
            },
        };
    }

    /**
     * الحصول على نتيجة task من Manus API مع polling
     */
    private function getTaskResult(string $taskId, string $apiKey, string $baseUrl, int $maxAttempts = 210, int $delaySeconds = 2): ?string
    {
        try {
            $taskUrl = $baseUrl . '/tasks/' . $taskId;
            
            Log::info('Manus API Getting Task Result', [
                'task_id' => $taskId,
                'url' => $taskUrl,
                'max_attempts' => $maxAttempts,
            ]);
            
            // Polling: محاولة الحصول على النتيجة عدة مرات
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $response = Http::withHeaders([
                    'API_KEY' => trim($apiKey),
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(30)->get($taskUrl);

                if ($response->successful()) {
                    // تطبيق نفس منطق تنظيف encoding من Zai
                    $rawBody = $response->body();
                    
                    // التحقق من الترميز وإصلاحه إذا لزم الأمر
                    if (!mb_check_encoding($rawBody, 'UTF-8')) {
                        $body = mb_convert_encoding($rawBody, 'UTF-8', 'auto');
                        if (!mb_check_encoding($body, 'UTF-8')) {
                            $body = mb_convert_encoding($rawBody, 'UTF-8', ['UTF-8', 'ISO-8859-1', 'Windows-1256']);
                        }
                    } else {
                        $body = $rawBody;
                    }
                    
                    // تنظيف النص من الأحرف غير الصالحة في UTF-8
                    $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
                    
                    try {
                        $data = json_decode($body, true, 512, JSON_INVALID_UTF8_IGNORE);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::error('Manus Task Result JSON decode error', [
                                'error' => json_last_error_msg(),
                                'body_preview' => mb_substr($body, 0, 500),
                            ]);
                            continue;
                        }
                    } catch (\JsonException $e) {
                        Log::error('Manus Task Result JSON exception: ' . $e->getMessage());
                        continue;
                    }
                    
                    Log::info('Manus API Task Result Attempt', [
                        'attempt' => $attempt,
                        'status' => $data['status'] ?? 'unknown',
                        'data' => $data,
                    ]);
                    
                    $status = $data['status'] ?? 'unknown';
                    
                    // دالة مساعدة لتنظيف المحتوى قبل الإرجاع
                    $cleanContent = function($content) {
                        if (empty($content)) {
                            return $content;
                        }
                        if (is_array($content)) {
                            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
                        }
                        if (!is_string($content)) {
                            $content = (string)$content;
                        }
                        // تطبيق تنظيف encoding
                        if (!mb_check_encoding($content, 'UTF-8')) {
                            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                        }
                        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                        return $content;
                    };
                    
                    // إذا كانت المهمة مكتملة، استخرج النتيجة
                    if ($status === 'completed' || $status === 'success') {
                        // Manus قد يعيد messages array مع output_file في 'output' أو 'messages'
                        $messagesArray = null;
                        if (isset($data['output']) && is_array($data['output'])) {
                            $messagesArray = $data['output'];
                        } elseif (isset($data['messages']) && is_array($data['messages'])) {
                            $messagesArray = $data['messages'];
                        }
                        
                        if ($messagesArray) {
                            $fileUrl = $this->extractFileUrlFromMessages($messagesArray);
                            if ($fileUrl) {
                                Log::info('Manus API Found file URL in messages', [
                                    'file_url' => $fileUrl,
                                ]);
                                $fileContent = $this->downloadFileContent($fileUrl);
                                if ($fileContent !== null && !empty($fileContent)) {
                                    $cleanedContent = $cleanContent($fileContent);
                                    Log::info('Manus API File content retrieved successfully', [
                                        'content_length' => strlen($cleanedContent),
                                        'content_preview' => mb_substr($cleanedContent, 0, 200),
                                    ]);
                                    return $cleanedContent;
                                }
                            }
                            
                            // محاولة استخراج النص من messages
                            $textContent = $this->extractTextFromMessages($messagesArray);
                            if (!empty($textContent)) {
                                return $cleanContent($textContent);
                            }
                        }
                        
                        // استخراج النص من task result (fallback)
                        if (isset($data['result'])) {
                            return $cleanContent($data['result']);
                        } elseif (isset($data['response'])) {
                            return $cleanContent($data['response']);
                        } elseif (isset($data['output'])) {
                            return $cleanContent($data['output']);
                        } elseif (isset($data['text'])) {
                            return $cleanContent($data['text']);
                        } elseif (isset($data['content'])) {
                            return $cleanContent($data['content']);
                        } elseif (isset($data['message'])) {
                            return $cleanContent($data['message']);
                        }
                    }
                    
                    // إذا كانت المهمة فاشلة
                    if ($status === 'failed' || $status === 'error') {
                        Log::error('Manus task failed', [
                            'task_id' => $taskId,
                            'status' => $status,
                            'data' => $data,
                        ]);
                        return null;
                    }
                    
                    // إذا كانت المهمة لا تزال pending، انتظر ثم حاول مرة أخرى
                    if ($status === 'pending' || $status === 'processing' || $status === 'running') {
                        if ($attempt < $maxAttempts) {
                            Log::info('Manus task still pending, waiting...', [
                                'attempt' => $attempt,
                                'status' => $status,
                                'waiting_seconds' => $delaySeconds,
                            ]);
                            sleep($delaySeconds);
                            continue;
                        } else {
                            Log::warning('Manus task timeout - max attempts reached', [
                                'task_id' => $taskId,
                                'status' => $status,
                            ]);
                            return null;
                        }
                    }
                } else {
                    Log::warning('Manus API task request failed', [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                    
                    if ($attempt < $maxAttempts) {
                        sleep($delaySeconds);
                        continue;
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get Manus task result: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * استخراج fileUrl من messages array
     */
    private function extractFileUrlFromMessages(array $messages): ?string
    {
        foreach ($messages as $message) {
            if (isset($message['content']) && is_array($message['content'])) {
                foreach ($message['content'] as $contentItem) {
                    if (isset($contentItem['type']) && $contentItem['type'] === 'output_file' && isset($contentItem['fileUrl'])) {
                        return $contentItem['fileUrl'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * تحميل محتوى الملف من URL
     * مطابق لمنطق ZaiProviderService
     */
    private function downloadFileContent(string $fileUrl): ?string
    {
        try {
            Log::info('Manus API Downloading file', [
                'file_url' => $fileUrl,
            ]);
            
            $response = Http::timeout(30)->get($fileUrl);
            
            if ($response->successful()) {
                $rawContent = $response->body();
                
                // تطبيق نفس منطق تنظيف encoding من Zai
                if (!mb_check_encoding($rawContent, 'UTF-8')) {
                    $content = mb_convert_encoding($rawContent, 'UTF-8', 'auto');
                    if (!mb_check_encoding($content, 'UTF-8')) {
                        $content = mb_convert_encoding($rawContent, 'UTF-8', ['UTF-8', 'ISO-8859-1', 'Windows-1256']);
                    }
                } else {
                    $content = $rawContent;
                }
                
                // تنظيف النص من الأحرف غير الصالحة في UTF-8
                $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                // إزالة BOM إذا كان موجوداً
                $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
                
                Log::info('Manus API File downloaded successfully', [
                    'content_length' => strlen($content),
                    'content_preview' => mb_substr($content, 0, 200),
                    'encoding_valid' => mb_check_encoding($content, 'UTF-8'),
                ]);
                return $content;
            }
            
            Log::warning('Manus API File download failed', [
                'status' => $response->status(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Manus API File download exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * استخراج النص من messages array
     * مطابق لمنطق ZaiProviderService
     */
    private function extractTextFromMessages(array $messages): string
    {
        $text = '';
        foreach ($messages as $message) {
            if (isset($message['content']) && is_array($message['content'])) {
                foreach ($message['content'] as $contentItem) {
                    if (isset($contentItem['type']) && $contentItem['type'] === 'output_text' && isset($contentItem['text'])) {
                        $text .= $contentItem['text'] . "\n";
                    }
                }
            }
        }
        
        $text = trim($text);
        
        // تطبيق تنظيف encoding (مثل Zai)
        if (!empty($text)) {
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            }
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        }
        
        return $text;
    }

    /**
     * تحويل messages إلى prompt (Manus يستخدم prompt وليس messages)
     */
    private function convertMessagesToPrompt(array $messages): string
    {
        $prompt = '';
        
        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            
            if ($role === 'system') {
                $prompt .= "System: {$content}\n\n";
            } elseif ($role === 'assistant') {
                $prompt .= "Assistant: {$content}\n\n";
            } elseif ($role === 'user') {
                $prompt .= "User: {$content}\n\n";
            } else {
                $prompt .= "{$content}\n\n";
            }
        }
        
        // إزالة المسافات الزائدة في النهاية
        return trim($prompt);
    }

    /**
     * توليد نص من prompt
     * مطابق لمنطق ZaiProviderService
     */
    public function generateText(string $prompt, array $options = []): string
    {
        // استخدام chat() method مثل Zai
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $result = $this->chat($messages, $options);
        
        if (!$result['success']) {
            $this->setLastError($result['error'] ?? 'خطأ غير معروف في توليد النص');
            return '';
        }
        
        $content = $result['content'] ?? '';
        
        // تطبيق نفس منطق تنظيف encoding من Zai
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

    /**
     * تقدير عدد الـ tokens
     */
    public function estimateTokens(string $text): int
    {
        // تقدير تقريبي: ~4 characters per token
        // يمكن استخدام مكتبة tiktoken للحصول على تقدير أدق
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection(): bool
    {
        try {
            // استخدام generateText مباشرة لاختبار أبسط
            $result = $this->generateText('Say "OK" only.', ['max_tokens' => 10]);

            if (empty($result)) {
                $this->setLastError($this->getLastError() ?? 'فشل اختبار الاتصال');
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Manus test connection failed: ' . $e->getMessage());
            $this->setLastError('فشل اختبار الاتصال: ' . $e->getMessage());
            return false;
        }
    }
}

