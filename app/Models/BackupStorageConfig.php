<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class BackupStorageConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'config',
        'is_active',
        'priority',
        'max_backups',
        'created_by',
        'redundancy',
        'pricing_config',
        'monthly_budget',
        'cost_alert_threshold',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_backups' => 'integer',
        'redundancy' => 'boolean',
        'pricing_config' => 'array',
        'monthly_budget' => 'decimal:2',
        'cost_alert_threshold' => 'decimal:2',
    ];

    /**
     * أنواع السواق
     */
    public const DRIVERS = [
        'local' => 'Local Storage',
        's3' => 'Amazon S3',
        'ftp' => 'FTP',
        'sftp' => 'SFTP',
        'azure' => 'Azure Blob Storage',
        'digitalocean' => 'DigitalOcean Spaces',
        'wasabi' => 'Wasabi',
        'backblaze' => 'Backblaze B2',
        'cloudflare_r2' => 'Cloudflare R2',
    ];

    /**
     * العلاقة مع منشئ الإعداد
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع النسخ
     */
    public function backups()
    {
        return $this->hasMany(Backup::class, 'storage_driver', 'driver');
    }

    /**
     * العلاقة مع الإحصائيات
     */
    public function analytics()
    {
        return $this->hasMany(StorageAnalytic::class, 'storage_config_id');
    }

    /**
     * الحصول على instance التخزين
     */
    public function getStorageInstance()
    {
        $config = $this->getDecryptedConfig();
        
        return match($this->driver) {
            'local' => Storage::disk('local'),
            's3' => $this->getS3Storage($config),
            'google_drive' => $this->getGoogleDriveStorage($config),
            'dropbox' => $this->getDropboxStorage($config),
            'ftp', 'sftp' => $this->getFTPStorage($config),
            'azure' => $this->getAzureStorage($config),
            'digitalocean', 'wasabi', 'backblaze', 'cloudflare_r2' => $this->getS3Storage($config), // S3-compatible
            default => Storage::disk('local'),
        };
    }

    /**
     * اختبار الاتصال
     */
    public function testConnection(): array
    {
        try {
            $driver = \App\Services\Backup\StorageFactory::create($this);
            $result = $driver->testConnection();
            
            return [
                'success' => $result,
                'message' => $result ? 'الاتصال ناجح' : 'فشل الاتصال',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'فشل الاتصال: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * الحصول على المساحة المتاحة
     */
    public function getAvailableSpace(): ?int
    {
        // سيتم تنفيذ هذا حسب نوع السائق
        return null;
    }

    /**
     * الحصول على الإعدادات (مفكوكة)
     */
    public function getDecryptedConfig(): array
    {
        try {
            return json_decode(Crypt::decryptString($this->config), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * حفظ الإعدادات (مشفرة)
     */
    public function setConfigAttribute($value)
    {
        if (is_array($value)) {
            // إزالة الحقول الفارغة للـ passwords
            $filtered = array_filter($value, function($v) {
                return $v !== null && $v !== '';
            });
            $this->attributes['config'] = Crypt::encryptString(json_encode($filtered));
        } else {
            $this->attributes['config'] = $value;
        }
    }

    /**
     * الحصول على S3 Storage
     */
    private function getS3Storage(array $config)
    {
        // سيتم تنفيذ هذا في Service
        return Storage::disk('s3');
    }

    /**
     * الحصول على Google Drive Storage
     */
    private function getGoogleDriveStorage(array $config)
    {
        // سيتم تنفيذ هذا في Service
        return Storage::disk('local');
    }

    /**
     * الحصول على Dropbox Storage
     */
    private function getDropboxStorage(array $config)
    {
        // سيتم تنفيذ هذا في Service
        return Storage::disk('local');
    }

    /**
     * الحصول على FTP Storage
     */
    private function getFTPStorage(array $config)
    {
        // سيتم تنفيذ هذا في Service
        return Storage::disk('local');
    }

    /**
     * الحصول على Azure Storage
     */
    private function getAzureStorage(array $config)
    {
        // سيتم تنفيذ هذا في Service
        return Storage::disk('local');
    }
}
