<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_id',
        'whmcs_client_id',
        'invoicenum',
        'date',
        'duedate',
        'datepaid',
        'subtotal',
        'credit',
        'tax',
        'taxrate',
        'tax2',
        'taxrate2',
        'total',
        'status',
        'paymentmethod',
        'notes',
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
        'duedate' => 'datetime',
        'datepaid' => 'datetime',
        'subtotal' => 'float',
        'credit' => 'float',
        'tax' => 'float',
        'taxrate' => 'float',
        'tax2' => 'float',
        'taxrate2' => 'float',
        'total' => 'float',
        'synced_at' => 'datetime',
    ];

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'whmcs_client_id', 'whmcs_id');
    }

    /**
     * العلاقة مع بنود الفاتورة
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    /**
     * العلاقة مع المنتجات من خلال بنود الفاتورة
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_items', 'invoice_id', 'product_id')
            ->withPivot(['id', 'whmcs_invoice_item_id', 'description', 'amount', 'taxed', 'created_at', 'updated_at']);
    }

    /**
     * العلاقة مع المدفوعات
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * الحصول على اسم الحالة
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'Draft' => 'مسودة',
            'Unpaid' => 'غير مدفوع',
            'Paid' => 'مدفوع',
            'Cancelled' => 'ملغى',
            'Refunded' => 'مسترد',
            'Collections' => 'تحصيلات',
            'Payment Pending' => 'الدفع قيد الانتظار',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'Draft' => 'secondary',
            'Unpaid' => 'danger',
            'Paid' => 'success',
            'Cancelled' => 'dark',
            'Refunded' => 'warning',
            'Collections' => 'info',
            'Payment Pending' => 'warning',
        ];

        return $colors[$this->status] ?? 'secondary';
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
     * الحصول على المبلغ المتبقي
     */
    public function getBalanceAttribute()
    {
        $paidAmount = $this->payments()->sum('amount');
        return max(0, $this->total - $paidAmount);
    }

    /**
     * الحصول على نسبة الضريبة الإجمالية
     */
    public function getTotalTaxRateAttribute()
    {
        return $this->taxrate + $this->taxrate2;
    }

    /**
     * الحصول على إجمالي الضريبة
     */
    public function getTotalTaxAttribute()
    {
        return $this->tax + $this->tax2;
    }

    /**
     * الحصول على المبلغ الصافي (بعد الخصم والضريبة)
     */
    public function getNetTotalAttribute()
    {
        return $this->subtotal - $this->credit + $this->getTotalTaxAttribute();
    }

    /**
     * التحقق مما إذا كانت الفاتورة مدفوعة بالكامل
     */
    public function getIsPaidAttribute()
    {
        return $this->status === 'Paid' || $this->balance <= 0;
    }

    /**
     * التحقق مما إذا كانت الفاتورة متأخرة
     */
    public function getIsOverdueAttribute()
    {
        return $this->status === 'Unpaid' && $this->duedate < now();
    }

    /**
     * الحصول على عدد الأيام المتأخرة
     */
    public function getOverdueDaysAttribute()
    {
        if (!$this->isOverdue) {
            return 0;
        }

        return now()->diffInDays($this->duedate);
    }
}