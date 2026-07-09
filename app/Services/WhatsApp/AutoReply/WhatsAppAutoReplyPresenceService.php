<?php

namespace App\Services\WhatsApp\AutoReply;

use App\Services\WhatsApp\Evolution\EvolutionApiClient;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Support\Facades\Log;

class WhatsAppAutoReplyPresenceService
{
    public function __construct(
        private WhatsAppSettingsService $settingsService,
    ) {}

    public function sendComposing(string $instanceName, string $recipient, int $delayMs): void
    {
        if ($instanceName === '' || $recipient === '') {
            return;
        }

        try {
            $config = $this->settingsService->getProviderConfig();
            $client = EvolutionApiClient::fromConfig($config);
            $client->sendChatPresence($instanceName, $recipient, 'composing', $delayMs);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('AutoReply: typing presence failed', [
                'instance' => $instanceName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
