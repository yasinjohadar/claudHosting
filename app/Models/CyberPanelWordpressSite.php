<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CyberPanelWordpressSite extends Model
{
    protected $table = 'cyberpanel_wordpress_sites';

    public const STATUSES = [
        'provisioning' => 'قيد التثبيت',
        'running' => 'يعمل',
        'failed' => 'فشل',
    ];

    protected $fillable = [
        'uuid',
        'cyberpanel_website_id',
        'domain',
        'wp_admin_url',
        'wp_user',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $site) {
            if (empty($site->uuid)) {
                $site->uuid = (string) Str::uuid();
            }
        });
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(CyberPanelWebsite::class, 'cyberpanel_website_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPublicUrlAttribute(): ?string
    {
        $domain = trim((string) ($this->domain ?? ''));
        if ($domain === '') {
            return null;
        }

        return str_starts_with($domain, 'http') ? $domain : 'https://'.$domain;
    }

    public function getAdminUrlAttribute(): ?string
    {
        if ($this->wp_admin_url) {
            return $this->wp_admin_url;
        }

        $base = $this->public_url;

        return $base ? rtrim($base, '/').'/wp-admin' : null;
    }

    public function hasStoredAdminPassword(): bool
    {
        $meta = is_array($this->metadata) ? $this->metadata : [];

        return ! empty($meta['wp_admin_password_enc']);
    }

    public function getAdminPassword(): ?string
    {
        $meta = is_array($this->metadata) ? $this->metadata : [];
        $encrypted = $meta['wp_admin_password_enc'] ?? null;
        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function storeAdminPassword(string $password, ?string $username = null): void
    {
        $meta = is_array($this->metadata) ? $this->metadata : [];
        $meta['wp_admin_password_enc'] = Crypt::encryptString($password);
        $meta['credentials_saved_at'] = now()->toIso8601String();

        $updates = ['metadata' => $meta];
        if ($username !== null && $username !== '') {
            $updates['wp_user'] = $username;
        }

        $this->update($updates);
    }
}
