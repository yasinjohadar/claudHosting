<?php

namespace Tests\Unit;

use App\Models\EmailSetting;
use Tests\TestCase;

class EmailSettingTest extends TestCase
{
    public function test_apply_to_config_sets_smtp_values(): void
    {
        $setting = new EmailSetting([
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_username' => 'mailer-user',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'no-reply@example.com',
            'mail_from_name' => 'Example',
        ]);

        $setting->forceFill(['mail_password' => 'secret-pass']);

        $setting->applyToConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
    }

    public function test_resolve_mail_scheme_for_ssl_port(): void
    {
        $this->assertSame('smtps', EmailSetting::resolveMailScheme(465, 'ssl'));
        $this->assertSame('smtp', EmailSetting::resolveMailScheme(587, 'tls'));
    }
}
