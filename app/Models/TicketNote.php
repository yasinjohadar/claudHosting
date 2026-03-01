<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketNote extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'admin_id',
        'admin_name',
        'note',
        'date',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * العلاقة مع التذكرة
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * العلاقة مع المسؤول
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * للتوفق مع العرض (message = note)
     */
    public function getMessageAttribute()
    {
        return $this->note ?? '';
    }

    /**
     * الحصول على اسم المسؤول
     */
    public function getAdminNameAttribute()
    {
        if ($this->admin) {
            return $this->admin->name;
        }

        return $this->admin_name ?? 'غير معين';
    }
}