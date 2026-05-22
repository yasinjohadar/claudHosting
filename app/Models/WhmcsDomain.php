<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhmcsDomain extends Model
{
    protected $fillable = [
        'whmcs_domain_id',
        'customer_id',
        'whmcs_client_id',
        'domain',
        'status',
        'registrationdate',
        'expirydate',
        'nextduedate',
        'recurringamount',
        'registrar',
        'paymentmethod',
        'billingcycle',
        'domainstatus',
        'notes',
        'synced_at',
    ];

    protected $casts = [
        'registrationdate' => 'datetime',
        'expirydate' => 'datetime',
        'nextduedate' => 'datetime',
        'recurringamount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        $map = [
            'Active' => 'نشط',
            'Expired' => 'منتهٍ',
            'Grace' => 'فترة سماح',
            'Redemption' => 'استرداد',
            'Pending' => 'قيد الانتظار',
            'Pending Transfer' => 'نقل قيد الانتظار',
            'Cancelled' => 'ملغي',
            'Fraud' => 'احتيال',
        ];

        return $map[$this->status] ?? ($this->status ?? '—');
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expirydate) {
            return false;
        }

        return $this->expirydate->isFuture() && $this->expirydate->lte(now()->addDays($days));
    }
}
