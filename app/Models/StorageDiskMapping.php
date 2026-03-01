<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StorageDiskMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk_name',
        'label',
        'primary_storage_id',
        'fallback_storage_ids',
        'file_types',
        'is_active',
    ];

    protected $casts = [
        'fallback_storage_ids' => 'array',
        'file_types' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع التخزين الأساسي
     */
    public function primaryStorage(): BelongsTo
    {
        return $this->belongsTo(AppStorageConfig::class, 'primary_storage_id');
    }

    /**
     * الحصول على أماكن التخزين الاحتياطية
     */
    public function getFallbackStorages()
    {
        if (!$this->fallback_storage_ids) {
            return collect();
        }

        return AppStorageConfig::whereIn('id', $this->fallback_storage_ids)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
    }
}
