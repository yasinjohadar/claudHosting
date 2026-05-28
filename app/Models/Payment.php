<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_FAILED = 'Failed';

    public const STATUS_REFUNDED = 'Refunded';

    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'whmcs_id',
        'invoice_id',
        'customer_id',
        'whmcs_invoice_id',
        'whmcs_client_id',
        'date',
        'amount',
        'fees',
        'paymentmethod',
        'transid',
        'status',
        'notes',
        'recorded_by_user_id',
        'proof_path',
        'initiated_by',
        'created_at',
        'updated_at',
        'synced_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'float',
        'fees' => 'float',
        'synced_at' => 'datetime',
    ];

    public function getGatewayAttribute(): string
    {
        return $this->paymentmethod ?? '';
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getPaymentMethodNameAttribute(): string
    {
        $methods = [
            'banktransfer' => 'تحويل بنكي',
            'manual' => 'يدوي',
            'paypal' => 'باي بال',
            'stripe' => 'سترايب',
            'authorize' => 'أوثورايز',
            'coinbase' => 'كوين بيس',
            'mailin' => 'دفع بريدي',
            'cash' => 'نقدي',
            'creditcard' => 'بطاقة ائتمان',
            'other' => 'أخرى',
        ];

        return $methods[$this->paymentmethod] ?? $this->paymentmethod;
    }

    public function getStatusNameAttribute(): string
    {
        $statuses = [
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_FAILED => 'فشل',
            self::STATUS_REFUNDED => 'مسترد',
            self::STATUS_CANCELLED => 'ملغى',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_COMPLETED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REFUNDED => 'info',
            self::STATUS_CANCELLED => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this->fees;
    }

    public function getInitiatedByLabelAttribute(): string
    {
        return match ($this->initiated_by) {
            'client' => 'العميل',
            default => 'الإدارة',
        };
    }
}
