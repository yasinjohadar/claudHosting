<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Backup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'backup_type',
        'storage_driver',
        'storage_config_id', // ربط مع AppStorageConfig
        'storage_path',
        'file_path',
        'file_size',
        'compression_type',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'error_message',
        'metadata',
        'retention_days',
        'expires_at',
        'created_by',
        'schedule_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'duration' => 'integer',
        'retention_days' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * أنواع النسخ
     */
    public const TYPES = [
        'manual' => 'يدوي',
        'scheduled' => 'مجدول',
        'automatic' => 'تلقائي',
    ];

    /**
     * أنواع المحتوى
     */
    public const BACKUP_TYPES = [
        'full' => 'كامل',
        'database' => 'قاعدة البيانات',
        'files' => 'الملفات',
        'config' => 'الإعدادات',
    ];

    /**
     * أنواع الضغط
     */
    public const COMPRESSION_TYPES = [
        'zip' => 'ZIP',
        'gzip' => 'GZIP',
        'tar' => 'TAR',
    ];

    /**
     * الحالات
     */
    public const STATUSES = [
        'pending' => 'معلق',
        'running' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'failed' => 'فشل',
    ];

    /**
     * العلاقة مع منشئ النسخة
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع الجدولة
     */
    public function schedule()
    {
        return $this->belongsTo(BackupSchedule::class, 'schedule_id');
    }

    /**
     * العلاقة مع السجلات
     */
    public function logs()
    {
        return $this->hasMany(BackupLog::class, 'backup_id');
    }

    /**
     * العلاقة مع إعدادات التخزين (AppStorageConfig)
     */
    public function storageConfig()
    {
        return $this->belongsTo(AppStorageConfig::class, 'storage_config_id');
    }

    /**
     * نطاق النسخ المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * نطاق النسخ الفاشلة
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * نطاق النسخ المنتهية الصلاحية
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * نطاق النسخ حسب النوع
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * نطاق النسخ حسب نوع المحتوى
     */
    public function scopeByBackupType($query, string $backupType)
    {
        return $query->where('backup_type', $backupType);
    }

    /**
     * نطاق النسخ حسب نوع التخزين
     */
    public function scopeByStorageDriver($query, string $driver)
    {
        return $query->where('storage_driver', $driver);
    }

    /**
     * الحصول على حجم الملف بصيغة مقروءة
     */
    public function getFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * تحميل النسخة
     */
    public function download()
    {
        // سيتم تنفيذ هذا في Service
        return null;
    }

    /**
     * استعادة النسخة
     */
    public function restore(array $options = []): bool
    {
        // سيتم تنفيذ هذا في Service
        return false;
    }

    /**
     * التحقق من انتهاء الصلاحية
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * حساب تاريخ انتهاء الصلاحية
     */
    public function calculateExpiresAt(): Carbon
    {
        return now()->addDays($this->retention_days);
    }
}
