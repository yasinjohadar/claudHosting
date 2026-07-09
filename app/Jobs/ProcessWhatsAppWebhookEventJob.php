<?php

namespace App\Jobs;

use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsApp\AutoReply\WhatsAppAutoReplyService;
use App\Services\WhatsApp\WebhookParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'whatsapp';

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public WhatsAppWebhookEvent $webhookEvent
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookParser $parser, WhatsAppAutoReplyService $autoReplyService): void
    {
        try {
            $payload = $this->webhookEvent->payload;
            $parsed = $parser->parse($payload);

            // Process inbound messages
            foreach ($parsed['messages'] as $messageDTO) {
                $this->processInboundMessage($messageDTO, $autoReplyService);
            }

            // Process status updates
            foreach ($parsed['statuses'] as $statusDTO) {
                $this->processStatusUpdate($statusDTO);
            }

            // Mark event as processed
            $this->webhookEvent->markAsProcessed();

            Log::channel('whatsapp')->info('Webhook event processed successfully', [
                'event_id' => $this->webhookEvent->id,
                'messages_count' => count($parsed['messages']),
                'statuses_count' => count($parsed['statuses']),
            ]);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error processing webhook event', [
                'event_id' => $this->webhookEvent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process inbound message
     */
    protected function processInboundMessage($messageDTO, WhatsAppAutoReplyService $autoReplyService): void
    {
        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($messageDTO->from);
        $contact->updateLastSeen();

        $instanceName = (string) ($this->webhookEvent->payload['instance'] ?? '');
        $payload = $messageDTO->metadata ?? [];
        $record = is_array($payload['provider_payload'] ?? null) ? $payload['provider_payload'] : [];
        $key = is_array($record['key'] ?? null) ? $record['key'] : [];
        $remoteJid = (string) ($key['remoteJid'] ?? '');
        $remoteJidAlt = (string) ($key['remoteJidAlt'] ?? '');

        if ($instanceName !== '') {
            $payload['evolution_instance_name'] = $instanceName;
        }
        $instanceUuid = (string) (data_get($record, 'instanceId') ?? data_get($this->webhookEvent->payload, 'data.instanceId') ?? '');
        if ($instanceUuid !== '') {
            $payload['evolution_instance_uuid'] = $instanceUuid;
        }
        if ($remoteJid !== '') {
            $payload['evolution_remote_jid'] = $remoteJid;
        }
        if ($remoteJidAlt !== '') {
            $payload['evolution_remote_jid_alt'] = $remoteJidAlt;
        }
        $payload['evolution_reply_jid'] = $messageDTO->from !== ''
            ? $messageDTO->from
            : ($remoteJidAlt !== '' ? $remoteJidAlt : $remoteJid);

        // Avoid duplicate inbound record creation on webhook retries
        $message = WhatsAppMessage::firstOrCreate(
            [
                'direction' => WhatsAppMessage::DIRECTION_INBOUND,
                'meta_message_id' => $messageDTO->messageId,
            ],
            [
                'contact_id' => $contact->id,
                'type' => $messageDTO->type,
                'body' => $messageDTO->textBody,
                'status' => WhatsAppMessage::STATUS_DELIVERED,
                'payload' => $payload,
            ]
        );

        if (!$message->wasRecentlyCreated) {
            return;
        }

        try {
            $autoReplyService->scheduleForReply($message);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('AutoReply: schedule failed after inbound', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('whatsapp')->info('Inbound message processed', [
            'message_id' => $message->id,
            'meta_message_id' => $messageDTO->messageId,
            'from' => $messageDTO->from,
        ]);
    }

    /**
     * Process status update
     */
    protected function processStatusUpdate($statusDTO): void
    {
        // Find message by meta_message_id
        $message = WhatsAppMessage::byMetaMessageId($statusDTO->messageId)->first();

        if (!$message) {
            Log::channel('whatsapp')->warning('Status update received for unknown message', [
                'meta_message_id' => $statusDTO->messageId,
            ]);
            return;
        }

        // Map status from Meta API to our status enum
        $statusMap = [
            'sent' => WhatsAppMessage::STATUS_SENT,
            'delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'read' => WhatsAppMessage::STATUS_READ,
            'failed' => WhatsAppMessage::STATUS_FAILED,
            'message_sent' => WhatsAppMessage::STATUS_SENT,
            'message_delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'message_read' => WhatsAppMessage::STATUS_READ,
            'message_failed' => WhatsAppMessage::STATUS_FAILED,
        ];

        $newStatus = $statusMap[strtolower($statusDTO->status)] ?? WhatsAppMessage::STATUS_SENT;

        // Update message status
        $updateData = ['status' => $newStatus];

        // Store conversation and pricing data if available
        if ($statusDTO->conversation || $statusDTO->pricing) {
            $payload = $message->payload ?? [];
            if ($statusDTO->conversation) {
                $payload['conversation'] = $statusDTO->conversation;
            }
            if ($statusDTO->pricing) {
                $payload['pricing'] = $statusDTO->pricing;
            }
            $updateData['payload'] = $payload;
        }

        $message->update($updateData);

        Log::channel('whatsapp')->info('Message status updated', [
            'message_id' => $message->id,
            'meta_message_id' => $statusDTO->messageId,
            'status' => $newStatus,
        ]);
    }
}
