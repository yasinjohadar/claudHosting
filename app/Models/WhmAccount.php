<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhmAccount extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'username',
        'domain',
        'email',
        'joined_at',
        'package',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'joined_at' => 'datetime',
    ];

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
