<?php

namespace App\Services\WhatsApp\AutoReply;

use App\Models\AIModel;
use App\Services\Ai\AIModelService;
use App\Services\Ai\AIProviderFactory;
use Illuminate\Support\Facades\Log;

class WhatsAppAutoReplyAiGenerator
{
    public function __construct(
        private AIModelService $aiModelService,
        private WhatsAppAutoReplyPromptBuilder $promptBuilder,
    ) {}

    /**
     * @param  string[]  $incomingMessages
     */
    public function generate(array $settings, array $incomingMessages): ?string
    {
        $modelId = $settings['auto_reply_ai_model_id'] ?? null;
        $model = null;

        if (! empty($modelId)) {
            $model = AIModel::find($modelId);
        }
        if (! $model || ! $model->is_active) {
            $model = $this->aiModelService->getBestModelFor('chat');
        }
        if (! $model) {
            Log::channel('whatsapp')->warning('WhatsApp AI auto-reply: no AI model available');

            return null;
        }

        $messages = $this->promptBuilder->buildChatMessages($settings, $incomingMessages);

        try {
            $provider = AIProviderFactory::create($model);
            $maxTokens = min(256, (int) ($model->max_tokens ?? 256));
            $response = $provider->chat($messages, ['max_tokens' => $maxTokens]);

            if (! empty($response['success']) && ! empty($response['content'])) {
                return trim($response['content']);
            }

            Log::channel('whatsapp')->warning('WhatsApp AI auto-reply: empty or failed response', [
                'error' => $response['error'] ?? 'unknown',
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WhatsApp AI auto-reply failed', [
                'error' => $e->getMessage(),
                'model_id' => $model->id,
            ]);

            return null;
        }
    }

    /**
     * Preview without side effects — returns reply and chunks.
     *
     * @return array{reply: string, chunks: string[]}
     */
    public function preview(array $settings, string $question, WhatsAppAutoReplyHumanizer $humanizer): array
    {
        $reply = $this->generate($settings, [$question]);
        if ($reply === null || trim($reply) === '') {
            $reply = $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.';
        }

        $chunks = $humanizer->splitIntoChunks(
            $reply,
            (int) ($settings['auto_reply_chunk_max_chars'] ?? 350),
            (int) ($settings['auto_reply_max_chunks'] ?? 3),
        );

        return ['reply' => $reply, 'chunks' => $chunks];
    }
}
