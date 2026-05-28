<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerService extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'customer_id',
        'offered_service_id',
        'invoice_id',
        'name',
        'price',
        'currency',
        'execution_duration',
        'execution_days',
        'subscribed_at',
        'renewal_at',
        'amount_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'subscribed_at' => 'date',
        'renewal_at' => 'date',
        'execution_days' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function offeredService(): BelongsTo
    {
        return $this->belongsTo(OfferedService::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        $label = ($this->currency ?? 'SAR') === 'SAR' ? 'ر.س' : $this->currency;

        return number_format((float) $this->price, 2).' '.$label;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'نشطة',
            self::STATUS_COMPLETED => 'مكتملة',
            self::STATUS_CANCELLED => 'ملغاة',
            self::STATUS_OVERDUE => 'متأخرة',
            default => 'قيد الانتظار',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_OVERDUE => 'danger',
            default => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_ACTIVE => 'نشطة',
            self::STATUS_COMPLETED => 'مكتملة',
            self::STATUS_CANCELLED => 'ملغاة',
            self::STATUS_OVERDUE => 'متأخرة',
        ];
    }
}
