<?php

namespace App\Services\Auth;

use App\Mail\PasswordCredentialsMail;
use App\Models\EmailSetting;
use App\Models\EvolutionInstance;
use App\Models\User;
use App\Services\WhatsApp\Evolution\EvolutionInstanceRotator;
use App\Services\WhatsApp\Evolution\EvolutionWhatsAppNumberResolver;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppDeliveryAcceptance;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Support\InternationalPhoneDigits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordCredentialDeliveryService
{
    public const CONTEXT_FORGOT_AUTO = 'forgot_auto';

    public const CONTEXT_FORGOT_MANUAL = 'forgot_manual';

    public const CONTEXT_ADMIN_RESET = 'admin_reset';

    private const WHATSAPP_MAX_ATTEMPTS = 3;

    public function __construct(
        private PasswordResetMessageRenderer $renderer,
        private WhatsAppSettingsService $whatsappSettings,
        private SendWhatsAppMessage $whatsAppSender,
        private EvolutionInstanceRotator $evolutionRotator,
        private EvolutionWhatsAppNumberResolver $numberResolver,
    ) {}

    public function generateSecurePassword(): string
    {
        return Str::password(16, symbols: true);
    }

    /**
     * @param  bool  $viaEmail  false to skip the email channel entirely
     * @param  bool  $viaWhatsApp  false to skip the WhatsApp channel entirely
     * @return array{
     *     email_sent: bool,
     *     whatsapp_sent: bool,
     *     whatsapp_recipient: ?string,
     *     email_error: ?string,
     *     whatsapp_error: ?string
     * }
     */
    public function deliver(
        User $user,
        #[\SensitiveParameter] string $plainPassword,
        string $context,
        ?string $whatsappRecipientOverride = null,
        bool $requireWhatsApp = false,
        bool $requireEmail = false,
        bool $viaEmail = true,
        bool $viaWhatsApp = true,
    ): array {
        // Both channels default to on, which is how every existing caller behaves. The flags
        // exist for the admin screen, where the operator picks the channels — without them an
        // unticked "email" box was silently ignored and the customer got the mail anyway.
        $viaWhatsApp = $viaWhatsApp || $requireWhatsApp;
        $viaEmail = $viaEmail || $requireEmail;

        $emailSent = false;
        $whatsappSent = false;
        $whatsappRecipient = null;
        $emailError = null;
        $whatsappError = null;

        // Prefer the required channel first so we never email/WA a password that will not be saved.
        if ($requireWhatsApp) {
            ['sent' => $whatsappSent, 'recipient' => $whatsappRecipient, 'error' => $whatsappError]
                = $this->attemptWhatsApp($user, $plainPassword, $context, $whatsappRecipientOverride);

            if (! $whatsappSent) {
                throw new \InvalidArgumentException(
                    $whatsappError ?: 'تعذّر إرسال بيانات الدخول عبر الواتساب. حاول لاحقاً أو استخدم البريد الإلكتروني.'
                );
            }

            if ($viaEmail) {
                ['sent' => $emailSent, 'error' => $emailError] = $this->attemptEmail($user, $plainPassword, $context);
            }
        } elseif ($requireEmail) {
            ['sent' => $emailSent, 'error' => $emailError] = $this->attemptEmail($user, $plainPassword, $context);

            if (! $emailSent) {
                throw new \InvalidArgumentException(
                    $emailError ?: 'تعذّر إرسال بيانات الدخول عبر البريد الإلكتروني. تحقق من إعدادات SMTP أو حاول لاحقاً.'
                );
            }

            if ($viaWhatsApp) {
                ['sent' => $whatsappSent, 'recipient' => $whatsappRecipient, 'error' => $whatsappError]
                    = $this->attemptWhatsApp($user, $plainPassword, $context, $whatsappRecipientOverride);
            }
        } else {
            if ($viaEmail) {
                ['sent' => $emailSent, 'error' => $emailError] = $this->attemptEmail($user, $plainPassword, $context);
            }

            if ($viaWhatsApp) {
                ['sent' => $whatsappSent, 'recipient' => $whatsappRecipient, 'error' => $whatsappError]
                    = $this->attemptWhatsApp($user, $plainPassword, $context, $whatsappRecipientOverride);
            }
        }

        Log::info('Password credentials delivered', [
            'user_id' => $user->id,
            'context' => $context,
            'email_sent' => $emailSent,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_recipient' => $whatsappRecipient,
            'email_error' => $emailError,
            'whatsapp_error' => $whatsappError,
        ]);

        return [
            'email_sent' => $emailSent,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_recipient' => $whatsappRecipient,
            'email_error' => $emailError,
            'whatsapp_error' => $whatsappError,
        ];
    }

    /**
     * One email attempt. Never throws — the caller decides whether a failure is fatal.
     *
     * @return array{sent: bool, error: ?string}
     */
    private function attemptEmail(User $user, #[\SensitiveParameter] string $plainPassword, string $context): array
    {
        try {
            $this->sendEmail($user, $plainPassword);

            return ['sent' => true, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Password credential email failed', [
                'user_id' => $user->id,
                'context' => $context,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return ['sent' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * One WhatsApp attempt (with the retries the sender does internally). Never throws.
     *
     * @return array{sent: bool, recipient: ?string, error: ?string}
     */
    private function attemptWhatsApp(
        User $user,
        #[\SensitiveParameter] string $plainPassword,
        string $context,
        ?string $whatsappRecipientOverride,
    ): array {
        try {
            return $this->sendWhatsAppWithRetries($user, $plainPassword, $whatsappRecipientOverride);
        } catch (\Throwable $e) {
            Log::error('Password credential WhatsApp failed', [
                'user_id' => $user->id,
                'context' => $context,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return ['sent' => false, 'recipient' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{sent: bool, recipient: ?string, error: ?string}
     */
    private function sendWhatsAppWithRetries(
        User $user,
        #[\SensitiveParameter] string $plainPassword,
        ?string $whatsappRecipientOverride = null,
    ): array {
        $recipient = $whatsappRecipientOverride ?? $this->resolveWhatsAppRecipient($user);

        if ($recipient === null) {
            return [
                'sent' => false,
                'recipient' => null,
                'error' => 'لا يوجد رقم واتساب مسجّل لهذا الحساب.',
            ];
        }

        if (! $this->isWhatsAppAvailable()) {
            return [
                'sent' => false,
                'recipient' => $recipient,
                'error' => 'خدمة الواتساب غير مفعّلة حالياً.',
            ];
        }

        $provider = $this->whatsappSettings->getSettings()['whatsapp_provider'] ?? '';
        $sendTo = $recipient;

        if ($provider === 'evolution') {
            $resolved = $this->numberResolver->resolve($recipient);

            if ($resolved['checked'] && $resolved['exists'] === false) {
                return [
                    'sent' => false,
                    'recipient' => $recipient,
                    'error' => 'الرقم غير مسجّل على واتساب: '.$recipient,
                ];
            }

            if ($resolved['digits'] !== '') {
                $sendTo = InternationalPhoneDigits::toDisplay($resolved['digits']);
                $recipient = $sendTo;
            }
        }

        $messageBody = $this->renderer->renderCredentialWhatsApp($user, $plainPassword);
        $lastError = null;

        for ($attempt = 1; $attempt <= self::WHATSAPP_MAX_ATTEMPTS; $attempt++) {
            try {
                $sentMessage = $this->whatsAppSender->sendTextSync(
                    $sendTo,
                    $messageBody,
                    previewUrl: false,
                    applySendDelay: false,
                );

                if (WhatsAppDeliveryAcceptance::isAccepted($sentMessage)) {
                    return [
                        'sent' => true,
                        'recipient' => $recipient,
                        'error' => null,
                    ];
                }

                $lastError = WhatsAppDeliveryAcceptance::rejectionReason($sentMessage);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            Log::warning('Password credential WhatsApp attempt failed', [
                'user_id' => $user->id,
                'recipient' => $recipient,
                'attempt' => $attempt,
                'error' => $lastError,
            ]);

            if ($attempt < self::WHATSAPP_MAX_ATTEMPTS) {
                usleep(400_000 * $attempt);
            }
        }

        return [
            'sent' => false,
            'recipient' => $recipient,
            'error' => $lastError ?: 'فشل إرسال رسالة الواتساب بعد عدة محاولات.',
        ];
    }

    private function sendEmail(User $user, #[\SensitiveParameter] string $plainPassword): void
    {
        if (empty($user->email)) {
            throw new \InvalidArgumentException('لا يوجد بريد إلكتروني مسجّل لهذا الحساب.');
        }

        $this->applyActiveMailSettings();

        Mail::to($user->email)->send(new PasswordCredentialsMail($user, $plainPassword));
    }

    private function applyActiveMailSettings(): void
    {
        $setting = EmailSetting::getActive();

        if ($setting) {
            $setting->applyToConfig();
        }
    }

    public function isWhatsAppAvailable(): bool
    {
        $settings = $this->whatsappSettings->getSettings();

        if (! ($settings['whatsapp_enabled'] ?? false)) {
            return false;
        }

        $provider = $settings['whatsapp_provider'] ?? '';

        if ($provider !== 'evolution') {
            return false;
        }

        return $this->isEvolutionReady($settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function isEvolutionReady(array $settings): bool
    {
        $baseUrl = trim((string) ($settings['evolution_base_url'] ?? ''));
        $apiKey = trim((string) ($settings['evolution_api_key'] ?? ''));

        if ($baseUrl === '' || $apiKey === '') {
            return false;
        }

        if ($this->evolutionRotator->poolCount() > 0) {
            return true;
        }

        $configuredInstance = trim((string) ($settings['evolution_instance_name'] ?? ''));
        if ($configuredInstance !== '') {
            return true;
        }

        return EvolutionInstance::defaultInstance() !== null;
    }

    private function resolveWhatsAppRecipient(User $user): ?string
    {
        $digits = InternationalPhoneDigits::forUser($user);

        return $digits !== null ? InternationalPhoneDigits::toDisplay($digits) : null;
    }
}
