<?php

namespace App\Services\Ai;

use App\Ai\Agents\ContentImprovementAgent;
use App\Models\AIModel;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class AIContentImprovementService
{
    public function __construct(
        private AIModelService $modelService,
        private AIPromptService $promptService,
        private AIModelResolver $resolver,
        private LaravelAiConfigurator $configurator,
        private LegacyProviderGateway $legacyGateway,
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
            $response = $this->promptModel($model, $prompt, [
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
            $response = $this->promptModel($model, $prompt, [
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
            return $this->promptModel($model, $prompt, [
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
            $response = $this->promptModel($model, $prompt, [
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

    protected function promptModel(AIModel $model, string $prompt, array $options = []): string
    {
        if ($this->resolver->isLegacy($model)) {
            return $this->legacyGateway->prompt($model, $prompt, $options);
        }

        $lab = $this->resolver->resolveLab($model);
        if (! $lab instanceof Lab) {
            throw new \RuntimeException("Unsupported AI provider: {$model->provider}");
        }

        return $this->configurator->using($model, function () use ($model, $prompt, $lab) {
            $response = ContentImprovementAgent::make()->prompt(
                $prompt,
                provider: $lab,
                model: $model->model_key,
                timeout: 180
            );

            return (string) $response;
        });
    }
}

