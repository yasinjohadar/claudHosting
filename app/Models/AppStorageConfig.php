<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class AppStorageConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'config',
        'is_active',
        'priority',
        'max_backups', // للاستخدام مع النسخ الاحتياطية
        'redundancy',
        'pricing_config',
        'monthly_budget',
        'cost_alert_threshold',
        'cdn_url',
        'file_types',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_backups' => 'integer',
        'redundancy' => 'boolean',
        'pricing_config' => 'array',
        'monthly_budget' => 'decimal:2',
        'cost_alert_threshold' => 'decimal:2',
        'file_types' => 'array',
    ];

    /**
     * أنواع السواق
     */
    public const DRIVERS = [
        'local' => 'Local Storage',
        's3' => 'Amazon S3',
        'google_drive' => 'Google Drive',
        'dropbox' => 'Dropbox',
        'ftp' => 'FTP',
        'sftp' => 'SFTP',
        'azure' => 'Azure Blob Storage',
        'digitalocean' => 'DigitalOcean Spaces',
        'wasabi' => 'Wasabi',
        'backblaze' => 'Backblaze B2',
        'cloudflare_r2' => 'Cloudflare R2',
        'bunny' => 'Bunny Storage',
    ];

    /**
     * العلاقة مع منشئ الإعداد
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع الإحصائيات
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(AppStorageAnalytic::class, 'storage_config_id');
    }

    /**
     * العلاقة مع Disk Mappings
     */
    public function diskMappings(): HasMany
    {
        return $this->hasMany(StorageDiskMapping::class, 'primary_storage_id');
    }

    /**
     * العلاقة مع Fallback Disk Mappings
     */
    public function fallbackDiskMappings(): HasMany
    {
        return $this->hasMany(StorageDiskMapping::class, 'fallback_storage_ids');
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
     * اختبار الاتصال
     */
    public function testConnection(): array
    {
        try {
            $disk = \App\Services\Storage\AppStorageFactory::create($this);
            // محاولة كتابة ملف اختبار
            $testPath = 'test_' . time() . '.txt';
            $testContent = 'test';
            $result = $disk->put($testPath, $testContent);
            
            if ($result) {
                // حذف الملف الاختباري
                $disk->delete($testPath);
                return [
                    'success' => true,
                    'message' => 'الاتصال ناجح',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'فشل الاتصال',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'فشل الاتصال: ' . $e->getMessage(),
            ];
        }
    }
}
