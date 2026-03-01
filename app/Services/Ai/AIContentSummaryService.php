<?php

namespace App\Services\Ai;

use App\Models\ContentSummary;
use App\Models\AIModel;
use Illuminate\Support\Facades\Log;

class AIContentSummaryService
{
    public function __construct(
        private AIModelService $modelService,
        private AIPromptService $promptService
    ) {}

    /**
     * تلخيص محتوى عام
     */
    public function summarize(string $content, string $type = 'short', ?AIModel $model = null): ContentSummary
    {
        // زيادة وقت التنفيذ إلى 3 دقائق للطلبات الطويلة
        set_time_limit(180);
        
        if (!$model) {
            $model = $this->modelService->getBestModelFor('question_solving');
        }

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح للتلخيص');
        }

        try {
            $prompt = $this->promptService->getContentSummaryPrompt($content, $type);
            
            $provider = AIProviderFactory::create($model);
            $response = $provider->generateText($prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.5,
            ]);

            $tokensUsed = $provider->estimateTokens($prompt . $response);
            $cost = $model->getCost($tokensUsed);

            $summary = ContentSummary::create([
                'summarizable_type' => 'manual',
                'summarizable_id' => 0,
                'summary_text' => $response,
                'summary_type' => $type,
                'ai_model_id' => $model->id,
                'tokens_used' => $tokensUsed,
                'cost' => $cost,
                'created_by' => auth()->id(),
            ]);

            return $summary;
        } catch (\Exception $e) {
            Log::error('Error summarizing content: ' . $e->getMessage());
            throw $e;
        }
    }

    // تم إزالة summarizeLesson و summarizeCourse لأن Lesson و Course models غير موجودة في هذا المشروع
}

