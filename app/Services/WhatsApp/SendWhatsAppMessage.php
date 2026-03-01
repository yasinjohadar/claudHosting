<?php

namespace App\Services\WhatsApp;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Log;

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
        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($to);

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
     * Send template message
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $language = 'ar',
        array $components = []
    ): WhatsAppMessage {
        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($to);

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
        // Find or create contact
        $contact = WhatsAppContact::findOrCreateByWaId($to);

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




