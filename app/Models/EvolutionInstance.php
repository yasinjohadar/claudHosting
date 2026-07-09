<?php

namespace App\Models;

use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EvolutionInstance extends Model
{
    protected $fillable = [
        'instance_name',
        'label',
        'evolution_uuid',
        'connection_status',
        'owner_jid',
        'profile_name',
        'phone_number',
        'profile_pic_url',
        'qr_code',
        'is_default',
        'is_manual',
        'rotation_enabled',
        'last_used_at',
        'metadata',
        'evolution_base_url',
        'evolution_api_key',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_manual' => 'boolean',
        'rotation_enabled' => 'boolean',
        'metadata' => 'array',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'evolution_api_key',
    ];

    public function isConnected(): bool
    {
        return $this->connection_status === 'open';
    }

    public function hasCustomCredentials(): bool
    {
        return trim((string) ($this->evolution_base_url ?? '')) !== ''
            || trim((string) ($this->getRawApiKey() ?? '')) !== '';
    }

    /**
     * @return array{base_url: string, api_key: string}
     */
    public function resolveApiConfig(): array
    {
        $global = app(WhatsAppSettingsService::class)->getSettings();
        $baseUrl = trim((string) ($this->evolution_base_url ?? ''));
        $apiKey = $this->decryptApiKey();

        return [
            'base_url' => $baseUrl !== '' ? $baseUrl : (string) ($global['evolution_base_url'] ?? ''),
            'api_key' => $apiKey !== '' ? $apiKey : (string) ($global['evolution_api_key'] ?? ''),
        ];
    }

    public function setEvolutionApiKeyAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $this->attributes['evolution_api_key'] = Crypt::encryptString(trim($value));
    }

    public function decryptApiKey(): string
    {
        $raw = $this->getRawApiKey();
        if ($raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw;
        }
    }

    public function getRawApiKey(): string
    {
        return trim((string) ($this->attributes['evolution_api_key'] ?? ''));
    }

    public function scopeConnected($query)
    {
        return $query->where('connection_status', 'open');
    }

    public function scopeRotationEligible($query)
    {
        return $query->connected()->where('rotation_enabled', true);
    }

    public static function rotationPoolCount(): int
    {
        return static::rotationEligible()->count();
    }

    public static function syncFromApiArray(array $instance, bool $markDefault = false): self
    {
        $name = (string) ($instance['name'] ?? '');
        $status = strtolower((string) ($instance['connectionStatus'] ?? 'close'));
        $existing = static::where('instance_name', $name)->first();

        $model = static::updateOrCreate(
            ['instance_name' => $name],
            [
                'evolution_uuid' => $instance['id'] ?? null,
                'connection_status' => $status === 'open' ? 'open' : $status,
                'owner_jid' => $instance['ownerJid'] ?? null,
                'profile_name' => $instance['profileName'] ?? null,
                'phone_number' => $instance['number'] ?? null,
                'profile_pic_url' => $instance['profilePicUrl'] ?? null,
                'is_manual' => $existing?->is_manual ?? false,
                'metadata' => [
                    'integration' => $instance['integration'] ?? null,
                    'counts' => $instance['_count'] ?? null,
                ],
                'connected_at' => $status === 'open' ? now() : null,
                'disconnected_at' => $status === 'open' ? null : now(),
            ]
        );

        if ($markDefault) {
            static::where('id', '!=', $model->id)->update(['is_default' => false]);
            $model->update(['is_default' => true]);
        }

        return $model;
    }

    public static function defaultInstance(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('connection_status', 'open')->latest()->first();
    }
}
