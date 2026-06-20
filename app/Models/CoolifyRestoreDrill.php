<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CoolifyRestoreDrill extends Model
{
    protected $fillable = [
        'uuid',
        'snapshot_id',
        'status',
        'items_total',
        'items_verified',
        'items_failed',
        'summary',
        'results',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(CoolifyProjectSnapshot::class, 'snapshot_id');
    }
}
