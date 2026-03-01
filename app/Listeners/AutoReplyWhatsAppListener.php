<?php

namespace App\Listeners;

use App\Events\WhatsAppMessageReceived;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use Illuminate\Support\Facades\Log;

class AutoReplyWhatsAppListener
{
    public function __construct(
        private SendWhatsAppMessage $sendService,
        private WhatsAppSettingsService $settingsService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(WhatsAppMessageReceived $event): void
    {
        try {
            $message = $event->message;

            // Only process inbound messages
            if ($message->direction !== 'inbound') {
                return;
            }

            // Check if auto-reply is enabled
            $settings = $this->settingsService->getSettings();
            if (!($settings['auto_reply'] ?? false)) {
                return;
            }

            // Get auto-reply message
            $autoReplyMessage = $settings['auto_reply_message'] ?? 'شكراً لك، تم استلام رسالتك. سنرد عليك قريباً.';

            // Get provider settings
            $provider = $settings['whatsapp_provider'] ?? 'meta';
            $config = $this->settingsService->getProviderConfig();

            // Create provider instance
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);

            // Get contact phone number
            $contact = $message->contact;
            if (!$contact || !$contact->wa_id) {
                Log::channel('whatsapp')->warning('Auto-reply skipped: Contact or phone number not found', [
                    'message_id' => $message->id,
                ]);
                return;
            }

            // Send auto-reply
            $response = $providerInstance->sendText(
                $contact->wa_id,
                $autoReplyMessage,
                false
            );

            Log::channel('whatsapp')->info('Auto-reply sent successfully', [
                'message_id' => $message->id,
                'reply_meta_message_id' => $response->metaMessageId,
                'to' => $contact->wa_id,
            ]);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('Failed to send auto-reply', [
                'message_id' => $event->message->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
