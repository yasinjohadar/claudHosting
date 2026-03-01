<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_id',
        'invoice_id',
        'whmcs_invoice_id',
        'whmcs_client_id',
        'date',
        'amount',
        'fees',
        'paymentmethod',
        'transid',
        'status',
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
        'date' => 'datetime',
        'amount' => 'float',
        'fees' => 'float',
        'synced_at' => 'datetime',
    ];

    /**
     * للتوفق مع العرض (gateway = paymentmethod)
     */
    public function getGatewayAttribute()
    {
        return $this->paymentmethod ?? '';
    }

    /**
     * العلاقة مع الفاتورة
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'whmcs_client_id', 'whmcs_id');
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
     * الحصول على اسم الحالة
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'Completed' => 'مكتمل',
            'Pending' => 'قيد الانتظار',
            'Failed' => 'فشل',
            'Refunded' => 'مسترد',
            'Cancelled' => 'ملغى',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'Completed' => 'success',
            'Pending' => 'warning',
            'Failed' => 'danger',
            'Refunded' => 'info',
            'Cancelled' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * الحصول على المبلغ الصافي (بعد خصم الرسوم)
     */
    public function getNetAmountAttribute()
    {
        return $this->amount - $this->fees;
    }
}