<?php

namespace App\Webhooks\N8n\Handlers;

class SendWhatsAppHandler extends BaseHandler
{
    public function handle(array $payload): array
    {
        try {
            $this->validate($payload, ['phone', 'message']);

            // This is a placeholder - actual WhatsApp integration would be done through n8n
            // We just log that we received the request
            $this->logSuccess('WhatsApp message request received', [
                'phone' => $payload['phone'],
            ]);

            return $this->success('WhatsApp message request logged. Actual sending is handled by n8n workflow.', [
                'phone' => $payload['phone'],
                'message_preview' => substr($payload['message'], 0, 50),
            ]);
        } catch (\Exception $e) {
            $this->logError('WhatsApp handler failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to process WhatsApp request: ' . $e->getMessage());
        }
    }
}
