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
        'username',
        'email',
        'phone',
        'country_code',
        'companyname',
        'address1',
        'address2',
        'city',
        'state',
        'postcode',
        'country',
        'password',
        'status',
        'is_active',
        'photo',
        'created_by',
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

    public function whmAccounts()
    {
        return $this->hasMany(WhmAccount::class);
    }

    public function cyberpanelWebsites()
    {
        return $this->hasMany(CyberPanelWebsite::class);
    }

    public function clientDomains()
    {
        return $this->hasMany(ClientDomain::class);
    }

    public function clientCoolifyProjects()
    {
        return $this->hasMany(ClientCoolifyProject::class);
    }

    public function clientCoolifyTeam()
    {
        return $this->hasOne(ClientCoolifyTeam::class);
    }

    public function photoUrl(): string
    {
        if (empty($this->photo)) {
            return asset('assets/images/faces/default-avatar.jpg');
        }

        return asset('storage/'.$this->photo);
    }

    public function isAdminPanelUser(): bool
    {
        if ($this->hasAnyRole(['admin'], 'web')) {
            return true;
        }

        try {
            return $this->hasPermissionTo('user-list', 'web');
        } catch (\Throwable) {
            return false;
        }
    }
}