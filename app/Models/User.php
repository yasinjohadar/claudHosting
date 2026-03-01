<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * السمات التي يمكن تعيينها بشكل جماعي.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * السمات التي يجب إخفاؤها للمصفوفات.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * السمات التي يجب تحويلها.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * طلبات الباقات المرتبطة بالمستخدم (إن وُجدت).
     */
    public function packageOrderRequests()
    {
        return $this->hasMany(PackageOrderRequest::class);
    }

    /**
     * العميل المرتبط في WHMCS (إن وُجد — للمستخدمين المسجّلين مع ربط WHMCS).
     */
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
}