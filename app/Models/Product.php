<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_id',
        'type',
        'gid',
        'name',
        'description',
        'paytype',
        'pricing',
        'currency',
        'showdomainoptions',
        'stockcontrol',
        'qty',
        'prorata',
        'proratadate',
        'proratachargenextmonth',
        'hidden',
        'tax',
        'allowqty',
        'recurring',
        'autoterminate',
        'autorenew',
        'servertype',
        'servergroup',
        'configoption1',
        'configoption2',
        'configoption3',
        'configoption4',
        'configoption5',
        'configoption6',
        'configoption7',
        'configoption8',
        'configoption9',
        'configoption10',
        'configoption11',
        'configoption12',
        'configoption13',
        'configoption14',
        'configoption15',
        'configoption16',
        'configoption17',
        'configoption18',
        'configoption19',
        'configoption20',
        'configoption21',
        'configoption22',
        'configoption23',
        'configoption24',
        'status',
        'product_group',
        'sales_count',
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
        'pricing' => 'array',
        'showdomainoptions' => 'boolean',
        'stockcontrol' => 'boolean',
        'prorata' => 'boolean',
        'proratachargenextmonth' => 'boolean',
        'hidden' => 'boolean',
        'tax' => 'boolean',
        'allowqty' => 'boolean',
        'recurring' => 'boolean',
        'autoterminate' => 'boolean',
        'autorenew' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * العلاقة مع العملاء
     */
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_products', 'product_id', 'customer_id')
            ->withPivot(['id', 'whmcs_service_id', 'status', 'nextduedate', 'amount', 'billingcycle', 'created_at', 'updated_at']);
    }

    /**
     * العلاقة مع الفواتير
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_items', 'product_id', 'invoice_id')
            ->withPivot(['id', 'whmcs_invoice_item_id', 'description', 'amount', 'taxed', 'created_at', 'updated_at']);
    }

    /**
     * الحصول على اسم نوع المنتج
     */
    public function getTypeNameAttribute()
    {
        $types = [
            'hostingaccount' => 'حساب استضافة',
            'reselleraccount' => 'حساب ريستلر',
            'server' => 'خادم',
            'other' => 'آخر',
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * الحصول على اسم طريقة الدفع
     */
    public function getPayTypeNameAttribute()
    {
        $paytypes = [
            'free' => 'مجاني',
            'onetime' => 'مرة واحدة',
            'recurring' => 'متكرر',
        ];

        return $paytypes[$this->paytype] ?? $this->paytype;
    }

    /**
     * الحصول على سعر المنتج (قيمة قابلة للعرض - من pricing حسب بنية WHMCS)
     */
    public function getPriceAttribute()
    {
        $pricing = $this->pricing;
        if (! is_array($pricing)) {
            return '0';
        }
        // قد يكون المفتاح أول عملة متوفرة (USD, SAR, ...)
        $first = is_array($pricing) ? reset($pricing) : null;
        if (! is_array($first)) {
            return '0';
        }
        // WHMCS يرجع: monthly, quarterly, annually, ... أو msetupfee إلخ
        foreach (['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'] as $cycle) {
            if (isset($first[$cycle]) && $first[$cycle] !== '' && $first[$cycle] !== '-1.00') {
                return is_scalar($first[$cycle]) ? (string) $first[$cycle] : '0';
            }
        }
        return '0';
    }

    /**
     * الحصول على رسوم الإعداد للعرض
     */
    public function getSetupfeeAttribute()
    {
        $pricing = $this->pricing;
        if (! is_array($pricing)) {
            return '0';
        }
        $first = reset($pricing);
        if (! is_array($first)) {
            return '0';
        }
        foreach (['msetupfee', 'qsetupfee', 'ssetupfee', 'asetupfee', 'bsetupfee', 'tsetupfee'] as $key) {
            if (isset($first[$key]) && $first[$key] !== '' && $first[$key] !== '-1.00') {
                return is_scalar($first[$key]) ? (string) $first[$key] : '0';
            }
        }
        return '0';
    }

    /**
     * دورة الفوترة الافتراضية للعرض (المنتج قد لا يخزنها - من أول سعر متوفر)
     */
    public function getBillingcycleAttribute()
    {
        $pricing = $this->pricing;
        if (! is_array($pricing)) {
            return null;
        }
        $first = reset($pricing);
        if (! is_array($first)) {
            return null;
        }
        foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semiannually' => 'Semi-Annually', 'annually' => 'Annually', 'biennially' => 'Biennially', 'triennially' => 'Triennially'] as $key => $label) {
            if (! empty($first[$key]) && $first[$key] !== '-1.00') {
                return $label;
            }
        }
        return null;
    }

    /**
     * الحصول على اسم المجموعة
     */
    public function getGroupNameAttribute()
    {
        $groups = [
            1 => 'الاستضافة',
            2 => 'السيرفرات',
            3 => 'الخدمات الإضافية',
            4 => 'النطاقات',
        ];

        return $groups[$this->gid] ?? "مجموعة {$this->gid}";
    }

    /**
     * الحصول على حالة التوفر
     */
    public function getAvailabilityStatusAttribute()
    {
        if ($this->stockcontrol && $this->qty <= 0) {
            return 'غير متوفر';
        }

        return 'متوفر';
    }
}