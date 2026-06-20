<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class VpsServer extends Model
{
    public const PROVIDERS = [
        'contabo' => 'Contabo',
        'hetzner' => 'Hetzner Cloud',
        'digitalocean' => 'DigitalOcean',
        'ovh' => 'OVHcloud',
        'netcup' => 'Netcup',
    ];

    public const STATUS_LABELS = [
        'running' => 'يعمل',
        'stopped' => 'متوقف',
        'starting' => 'جاري التشغيل',
        'stopping' => 'جاري الإيقاف',
        'rebooting' => 'إعادة تشغيل',
        'unknown' => 'غير معروف',
    ];

    protected $fillable = [
        'uuid',
        'provider',
        'external_id',
        'name',
        'ip',
        'region',
        'status',
        'coolify_server_uuid',
        'metadata',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(VpsActionLog::class);
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(VpsMetricSnapshot::class);
    }

    public function latestMetricSnapshot(): HasOne
    {
        return $this->hasOne(VpsMetricSnapshot::class)->latestOfMany('recorded_at');
    }

    public function displayName(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        if ($this->ip) {
            return (string) $this->ip;
        }

        return $this->providerLabel().' #'.$this->external_id;
    }

    public function providerLabel(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['running', 'starting'], true);
    }

    public function productLineLabel(): ?string
    {
        $line = $this->metadata['product_line'] ?? null;

        return match ($line) {
            'ovh_vps' => 'OVH VPS',
            'ovh_dedicated' => 'OVH Dedicated',
            'ovh_public_cloud' => 'OVH Public Cloud',
            default => is_string($line) ? $line : null,
        };
    }

    public function supportsLifecycle(): bool
    {
        return in_array($this->provider, ['ovh', 'netcup'], true);
    }
}
