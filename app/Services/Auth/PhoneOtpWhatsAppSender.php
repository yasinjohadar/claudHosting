<?php

namespace App\Services\Auth;

use InvalidArgumentException;

class PhoneOtpWhatsAppSender
{
    public function __construct(
        private PhoneOtpSettingsService $settingsService,
        private PhoneOtpEvolutionSender $evolutionSender,
    ) {}

    public function isAvailable(): bool
    {
        if (! $this->settingsService->isEnabled()) {
            return false;
        }

        return $this->evolutionSender->isAvailable();
    }

    public function send(string $phone, string $code): void
    {
        $this->evolutionSender->send($phone, $code);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildHealthReport(): array
    {
        $settings = $this->settingsService->getSettings();
        $templateIssues = $this->evolutionSender->availabilityIssues();
        $channelReady = $this->evolutionSender->isAvailable();

        return [
            'delivery_channel' => 'evolution',
            'otp_enabled' => (bool) ($settings['enabled'] ?? false),
            'template_name' => 'Evolution (نص)',
            'template_issues' => $templateIssues,
            'ready' => ($settings['enabled'] ?? false) && $channelReady,
        ];
    }
}
