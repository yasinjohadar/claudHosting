<?php

namespace App\Services\Auth;

use App\Services\WhatsApp\Evolution\EvolutionInstanceRotator;
use App\Services\WhatsApp\SendWhatsAppMessage;
use InvalidArgumentException;

class PhoneOtpEvolutionSender
{
    public function __construct(
        private PhoneOtpSettingsService $settingsService,
        private SendWhatsAppMessage $sendWhatsAppMessage,
        private EvolutionInstanceRotator $rotator,
    ) {}

    public function isAvailable(): bool
    {
        $settings = $this->settingsService->getSettings();
        $template = trim((string) ($settings['evolution_message_template'] ?? ''));

        if ($template === '' || ! str_contains($template, '{code}')) {
            return false;
        }

        return $this->rotator->poolCount() > 0;
    }

    public function send(string $phone, string $code): void
    {
        $settings = $this->settingsService->getSettings();
        $template = trim((string) ($settings['evolution_message_template'] ?? ''));

        if ($template === '') {
            throw new InvalidArgumentException('لم يُعرّف قالب رسالة OTP لـ Evolution. أدخله من إعدادات OTP.');
        }

        if (! str_contains($template, '{code}')) {
            throw new InvalidArgumentException('قالب رسالة Evolution يجب أن يحتوي على {code}.');
        }

        if ($this->rotator->poolCount() === 0) {
            throw new InvalidArgumentException('لا توجد أرقام Evolution متصلة ومفعّلة لإرسال OTP.');
        }

        $text = str_replace('{code}', $code, $template);
        $this->sendWhatsAppMessage->sendTextSync($phone, $text, false, applySendDelay: false);
    }

    /**
     * @return list<string>
     */
    public function availabilityIssues(): array
    {
        $settings = $this->settingsService->getSettings();
        $issues = [];
        $template = trim((string) ($settings['evolution_message_template'] ?? ''));

        if ($template === '') {
            $issues[] = 'لم يُعرّف قالب رسالة Evolution.';
        } elseif (! str_contains($template, '{code}')) {
            $issues[] = 'قالب Evolution يجب أن يحتوي على {code}.';
        }

        if ($this->rotator->poolCount() === 0) {
            $issues[] = 'لا توجد instances Evolution متصلة ومفعّلة للتبديل.';
        }

        return $issues;
    }
}
