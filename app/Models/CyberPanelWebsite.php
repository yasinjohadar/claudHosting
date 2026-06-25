<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CyberPanelWebsite extends Model
{
    protected $table = 'cyberpanel_websites';

    public const STATUSES = [
        'active' => 'نشط',
        'suspended' => 'موقوف',
        'terminated' => 'محذوف',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'domain',
        'owner',
        'email',
        'package',
        'php_version',
        'status',
        'joined_at',
        'subscription_ends_at',
        'last_renewed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'joined_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_renewed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'cyberpanel_website_id');
    }

    public function wordpressSite(): HasOne
    {
        return $this->hasOne(CyberPanelWordpressSite::class, 'cyberpanel_website_id');
    }

    public function getSiteUrlAttribute(): ?string
    {
        $domain = trim((string) ($this->domain ?? ''));
        if ($domain === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $domain)) {
            return $domain;
        }

        return 'https://'.$domain;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsSubscriptionExpiredAttribute(): bool
    {
        if ($this->subscription_ends_at === null) {
            return false;
        }

        return $this->subscription_ends_at->isPast();
    }

    public function getSubscriptionDaysRemainingAttribute(): ?int
    {
        if ($this->subscription_ends_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->subscription_ends_at->startOfDay(), false);
    }
}
