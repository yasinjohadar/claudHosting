<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_service_id',
        'customer_id',
        'product_id',
        'orderid',
        'regdate',
        'domain',
        'paymentmethod',
        'firstpaymentamount',
        'amount',
        'billingcycle',
        'nextduedate',
        'nextinvoicedate',
        'termination_date',
        'completed_date',
        'domainstatus',
        'status',
        'username',
        'password',
        'notes',
        'subscriptionid',
        'promoid',
        'overideautosuspend',
        'overidesuspenduntil',
        'lastupdate',
        'created_at',
        'updated_at',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'regdate' => 'datetime',
        'nextduedate' => 'datetime',
        'nextinvoicedate' => 'datetime',
        'termination_date' => 'datetime',
        'completed_date' => 'datetime',
        'overidesuspenduntil' => 'datetime',
        'lastupdate' => 'datetime',
        'firstpaymentamount' => 'float',
        'amount' => 'float',
        'overideautosuspend' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * العلاقة مع الفواتير
     */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'whmcs_service_id', 'whmcs_service_id');
    }

    /**
     * الحصول على اسم حالة النطاق/الخدمة
     */
    public function getDomainStatusNameAttribute()
    {
        $statuses = [
            'Pending' => 'قيد الانتظار',
            'Active' => 'نشط',
            'Suspended' => 'معلق',
            'Terminated' => 'ملغى',
            'Cancelled' => 'ملغى',
            'Fraud' => 'احتيال',
            'Completed' => 'مكتمل',
        ];

        return $statuses[$this->domainstatus] ?? $this->domainstatus;
    }

    /**
     * الحصول على لون حالة النطاق/الخدمة
     */
    public function getDomainStatusColorAttribute()
    {
        $colors = [
            'Pending' => 'warning',
            'Active' => 'success',
            'Suspended' => 'danger',
            'Terminated' => 'dark',
            'Cancelled' => 'secondary',
            'Fraud' => 'danger',
            'Completed' => 'info',
        ];

        return $colors[$this->domainstatus] ?? 'secondary';
    }

    /**
     * الحصول على اسم دورة الفوترة
     */
    public function getBillingCycleNameAttribute()
    {
        $cycles = [
            'Free' => 'مجاني',
            'One Time' => 'مرة واحدة',
            'Monthly' => 'شهري',
            'Quarterly' => 'ربع سنوي',
            'Semi-Annually' => 'نصف سنوي',
            'Annually' => 'سنوي',
            'Biennially' => 'كل سنتين',
            'Triennially' => 'كل 3 سنوات',
        ];

        return $cycles[$this->billingcycle] ?? $this->billingcycle;
    }

    /**
     * الحصول على اسم طريقة الدفع
     */
    public function getPaymentMethodNameAttribute()
    {
        $methods = [
            'banktransfer' => 'تحويل بنكي',
            'paypal' => 'باي بال',
            'stripe' => 'سترايب',
            'authorize' => 'أوثورايز',
            'coinbase' => 'كوين بيس',
            'mailin' => 'دفع بريدي',
        ];

        return $methods[$this->paymentmethod] ?? $this->paymentmethod;
    }

    /**
     * التحقق مما إذا كانت الخدمة نشطة
     */
    public function getIsActiveAttribute()
    {
        return $this->domainstatus === 'Active';
    }

    /**
     * التحقق مما إذا كانت الخدمة معلقة
     */
    public function getIsSuspendedAttribute()
    {
        return $this->domainstatus === 'Suspended';
    }

    /**
     * التحقق مما إذا كانت الخدمة منتهية
     */
    public function getIsTerminatedAttribute()
    {
        return in_array($this->domainstatus, ['Terminated', 'Cancelled']);
    }

    /**
     * التحقق مما إذا كانت الخدمة مجانية
     */
    public function getIsFreeAttribute()
    {
        return $this->billingcycle === 'Free' || $this->amount == 0;
    }

    /**
     * التحقق مما إذا كانت الخدمة متكررة الدفع
     */
    public function getIsRecurringAttribute()
    {
        return !in_array($this->billingcycle, ['Free', 'One Time']);
    }

    /**
     * الحصول على عدد الأيام المتبقية للتجديد
     */
    public function getDaysUntilRenewalAttribute()
    {
        if (!$this->nextduedate || !$this->isRecurring) {
            return 0;
        }

        return max(0, now()->diffInDays($this->nextduedate, false));
    }

    /**
     * التحقق مما إذا كانت الخدمة ستنتهي قريباً (خلال 7 أيام)
     */
    public function getIsExpiringSoonAttribute()
    {
        if (!$this->isRecurring || !$this->nextduedate) {
            return false;
        }

        return now()->diffInDays($this->nextduedate) <= 7;
    }

    /**
     * التحقق مما إذا كانت الخدمة متأخرة في الدفع
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->isRecurring || !$this->nextduedate || !$this->isActive) {
            return false;
        }

        return $this->nextduedate < now();
    }

    /**
     * الحصول على سعر التجديد القادم
     */
    public function getNextRenewalPriceAttribute()
    {
        if (!$this->isRecurring) {
            return 0;
        }

        return $this->amount;
    }
}