<?php

namespace App\Services\Ai;

use App\Ai\Agents\ContentSummaryAgent;
use App\Models\ContentSummary;
use App\Models\AIModel;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class AIContentSummaryService
{
    public function __construct(
        private AIModelService $modelService,
        private AIPromptService $promptService,
        private AIModelResolver $resolver,
        private LaravelAiConfigurator $configurator,
        private LegacyProviderGateway $legacyGateway,
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
            $response = $this->promptModel($model, $prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => 0.5,
            ]);

            $tokensUsed = (int) ceil(strlen($prompt.$response) / 4);
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

    protected function promptModel(AIModel $model, string $prompt, array $options = []): string
    {
        if ($this->resolver->isLegacy($model)) {
            return $this->legacyGateway->prompt($model, $prompt, $options);
        }

        $lab = $this->resolver->resolveLab($model);
        if (! $lab instanceof Lab) {
            throw new \RuntimeException("Unsupported AI provider: {$model->provider}");
        }

        return $this->configurator->using($model, function () use ($model, $prompt, $lab, $options) {
            $response = ContentSummaryAgent::make()->prompt(
                $prompt,
                provider: $lab,
                model: $model->model_key,
                timeout: 180
            );

            return (string) $response;
        });
    }

    // تم إزالة summarizeLesson و summarizeCourse لأن Lesson و Course models غير موجودة في هذا المشروع
}

