<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageOrderRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'email',
        'phone',
        'billing_cycle',
        'notes',
        'status',
        'user_id',
        'whmcs_order_id',
        'whmcs_client_id',
    ];

    protected $casts = [
        //
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING    => 'قيد الانتظار',
            self::STATUS_CONTACTED  => 'تم التواصل',
            self::STATUS_CONVERTED  => 'تم التحويل لـ WHMCS',
            self::STATUS_CANCELLED  => 'ملغي',
        ];
    }

    public static function billingCycles(): array
    {
        return [
            'monthly'      => 'شهري',
            'quarterly'    => 'ربع سنوي',
            'semiannually' => 'نصف سنوي',
            'annually'     => 'سنوي',
            'biennially'   => 'كل سنتين',
            'triennially'  => 'كل ثلاث سنوات',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return self::billingCycles()[$this->billing_cycle] ?? $this->billing_cycle;
    }
}
