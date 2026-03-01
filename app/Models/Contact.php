<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $fillable = [
        'whmcs_id',
        'customer_id',
        'firstname',
        'lastname',
        'companyname',
        'email',
        'address1',
        'address2',
        'city',
        'state',
        'postcode',
        'country',
        'phonenumber',
        'generalemails',
        'productemails',
        'domainemails',
        'invoiceemails',
        'supportemails',
        'affiliateemails',
        'synced_at',
    ];

    protected $casts = [
        'generalemails' => 'boolean',
        'productemails' => 'boolean',
        'domainemails' => 'boolean',
        'invoiceemails' => 'boolean',
        'supportemails' => 'boolean',
        'affiliateemails' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->lastname) ?: '-';
    }
}
