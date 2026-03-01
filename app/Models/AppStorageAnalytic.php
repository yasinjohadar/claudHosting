<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppStorageAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_config_id',
        'date',
        'bytes_stored',
        'bytes_uploaded',
        'bytes_downloaded',
        'cost',
        'operations_count',
        'file_type',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'bytes_stored' => 'integer',
        'bytes_uploaded' => 'integer',
        'bytes_downloaded' => 'integer',
        'cost' => 'decimal:2',
        'operations_count' => 'integer',
        'metadata' => 'array',
    ];

    public function storageConfig(): BelongsTo
    {
        return $this->belongsTo(AppStorageConfig::class, 'storage_config_id');
    }
}
