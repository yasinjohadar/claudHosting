<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\EmailSetting;
use App\Models\User;
use App\Support\InternationalPhoneDigits;
use App\Support\UserPhoneCountryValidator;
use App\Support\WapiPhoneNormalizer;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;

class PasswordResetDeliveryService
{
    public function __construct(
        private PasswordCredentialDeliveryService $credentialDelivery,
    ) {}

    public function isWhatsAppAvailable(): bool
    {
        return $this->credentialDelivery->isWhatsAppAvailable();
    }

    /**
     * @return array{status: string, delivery: array{email_sent: bool, whatsapp_sent: bool, whatsapp_recipient: ?string}}
     */
    public function sendViaEmail(string $email): array
    {
        $this->applyActiveMailSettings();

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            return [
                'status' => Password::INVALID_USER,
                'delivery' => $this->emptyDelivery(),
            ];
        }

        return $this->assignPasswordAndDeliver($user, requireEmail: true);
    }

    /**
     * @return array{status: string, delivery: array{email_sent: bool, whatsapp_sent: bool, whatsapp_recipient: ?string}}
     */
    public function sendViaWhatsAppParts(string $countryCode, string $localPhone): array
    {
        return $this->sendViaWhatsAppDigits(
            self::buildFullPhoneDigits($countryCode, $localPhone),
            self::formatPhoneForDisplay($countryCode, $localPhone)
        );
    }

    public static function buildFullPhoneDigits(string $countryCode, string $localPhone): string
    {
        $digits = InternationalPhoneDigits::fromCountryAndLocal($countryCode, $localPhone);

        if ($digits === null) {
            throw new InvalidArgumentException('رمز الدولة ورقم الجوال مطلوبان.');
        }

        return $digits;
    }

    public function formatPhoneForDisplay(string $countryCode, string $localPhone): string
    {
        $digits = self::buildFullPhoneDigits($countryCode, $localPhone);

        return '+'.$digits;
    }

    public function sendViaWhatsApp(string $phoneInput): array
    {
        $digits = WapiPhoneNormalizer::normalize($phoneInput);

        return $this->sendViaWhatsAppDigits($digits, '+'.$digits);
    }

    /**
     * @return array{status: string, delivery: array{email_sent: bool, whatsapp_sent: bool, whatsapp_recipient: ?string}}
     */
    private function sendViaWhatsAppDigits(string $digits, string $displayRecipient): array
    {
        if ($digits === '' || ! WapiPhoneNormalizer::isValidE164Digits($digits)) {
            throw new InvalidArgumentException('رقم الجوال غير صالح.');
        }

        if (! $this->isWhatsAppAvailable()) {
            throw new InvalidArgumentException('خدمة الواتساب غير مفعّلة حالياً.');
        }

        $user = $this->findUserByPhone($digits);
        if (! $user) {
            return [
                'status' => Password::INVALID_USER,
                'delivery' => $this->emptyDelivery(),
            ];
        }

        if (! UserPhoneCountryValidator::isConsistent($user)) {
            $canonical = InternationalPhoneDigits::forUser($user);
            if ($canonical === null) {
                throw new InvalidArgumentException('رقم هاتف الحساب غير مكتمل أو غير صالح. تواصل مع الدعم.');
            }
        }

        return $this->assignPasswordAndDeliver($user, $displayRecipient, requireWhatsApp: true);
    }

    /**
     * @return array{status: string, delivery: array{email_sent: bool, whatsapp_sent: bool, whatsapp_recipient: ?string, email_error: ?string, whatsapp_error: ?string}}
     */
    private function assignPasswordAndDeliver(
        User $user,
        ?string $whatsappRecipientOverride = null,
        bool $requireWhatsApp = false,
        bool $requireEmail = false,
    ): array {
        /** @var PasswordBroker $broker */
        $broker = Password::broker();

        if ($broker->getRepository()->recentlyCreatedToken($user)) {
            return [
                'status' => Password::RESET_THROTTLED,
                'delivery' => $this->emptyDelivery(),
            ];
        }

        $plainPassword = $this->credentialDelivery->generateSecurePassword();

        $delivery = $this->credentialDelivery->deliver(
            $user,
            $plainPassword,
            PasswordCredentialDeliveryService::CONTEXT_FORGOT_AUTO,
            $whatsappRecipientOverride,
            requireWhatsApp: $requireWhatsApp,
            requireEmail: $requireEmail,
        );

        if (! $delivery['email_sent'] && ! $delivery['whatsapp_sent']) {
            $detail = $delivery['whatsapp_error'] ?: $delivery['email_error'] ?: '';
            $message = 'تعذّر إرسال بيانات الدخول عبر البريد والواتساب. تحقق من إعدادات الإرسال أو تواصل مع الدعم.';
            if ($detail !== '') {
                $message .= ' ('.$detail.')';
            }

            throw new InvalidArgumentException($message);
        }

        // Only mutate password after at least one channel (or the required channel) succeeded.
        $user->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        $broker->createToken($user);

        return [
            'status' => Password::RESET_LINK_SENT,
            'delivery' => $delivery,
        ];
    }

    public function findUserByPhone(string $phoneInput): ?User
    {
        $digits = InternationalPhoneDigits::fromInput($phoneInput);
        if ($digits === null) {
            return null;
        }

        $users = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->get();

        foreach ($users as $user) {
            $userDigits = InternationalPhoneDigits::fromInput((string) $user->phone);

            if ($userDigits === null || strlen($userDigits) < 8) {
                continue;
            }

            if ($userDigits === $digits) {
                return $user;
            }
        }

        $customers = Customer::query()
            ->whereNotNull('phonenumber')
            ->where('phonenumber', '!=', '')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        foreach ($customers as $customer) {
            $customerDigits = InternationalPhoneDigits::forCustomer($customer);

            if ($customerDigits === null || strlen($customerDigits) < 8) {
                continue;
            }

            if ($customerDigits === $digits) {
                return $customer->user;
            }
        }

        return null;
    }

    public function buildResetUrl(User $user, string $token): string
    {
        return url(route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false));
    }

    public function applyActiveMailSettings(): void
    {
        $setting = EmailSetting::getActive();

        if ($setting) {
            $setting->applyToConfig();
        }
    }

    public function resolveWhatsAppRecipient(User $user): ?string
    {
        $digits = InternationalPhoneDigits::forUser($user);

        return $digits !== null ? InternationalPhoneDigits::toDisplay($digits) : null;
    }

    public static function buildSuccessMessage(array $delivery): string
    {
        $emailSent = (bool) ($delivery['email_sent'] ?? false);
        $whatsappSent = (bool) ($delivery['whatsapp_sent'] ?? false);
        $whatsappRecipient = (string) ($delivery['whatsapp_recipient'] ?? '');

        if ($emailSent && $whatsappSent) {
            $message = 'تم إرسال بيانات الدخول الجديدة إلى بريدك الإلكتروني وواتسابك';
            if ($whatsappRecipient !== '') {
                $message .= ' ('.$whatsappRecipient.')';
            }

            return $message.'.';
        }

        if ($emailSent) {
            $extra = '';
            $waError = trim((string) ($delivery['whatsapp_error'] ?? ''));
            if ($waError !== '') {
                $extra = ' السبب: '.$waError;
            }

            return 'تم إرسال بيانات الدخول إلى بريدك الإلكتروني. تعذّر الإرسال عبر الواتساب.'.$extra;
        }

        if ($whatsappSent) {
            $message = 'تم إرسال بيانات الدخول عبر الواتساب';
            if ($whatsappRecipient !== '') {
                $message .= ' إلى '.$whatsappRecipient;
            }

            $emailError = trim((string) ($delivery['email_error'] ?? ''));
            $message .= '. تعذّر الإرسال عبر البريد الإلكتروني.';
            if ($emailError !== '') {
                $message .= ' السبب: '.$emailError;
            }

            return $message;
        }

        return 'تعذّر إرسال بيانات الدخول.';
    }

    /**
     * @return array{email_sent: bool, whatsapp_sent: bool, whatsapp_recipient: ?string, email_error: ?string, whatsapp_error: ?string}
     */
    private function emptyDelivery(): array
    {
        return [
            'email_sent' => false,
            'whatsapp_sent' => false,
            'whatsapp_recipient' => null,
            'email_error' => null,
            'whatsapp_error' => null,
        ];
    }
}
