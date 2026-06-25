<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'provision_status',
        'coolify_wordpress_site_id',
        'whm_account_id',
        'cyberpanel_website_id',
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
            self::STATUS_CONVERTED  => 'تم التنفيذ',
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

    public function coolifyWordpressSite(): BelongsTo
    {
        return $this->belongsTo(CoolifyWordpressSite::class, 'coolify_wordpress_site_id');
    }

    public function whmAccount(): BelongsTo
    {
        return $this->belongsTo(WhmAccount::class, 'whm_account_id');
    }

    public function cyberpanelWebsite(): BelongsTo
    {
        return $this->belongsTo(CyberPanelWebsite::class, 'cyberpanel_website_id');
    }

    public static function provisionStatuses(): array
    {
        return [
            'pending' => 'معلق',
            'provisioning' => 'جاري التزويد',
            'running' => 'يعمل',
            'failed' => 'فاشل',
        ];
    }

    public function getProvisionStatusLabelAttribute(): string
    {
        return self::provisionStatuses()[$this->provision_status] ?? ($this->provision_status ?: '—');
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
