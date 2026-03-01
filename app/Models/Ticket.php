<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
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
        'tid',
        'deptid',
        'userid',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'priority',
        'admin',
        'lastreply',
        'lastadminreply',
        'date',
        'lastmodified',
        'service',
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
        'lastreply' => 'datetime',
        'lastadminreply' => 'datetime',
        'date' => 'datetime',
        'lastmodified' => 'datetime',
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
     * العلاقة مع ردود التذاكر
     */
    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    /**
     * العلاقة مع الملاحظات
     */
    public function notes()
    {
        return $this->hasMany(TicketNote::class, 'ticket_id');
    }

    /**
     * الحصول على اسم الحالة
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'Open' => 'مفتوح',
            'Answered' => 'تم الرد',
            'Customer-Reply' => 'رد العميل',
            'Closed' => 'مغلق',
            'In Progress' => 'قيد المعالجة',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'Open' => 'danger',
            'Answered' => 'info',
            'Customer-Reply' => 'warning',
            'Closed' => 'success',
            'In Progress' => 'primary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * الحصول على اسم الأولوية
     */
    public function getPriorityNameAttribute()
    {
        $priorities = [
            'Low' => 'منخفضة',
            'Medium' => 'متوسطة',
            'High' => 'عالية',
            'Critical' => 'حرجة',
            'Emergency' => 'طارئة',
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    /**
     * الحصول على لون الأولوية
     */
    public function getPriorityColorAttribute()
    {
        $colors = [
            'Low' => 'success',
            'Medium' => 'info',
            'High' => 'warning',
            'Critical' => 'danger',
            'Emergency' => 'danger',
        ];

        return $colors[$this->priority] ?? 'secondary';
    }

    /**
     * الحصول على اسم القسم
     */
    public function getDepartmentNameAttribute()
    {
        $departments = [
            1 => 'الدعم الفني',
            2 => 'المبيعات',
            3 => 'الفوترة',
            4 => 'الاستضافة',
            5 => 'النطاقات',
        ];

        return $departments[$this->deptid] ?? "قسم {$this->deptid}";
    }

    /**
     * التحقق مما إذا كانت التذكرة مغلقة
     */
    public function getIsClosedAttribute()
    {
        return $this->status === 'Closed';
    }

    /**
     * التحقق مما إذا كانت التذكرة تنتظر رد العميل
     */
    public function getIsWaitingCustomerReplyAttribute()
    {
        return $this->status === 'Customer-Reply';
    }

    /**
     * التحقق مما إذا كانت التذكرة تنتظر رد الأدمن
     */
    public function getIsWaitingAdminReplyAttribute()
    {
        return in_array($this->status, ['Open', 'Answered', 'In Progress']);
    }

    /**
     * الحصول على عدد الردود
     */
    public function getRepliesCountAttribute()
    {
        return $this->replies()->count();
    }

    /**
     * الحصول على آخر رد
     */
    public function getLastReplyAttribute()
    {
        return $this->replies()->latest()->first();
    }

    /**
     * الحصول على اسم المسؤول المعين
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
     * الحصول على أيام الانتظار
     */
    public function getWaitingDaysAttribute()
    {
        if ($this->isClosed) {
            return 0;
        }

        $lastReply = $this->lastreply ?? $this->date;
        return now()->diffInDays($lastReply);
    }

    /**
     * التحقق مما إذا كانت التذكرة متأخرة
     */
    public function getIsOverdueAttribute()
    {
        if ($this->isClosed || $this->priority === 'Low') {
            return false;
        }

        $waitingDays = $this->waitingDays;
        
        if ($this->priority === 'Medium' && $waitingDays > 2) {
            return true;
        }
        
        if ($this->priority === 'High' && $waitingDays > 1) {
            return true;
        }
        
        if (in_array($this->priority, ['Critical', 'Emergency']) && $waitingDays > 0) {
            return true;
        }

        return false;
    }
}