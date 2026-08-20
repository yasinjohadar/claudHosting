<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PasswordResetMessageRenderer
{
    public function __construct(
        private PasswordResetMessageSettingsService $settingsService
    ) {}

    /**
     * @return array<string, string>
     */
    public function credentialVariables(User $user, #[\SensitiveParameter] string $plainPassword): array
    {
        $settings = $this->settingsService->getSettings();
        $customer = $user->customer;
        $nameAr = trim((string) ($user->name ?? ''));
        if ($customer) {
            $customerName = trim((string) ($customer->fullname ?: trim($customer->firstname.' '.$customer->lastname)));
            if ($customerName !== '') {
                $nameAr = $customerName;
            }
        }
        if ($nameAr === '') {
            $nameAr = 'عزيزي العميل';
        }
        $nameEn = $nameAr;
        $appName = (string) (config('app.name') ?: 'كلاودسوفت');
        $loginUrl = $this->resolveLoginUrl();
        $email = (string) ($customer?->email ?: $user->email ?? '');
        $phoneDigits = \App\Support\InternationalPhoneDigits::forUser($user);
        $phone = $phoneDigits ? \App\Support\InternationalPhoneDigits::toDisplay($phoneDigits) : '';

        return [
            'customer_name' => $nameAr,
            'customer_name_ar' => $nameAr,
            'customer_name_en' => $nameEn,
            'customer_email' => $email,
            'company_name' => trim((string) ($customer?->companyname ?? '')),
            'phone' => $phone,
            'customer_phone' => $phone,
            'customer_city' => trim((string) ($customer?->city ?? '')),
            'customer_country' => trim((string) ($customer?->country ?? '')),
            'student_name_ar' => $nameAr,
            'student_name_en' => $nameEn,
            'student_name' => $nameAr,
            'user_name' => $nameAr,
            'email' => $email,
            'password' => $plainPassword,
            'new_password' => $plainPassword,
            'login_url' => $loginUrl,
            'admin_instructions' => trim((string) ($settings['admin_instructions'] ?? '')),
            'app_name' => $appName,
        ];
    }

    /**
     * متغيرات رسائل بيانات الدخول (بدون صلاحية رابط — كلمة المرور تُعيَّن فوراً).
     *
     * @return array<string, string>
     */
    public function credentialMessageVariables(User $user, #[\SensitiveParameter] string $plainPassword): array
    {
        $loginUrl = $this->resolveLoginUrl();

        return array_merge($this->credentialVariables($user, $plainPassword), [
            'reset_url' => $loginUrl,
            'reset_link' => $loginUrl,
            'expire_minutes' => '',
            'expire_at' => '',
            'expire_at_date' => '',
            'expire_at_time' => '',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function variables(User $user, string $resetUrl, int $expireMinutes): array
    {
        $expiresAt = Carbon::now()->addMinutes($expireMinutes);
        $userName = $user->customer?->fullname ?: ($user->name ?? 'عزيزي العميل');
        $appName = (string) (config('app.name') ?: 'كلاودسوفت');

        return array_merge($this->credentialVariables($user, ''), [
            'reset_url' => $resetUrl,
            'reset_link' => $resetUrl,
            'expire_minutes' => (string) $expireMinutes,
            'expire_at' => $expiresAt->format('Y-m-d H:i'),
            'expire_at_date' => $expiresAt->format('Y-m-d'),
            'expire_at_time' => $expiresAt->format('H:i'),
            'app_name' => $appName,
            'email' => (string) ($user->email ?? ''),
            'user_name' => $userName,
            'student_name' => $userName,
            'password' => '',
            'new_password' => '',
        ]);
    }

    public function renderCredentialWhatsApp(User $user, #[\SensitiveParameter] string $plainPassword): string
    {
        $settings = $this->settingsService->getSettings();
        $variables = $this->credentialMessageVariables($user, $plainPassword);

        // A managed template dedicated to credentials wins: it is the one the admin edits from
        // the templates screen, and it keeps this wording out of the password-reset settings
        // where it was mixed in with the reset-link message.
        $managed = $this->managedCredentialTemplate();
        if ($managed !== null) {
            $rendered = trim($managed->render($variables, ['user' => $user]));
            if ($rendered !== '') {
                return $rendered;
            }
        }

        if (! empty($settings['whatsapp_template_id'])) {
            $template = WhatsAppMessageTemplate::active()
                ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
                ->find($settings['whatsapp_template_id']);

            if ($template) {
                return $template->render($variables);
            }
        }

        $body = trim((string) ($settings['whatsapp_body'] ?? ''));
        if ($body === '') {
            $body = self::defaultWhatsAppBody();
        } else {
            $body = WhatsAppMessageTemplate::normalizeBodyForSending($body);
        }

        return $this->renderTemplate($body, $variables, forWhatsApp: true);
    }

    public function renderWhatsApp(User $user, string $resetUrl, int $expireMinutes): string
    {
        $settings = $this->settingsService->getSettings();
        $variables = $this->variables($user, $resetUrl, $expireMinutes);

        if (! empty($settings['whatsapp_template_id'])) {
            $template = WhatsAppMessageTemplate::active()
                ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
                ->find($settings['whatsapp_template_id']);

            if ($template) {
                return $template->render($variables);
            }
        }

        $body = trim((string) ($settings['whatsapp_body'] ?? ''));
        if ($body === '') {
            $body = self::defaultWhatsAppBody();
        }

        return $this->renderTemplate($body, $variables, forWhatsApp: true);
    }

    public function renderEmailSubject(): string
    {
        $settings = $this->settingsService->getSettings();
        $subject = trim((string) ($settings['email_subject'] ?? ''));

        return $subject !== '' ? $subject : 'بيانات الدخول - كلاودسوفت';
    }

    public function renderCredentialEmailBodyHtml(User $user, #[\SensitiveParameter] string $plainPassword): string
    {
        $settings = $this->settingsService->getSettings();
        $variables = $this->credentialMessageVariables($user, $plainPassword);

        $body = trim((string) ($settings['email_body'] ?? ''));
        if ($body === '') {
            $body = self::defaultEmailBody();
        }

        return $this->renderTemplate($body, $variables, forWhatsApp: false);
    }

    public function renderEmailBodyHtml(User $user, string $resetUrl, int $expireMinutes): string
    {
        $settings = $this->settingsService->getSettings();
        $variables = $this->variables($user, $resetUrl, $expireMinutes);

        $body = trim((string) ($settings['email_body'] ?? ''));
        if ($body === '') {
            $body = self::defaultEmailBody();
        }

        return $this->renderTemplate($body, $variables, forWhatsApp: false);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderTemplate(string $template, array $variables, bool $forWhatsApp): string
    {
        $output = $template;
        foreach ($variables as $key => $value) {
            $patterns = [
                '{{'.$key.'}}',
                '{'.$key.'}',
                '{{ '.$key.' }}',
                '{ '.$key.' }',
            ];
            $output = str_replace($patterns, $value, $output);
        }

        if ($forWhatsApp) {
            return WhatsAppMessageTemplate::normalizeBodyForSending($output);
        }

        return $output;
    }

    private function resolveLoginUrl(): string
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($this->isLocalAppUrl($appUrl)) {
            $waSettings = app(WhatsAppSettingsService::class)->getSettings();
            $publicBase = rtrim((string) ($waSettings['evolution_webhook_base_url'] ?? ''), '/');

            if ($publicBase !== '' && ! $this->isLocalAppUrl($publicBase)) {
                return $publicBase.route('login', [], false);
            }
        }

        return url(route('login'));
    }

    private function isLocalAppUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    public static function defaultWhatsAppBody(): string
    {
        return <<<'TEXT'
مرحباً {customer_name} 👋

تم تعيين كلمة مرور جديدة لحسابك في {app_name}.

👤 الاسم: {customer_name}
🏢 الشركة: {company_name}
📧 البريد: {email}
📱 الجوال: {phone}
🔑 كلمة المرور: {password}
🔗 رابط الدخول: {login_url}

{admin_instructions}
TEXT;
    }

    public static function defaultEmailBody(): string
    {
        return <<<'HTML'
<p class="greeting" style="font-size:20px;font-weight:700;color:#0057B8;margin-bottom:20px;">مرحباً {customer_name}! 👋</p>
<p style="margin-bottom:15px;font-size:16px;color:#555555;">تم تعيين كلمة مرور جديدة لحسابك في {app_name}. فيما يلي بيانات الدخول:</p>
<div style="background-color:#f8f9fa;border-right:4px solid #0057B8;padding:20px;margin:20px 0;border-radius:5px;">
    <p style="margin-bottom:10px;font-size:16px;color:#333333;"><strong>الاسم:</strong> {customer_name}</p>
    <p style="margin-bottom:10px;font-size:16px;color:#333333;"><strong>الشركة:</strong> {company_name}</p>
    <p style="margin-bottom:10px;font-size:16px;color:#333333;"><strong>البريد الإلكتروني:</strong> {email}</p>
    <p style="margin-bottom:10px;font-size:16px;color:#333333;"><strong>الجوال:</strong> {phone}</p>
    <p style="margin-bottom:10px;font-size:16px;color:#333333;"><strong>كلمة المرور:</strong> {password}</p>
</div>
<p style="text-align:center;margin:30px 0;">
    <a href="{login_url}" style="display:inline-block;padding:15px 40px;background-color:#0057B8;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;font-size:16px;">تسجيل الدخول</a>
</p>
<div style="background-color:#e7f3ff;border-right:4px solid #0057B8;padding:15px;margin:20px 0;border-radius:5px;color:#004085;">
    <strong>📋 إرشادات:</strong>
    <p style="margin-top:10px;margin-bottom:0;">{admin_instructions}</p>
</div>
HTML;
    }

    /**
     * The managed credentials template, or null.
     *
     * Guarded because this runs while an admin is saving a password: a missing table on a
     * not-yet-migrated install must fall back to the previous wording, not fail the save.
     */
    private function managedCredentialTemplate(): ?WhatsAppMessageTemplate
    {
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return null;
        }

        try {
            return WhatsAppMessageTemplate::findBySlug(WhatsAppMessageTemplate::SLUG_CREDENTIALS);
        } catch (\Throwable) {
            return null;
        }
    }
}
