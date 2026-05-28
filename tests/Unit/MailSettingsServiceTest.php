<?php

namespace Tests\Unit;

use App\Services\Mail\MailSettingsService;
use Tests\TestCase;

class MailSettingsServiceTest extends TestCase
{
    public function test_apply_runtime_config_uses_smtp_values(): void
    {
        $service = new class extends MailSettingsService
        {
            public function getSettings(): array
            {
                return [
                    'mail_enabled' => true,
                    'mailer' => 'smtp',
                    'host' => 'smtp.example.com',
                    'port' => 587,
                    'username' => 'mailer-user',
                    'password' => 'secret-pass',
                    'encryption' => 'tls',
                    'from_address' => 'no-reply@example.com',
                    'from_name' => 'Example',
                ];
            }
        };

        $service->applyRuntimeConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
    }

    public function test_apply_runtime_config_switches_to_log_when_disabled(): void
    {
        $service = new class extends MailSettingsService
        {
            public function getSettings(): array
            {
                return [
                    'mail_enabled' => false,
                ];
            }
        };

        $service->applyRuntimeConfig();

        $this->assertSame('log', config('mail.default'));
    }
}
