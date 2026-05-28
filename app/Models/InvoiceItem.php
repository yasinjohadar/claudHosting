<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_invoice_item_id',
        'invoice_id',
        'product_id',
        'offered_service_id',
        'customer_service_id',
        'whmcs_service_id',
        'description',
        'amount',
        'taxed',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'taxed' => 'boolean',
    ];

    /**
     * العلاقة مع الفاتورة
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function offeredService()
    {
        return $this->belongsTo(OfferedService::class);
    }

    public function customerService()
    {
        return $this->belongsTo(CustomerService::class);
    }

    /**
     * العلاقة مع الخدمة
     */
    public function service()
    {
        return $this->belongsTo(CustomerProduct::class, 'whmcs_service_id', 'whmcs_service_id');
    }
}