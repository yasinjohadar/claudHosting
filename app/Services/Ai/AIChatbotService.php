<?php

namespace App\Services\Ai;

use App\Ai\Agents\ChatbotAgent;
use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Models\AIModel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class AIChatbotService
{
    public function __construct(
        private AIModelService $modelService,
        private AIPromptService $promptService,
        private AIModelResolver $resolver,
        private LaravelAiConfigurator $configurator,
        private LegacyProviderGateway $legacyGateway,
    ) {}

    /**
     * إنشاء محادثة جديدة
     */
    public function createConversation(
        User $user,
        ?int $courseId = null,
        ?int $lessonId = null,
        ?AIModel $model = null,
        ?string $title = null
    ): AIConversation {
        // تحديد نوع المحادثة
        $conversationType = 'general';
        if ($lessonId) {
            $conversationType = 'lesson';
        } elseif ($courseId) {
            $conversationType = 'subject';
        }

        // الحصول على الموديل
        if (!$model) {
            $model = $this->modelService->getBestModelFor('chat');
        }

        $conversation = AIConversation::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'conversation_type' => $conversationType,
            'title' => $title,
            'ai_model_id' => $model?->id,
        ]);

        // إضافة رسالة نظام
        $systemPrompt = $this->promptService->getChatbotPrompt($conversation);
        $conversation->addMessage('system', $systemPrompt);

        return $conversation;
    }

    /**
     * إرسال رسالة
     */
    public function sendMessage(
        AIConversation $conversation,
        string $message,
        ?AIModel $model = null
    ): AIMessage {
        $startTime = microtime(true);

        // الحصول على الموديل
        if (!$model) {
            $model = $conversation->model ?? $this->modelService->getBestModelFor('chat');
        }

        if (!$model) {
            throw new \Exception('لا يوجد موديل AI متاح');
        }

        // إضافة رسالة المستخدم
        $userMessage = $conversation->addMessage('user', $message);

        // الحصول على تاريخ المحادثة
        $history = $this->getConversationHistory($conversation, 20);
        $messages = $history->map(function($msg) {
            return [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        })->toArray();

        // إرسال الطلب إلى AI
        try {
            $reply = $this->chatWithModel($model, $messages);
            if (trim($reply) === '') {
                throw new \Exception('خطأ في الاتصال بـ AI');
            }

            // إضافة رد AI
            $tokensUsed = (int) ceil(strlen($message.$reply) / 4);
            $assistantMessage = $conversation->addMessage('assistant', $reply, [
                'tokens_used' => $tokensUsed,
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
            ]);

            // تحديث التكلفة والوقت
            $responseTime = (microtime(true) - $startTime) * 1000; // بالمللي ثانية
            $cost = $model->getCost($tokensUsed);

            $assistantMessage->update([
                'tokens_used' => $tokensUsed,
                'cost' => $cost,
                'response_time' => (int) $responseTime,
            ]);

            return $assistantMessage;
        } catch (\Exception $e) {
            Log::error('Error sending AI message: ' . $e->getMessage(), [
                'conversation_id' => $conversation->id,
                'model_id' => $model->id,
            ]);

            throw $e;
        }
    }

    /**
     * الحصول على تاريخ المحادثة
     */
    public function getConversationHistory(AIConversation $conversation, int $limit = 50): Collection
    {
        return $conversation->messages()
                           ->where('role', '!=', 'system')
                           ->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get()
                           ->reverse();
    }

    /**
     * الحصول على السياق للمحادثة
     */
    public function getContextForConversation(AIConversation $conversation): string
    {
        return $conversation->getContext();
    }

    /**
     * تقدير التكلفة
     */
    public function estimateCost(AIConversation $conversation, string $message): float
    {
        $model = $conversation->model ?? $this->modelService->getBestModelFor('chat');
        if (!$model) {
            return 0;
        }

        $estimatedTokens = (int) ceil(strlen($message) / 4);
        
        return $model->getCost($estimatedTokens);
    }

    protected function chatWithModel(AIModel $model, array $messages): string
    {
        $prompt = $this->messagesToPrompt($messages);

        if ($this->resolver->isLegacy($model)) {
            return $this->legacyGateway->prompt($model, $prompt, [
                'max_tokens' => $model->max_tokens,
                'temperature' => $model->temperature,
            ]);
        }

        $lab = $this->resolver->resolveLab($model);
        if (! $lab instanceof Lab) {
            throw new \RuntimeException("Unsupported AI provider: {$model->provider}");
        }

        return $this->configurator->using($model, function () use ($model, $lab, $prompt) {
            $response = ChatbotAgent::make()->prompt(
                $prompt,
                provider: $lab,
                model: $model->model_key,
                timeout: 180
            );

            return (string) $response;
        });
    }

    protected function messagesToPrompt(array $messages): string
    {
        $lines = [];

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            $lines[] = strtoupper($role).': '.$content;
        }

        return implode("\n\n", $lines);
    }
}

