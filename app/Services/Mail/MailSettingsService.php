<?php

namespace App\Services\Mail;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;

class MailSettingsService
{
    public function getSettings(): array
    {
        if (! Schema::hasTable('system_settings')) {
            return [
                'mail_enabled' => true,
                'mailer' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
                'port' => (int) config('mail.mailers.smtp.port', 2525),
                'username' => (string) config('mail.mailers.smtp.username', ''),
                'password' => (string) config('mail.mailers.smtp.password', ''),
                'encryption' => (string) config('mail.mailers.smtp.scheme', ''),
                'from_address' => (string) config('mail.from.address', 'hello@example.com'),
                'from_name' => (string) config('mail.from.name', config('app.name', 'Laravel')),
            ];
        }

        $settings = SystemSetting::query()
            ->where('group', 'mail')
            ->get()
            ->keyBy('key')
            ->map(fn ($row) => $row->value)
            ->toArray();

        return [
            'mail_enabled' => filter_var($settings['mail_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'mailer' => $settings['mailer'] ?? 'smtp',
            'host' => $settings['host'] ?? '127.0.0.1',
            'port' => (int) ($settings['port'] ?? 2525),
            'username' => $settings['username'] ?? '',
            'password' => $settings['password'] ?? '',
            'encryption' => $settings['encryption'] ?? 'tls',
            'from_address' => $settings['from_address'] ?? 'hello@example.com',
            'from_name' => $settings['from_name'] ?? config('app.name', 'Laravel'),
        ];
    }

    public function updateSettings(array $settings): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        foreach ($settings as $key => $value) {
            SystemSetting::set(
                key: $key,
                value: is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                type: 'string',
                group: 'mail'
            );
        }
    }

    public function initializeDefaults(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $defaults = [
            'mail_enabled' => '1',
            'mailer' => 'smtp',
            'host' => '127.0.0.1',
            'port' => '2525',
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => 'hello@example.com',
            'from_name' => config('app.name', 'Laravel'),
        ];

        foreach ($defaults as $key => $value) {
            if (! SystemSetting::query()->where('group', 'mail')->where('key', $key)->exists()) {
                SystemSetting::set($key, $value, 'string', 'mail');
            }
        }
    }

    public function applyRuntimeConfig(): void
    {
        $settings = $this->getSettings();
        if (! $settings['mail_enabled']) {
            config([
                'mail.default' => 'log',
            ]);

            return;
        }

        config([
            'mail.default' => $settings['mailer'] ?: 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings['host'],
            'mail.mailers.smtp.port' => $settings['port'],
            'mail.mailers.smtp.username' => $settings['username'] ?: null,
            'mail.mailers.smtp.password' => $settings['password'] ?: null,
            'mail.mailers.smtp.scheme' => $settings['encryption'] ?: null,
            'mail.from.address' => $settings['from_address'],
            'mail.from.name' => $settings['from_name'],
        ]);
    }
}
