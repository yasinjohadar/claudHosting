<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\Evolution\EvolutionInstanceRotator;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppTemplateRenderer;
use Illuminate\Support\Facades\Schema;
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
        $template = $this->resolveTemplateBody();

        if ($template === '' || ! str_contains($template, '{code}')) {
            return false;
        }

        return $this->rotator->poolCount() > 0;
    }

    public function send(string $phone, string $code, ?User $user = null): void
    {
        $template = $this->resolveTemplateBody();

        if ($template === '') {
            throw new InvalidArgumentException('لم يُعرّف قالب رسالة OTP لـ Evolution. أدخله من إعدادات OTP أو من صفحة قوالب الواتساب.');
        }

        // Kept as a hard guard, not a warning: a message with no code in it is worse than no
        // message at all, because the user waits for something that will never be readable.
        if (! str_contains($template, '{code}')) {
            throw new InvalidArgumentException('قالب رسالة Evolution يجب أن يحتوي على {code}.');
        }

        if ($this->rotator->poolCount() === 0) {
            throw new InvalidArgumentException('لا توجد أرقام Evolution متصلة ومفعّلة لإرسال OTP.');
        }

        $text = app(WhatsAppTemplateRenderer::class)->renderText(
            $template,
            $user !== null ? ['user' => $user] : [],
            ['code' => $code],
            'otp',
        );

        $this->sendWhatsAppMessage->sendTextSync($phone, $text, false, applySendDelay: false);
    }

    /**
     * @return list<string>
     */
    public function availabilityIssues(): array
    {
        $issues = [];
        $template = $this->resolveTemplateBody();

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

    /** Is the body coming from the managed template rather than the settings field? */
    public function usesManagedTemplate(): bool
    {
        return $this->managedTemplate() !== null;
    }

    /**
     * The OTP body: the managed template when one is active, otherwise the settings field.
     *
     * One resolver for all three methods, so the health report and the availability check can
     * never disagree with what send() will actually use.
     */
    private function resolveTemplateBody(): string
    {
        $managed = $this->managedTemplate();
        if ($managed !== null) {
            return trim((string) $managed->body);
        }

        return trim((string) ($this->settingsService->getSettings()['evolution_message_template'] ?? ''));
    }

    private function managedTemplate(): ?WhatsAppMessageTemplate
    {
        // Missing table on a not-yet-migrated install must fall back, not break login.
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return null;
        }

        try {
            return WhatsAppMessageTemplate::findBySlug(WhatsAppMessageTemplate::SLUG_OTP);
        } catch (\Throwable) {
            return null;
        }
    }
}
