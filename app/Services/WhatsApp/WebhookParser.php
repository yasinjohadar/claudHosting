<?php

namespace App\Services\WhatsApp;

use App\DTOs\WhatsApp\InboundMessageDTO;
use App\DTOs\WhatsApp\StatusUpdateDTO;
use App\Services\WhatsApp\Evolution\EvolutionWebhookParser;
use Illuminate\Support\Facades\Log;

class WebhookParser
{
    public function __construct(
        private EvolutionWebhookParser $evolutionParser
    ) {}

    /**
     * Parse webhook payload and extract messages and statuses
     *
     * @return array{messages: InboundMessageDTO[], statuses: StatusUpdateDTO[]}
     */
    public function parse(array $payload): array
    {
        $messages = [];
        $statuses = [];

        try {
            // Meta style payload
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    // Parse inbound messages
                    $messagesData = $value['messages'] ?? [];
                    foreach ($messagesData as $messageData) {
                        try {
                            $message = $this->parseMessage($messageData, $value);
                            if ($message) {
                                $messages[] = $message;
                            }
                        } catch (\Exception $e) {
                            Log::channel('whatsapp')->error('Error parsing message', [
                                'error' => $e->getMessage(),
                                'message_data' => $messageData,
                            ]);
                        }
                    }

                    // Parse status updates
                    $statusesData = $value['statuses'] ?? [];
                    foreach ($statusesData as $statusData) {
                        try {
                            $status = $this->parseStatus($statusData);
                            if ($status) {
                                $statuses[] = $status;
                            }
                        } catch (\Exception $e) {
                            Log::channel('whatsapp')->error('Error parsing status', [
                                'error' => $e->getMessage(),
                                'status_data' => $statusData,
                            ]);
                        }
                    }
                }
            }

            // Evolution API payload
            if (empty($messages) && empty($statuses) && $this->evolutionParser->isEvolutionPayload($payload)) {
                $evolutionParsed = $this->evolutionParser->parse($payload);
                $messages = $evolutionParsed['messages'];
                $statuses = $evolutionParsed['statuses'];
            }

            // Wasender/Custom API style payload fallback
            if (empty($messages) && empty($statuses)) {
                $this->parseCustomProviderPayload($payload, $messages, $statuses);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Error parsing webhook payload', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        return [
            'messages' => $messages,
            'statuses' => $statuses,
        ];
    }

    /**
     * Parse a single message from webhook data
     */
    protected function parseMessage(array $messageData, array $value): ?InboundMessageDTO
    {
        $messageId = $messageData['id'] ?? null;
        $from = $messageData['from'] ?? null;
        $timestamp = (int) ($messageData['timestamp'] ?? time());

        if (!$messageId || !$from) {
            return null;
        }

        $type = $messageData['type'] ?? 'text';
        $textBody = null;
        $metadata = [];

        // Extract text body if type is text
        if ($type === 'text' && isset($messageData['text']['body'])) {
            $textBody = $messageData['text']['body'];
        }

        // Store full message data as metadata
        $metadata = [
            'original_data' => $messageData,
            'context' => $value['context'] ?? null,
            'metadata' => $value['metadata'] ?? null,
        ];

        return new InboundMessageDTO(
            messageId: $messageId,
            from: $from,
            timestamp: $timestamp,
            type: $type,
            textBody: $textBody,
            metadata: $metadata
        );
    }

    /**
     * Parse a single status update from webhook data
     */
    protected function parseStatus(array $statusData): ?StatusUpdateDTO
    {
        $messageId = $statusData['id'] ?? null;
        $status = $statusData['status'] ?? null;
        $timestamp = (int) ($statusData['timestamp'] ?? time());
        $recipientId = $statusData['recipient_id'] ?? null;

        if (!$messageId || !$status || !$recipientId) {
            return null;
        }

        return new StatusUpdateDTO(
            messageId: $messageId,
            status: $status,
            timestamp: $timestamp,
            recipientId: $recipientId,
            conversation: $statusData['conversation'] ?? null,
            pricing: $statusData['pricing'] ?? null
        );
    }

    /**
     * Parse payloads from non-Meta providers (e.g. Wasender custom API webhook).
     *
     * This parser intentionally supports multiple possible keys for compatibility.
     */
    protected function parseCustomProviderPayload(array $payload, array &$messages, array &$statuses): void
    {
        $rootEvent = strtolower((string) ($payload['event'] ?? $payload['type'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        // Inbound candidate (message upsert / message received)
        $inboundEventNames = ['message_received', 'message_upsert', 'messages.upsert', 'inbound', 'message'];
        $statusEventNames = ['message_status', 'message_status_update', 'status', 'message_sent', 'message_delivered', 'message_read', 'message_failed'];

        if (in_array($rootEvent, $inboundEventNames, true) || isset($data['from']) || isset($data['sender'])) {
            $messageId = (string) ($data['message_id'] ?? $data['id'] ?? $data['key']['id'] ?? '');
            $from = (string) ($data['from'] ?? $data['sender'] ?? $data['key']['remoteJid'] ?? '');
            $timestamp = (int) ($data['timestamp'] ?? $data['messageTimestamp'] ?? time());
            $type = (string) ($data['type'] ?? 'text');
            $textBody = (string) ($data['text'] ?? $data['body'] ?? $data['message']['conversation'] ?? '');

            if ($messageId !== '' && $from !== '') {
                $messages[] = new InboundMessageDTO(
                    messageId: $messageId,
                    from: $from,
                    timestamp: $timestamp,
                    type: $type,
                    textBody: $textBody !== '' ? $textBody : null,
                    metadata: ['provider_payload' => $payload]
                );
            }
        }

        // Status candidate
        $rawStatus = (string) ($data['status'] ?? $payload['status'] ?? '');
        if (in_array($rootEvent, $statusEventNames, true) || $rawStatus !== '') {
            $messageId = (string) ($data['message_id'] ?? $data['id'] ?? $payload['message_id'] ?? '');
            $recipientId = (string) ($data['to'] ?? $data['recipient_id'] ?? $payload['to'] ?? 'unknown');
            $timestamp = (int) ($data['timestamp'] ?? $payload['timestamp'] ?? time());
            $status = $rawStatus !== '' ? strtolower($rawStatus) : strtolower($rootEvent);

            if ($messageId !== '' && $status !== '') {
                $statuses[] = new StatusUpdateDTO(
                    messageId: $messageId,
                    status: $status,
                    timestamp: $timestamp,
                    recipientId: $recipientId,
                    conversation: null,
                    pricing: null
                );
            }
        }
    }
}




