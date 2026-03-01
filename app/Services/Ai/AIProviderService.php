<?php

namespace App\Services\Ai;

use App\Models\AIModel;

/**
 * Abstract base class for AI providers
 */
abstract class AIProviderService
{
    protected AIModel $model;
    protected ?string $lastError = null;

    public function __construct(AIModel $model)
    {
        $this->model = $model;
    }

    /**
     * الحصول على آخر خطأ
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * تعيين آخر خطأ
     */
    protected function setLastError(string $error): void
    {
        $this->lastError = $error;
    }

    /**
     * إرسال رسالة في محادثة
     */
    abstract public function chat(array $messages, array $options = []): array;

    /**
     * توليد نص من prompt
     */
    abstract public function generateText(string $prompt, array $options = []): string;

    /**
     * تقدير عدد الـ tokens
     */
    abstract public function estimateTokens(string $text): int;

    /**
     * حساب التكلفة
     */
    public function calculateCost(int $tokens): float
    {
        return $this->model->getCost($tokens);
    }

    /**
     * اختبار الاتصال
     */
    abstract public function testConnection(): bool;

    /**
     * الحصول على API Key
     */
    protected function getApiKey(): ?string
    {
        return $this->model->getDecryptedApiKey();
    }

    /**
     * الحصول على Base URL
     */
    protected function getBaseUrl(): ?string
    {
        return $this->model->base_url;
    }

    /**
     * الحصول على API Endpoint
     */
    protected function getApiEndpoint(): ?string
    {
        return $this->model->api_endpoint;
    }
}

