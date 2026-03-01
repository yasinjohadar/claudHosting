<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    // لو لم يكن الاسم الافتراضي "sessions"
    protected $table = 'sessions';

    // المفتاح الأساسي من نوع string وليس id رقمي
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // لتفعيل التواريخ مثل login_at و logout_at
    protected $dates = [
        'login_at',
        'logout_at',
        'created_at',
        'updated_at',
    ];

    // الحقول القابلة للكتابة الجماعية
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'location',
        'login_at',
        'logout_at',
        'session_duration',
        'is_current',
        'failed_attempts',
        'payload',
        'last_activity',
    ];

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}