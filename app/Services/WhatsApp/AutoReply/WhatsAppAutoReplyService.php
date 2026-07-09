<?php

namespace App\Services\WhatsApp\AutoReply;

use App\Jobs\ProcessWhatsAppAutoReplyJob;
use App\Models\EvolutionInstance;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\Evolution\EvolutionRotatingSendService;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Support\WhatsAppRecipientNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppAutoReplyService
{
    private const BUFFER_PREFIX = 'auto_reply_buffer:';

    private const RUN_AT_PREFIX = 'auto_reply_run_at:';

    private const COOLDOWN_PREFIX = 'auto_reply_cooldown:';

    public function __construct(
        private WhatsAppSettingsService $settingsService,
        private WhatsAppAutoReplyAiGenerator $aiGenerator,
        private WhatsAppAutoReplyHumanizer $humanizer,
        private WhatsAppAutoReplyPresenceService $presenceService,
        private EvolutionRotatingSendService $rotatingSendService,
        private SendWhatsAppMessage $sendService,
    ) {}

    /**
     * Queue debounced auto-reply for an inbound message.
     */
    public function scheduleForReply(WhatsAppMessage $message): void
    {
        $message->loadMissing('contact');

        if (! $this->passesInboundGate($message, $settings = $this->settingsService->getAutoReplySettings())) {
            return;
        }

        $contactId = (int) $message->contact_id;
        $bufferKey = self::BUFFER_PREFIX.$contactId;
        $runAtKey = self::RUN_AT_PREFIX.$contactId;

        $buffer = Cache::get($bufferKey, [
            'message_ids' => [],
            'instance' => $this->resolveInboundInstance($message),
            'reply_jid' => $this->resolveReplyJid($message),
        ]);

        if (! in_array($message->id, $buffer['message_ids'], true)) {
            $buffer['message_ids'][] = $message->id;
        }
        $buffer['instance'] = $this->resolveInboundInstance($message) ?: ($buffer['instance'] ?? '');
        $buffer['reply_jid'] = $this->resolveReplyJid($message) ?: ($buffer['reply_jid'] ?? '');

        Cache::put($bufferKey, $buffer, now()->addMinutes(10));

        $debounceSeconds = max(1, (int) ($settings['auto_reply_debounce_seconds'] ?? 8));
        $runAt = now()->addSeconds($debounceSeconds);
        Cache::put($runAtKey, $runAt->timestamp, $runAt->copy()->addMinutes(10));

        ProcessWhatsAppAutoReplyJob::dispatch($contactId)
            ->delay($runAt)
            ->onQueue('whatsapp');

        Log::channel('whatsapp')->info('AutoReply: debounced job scheduled', [
            'contact_id' => $contactId,
            'message_id' => $message->id,
            'debounce_seconds' => $debounceSeconds,
            'instance' => $buffer['instance'] ?: null,
        ]);
    }

    public function secondsUntilAutoReplyReady(int $contactId): int
    {
        $runAt = (int) Cache::get(self::RUN_AT_PREFIX.$contactId, 0);

        return max(0, $runAt - time());
    }

    public function shouldDeferAutoReply(int $contactId): bool
    {
        return $this->secondsUntilAutoReplyReady($contactId) > 0;
    }

    /**
     * Process debounced messages for a contact (called from job).
     */
    public function processContact(int $contactId): void
    {
        $settings = $this->settingsService->getAutoReplySettings();
        $bufferKey = self::BUFFER_PREFIX.$contactId;
        $runAtKey = self::RUN_AT_PREFIX.$contactId;

        $buffer = Cache::pull($bufferKey, ['message_ids' => [], 'instance' => '', 'reply_jid' => '']);
        Cache::forget($runAtKey);

        $messageIds = array_values(array_unique(array_filter($buffer['message_ids'] ?? [])));
        if ($messageIds === []) {
            return;
        }

        $cooldownKey = self::COOLDOWN_PREFIX.$contactId;
        if (Cache::has($cooldownKey)) {
            Log::channel('whatsapp')->info('AutoReply: skipped — contact cooldown active', [
                'contact_id' => $contactId,
            ]);

            return;
        }

        $messages = WhatsAppMessage::query()
            ->with('contact')
            ->whereIn('id', $messageIds)
            ->where('direction', WhatsAppMessage::DIRECTION_INBOUND)
            ->orderBy('id')
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        $first = $messages->first();
        if (! $this->passesInboundGate($first, $settings)) {
            return;
        }

        $supportInstance = $this->resolveSupportInstance($settings);
        $instanceName = ($buffer['instance'] ?? '') ?: $this->resolveInboundInstance($first);
        if ($instanceName === '') {
            $instanceName = $supportInstance;
        }
        if (! $this->inboundBelongsToSupportInstance($first, $supportInstance, $instanceName)) {
            Log::channel('whatsapp')->warning('AutoReply: skipped — instance mismatch at send', [
                'instance' => $instanceName,
                'configured' => $supportInstance,
            ]);

            return;
        }
        $instanceName = $supportInstance;

        $contact = $first->contact;
        if (! $contact) {
            return;
        }

        $recipient = ($buffer['reply_jid'] ?? '') ?: $this->resolveReplyJid($first) ?: $contact->wa_id;

        $incomingBodies = $messages
            ->pluck('body')
            ->map(fn ($b) => trim((string) $b))
            ->filter()
            ->values()
            ->all();

        if ($incomingBodies === []) {
            return;
        }

        $initialDelay = $this->humanizer->randomInitialDelaySeconds(
            (int) ($settings['auto_reply_initial_delay_min'] ?? 2),
            (int) ($settings['auto_reply_initial_delay_max'] ?? 5),
        );
        if ($initialDelay > 0) {
            sleep($initialDelay);
        }

        $useAi = (bool) ($settings['auto_reply_use_ai'] ?? false);
        $replyText = $useAi
            ? $this->aiGenerator->generate($settings, $incomingBodies)
            : ($settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.');

        if ($replyText === null || trim($replyText) === '') {
            $replyText = $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.';
        }

        $chunks = $this->humanizer->splitIntoChunks(
            $replyText,
            (int) ($settings['auto_reply_chunk_max_chars'] ?? 350),
            (int) ($settings['auto_reply_max_chunks'] ?? 3),
        );

        if ($chunks === []) {
            return;
        }

        if (! WhatsAppRecipientNormalizer::isReplyableRecipient($recipient)) {
            Log::channel('whatsapp')->warning('AutoReply: skipped — non-replyable recipient', [
                'recipient' => $recipient,
                'contact_wa_id' => $contact->wa_id,
            ]);

            return;
        }

        $typingMs = $this->humanizer->typingDelayMs((int) ($settings['auto_reply_typing_duration'] ?? 3));

        foreach ($chunks as $chunk) {
            try {
                if ($instanceName !== '' && ($settings['whatsapp_provider'] ?? '') === 'evolution') {
                    $this->presenceService->sendComposing($instanceName, $recipient, $typingMs);
                    usleep($typingMs * 1000);
                }

                if ($instanceName !== '') {
                    $this->rotatingSendService->waitBeforeSend($instanceName);
                }

                $this->sendService->sendTextSync(
                    $recipient,
                    $chunk,
                    false,
                    true,
                    $instanceName !== '' ? $instanceName : null,
                );
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('AutoReply: send chunk failed', [
                    'contact_id' => $contactId,
                    'recipient' => $recipient,
                    'instance' => $instanceName,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        $cooldownSeconds = max(1, (int) ($settings['auto_reply_contact_cooldown'] ?? 45));
        Cache::put($cooldownKey, 1, now()->addSeconds($cooldownSeconds));

        Log::channel('whatsapp')->info('AutoReply: sent', [
            'contact_id' => $contactId,
            'instance' => $instanceName,
            'chunks' => count($chunks),
            'used_ai' => $useAi,
            'initial_delay' => $initialDelay,
        ]);
    }

    /**
     * Full pipeline test send to a specific number (admin).
     */
    public function testSend(array $settings, string $to, string $question): void
    {
        $instanceName = $this->resolveSupportInstance($settings);
        if ($instanceName === '') {
            throw new \InvalidArgumentException('لم يُحدَّد Instance الدعم — اختره من إعدادات الرد التلقائي.');
        }

        $useAi = (bool) ($settings['auto_reply_use_ai'] ?? false);
        $replyText = $useAi
            ? $this->aiGenerator->generate($settings, [$question])
            : ($settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.');

        if ($replyText === null || trim($replyText) === '') {
            $replyText = $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.';
        }

        $chunks = $this->humanizer->splitIntoChunks(
            $replyText,
            (int) ($settings['auto_reply_chunk_max_chars'] ?? 350),
            (int) ($settings['auto_reply_max_chunks'] ?? 3),
        );

        $typingMs = $this->humanizer->typingDelayMs((int) ($settings['auto_reply_typing_duration'] ?? 3));
        $initialDelay = $this->humanizer->randomInitialDelaySeconds(
            (int) ($settings['auto_reply_initial_delay_min'] ?? 2),
            (int) ($settings['auto_reply_initial_delay_max'] ?? 5),
        );
        if ($initialDelay > 0) {
            sleep($initialDelay);
        }

        foreach ($chunks as $chunk) {
            $this->presenceService->sendComposing($instanceName, $to, $typingMs);
            usleep($typingMs * 1000);
            $this->rotatingSendService->waitBeforeSend($instanceName);
            $this->sendService->sendTextSync($to, $chunk, false, true, $instanceName);
        }
    }

    public function passesInboundGate(WhatsAppMessage $message, ?array $settings = null): bool
    {
        $settings ??= $this->settingsService->getAutoReplySettings();

        if ($message->direction !== WhatsAppMessage::DIRECTION_INBOUND) {
            return false;
        }

        if ($message->type !== 'text') {
            Log::channel('whatsapp')->info('AutoReply: skipped — non-text message', ['type' => $message->type]);

            return false;
        }

        if (trim((string) ($message->body ?? '')) === '') {
            Log::channel('whatsapp')->info('AutoReply: skipped — empty message body');

            return false;
        }

        if (! ($settings['whatsapp_enabled'] ?? false)) {
            Log::channel('whatsapp')->info('AutoReply: skipped — WhatsApp disabled');

            return false;
        }

        if (! ($settings['auto_reply'] ?? false)) {
            Log::channel('whatsapp')->info('AutoReply: skipped — auto_reply disabled');

            return false;
        }

        if (($settings['whatsapp_provider'] ?? '') !== 'evolution') {
            Log::channel('whatsapp')->info('AutoReply: skipped — provider is not evolution');

            return false;
        }

        $supportInstance = $this->resolveSupportInstance($settings);
        if ($supportInstance === '') {
            Log::channel('whatsapp')->info('AutoReply: skipped — no support instance configured');

            return false;
        }

        $inboundInstance = $this->resolveInboundInstance($message);
        if ($inboundInstance !== '' && ! $this->inboundBelongsToSupportInstance($message, $supportInstance, $inboundInstance)) {
            Log::channel('whatsapp')->info('AutoReply: skipped — instance mismatch', [
                'inbound' => $inboundInstance,
                'configured' => $supportInstance,
            ]);

            return false;
        }

        $contact = $message->contact;
        $replyJid = $this->resolveReplyJid($message) ?: $contact?->wa_id;
        if (! $contact || ! $replyJid || ! WhatsAppRecipientNormalizer::isReplyableRecipient($replyJid)) {
            Log::channel('whatsapp')->info('AutoReply: skipped — non-replyable recipient', [
                'wa_id' => $contact?->wa_id,
                'reply_jid' => $replyJid,
            ]);

            return false;
        }

        return true;
    }

    public function resolveInboundInstance(WhatsAppMessage $message): string
    {
        $payload = $message->payload ?? [];

        return (string) ($payload['evolution_instance_name'] ?? $payload['instance'] ?? '');
    }

    public function resolveReplyJid(WhatsAppMessage $message): string
    {
        $payload = $message->payload ?? [];

        return (string) (
            $payload['evolution_reply_jid']
            ?? $payload['evolution_remote_jid_alt']
            ?? $payload['evolution_remote_jid']
            ?? ''
        );
    }

    public function resolveSupportInstance(array $settings): string
    {
        $configured = trim($settings['auto_reply_evolution_instance'] ?? '');
        if ($configured !== '') {
            return $configured;
        }

        $all = $this->settingsService->getSettings();

        return trim($all['evolution_instance_name'] ?? '');
    }

    public function instancesMatch(string $a, string $b): bool
    {
        $a = $this->normalizeInstanceName($a);
        $b = $this->normalizeInstanceName($b);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b;
    }

    public function inboundBelongsToSupportInstance(WhatsAppMessage $message, string $supportInstance, ?string $inboundInstance = null): bool
    {
        $supportInstance = trim($supportInstance);
        if ($supportInstance === '') {
            return false;
        }

        $inboundInstance = $inboundInstance ?? $this->resolveInboundInstance($message);
        if ($inboundInstance === '') {
            return true;
        }

        if ($this->instancesMatch($inboundInstance, $supportInstance)) {
            return true;
        }

        $payload = $message->payload ?? [];
        $instanceUuid = (string) (
            $payload['evolution_instance_uuid']
            ?? data_get($payload, 'provider_payload.instanceId')
            ?? ''
        );

        if ($instanceUuid !== '') {
            $mappedName = EvolutionInstance::query()
                ->where('evolution_uuid', $instanceUuid)
                ->value('instance_name');

            if (is_string($mappedName) && $mappedName !== '' && $this->instancesMatch($mappedName, $supportInstance)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeInstanceName(string $name): string
    {
        $name = urldecode(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_strtolower($name);
    }
}
