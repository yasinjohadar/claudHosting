<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhmWordpressSite extends Model
{
    public const SOURCE_SOFTACULOUS = 'softaculous';

    public const SOURCE_WP_TOOLKIT = 'wp_toolkit';

    public const SOURCE_MANUAL = 'manual';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MISSING = 'missing';

    public const STATUS_UNKNOWN = 'unknown';

    public const SOURCES = [
        self::SOURCE_SOFTACULOUS => 'Softaculous',
        self::SOURCE_WP_TOOLKIT => 'WP Toolkit',
        self::SOURCE_MANUAL => 'يدوي',
    ];

    protected $fillable = [
        'whm_account_id',
        'source',
        'external_id',
        'domain',
        'path',
        'url',
        'wp_version',
        'title',
        'status',
        'metadata',
        'last_seen_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhmAccount::class, 'whm_account_id');
    }

    public function operations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WhmWordpressOperation::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'نشط',
            self::STATUS_MISSING => 'غير موجود',
            default => 'غير معروف',
        };
    }

    public function getDisplayNameAttribute(): string
    {
        $title = trim((string) ($this->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        $domain = trim((string) ($this->domain ?? ''));
        if ($domain !== '') {
            return $domain;
        }

        return $this->url ?: ('موقع #'.$this->id);
    }

    public function getPublicUrlAttribute(): ?string
    {
        $url = trim((string) ($this->url ?? ''));
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return 'https://'.$url;
    }

    public function getWpAdminUrlAttribute(): ?string
    {
        $base = $this->public_url;
        if ($base === null) {
            return null;
        }

        return rtrim($base, '/').'/wp-admin/';
    }
}
