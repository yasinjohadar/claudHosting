<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupStorageConfig;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class BackupStorageService
{
    /**
     * تخزين نسخة
     */
    public function storeBackup(Backup $backup, string $filePath): string
    {
        $storage = $this->getStorage($backup->storage_driver);
        $storagePath = 'backups/' . $backup->id . '/' . basename($filePath);

        $storage->put($storagePath, file_get_contents($filePath));

        return $storagePath;
    }

    /**
     * الحصول على نسخة من التخزين
     */
    public function getBackupFromStorage(Backup $backup): string
    {
        $storage = $this->getStorage($backup->storage_driver);
        $tempPath = storage_path('app/temp/' . basename($backup->storage_path));

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        file_put_contents($tempPath, $storage->get($backup->storage_path));

        return $tempPath;
    }

    /**
     * حذف نسخة من التخزين
     */
    public function deleteBackupFromStorage(Backup $backup): bool
    {
        // إذا لم يكن هناك storage_path، لا يوجد شيء للحذف
        if (!$backup->storage_path) {
            return true;
        }

        try {
            $storage = $this->getStorage($backup->storage_driver);
            return $storage->delete($backup->storage_path);
        } catch (\Exception $e) {
            \Log::warning('Failed to delete backup from storage: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'storage_path' => $backup->storage_path,
            ]);
            return false;
        }
    }

    /**
     * قائمة النسخ في التخزين
     */
    public function listBackupsInStorage(string $driver): Collection
    {
        $storage = $this->getStorage($driver);
        return collect($storage->files('backups'));
    }

    /**
     * الحصول على Local Storage
     */
    public function getLocalStorage()
    {
        return Storage::disk('local');
    }

    /**
     * الحصول على S3 Storage
     */
    public function getS3Storage(array $config)
    {
        return Storage::disk('s3');
    }

    /**
     * الحصول على Google Drive Storage
     */
    public function getGoogleDriveStorage(array $config)
    {
        // يحتاج تثبيت: composer require masbug/flysystem-google-drive-ext
        return Storage::disk('local'); // مؤقت
    }

    /**
     * الحصول على Dropbox Storage
     */
    public function getDropboxStorage(array $config)
    {
        // يحتاج تثبيت: composer require spatie/flysystem-dropbox
        return Storage::disk('local'); // مؤقت
    }

    /**
     * الحصول على FTP Storage
     */
    public function getFTPStorage(array $config)
    {
        // يحتاج تثبيت: composer require league/flysystem-sftp-v3
        return Storage::disk('local'); // مؤقت
    }

    /**
     * الحصول على Azure Storage
     */
    public function getAzureStorage(array $config)
    {
        // يحتاج تثبيت: composer require league/flysystem-azure-blob-storage
        return Storage::disk('local'); // مؤقت
    }

    /**
     * اختبار اتصال التخزين
     */
    public function testStorageConnection(string $driver, array $config): bool
    {
        try {
            $storage = match($driver) {
                'local' => $this->getLocalStorage(),
                's3' => $this->getS3Storage($config),
                'google_drive' => $this->getGoogleDriveStorage($config),
                'dropbox' => $this->getDropboxStorage($config),
                'ftp' => $this->getFTPStorage($config),
                'azure' => $this->getAzureStorage($config),
                default => throw new \Exception('نوع التخزين غير معروف'),
            };

            $testFile = 'backup_test_' . time() . '.txt';
            $storage->put($testFile, 'test');
            $storage->delete($testFile);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * الحصول على Storage حسب السائق
     */
    private function getStorage(string $driver)
    {
        $config = BackupStorageConfig::where('driver', $driver)
                                     ->where('is_active', true)
                                     ->orderBy('priority', 'desc')
                                     ->first();

        if (!$config) {
            return Storage::disk('local');
        }

        try {
            return $config->getStorageInstance();
        } catch (\Exception $e) {
            \Log::error('Error getting storage instance: ' . $e->getMessage());
            return Storage::disk('local');
        }
    }
}

