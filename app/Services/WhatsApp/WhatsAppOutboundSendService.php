<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\Evolution\EvolutionRotatingSendService;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Support\WhatsAppRecipientNormalizer;

/**
 * Single place for outbound send logic (used by queue job and sync paths).
 * Avoids dispatching SendWhatsAppMessageJob twice when broadcast uses sync send.
 */
class WhatsAppOutboundSendService
{
    public function __construct(
        private WhatsAppSettingsService $settingsService,
        private EvolutionRotatingSendService $rotatingSendService,
        private EvolutionService $evolutionService,
    ) {}

    /**
     * Perform API send and update the WhatsAppMessage row.
     *
     * @throws WhatsAppApiException|\Throwable
     */
    public function send(WhatsAppMessage $message, array $messageData = []): void
    {
        $contact = $message->contact;
        if (! $contact) {
            $message->load('contact');
            $contact = $message->contact;
        }

        $settings = $this->settingsService->getSettings();
        $provider = $settings['whatsapp_provider'] ?? 'evolution';
        $config = $this->settingsService->getProviderConfig();
        $to = WhatsAppRecipientNormalizer::normalize($provider, $contact->wa_id);
        $messageType = $messageData['type'] ?? $message->type;

        $forcedInstance = $messageData['evolution_instance_name']
            ?? ($message->payload['evolution_instance_name'] ?? null);
        $applyDelay = $messageData['apply_send_delay'] ?? true;

        if ($provider === 'evolution') {
            $sendResult = $this->rotatingSendService->sendWithRotation(
                function (string $instanceName) use ($to, $messageType, $messageData, $message) {
                    $instanceConfig = $this->evolutionService->providerConfigForInstance($instanceName);
                    $providerInstance = WhatsAppProviderFactory::create('evolution', $instanceConfig);

                    return $this->dispatchToProvider($providerInstance, $messageType, $to, $messageData, $message);
                },
                $forcedInstance ? (string) $forcedInstance : null,
                (bool) $applyDelay,
            );

            $response = $sendResult['result'];
            $usedInstance = $sendResult['instance_name'];
        } else {
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);
            $response = $this->dispatchToProvider($providerInstance, $messageType, $to, $messageData, $message);
            $usedInstance = null;
        }

        $payload = array_merge($message->payload ?? [], [
            'response' => $response->rawResponse,
        ]);

        if ($usedInstance !== null) {
            $payload['evolution_instance_name'] = $usedInstance;
        }

        $message->update([
            'meta_message_id' => $response->metaMessageId,
            'status' => WhatsAppMessage::STATUS_SENT,
            'payload' => $payload,
        ]);
    }

    private function dispatchToProvider(
        WhatsAppProviderService $providerInstance,
        string $messageType,
        string $to,
        array $messageData,
        WhatsAppMessage $message
    ) {
        if ($messageType === 'template') {
            return $providerInstance->sendTemplate(
                $to,
                $messageData['template_name'] ?? $message->body,
                $messageData['language'] ?? 'ar',
                $messageData['components'] ?? []
            );
        }

        if ($messageType === 'document') {
            return $providerInstance->sendDocument(
                $to,
                $messageData['document_url'] ?? '',
                $messageData['filename'] ?? 'document.pdf',
                $messageData['caption'] ?? null
            );
        }

        return $providerInstance->sendText(
            $to,
            $messageData['text'] ?? $message->body ?? '',
            $messageData['preview_url'] ?? false
        );
    }
}
