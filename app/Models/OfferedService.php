<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OfferedService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_type_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'execution_duration',
        'execution_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'execution_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function customerServices(): HasMany
    {
        return $this->hasMany(CustomerService::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getFormattedPriceAttribute(): string
    {
        $currency = $this->currency ?: 'SAR';
        $label = $currency === 'SAR' ? 'ر.س' : $currency;

        return number_format((float) $this->price, 2).' '.$label;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (OfferedService $service) {
            if (empty($service->slug)) {
                $service->slug = static::uniqueSlug(
                    Str::slug($service->name, '-', 'ar') ?: 'service'
                );
            }
        });
    }

    public static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
