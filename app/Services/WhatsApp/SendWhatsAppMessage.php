<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use App\Support\WhatsAppRecipientNormalizer;
use App\Services\WhatsApp\WhatsAppOutboundSendService;

class SendWhatsAppMessage
{
    protected WhatsAppSettingsService $settingsService;

    public function __construct(WhatsAppSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Send text message
     */
    public function sendText(string $to, string $text, bool $previewUrl = false): WhatsAppMessage
    {
        $provider = $this->settingsService->getSettings()['whatsapp_provider'] ?? 'evolution';
        $normalizedRecipient = WhatsAppRecipientNormalizer::normalize($provider, $to);

        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($normalizedRecipient);

        // Create message record with queued status
        $message = WhatsAppMessage::create([
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'contact_id' => $contact->id,
            'type' => WhatsAppMessage::TYPE_TEXT,
            'body' => $text,
            'status' => WhatsAppMessage::STATUS_QUEUED,
        ]);

        // Dispatch job to send message
        SendWhatsAppMessageJob::dispatch($message, [
            'type' => 'text',
            'text' => $text,
            'preview_url' => $previewUrl,
        ]);

        return $message;
    }

    /**
     * Send text message synchronously (runs in the same request, no queue).
     * Use for welcome messages so they are sent immediately without needing queue:work.
     */
    public function sendTextSync(
        string $to,
        string $text,
        bool $previewUrl = false,
        bool $applySendDelay = true,
        ?string $evolutionInstanceName = null,
    ): WhatsAppMessage {
        $provider = $this->settingsService->getSettings()['whatsapp_provider'] ?? 'evolution';
        $normalizedRecipient = WhatsAppRecipientNormalizer::normalize($provider, $to);
        $contact = WhatsAppContact::findOrCreateByWaId($normalizedRecipient);

        $payload = [];
        if ($evolutionInstanceName !== null && $evolutionInstanceName !== '') {
            $payload['evolution_instance_name'] = $evolutionInstanceName;
        }

        $message = WhatsAppMessage::create([
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'contact_id' => $contact->id,
            'type' => WhatsAppMessage::TYPE_TEXT,
            'body' => $text,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'payload' => $payload ?: null,
        ]);

        $messageData = [
            'type' => 'text',
            'text' => $text,
            'preview_url' => $previewUrl,
            'apply_send_delay' => $applySendDelay,
        ];

        if ($evolutionInstanceName !== null && $evolutionInstanceName !== '') {
            $messageData['evolution_instance_name'] = $evolutionInstanceName;
        }

        try {
            app(WhatsAppOutboundSendService::class)->send($message, $messageData);
        } catch (\Throwable $e) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ]);

            throw $e;
        }

        return $message->fresh();
    }

    /**
     * Send template message
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $language = 'ar',
        array $components = []
    ): WhatsAppMessage {
        $provider = $this->settingsService->getSettings()['whatsapp_provider'] ?? 'evolution';
        $normalizedRecipient = WhatsAppRecipientNormalizer::normalize($provider, $to);

        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($normalizedRecipient);

        // Create message record with queued status
        $message = WhatsAppMessage::create([
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'contact_id' => $contact->id,
            'type' => WhatsAppMessage::TYPE_TEMPLATE,
            'body' => $templateName,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'payload' => [
                'template_name' => $templateName,
                'language' => $language,
                'components' => $components,
            ],
        ]);

        // Dispatch job to send message
        SendWhatsAppMessageJob::dispatch($message, [
            'type' => 'template',
            'template_name' => $templateName,
            'language' => $language,
            'components' => $components,
        ]);

        return $message;
    }

    /**
     * Send document message
     */
    public function sendDocument(
        string $to,
        string $documentUrl,
        string $filename,
        ?string $caption = null
    ): WhatsAppMessage {
        $provider = $this->settingsService->getSettings()['whatsapp_provider'] ?? 'evolution';
        $normalizedRecipient = WhatsAppRecipientNormalizer::normalize($provider, $to);

        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($normalizedRecipient);

        // Create message record with queued status
        $message = WhatsAppMessage::create([
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'contact_id' => $contact->id,
            'type' => WhatsAppMessage::TYPE_DOCUMENT,
            'body' => $caption ?? $filename,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'payload' => [
                'document_url' => $documentUrl,
                'filename' => $filename,
                'caption' => $caption,
            ],
        ]);

        // Dispatch job to send message
        SendWhatsAppMessageJob::dispatch($message, [
            'type' => 'document',
            'document_url' => $documentUrl,
            'filename' => $filename,
            'caption' => $caption,
        ]);

        return $message;
    }
}




