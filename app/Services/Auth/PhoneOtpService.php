<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Models\PhoneOtpCode;
use App\Models\User;
use App\Support\WapiPhoneNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PhoneOtpService
{
    public function __construct(
        private PhoneOtpSettingsService $settingsService,
        private PhoneOtpWhatsAppSender $sender
    ) {}

    public function isAvailableFor(OtpPurpose $purpose): bool
    {
        if (! $this->sender->isAvailable()) {
            return false;
        }

        $settings = $this->settingsService->getSettings();

        return match ($purpose) {
            OtpPurpose::Register => (bool) ($settings['register_enabled'] ?? false),
            OtpPurpose::Login => (bool) ($settings['login_enabled'] ?? false),
            OtpPurpose::ResetPassword => (bool) ($settings['reset_password_enabled'] ?? false),
            OtpPurpose::ChangePhone => (bool) ($settings['change_phone_enabled'] ?? false),
            OtpPurpose::SensitiveAction => true,
        };
    }

    /**
     * @return array{otp_id: int, expires_at: string, cooldown_seconds: int}
     */
    public function send(string $phoneInput, OtpPurpose $purpose, ?User $user = null, ?string $ip = null): array
    {
        if (! $this->isAvailableFor($purpose)) {
            throw new InvalidArgumentException('خدمة OTP عبر الواتساب غير متاحة حالياً.');
        }

        $phone = $this->normalizePhone($phoneInput);
        $settings = $this->settingsService->getSettings();

        $this->assertRateLimit($phone, $ip);
        $this->assertResendCooldown($phone, $purpose);

        $codeLength = max(4, min(8, (int) ($settings['code_length'] ?? 6)));
        $code = $this->generateNumericCode($codeLength);
        $ttl = max(60, (int) ($settings['ttl_seconds'] ?? 300));

        PhoneOtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('verified_at')
            ->delete();

        $otp = PhoneOtpCode::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'user_id' => $user?->id,
            'expires_at' => now()->addSeconds($ttl),
            'ip_address' => $ip,
        ]);

        $this->sender->send($phone, $code);

        $cooldown = max(30, (int) ($settings['resend_cooldown_seconds'] ?? 60));
        Cache::put(
            $this->resendCacheKey($phone, $purpose),
            now()->addSeconds($cooldown)->timestamp,
            now()->addSeconds($cooldown)
        );

        $this->incrementRateLimit($phone, $ip);

        return [
            'otp_id' => $otp->id,
            'expires_at' => $otp->expires_at->toIso8601String(),
            'cooldown_seconds' => (int) ($settings['resend_cooldown_seconds'] ?? 60),
        ];
    }

    public function verify(string $phoneInput, OtpPurpose $purpose, string $code): PhoneOtpCode
    {
        $phone = $this->normalizePhone($phoneInput);
        $settings = $this->settingsService->getSettings();
        $maxAttempts = max(1, (int) ($settings['max_attempts'] ?? 5));

        $otp = PhoneOtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw new InvalidArgumentException('لم يُطلب رمز تحقق لهذا الرقم. أعد طلب الرمز.');
        }

        if ($otp->isExpired()) {
            throw new InvalidArgumentException('انتهت صلاحية الرمز. اطلب رمزاً جديداً.');
        }

        if ($otp->attempts >= $maxAttempts) {
            throw new InvalidArgumentException('تجاوزت الحد الأقصى للمحاولات. اطلب رمزاً جديداً.');
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw new InvalidArgumentException('رمز التحقق غير صحيح.');
        }

        $otp->update(['verified_at' => now()]);

        return $otp->fresh();
    }

    public function markSessionVerified(string $phoneInput, OtpPurpose $purpose): void
    {
        $phone = $this->normalizePhone($phoneInput);
        $minutes = (int) config('phone_otp.recent_verification_minutes', 15);

        session([
            $this->sessionKey($phone, $purpose) => now()->timestamp,
            'phone_otp_verified_phone' => $phone,
            'phone_otp_verified_purpose' => $purpose->value,
        ]);

        session()->put($this->sessionKey($phone, $purpose).'_expires', now()->addMinutes($minutes)->timestamp);
    }

    public function hasRecentVerification(string $phoneInput, OtpPurpose $purpose): bool
    {
        $phone = $this->normalizePhone($phoneInput);
        $expires = session($this->sessionKey($phone, $purpose).'_expires');

        if (! $expires || (int) $expires < now()->timestamp) {
            return false;
        }

        return session($this->sessionKey($phone, $purpose)) !== null;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = WapiPhoneNormalizer::normalize($phone);
        if ($digits === '' || ! WapiPhoneNormalizer::isValidE164Digits($digits)) {
            throw new InvalidArgumentException('رقم الهاتف غير صالح.');
        }

        return $digits;
    }

    public function formatPhoneDisplay(string $countryCode, string $localPhone): string
    {
        $codeDigits = preg_replace('/\D+/', '', $countryCode) ?? '';
        $localDigits = ltrim(preg_replace('/\D+/', '', $localPhone) ?? '', '0');

        return '+'.$codeDigits.$localDigits;
    }

    public function getResendCooldownRemaining(string $phoneInput, OtpPurpose $purpose): int
    {
        try {
            $phone = $this->normalizePhone($phoneInput);
        } catch (InvalidArgumentException) {
            return 0;
        }

        $until = Cache::get($this->resendCacheKey($phone, $purpose));
        if (! is_numeric($until)) {
            return 0;
        }

        return max(0, (int) $until - now()->timestamp);
    }

    private function generateNumericCode(int $length): string
    {
        $max = (10 ** $length) - 1;
        $num = random_int(0, $max);

        return str_pad((string) $num, $length, '0', STR_PAD_LEFT);
    }

    private function assertRateLimit(string $phone, ?string $ip): void
    {
        $settings = $this->settingsService->getSettings();
        $max = max(1, (int) ($settings['rate_limit_max_per_phone'] ?? 3));
        $window = max(1, (int) ($settings['rate_limit_window_minutes'] ?? 15));

        $count = PhoneOtpCode::query()
            ->where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->count();

        if ($count >= $max) {
            throw new InvalidArgumentException('تجاوزت الحد المسموح لطلبات OTP. حاول لاحقاً.');
        }

        if ($ip) {
            $ipCount = PhoneOtpCode::query()
                ->where('ip_address', $ip)
                ->where('created_at', '>=', now()->subMinutes($window))
                ->count();

            if ($ipCount >= $max * 2) {
                throw new InvalidArgumentException('تجاوزت الحد المسموح لطلبات OTP من هذا الجهاز.');
            }
        }
    }

    private function assertResendCooldown(string $phone, OtpPurpose $purpose): void
    {
        if ($this->getResendCooldownRemaining($phone, $purpose) > 0) {
            throw new InvalidArgumentException('يرجى الانتظار قبل إعادة إرسال الرمز.');
        }
    }

    private function incrementRateLimit(string $phone, ?string $ip): void
    {
        // counted via DB rows in assertRateLimit
    }

    private function resendCacheKey(string $phone, OtpPurpose $purpose): string
    {
        return 'phone_otp_resend:'.$purpose->value.':'.$phone;
    }

    private function sessionKey(string $phone, OtpPurpose $purpose): string
    {
        return 'phone_otp_verified:'.$purpose->value.':'.substr(hash('sha256', $phone), 0, 16);
    }
}
