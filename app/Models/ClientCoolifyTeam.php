<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ClientCoolifyTeam extends Model
{
    protected $fillable = [
        'user_id',
        'coolify_team_id',
        'team_name',
        'api_token',
        'token_configured_at',
        'notes',
    ];

    protected $casts = [
        'coolify_team_id' => 'integer',
        'token_configured_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasApiToken(): bool
    {
        return $this->getDecryptedApiToken() !== '';
    }

    public function getDecryptedApiToken(): string
    {
        $raw = (string) ($this->attributes['api_token'] ?? '');

        if ($raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw;
        }
    }

    public function setApiTokenAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['api_token'] = null;

            return;
        }

        $this->attributes['api_token'] = Crypt::encryptString($value);
        $this->attributes['token_configured_at'] = now();
    }
}
