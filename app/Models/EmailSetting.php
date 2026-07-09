<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmailSetting extends Model
{
    protected $fillable = [
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'is_active',
        'provider',
        'test_results',
        'last_tested_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'test_results' => 'array',
        'last_tested_at' => 'datetime',
        'mail_port' => 'integer',
    ];

    public function setMailPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['mail_password'] = Crypt::encryptString($value);
        }
    }

    public function getMailPasswordAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function activate(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        $this->applyToConfig();
    }

    /**
     * @return array{host: string, port: int, encryption: string, username: string, password: string}
     */
    public function toConnectionConfig(): array
    {
        return [
            'host' => (string) $this->mail_host,
            'port' => (int) $this->mail_port,
            'encryption' => (string) $this->mail_encryption,
            'username' => (string) $this->mail_username,
            'password' => (string) $this->mail_password,
        ];
    }

    public static function resolveMailScheme(int $port, string $encryption): string
    {
        $encryption = strtolower($encryption);

        if ($port === 587) {
            return 'smtp';
        }

        if ($port === 465) {
            return 'smtps';
        }

        return $encryption === 'ssl' ? 'smtps' : 'smtp';
    }

    public static function usesImplicitTls(int $port, string $encryption): bool
    {
        return self::resolveMailScheme($port, $encryption) === 'smtps';
    }

    public static function normalizeEncryption(int $port, string $encryption): string
    {
        $encryption = strtolower($encryption);

        if ($port === 587 && $encryption === 'ssl') {
            return 'tls';
        }

        if ($port === 465 && $encryption === 'tls') {
            return 'ssl';
        }

        return $encryption;
    }

    public function applyToConfig(): void
    {
        $port = (int) $this->mail_port;
        $encryption = (string) $this->mail_encryption;

        config([
            'mail.default' => $this->mail_mailer,
            'mail.mailers.smtp.scheme' => self::resolveMailScheme($port, $encryption),
            'mail.mailers.smtp.host' => $this->mail_host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $this->mail_username,
            'mail.mailers.smtp.password' => $this->mail_password,
            'mail.from.address' => $this->mail_from_address,
            'mail.from.name' => $this->mail_from_name,
        ]);
    }

    public static function getProviderPresets(): array
    {
        return [
            'gmail' => [
                'name' => 'Gmail',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'mail_host' => 'smtp-mail.outlook.com',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'mail_host' => 'smtp.mail.yahoo.com',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
            'sendgrid' => [
                'name' => 'SendGrid',
                'mail_host' => 'smtp.sendgrid.net',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
            'mailgun' => [
                'name' => 'Mailgun',
                'mail_host' => 'smtp.mailgun.org',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
            'custom' => [
                'name' => 'إعدادات مخصصة',
                'mail_host' => '',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
            ],
        ];
    }
}
