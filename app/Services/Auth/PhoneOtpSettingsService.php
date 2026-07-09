<?php

namespace App\Services\Auth;

use App\Models\SystemSetting;

class PhoneOtpSettingsService
{
    public const GROUP = 'phone_otp';

    public function initializeDefaults(): void
    {
        foreach ($this->defaultSettings() as $key => $meta) {
            if (! SystemSetting::byKey($key)->ofGroup(self::GROUP)->exists()) {
                SystemSetting::set($key, $meta['value'], $meta['type'], self::GROUP, $meta['description'] ?? null);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $this->initializeDefaults();

        $stored = SystemSetting::where('group', self::GROUP)
            ->get()
            ->keyBy('key')
            ->map(fn ($row) => $row->value)
            ->toArray();

        $defaults = collect($this->defaultSettings())->mapWithKeys(
            fn ($meta, $key) => [$key => $meta['value']]
        )->all();

        $merged = array_merge($defaults, $stored);

        foreach (['enabled', 'register_enabled', 'login_enabled', 'reset_password_enabled', 'change_phone_enabled'] as $boolKey) {
            $merged[$boolKey] = filter_var($merged[$boolKey] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $merged['wapi_template_id'] = $merged['wapi_template_id'] !== '' && $merged['wapi_template_id'] !== null
            ? (int) $merged['wapi_template_id']
            : null;
        $merged['ttl_seconds'] = (int) ($merged['ttl_seconds'] ?? config('phone_otp.ttl_seconds', 300));
        $merged['max_attempts'] = (int) ($merged['max_attempts'] ?? config('phone_otp.max_attempts', 5));
        $merged['resend_cooldown_seconds'] = (int) ($merged['resend_cooldown_seconds'] ?? config('phone_otp.resend_cooldown_seconds', 60));
        $merged['code_length'] = (int) ($merged['code_length'] ?? config('phone_otp.code_length', 6));
        $merged['rate_limit_max_per_phone'] = (int) ($merged['rate_limit_max_per_phone'] ?? config('phone_otp.rate_limit.max_per_phone', 3));
        $merged['rate_limit_window_minutes'] = (int) ($merged['rate_limit_window_minutes'] ?? config('phone_otp.rate_limit.window_minutes', 15));

        $channel = strtolower(trim((string) ($merged['delivery_channel'] ?? 'evolution')));
        $merged['delivery_channel'] = $channel === 'evolution' ? 'evolution' : 'evolution';
        $merged['evolution_message_template'] = (string) ($merged['evolution_message_template'] ?? 'رمز التحقق الخاص بك هو: {code}');

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateSettings(array $settings): void
    {
        $allowed = array_keys($this->defaultSettings());

        foreach ($settings as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            if (in_array($key, ['enabled', 'register_enabled', 'login_enabled', 'reset_password_enabled', 'change_phone_enabled'], true)) {
                $value = $value ? '1' : '0';
            }

            if ($key === 'wapi_template_id' && ($value === '' || $value === null)) {
                $value = '';
            }

            SystemSetting::set(
                $key,
                $value,
                $this->defaultSettings()[$key]['type'],
                self::GROUP,
                $this->defaultSettings()[$key]['description'] ?? null
            );
        }
    }

    /**
     * @return array<string, array{value: string, type: string, description?: string}>
     */
    public function defaultSettings(): array
    {
        return [
            'enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'تفعيل OTP عبر واتساب Evolution',
            ],
            'wapi_template_id' => [
                'value' => '',
                'type' => 'string',
                'description' => 'قالب Flaxxa لرسالة OTP',
            ],
            'template_language' => [
                'value' => 'ar',
                'type' => 'string',
                'description' => 'لغة القالب (ar / en_US)',
            ],
            'ttl_seconds' => [
                'value' => (string) config('phone_otp.ttl_seconds', 300),
                'type' => 'string',
                'description' => 'مدة صلاحية الرمز بالثواني',
            ],
            'max_attempts' => [
                'value' => (string) config('phone_otp.max_attempts', 5),
                'type' => 'string',
                'description' => 'أقصى محاولات تحقق',
            ],
            'resend_cooldown_seconds' => [
                'value' => (string) config('phone_otp.resend_cooldown_seconds', 60),
                'type' => 'string',
                'description' => 'فترة الانتظار بين إعادة الإرسال',
            ],
            'code_length' => [
                'value' => (string) config('phone_otp.code_length', 6),
                'type' => 'string',
                'description' => 'طول رمز OTP',
            ],
            'rate_limit_max_per_phone' => [
                'value' => (string) config('phone_otp.rate_limit.max_per_phone', 3),
                'type' => 'string',
                'description' => 'أقصى طلبات OTP لكل رقم خلال النافذة الزمنية',
            ],
            'rate_limit_window_minutes' => [
                'value' => (string) config('phone_otp.rate_limit.window_minutes', 15),
                'type' => 'string',
                'description' => 'نافذة حد المعدل بالدقائق',
            ],
            'register_enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'OTP عند التسجيل',
            ],
            'login_enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'تسجيل الدخول برمز OTP',
            ],
            'reset_password_enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'استعادة كلمة المرور عبر OTP',
            ],
            'change_phone_enabled' => [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'تغيير رقم الهاتف عبر OTP',
            ],
            'delivery_channel' => [
                'value' => 'evolution',
                'type' => 'string',
                'description' => 'قناة إرسال OTP: evolution',
            ],
            'evolution_message_template' => [
                'value' => 'رمز التحقق الخاص بك هو: {code}',
                'type' => 'string',
                'description' => 'قالب رسالة OTP عند استخدام Evolution',
            ],
        ];
    }

    public function isEnabled(): bool
    {
        $settings = $this->getSettings();

        if (! ($settings['enabled'] ?? false)) {
            return false;
        }

        if (config('phone_otp.enabled', true) === false) {
            return false;
        }

        return true;
    }

    public function restoreDefaults(): void
    {
        $payload = [];
        foreach ($this->defaultSettings() as $key => $meta) {
            $payload[$key] = $meta['value'];
        }
        $payload['enabled'] = true;
        $payload['register_enabled'] = true;
        $payload['login_enabled'] = true;
        $payload['reset_password_enabled'] = true;
        $payload['change_phone_enabled'] = true;

        $this->updateSettings($payload);
    }
}
