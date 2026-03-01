<?php

namespace App\Services\WhatsApp;

use App\DTOs\WhatsApp\InboundMessageDTO;
use App\DTOs\WhatsApp\StatusUpdateDTO;
use Illuminate\Support\Facades\Log;

class WebhookParser
{
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
}




