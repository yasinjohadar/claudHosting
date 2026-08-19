<?php

namespace App\Services\Auth;

use App\Models\User;

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

    public function send(string $phone, string $code, ?User $user = null): void
    {
        // The user is optional so existing callers keep working; passing it lets an OTP
        // template greet the recipient by name instead of being limited to {code}.
        $this->evolutionSender->send($phone, $code, $user);
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
