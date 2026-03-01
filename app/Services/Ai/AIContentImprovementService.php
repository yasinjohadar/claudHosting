<?php

namespace App\Services\Ai;

use App\Models\AIModel;
use Illuminate\Support\Facades\Log;

class AIContentImprovementService
{
    public function __construct(
        private AIModelService $modelService,
        private AIPromptService $promptService
    ) {}

    /**
     * تحسين المحتوى
     */
    public function improveContent(string $content, array $options = []): array
    {
        // زيادة وقت التنفيذ إلى 3 دقائق للطلبات الطويلة
        set_time_limit(180);
        
        $type = $options['type'] ?? 'general';
        $model = $options['model'] ?? $this->modelService->getBestModelFor('question_solving');

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح للتحسين');
        }

        try {
            $prompt = $this->promptService->getContentImprovementPrompt($content, $type);
            
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.4,
            ]);

            return [
                'content' => $response,
                'suggestions' => $this->extractSuggestions($response),
            ];
        } catch (\Exception $e) {
            Log::error('Error improving content: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * فحص القواعد
     */
    public function checkGrammar(string $text, ?AIModel $model = null): array
    {
        // زيادة وقت التنفيذ إلى 3 دقائق للطلبات الطويلة
        set_time_limit(180);
        
        if (!$model) {
            $model = $this->modelService->getBestModelFor('question_solving');
        }

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح لفحص القواعد');
        }

        try {
            $prompt = $this->promptService->getGrammarCheckPrompt($text);
            
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.2,
            ]);

            // محاولة استخراج JSON
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $decoded = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            return [
                'corrected' => $response,
                'errors' => [],
            ];
        } catch (\Exception $e) {
            Log::error('Error checking grammar: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تحسين الوضوح
     */
    public function enhanceClarity(string $text, ?AIModel $model = null): string
    {
        if (!$model) {
            $model = $this->modelService->getBestModelFor('question_solving');
        }

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح لتحسين الوضوح');
        }

        try {
            $prompt = $this->promptService->getClarityEnhancementPrompt($text);
            
            $provider = AIProviderFactory::create($model);
            return $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.4,
            ]);
        } catch (\Exception $e) {
            Log::error('Error enhancing clarity: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * اقتراح تحسينات
     */
    public function suggestImprovements(string $content, ?AIModel $model = null): array
    {
        if (!$model) {
            $model = $this->modelService->getBestModelFor('question_solving');
        }

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح لاقتراح التحسينات');
        }

        try {
            $prompt = $this->promptService->getImprovementSuggestionsPrompt($content);
            
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.5,
            ]);

            return $this->extractSuggestions($response);
        } catch (\Exception $e) {
            Log::error('Error suggesting improvements: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * استخراج الاقتراحات من النص
     */
    private function extractSuggestions(string $text): array
    {
        $suggestions = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // البحث عن نقاط أو أرقام
            if (preg_match('/^[-•*]\s*(.+)$/', $line, $matches) || 
                preg_match('/^\d+[\.\)]\s*(.+)$/', $line, $matches)) {
                $suggestions[] = $matches[1];
            } elseif (strlen($line) > 20) {
                $suggestions[] = $line;
            }
        }

        return $suggestions;
    }
}

