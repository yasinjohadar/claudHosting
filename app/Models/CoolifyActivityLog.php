<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoolifyActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_uuid',
        'resource_name',
        'message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $resourceType,
        ?string $resourceUuid = null,
        ?string $resourceName = null,
        ?string $message = null,
        ?array $meta = null
    ): void {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_uuid' => $resourceUuid,
            'resource_name' => $resourceName,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
