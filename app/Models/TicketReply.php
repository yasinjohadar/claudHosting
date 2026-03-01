<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketReply extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'whmcs_id',
        'ticket_id',
        'whmcs_ticket_id',
        'userid',
        'name',
        'email',
        'type',
        'date',
        'message',
        'attachment',
        'admin',
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
        'synced_at' => 'datetime',
    ];

    /**
     * العلاقة مع التذكرة
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'userid', 'whmcs_id');
    }

    /**
     * الحصول على اسم نوع الرد
     */
    public function getTypeNameAttribute()
    {
        $types = [
            'system' => 'نظام',
            'client' => 'عميل',
            'admin' => 'مسؤول',
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * الحصول على لون نوع الرد
     */
    public function getTypeColorAttribute()
    {
        $colors = [
            'system' => 'secondary',
            'client' => 'info',
            'admin' => 'success',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * التحقق مما إذا كان الرد من العميل
     */
    public function getIsClientReplyAttribute()
    {
        return $this->type === 'client';
    }

    /**
     * التحقق مما إذا كان الرد من المسؤول
     */
    public function getIsAdminReplyAttribute()
    {
        return $this->type === 'admin';
    }

    /**
     * التحقق مما إذا كان الرد من النظام
     */
    public function getIsSystemReplyAttribute()
    {
        return $this->type === 'system';
    }

    /**
     * الحصول على اسم المسؤول
     */
    public function getAdminNameAttribute()
    {
        if (empty($this->admin)) {
            return 'غير معين';
        }

        // يمكن تحسين هذا الجزء بالربط مع جدول المسؤولين
        return $this->admin;
    }

    /**
     * الحصول على اسم المرفق
     */
    public function getAttachmentNameAttribute()
    {
        if (empty($this->attachment)) {
            return null;
        }

        return basename($this->attachment);
    }

    /**
     * الحصول على رابط المرفق
     */
    public function getAttachmentUrlAttribute()
    {
        if (empty($this->attachment)) {
            return null;
        }

        return asset('storage/' . $this->attachment);
    }

    /**
     * التحقق مما إذا كان الرد يحتوي على مرفقات
     */
    public function getHasAttachmentAttribute()
    {
        return !empty($this->attachment);
    }
}