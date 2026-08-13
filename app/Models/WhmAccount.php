<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhmAccount extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'username',
        'domain',
        'email',
        'joined_at',
        'subscription_ends_at',
        'last_renewed_at',
        'package',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'joined_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_renewed_at' => 'datetime',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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

    public function getSubscriptionStatusBadgeAttribute(): string
    {
        $days = $this->subscription_days_remaining;

        if ($days === null) {
            return 'bg-secondary-transparent';
        }

        if ($days < 0) {
            return 'bg-danger-transparent';
        }

        if ($days <= 30) {
            return 'bg-warning-transparent';
        }

        return 'bg-success-transparent';
    }

    public function getSubscriptionStatusLabelAttribute(): string
    {
        $days = $this->subscription_days_remaining;

        if ($days === null) {
            return '—';
        }

        if ($days < 0) {
            return 'منتهي';
        }

        if ($days <= 30) {
            return 'ينتهي قريباً';
        }

        return 'ساري';
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

    public function getDisplayEmailAttribute(): ?string
    {
        if ($this->email) {
            return $this->email;
        }

        $meta = $this->metadata ?? [];
        if (! is_array($meta)) {
            return null;
        }

        foreach (['email', 'contactemail', 'acctemail'] as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value !== '' && str_contains($value, '@')) {
                return strtolower($value);
            }
        }

        return null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wordpressSites(): HasMany
    {
        return $this->hasMany(WhmWordpressSite::class);
    }

    public function getClientLabelAttribute(): ?string
    {
        $user = $this->client;
        if (! $user) {
            return null;
        }

        return trim($user->name.' — '.$user->email);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'نشط',
            'suspended' => 'موقوف',
            'terminated' => 'محذوف',
            default => $this->status,
        };
    }
}
