<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Models\BackupLog;
use App\Services\Backup\BackupStorageService;
use App\Services\Backup\BackupCompressionService;
use App\Services\Backup\BackupNotificationService;
use App\Services\Backup\StorageManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupService
{
    public function __construct(
        private BackupStorageService $storageService,
        private BackupCompressionService $compressionService,
        private BackupNotificationService $notificationService,
        private StorageManager $storageManager
    ) {}

    /**
     * إنشاء نسخة احتياطية
     */
    public function createBackup(array $options): Backup
    {
        // إذا تم تمرير backup_id، استخدم النسخة الموجودة
        $backup = null;
        if (isset($options['backup_id'])) {
            $backup = Backup::find($options['backup_id']);
            if (!$backup) {
                throw new \Exception('النسخة الاحتياطية غير موجودة');
            }
        }
        
        // الحصول على إعدادات التخزين من AppStorageConfig إذا تم تمرير storage_config_id
        $storageDriver = $options['storage_driver'] ?? 'local';
        $storageConfigId = $options['storage_config_id'] ?? null;
        
        if ($storageConfigId) {
            $storageConfig = \App\Models\AppStorageConfig::find($storageConfigId);
            if ($storageConfig) {
                $storageDriver = $storageConfig->driver;
            }
        }
        
        // إنشاء نسخة جديدة إذا لم يتم تمرير backup_id
        if (!$backup) {
        $backup = Backup::create([
            'name' => $options['name'] ?? 'backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => $options['type'] ?? 'manual',
            'backup_type' => $options['backup_type'] ?? 'full',
                'storage_driver' => $storageDriver,
                'storage_config_id' => $storageConfigId, // إضافة ربط مع AppStorageConfig
            'storage_path' => null, // سيتم تعيينه بعد الرفع
            'file_path' => null, // سيتم تعيينه بعد الإنشاء
            'compression_type' => $options['compression_type'] ?? 'zip',
            'status' => 'pending',
            'retention_days' => $options['retention_days'] ?? 30,
            'created_by' => $options['created_by'] ?? auth()->id(),
            'schedule_id' => $options['schedule_id'] ?? null,
        ]);
        }

        // تحديث حالة النسخة إلى running
        $backup->update([
            'expires_at' => $backup->calculateExpiresAt(),
            'started_at' => now(),
            'status' => 'running',
        ]);

        // تحميل storageConfig relationship
        $backup->load('storageConfig');

        try {
            $this->log($backup, 'info', 'بدء عملية النسخ الاحتياطي');

            $filePath = match($backup->backup_type) {
                'full' => $this->createFullBackup($backup, $options),
                'database' => $this->createDatabaseBackup($backup, $options),
                'files' => $this->createFilesBackup($backup, $options),
                'config' => $this->createConfigBackup($backup, $options),
                default => throw new \Exception('نوع النسخ غير معروف: ' . $backup->backup_type),
            };

            $this->log($backup, 'info', 'تم إنشاء ملف النسخة بنجاح: ' . $filePath);

            // حفظ مسار الملف الأصلي قبل الضغط (لحذفه لاحقاً بعد الرفع)
            $originalFilePath = $filePath;

            // تحديث file_path قبل الضغط
            $backup->update(['file_path' => $filePath]);

            // ضغط الملف
            $this->log($backup, 'info', 'بدء ضغط ملف النسخة...');
            $compressedPath = $this->compressionService->compress($backup, $backup->compression_type);
            $this->log($backup, 'info', 'تم ضغط ملف النسخة بنجاح: ' . $compressedPath);

            // رفع الملف إلى التخزين مع Auto-failover
            $this->log($backup, 'info', 'بدء رفع ملف النسخة إلى التخزين...');
            $this->storageManager->storeWithFailover($backup, $compressedPath);
            $this->log($backup, 'info', 'تم رفع ملف النسخة إلى التخزين بنجاح');
            
            // تحديث backup model من قاعدة البيانات لضمان تحديث storageConfig relationship
            $backup->refresh();
            $backup->load('storageConfig');
            
            // تخزين في أماكن متعددة إذا كان مفعلاً
            // التحقق من وجود AppStorageConfig مع redundancy = true قبل الاستدعاء
            $redundancyConfigs = \App\Models\AppStorageConfig::where('is_active', true)
                ->where('redundancy', true)
                ->get();
            
            // تخطي المكان الأساسي من قائمة Redundancy
            if ($backup->storageConfig) {
                $redundancyConfigs = $redundancyConfigs->filter(function ($config) use ($backup) {
                    return $config->id !== $backup->storageConfig->id;
                });
            }
            
            $redundancyResult = [
                'successful' => [],
                'failed' => [],
            ];
            
            // استدعاء storeToMultipleStorages فقط إذا كان هناك configs مع redundancy = true
            if ($redundancyConfigs->isNotEmpty()) {
                $this->log($backup, 'info', 'بدء التخزين في أماكن Redundancy...');
                $redundancyResult = $this->storageManager->storeToMultipleStorages($backup, $compressedPath);
                $this->log($backup, 'info', 'اكتمل التخزين في أماكن Redundancy');
            } else {
                $this->log($backup, 'info', 'لا توجد أماكن تخزين مع تفعيل Redundancy، تم تخطي التخزين المتعدد');
            }
            
            $storagePath = $backup->storage_path;

            $duration = now()->diffInSeconds($backup->started_at);
            
            // الحصول على حجم الملف - استخدام filesize() لأن compressedPath هو مسار كامل
            if (!file_exists($compressedPath)) {
                throw new \Exception('ملف النسخة الاحتياطية غير موجود: ' . $compressedPath);
            }
            
            $fileSize = filesize($compressedPath);
            if ($fileSize === false) {
                throw new \Exception('فشل في الحصول على حجم ملف النسخة الاحتياطية: ' . $compressedPath);
            }

            // إعداد metadata مع معلومات Redundancy
            $metadata = $backup->metadata ?? [];
            if (!empty($redundancyResult['successful'])) {
                $metadata['redundancy_storages'] = $redundancyResult['successful'];
            }
            if (!empty($redundancyResult['failed'])) {
                $metadata['redundancy_failed'] = $redundancyResult['failed'];
            }

            $backup->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration' => $duration,
                'file_path' => $compressedPath,
                'storage_path' => $storagePath,
                'file_size' => $fileSize,
                'metadata' => $metadata,
            ]);

            // تحديث backup model مرة أخرى بعد update
            $backup->refresh();
            $backup->load('storageConfig');

            // حذف الملف المحلي المؤقت بعد الرفع الناجح إلى التخزين السحابي
            // يتم الحذف فقط إذا كان التخزين الأساسي ليس محلياً
            // استخدام storage_driver مباشرة بدلاً من storageConfig->driver
            $storageDriver = $backup->storage_driver;
            $shouldDeleteLocal = false;
            $deleteReason = '';
            
            Log::info("Checking if local file should be deleted", [
                'backup_id' => $backup->id,
                'storage_driver' => $storageDriver,
                'compressed_path' => $compressedPath,
            ]);
            
            if ($storageDriver === 'local' || empty($storageDriver)) {
                $deleteReason = $storageDriver === 'local' ? 'التخزين محلي' : 'storage_driver فارغ';
                $this->log($backup, 'info', 'التخزين محلي، تم الاحتفاظ بالملف المحلي');
                Log::info("Keeping local file: {$deleteReason}", [
                    'backup_id' => $backup->id,
                    'compressed_path' => $compressedPath,
                ]);
            } else {
                $shouldDeleteLocal = true;
                Log::info("Local file should be deleted (storage_driver: {$storageDriver})", [
                    'backup_id' => $backup->id,
                    'storage_driver' => $storageDriver,
                ]);
            }
            
            if ($shouldDeleteLocal && $compressedPath) {
                // تنظيف file system cache
                clearstatcache(true, $compressedPath);
                
                if (file_exists($compressedPath)) {
                    Log::info("Attempting to delete local file", [
                        'backup_id' => $backup->id,
                        'compressed_path' => $compressedPath,
                        'file_exists' => true,
                        'is_readable' => is_readable($compressedPath),
                        'is_writable' => is_writable($compressedPath),
                        'file_permissions' => substr(sprintf('%o', fileperms($compressedPath)), -4),
                    ]);
                    
                    try {
                        // التحقق من أن الملف قابل للكتابة قبل الحذف
                        if (is_writable($compressedPath) || is_writable(dirname($compressedPath))) {
                            $deleted = unlink($compressedPath);
                            
                            if ($deleted) {
                                $this->log($backup, 'info', 'تم حذف الملف المحلي المؤقت بعد الرفع الناجح إلى السحابة');
                                Log::info("Successfully deleted local temporary file after upload", [
                                    'backup_id' => $backup->id,
                                    'storage_driver' => $storageDriver,
                                    'compressed_path' => $compressedPath,
                                    'storage_config_id' => $backup->storage_config_id,
                                ]);
                                
                                // التحقق مرة أخرى للتأكد من الحذف
                                clearstatcache(true, $compressedPath);
                                if (file_exists($compressedPath)) {
                                    Log::warning("File still exists after unlink()", [
                                        'backup_id' => $backup->id,
                                        'compressed_path' => $compressedPath,
                                    ]);
                                } else {
                                    Log::info("File confirmed deleted", [
                                        'backup_id' => $backup->id,
                                        'compressed_path' => $compressedPath,
                                    ]);
                                }
                            } else {
                                $errorMsg = 'unlink() returned false';
                                $this->log($backup, 'warning', 'فشل حذف الملف المحلي المؤقت: ' . $errorMsg);
                                Log::error("Failed to delete local file: unlink() returned false", [
                                    'backup_id' => $backup->id,
                                    'compressed_path' => $compressedPath,
                                    'storage_driver' => $storageDriver,
                                ]);
                            }
                        } else {
                            $errorMsg = 'الملف أو المجلد غير قابل للكتابة';
                            $this->log($backup, 'warning', 'فشل حذف الملف المحلي المؤقت: ' . $errorMsg);
                            Log::error("Cannot delete local file: file or directory is not writable", [
                                'backup_id' => $backup->id,
                                'compressed_path' => $compressedPath,
                                'file_writable' => is_writable($compressedPath),
                                'dir_writable' => is_writable(dirname($compressedPath)),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->log($backup, 'warning', 'فشل حذف الملف المحلي المؤقت: ' . $e->getMessage());
                        Log::error("Exception while deleting local file", [
                            'backup_id' => $backup->id,
                            'compressed_path' => $compressedPath,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // لا نعتبر هذا خطأ فادحاً، الملف موجود في التخزين السحابي
                    }
                } else {
                    Log::info("Local file does not exist (already deleted or never created)", [
                        'backup_id' => $backup->id,
                        'compressed_path' => $compressedPath,
                    ]);
                }
            }

            // حذف الملف الأصلي (قبل الضغط) بعد الرفع الناجح إلى السحابة
            // يتم الحذف فقط إذا كان التخزين ليس محلياً والملف الأصلي مختلف عن المضغوط
            if ($shouldDeleteLocal && isset($originalFilePath) && $originalFilePath && $originalFilePath !== $compressedPath) {
                clearstatcache(true, $originalFilePath);
                
                if (file_exists($originalFilePath)) {
                    Log::info("Attempting to delete original file (before compression)", [
                        'backup_id' => $backup->id,
                        'original_file_path' => $originalFilePath,
                        'compressed_path' => $compressedPath,
                        'file_exists' => true,
                        'is_readable' => is_readable($originalFilePath),
                        'is_writable' => is_writable($originalFilePath),
                    ]);
                    
                    try {
                        // التحقق من أن الملف قابل للكتابة قبل الحذف
                        if (is_writable($originalFilePath) || is_writable(dirname($originalFilePath))) {
                            $deleted = unlink($originalFilePath);
                            
                            if ($deleted) {
                                $this->log($backup, 'info', 'تم حذف الملف الأصلي بعد الضغط والرفع الناجح');
                                Log::info("Successfully deleted original file after compression and upload", [
                                    'backup_id' => $backup->id,
                                    'storage_driver' => $storageDriver,
                                    'original_file_path' => $originalFilePath,
                                    'compressed_path' => $compressedPath,
                                ]);
                                
                                // التحقق مرة أخرى للتأكد من الحذف
                                clearstatcache(true, $originalFilePath);
                                if (file_exists($originalFilePath)) {
                                    Log::warning("Original file still exists after unlink()", [
                                        'backup_id' => $backup->id,
                                        'original_file_path' => $originalFilePath,
                                    ]);
                                } else {
                                    Log::info("Original file confirmed deleted", [
                                        'backup_id' => $backup->id,
                                        'original_file_path' => $originalFilePath,
                                    ]);
                                }
                            } else {
                                $errorMsg = 'unlink() returned false for original file';
                                $this->log($backup, 'warning', 'فشل حذف الملف الأصلي: ' . $errorMsg);
                                Log::error("Failed to delete original file: unlink() returned false", [
                                    'backup_id' => $backup->id,
                                    'original_file_path' => $originalFilePath,
                                    'storage_driver' => $storageDriver,
                                ]);
                            }
                        } else {
                            $errorMsg = 'الملف الأصلي أو المجلد غير قابل للكتابة';
                            $this->log($backup, 'warning', 'فشل حذف الملف الأصلي: ' . $errorMsg);
                            Log::error("Cannot delete original file: file or directory is not writable", [
                                'backup_id' => $backup->id,
                                'original_file_path' => $originalFilePath,
                                'file_writable' => is_writable($originalFilePath),
                                'dir_writable' => is_writable(dirname($originalFilePath)),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->log($backup, 'warning', 'فشل حذف الملف الأصلي: ' . $e->getMessage());
                        Log::error("Exception while deleting original file", [
                            'backup_id' => $backup->id,
                            'original_file_path' => $originalFilePath,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // لا نعتبر هذا خطأ فادحاً، الملف موجود في التخزين السحابي
                    }
                } else {
                    Log::info("Original file does not exist (already deleted or never created)", [
                        'backup_id' => $backup->id,
                        'original_file_path' => $originalFilePath,
                    ]);
                }
            } elseif (isset($originalFilePath) && $originalFilePath && $originalFilePath !== $compressedPath) {
                Log::info("Keeping original file (storage is local or should not delete)", [
                    'backup_id' => $backup->id,
                    'original_file_path' => $originalFilePath,
                    'storage_driver' => $storageDriver,
                ]);
            }

            $this->log($backup, 'info', 'اكتملت عملية النسخ الاحتياطي بنجاح');
            $this->notificationService->notifyBackupCompleted($backup);

            return $backup->fresh();
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorDetails = [
                'message' => $errorMessage,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('Backup creation failed', [
                'backup_id' => $backup->id,
                'backup_name' => $backup->name,
                'backup_type' => $backup->backup_type,
                'error' => $errorDetails,
            ]);

            $backup->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $errorMessage,
            ]);

            $this->log($backup, 'error', 'فشلت عملية النسخ الاحتياطي: ' . $errorMessage);
            $this->log($backup, 'error', 'تفاصيل الخطأ: ' . json_encode($errorDetails, JSON_UNESCAPED_UNICODE));
            
            try {
                $this->notificationService->notifyBackupFailed($backup, $errorMessage);
            } catch (\Exception $notificationException) {
                Log::error('Failed to send backup failure notification', [
                    'backup_id' => $backup->id,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * إنشاء نسخة كاملة
     */
    public function createFullBackup(Backup $backup, array $options): string
    {
        $this->log($backup, 'info', 'بدء نسخ قاعدة البيانات');
        $dbPath = $this->createDatabaseBackup($backup, $options);

        $this->log($backup, 'info', 'بدء نسخ الملفات');
        $filesPath = $this->createFilesBackup($backup, $options);

        $this->log($backup, 'info', 'بدء نسخ الإعدادات');
        $configPath = $this->createConfigBackup($backup, $options);

        // دمج جميع الملفات في مجلد واحد
        $backupDir = storage_path('app/backups/temp/' . $backup->id);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        copy($dbPath, $backupDir . '/database.sql');
        $this->extractToDirectory($filesPath, $backupDir . '/files');
        $this->extractToDirectory($configPath, $backupDir . '/config');

        return $backupDir;
    }

    /**
     * إنشاء نسخة قاعدة البيانات
     */
    public function createDatabaseBackup(Backup $backup, array $options): string
    {
        $filename = 'database_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $backupDir = storage_path('app/backups');
        $path = $backupDir . '/' . $filename;

        // التأكد من وجود المجلد
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        // استخدام Laravel DB facade بدلاً من mysqldump
        try {
            $tables = DB::select('SHOW TABLES');
            $databaseName = $database;
            $tablesKey = 'Tables_in_' . $databaseName;
            
            $sqlContent = "-- Database Backup\n";
            $sqlContent .= "-- Generated: " . now()->toDateTimeString() . "\n";
            $sqlContent .= "-- Database: {$databaseName}\n\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $tableName = $table->$tablesKey;
                
                // الحصول على CREATE TABLE statement
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $sqlContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sqlContent .= $createTable[0]->{'Create Table'} . ";\n\n";

                // الحصول على البيانات
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sqlContent .= "LOCK TABLES `{$tableName}` WRITE;\n";
                    
                    // الحصول على أسماء الأعمدة من أول صف
                    $firstRow = (array) $rows->first();
                    $columns = array_map(function ($col) {
                        return "`{$col}`";
                    }, array_keys($firstRow));
                    $columnsStr = implode(", ", $columns);
                    
                    $values = [];
                    $chunkSize = 100;
                    $currentChunk = 0;
                    
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        
                        $valArray = array_map(function ($val) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return DB::getPdo()->quote($val);
                        }, array_values($rowArray));
                        
                        $values[] = "(" . implode(", ", $valArray) . ")";
                        $currentChunk++;
                        
                        // كتابة كل 100 صف
                        if ($currentChunk >= $chunkSize) {
                            $valuesStr = implode(",\n", $values);
                            $sqlContent .= "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES\n{$valuesStr};\n\n";
                            $values = [];
                            $currentChunk = 0;
                        }
                    }
                    
                    // كتابة الصفوف المتبقية
                    if (!empty($values)) {
                        $valuesStr = implode(",\n", $values);
                        $sqlContent .= "INSERT INTO `{$tableName}` ({$columnsStr}) VALUES\n{$valuesStr};\n\n";
                    }
                    
                    $sqlContent .= "UNLOCK TABLES;\n\n";
                }
            }

            $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($path, $sqlContent);

            if (!file_exists($path) || filesize($path) === 0) {
                throw new \Exception('فشل في إنشاء ملف النسخة الاحتياطية - الملف فارغ أو غير موجود');
            }

            $this->log($backup, 'info', 'تم نسخ قاعدة البيانات بنجاح');

            return $path;
        } catch (\Exception $e) {
            Log::error('Database backup failed: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('فشل في نسخ قاعدة البيانات: ' . $e->getMessage());
        }
    }

    /**
     * إنشاء نسخة الملفات
     */
    public function createFilesBackup(Backup $backup, array $options): string
    {
        $filesDir = storage_path('app/public');
        $backupDir = storage_path('app/backups/temp/files_' . $backup->id);

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->copyDirectory($filesDir, $backupDir);

        $this->log($backup, 'info', 'تم نسخ الملفات بنجاح');

        return $backupDir;
    }

    /**
     * إنشاء نسخة الإعدادات
     */
    public function createConfigBackup(Backup $backup, array $options): string
    {
        $configFiles = [
            '.env',
            'config/app.php',
            'config/database.php',
            'config/mail.php',
        ];

        $backupDir = storage_path('app/backups/temp/config_' . $backup->id);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        foreach ($configFiles as $file) {
            $sourcePath = base_path($file);
            if (file_exists($sourcePath)) {
                $destPath = $backupDir . '/' . basename($file);
                copy($sourcePath, $destPath);
            }
        }

        $this->log($backup, 'info', 'تم نسخ الإعدادات بنجاح');

        return $backupDir;
    }

    /**
     * حذف نسخة
     */
    public function deleteBackup(Backup $backup): bool
    {
        try {
            // حذف الملف من التخزين الأساسي
            $this->log($backup, 'info', 'بدء حذف النسخة من التخزين الأساسي...');
            $this->storageManager->delete($backup);
            $this->log($backup, 'info', 'تم حذف النسخة من التخزين الأساسي');
            
            // حذف المجلد في السحابة بعد حذف الملف
            if ($backup->storageConfig && $backup->storageConfig->driver !== 'local' && $backup->storage_path) {
                try {
                    $folderPath = dirname($backup->storage_path); // backups/{backup_id}
                    if ($folderPath && $folderPath !== '.') {
                        $driver = \App\Services\Backup\StorageFactory::create($backup->storageConfig);
                        // محاولة حذف المجلد باستخدام Laravel Storage
                        $disk = \App\Services\Storage\AppStorageFactory::create($backup->storageConfig);
                        if ($disk->exists($folderPath)) {
                            // التحقق من أن المجلد فارغ قبل الحذف
                            $files = $disk->files($folderPath);
                            if (empty($files)) {
                                $disk->deleteDirectory($folderPath);
                                $this->log($backup, 'info', 'تم حذف المجلد الفارغ من التخزين السحابي');
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->log($backup, 'warning', 'فشل حذف المجلد من التخزين السحابي: ' . $e->getMessage());
                    Log::warning("Failed to delete backup folder from cloud storage: {$e->getMessage()}");
                    // لا نعتبر هذا خطأ فادحاً
                }
            }

            // حذف الملف من أماكن Redundancy
            $metadata = $backup->metadata ?? [];
            if (!empty($metadata['redundancy_storages']) && is_array($metadata['redundancy_storages'])) {
                $this->log($backup, 'info', 'بدء حذف النسخة من أماكن Redundancy...');
                
                foreach ($metadata['redundancy_storages'] as $redundancyStorage) {
                    try {
                        // الحصول على storage config
                        $storageConfigId = $redundancyStorage['storage_config_id'] ?? null;
                        $storagePath = $redundancyStorage['storage_path'] ?? null;
                        
                        if ($storageConfigId && $storagePath) {
                            $storageConfig = \App\Models\AppStorageConfig::find($storageConfigId);
                            
                            if ($storageConfig && $storageConfig->is_active) {
                                $driver = \App\Services\Backup\StorageFactory::create($storageConfig);
                                
                                if ($driver->delete($storagePath)) {
                                    $this->log($backup, 'info', "تم حذف النسخة من: {$storageConfig->name}");
                                    
                                    // حذف المجلد الفارغ بعد حذف الملف
                                    try {
                                        $folderPath = dirname($storagePath);
                                        if ($folderPath && $folderPath !== '.' && $storageConfig->driver !== 'local') {
                                            $disk = \App\Services\Storage\AppStorageFactory::create($storageConfig);
                                            if ($disk->exists($folderPath)) {
                                                $files = $disk->files($folderPath);
                                                if (empty($files)) {
                                                    $disk->deleteDirectory($folderPath);
                                                    $this->log($backup, 'info', "تم حذف المجلد الفارغ من: {$storageConfig->name}");
                                                }
                                            }
                                        }
                                    } catch (\Exception $folderException) {
                                        Log::warning("Failed to delete redundancy folder: {$folderException->getMessage()}");
                                        // لا نعتبر هذا خطأ فادحاً
                                    }
                                } else {
                                    $this->log($backup, 'warning', "فشل حذف النسخة من: {$storageConfig->name}");
                                }
                            } else {
                                $this->log($backup, 'warning', "تخزين Redundancy غير موجود أو غير نشط: {$storageConfigId}");
                            }
                        }
                    } catch (\Exception $e) {
                        $storageName = $redundancyStorage['storage_config_name'] ?? 'Unknown';
                        $this->log($backup, 'error', "خطأ في حذف النسخة من {$storageName}: " . $e->getMessage());
                        Log::warning("Failed to delete backup from redundancy storage: {$storageName}", [
                            'backup_id' => $backup->id,
                            'storage_config_id' => $redundancyStorage['storage_config_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        // نستمر في حذف باقي الأماكن حتى لو فشل أحدها
                    }
                }
                
                $this->log($backup, 'info', 'اكتمل حذف النسخة من أماكن Redundancy');
            }

            // حذف الملف المحلي المضغوط - file_path هو مسار كامل (absolute path)
            if ($backup->file_path && file_exists($backup->file_path)) {
                try {
                @unlink($backup->file_path);
                    $this->log($backup, 'info', 'تم حذف الملف المحلي المضغوط');
                } catch (\Exception $e) {
                    $this->log($backup, 'warning', 'فشل حذف الملف المحلي المضغوط: ' . $e->getMessage());
                    Log::warning("Failed to delete local compressed file: {$backup->file_path} - {$e->getMessage()}");
                }
            }

            $backup->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting backup: ' . $e->getMessage(), [
                'backup_id' => $backup->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('فشل في حذف النسخة: ' . $e->getMessage());
        }
    }

    /**
     * تحميل نسخة
     */
    public function downloadBackup(Backup $backup): BinaryFileResponse
    {
        $fileContent = $this->storageManager->retrieve($backup);
        $tempFilePath = storage_path('app/temp/download_' . $backup->id . '_' . time() . '.' . $backup->compression_type);
        
        if (!is_dir(dirname($tempFilePath))) {
            mkdir(dirname($tempFilePath), 0755, true);
        }
        
        file_put_contents($tempFilePath, $fileContent);
        $filePath = $tempFilePath;

        if (!file_exists($filePath)) {
            throw new \Exception('الملف غير موجود');
        }

        return response()->download($filePath, $backup->name . '.' . $backup->compression_type);
    }

    /**
     * استعادة نسخة
     */
    public function restoreBackup(Backup $backup, array $options = []): bool
    {
        $preRestoreBackupPath = null;
        $preRestoreFilesPath = null;
        $preRestoreConfigPath = null;

        try {
            $this->log($backup, 'info', 'بدء عملية الاستعادة');

            // إنشاء backup تلقائي قبل الاستعادة
            $this->log($backup, 'info', 'إنشاء نسخة احتياطية من البيانات الحالية قبل الاستعادة...');
            $preRestoreBackup = $this->createPreRestoreBackup($backup->backup_type);
            if ($preRestoreBackup) {
                $this->log($backup, 'info', 'تم إنشاء نسخة احتياطية من البيانات الحالية بنجاح');
                $preRestoreBackupPath = $preRestoreBackup['database'] ?? null;
                $preRestoreFilesPath = $preRestoreBackup['files'] ?? null;
                $preRestoreConfigPath = $preRestoreBackup['config'] ?? null;
            }

            $this->log($backup, 'info', 'تحميل النسخة الاحتياطية من التخزين...');
            $fileContent = $this->storageManager->retrieve($backup);
            
            // التحقق من أن الملف تم تحميله فعلياً
            if (empty($fileContent)) {
                throw new \Exception('فشل تحميل النسخة: الملف فارغ');
            }
            
            $this->log($backup, 'info', 'تم تحميل النسخة من التخزين: ' . strlen($fileContent) . ' bytes');
            
            $tempFilePath = storage_path('app/temp/restore_' . $backup->id . '_' . time() . '.zip');
            
            if (!is_dir(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }
            
            $this->log($backup, 'info', 'حفظ النسخة في ملف مؤقت...');
            $bytesWritten = file_put_contents($tempFilePath, $fileContent);
            
            // التحقق من أن الملف تم حفظه فعلياً
            if ($bytesWritten === false || !file_exists($tempFilePath) || filesize($tempFilePath) === 0) {
                throw new \Exception('فشل حفظ النسخة في الملف المؤقت');
            }
            
            $this->log($backup, 'info', 'تم حفظ النسخة في الملف المؤقت: ' . filesize($tempFilePath) . ' bytes');
            $filePath = $tempFilePath;

            // فك الضغط
            $this->log($backup, 'info', 'بدء فك ضغط النسخة...');
            $extractedPath = $this->compressionService->decompress($filePath, storage_path('app/backups/restore_' . $backup->id));
            $this->log($backup, 'info', 'تم فك ضغط النسخة بنجاح');

            // حفظ مسار المجلد الأصلي للتنظيف لاحقاً
            $extractedDirectory = is_dir($extractedPath) ? $extractedPath : dirname($extractedPath);

            // البحث عن ملف SQL في المجلد المستخرج (لـ database backup)
            if ($backup->backup_type === 'database') {
                $sqlFile = $this->findSqlFile($extractedPath);
                if (!$sqlFile) {
                    throw new \Exception('لم يتم العثور على ملف SQL في النسخة المستخرجة. المسار: ' . $extractedPath);
                }
                
                // التحقق من أن ملف SQL موجود
                if (!file_exists($sqlFile)) {
                    throw new \Exception('ملف SQL غير موجود: ' . $sqlFile);
                }
                
                $this->log($backup, 'info', 'تم العثور على ملف SQL: ' . basename($sqlFile) . ' (' . filesize($sqlFile) . ' bytes)');
                $extractedPath = $sqlFile; // استخدام ملف SQL مباشرة
            }

            // استعادة حسب النوع
            $this->log($backup, 'info', 'بدء استعادة ' . $backup->backup_type . '...');
            match($backup->backup_type) {
                'database' => $this->restoreDatabase($extractedPath),
                'files' => $this->restoreFiles($extractedPath),
                'config' => $this->restoreConfig($extractedPath),
                'full' => $this->restoreFull($extractedPath),
                default => throw new \Exception('نوع النسخ غير معروف'),
            };
            $this->log($backup, 'info', 'تم استعادة ' . $backup->backup_type . ' بنجاح');

            // تنظيف الملفات المؤقتة - استخدام المجلد الأصلي
            $this->cleanupRestoreTempFiles($backup, $tempFilePath, $extractedDirectory ?? $extractedPath);

            $this->log($backup, 'info', 'اكتملت عملية الاستعادة بنجاح');

            return true;
        } catch (\Exception $e) {
            $this->log($backup, 'error', 'فشلت عملية الاستعادة: ' . $e->getMessage());
            
            // محاولة استعادة من backup التلقائي في حالة الفشل
            if ($preRestoreBackupPath || $preRestoreFilesPath || $preRestoreConfigPath) {
                $this->log($backup, 'warning', 'محاولة استعادة البيانات من النسخة الاحتياطية التلقائية...');
                try {
                    if ($preRestoreBackupPath && file_exists($preRestoreBackupPath)) {
                        $this->restoreDatabase($preRestoreBackupPath);
                        $this->log($backup, 'info', 'تم استعادة قاعدة البيانات من النسخة الاحتياطية التلقائية');
                    }
                    if ($preRestoreFilesPath && is_dir($preRestoreFilesPath)) {
                        $this->restoreFiles($preRestoreFilesPath);
                        $this->log($backup, 'info', 'تم استعادة الملفات من النسخة الاحتياطية التلقائية');
                    }
                    if ($preRestoreConfigPath && is_dir($preRestoreConfigPath)) {
                        $this->restoreConfig($preRestoreConfigPath);
                        $this->log($backup, 'info', 'تم استعادة الإعدادات من النسخة الاحتياطية التلقائية');
                    }
                } catch (\Exception $rollbackException) {
                    $this->log($backup, 'error', 'فشل استعادة البيانات من النسخة الاحتياطية التلقائية: ' . $rollbackException->getMessage());
                    Log::error('Rollback failed: ' . $rollbackException->getMessage());
                }
            }
            
            throw $e;
        }
    }

    /**
     * إنشاء backup تلقائي قبل الاستعادة
     */
    private function createPreRestoreBackup(string $backupType): ?array
    {
        $backups = [];
        $timestamp = date('Y-m-d_H-i-s');

        try {
            // Backup قاعدة البيانات
            if (in_array($backupType, ['database', 'full'])) {
                $dbBackupPath = storage_path('app/temp/pre_restore_db_' . $timestamp . '.sql');
                $this->createQuickDatabaseBackup($dbBackupPath);
                $backups['database'] = $dbBackupPath;
            }

            // Backup الملفات
            if (in_array($backupType, ['files', 'full'])) {
                $filesBackupPath = storage_path('app/temp/pre_restore_files_' . $timestamp);
                $this->createQuickFilesBackup($filesBackupPath);
                $backups['files'] = $filesBackupPath;
            }

            // Backup الإعدادات
            if (in_array($backupType, ['config', 'full'])) {
                $configBackupPath = storage_path('app/temp/pre_restore_config_' . $timestamp);
                $this->createQuickConfigBackup($configBackupPath);
                $backups['config'] = $configBackupPath;
            }

            return $backups;
        } catch (\Exception $e) {
            Log::warning('Failed to create pre-restore backup: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * إنشاء backup سريع لقاعدة البيانات
     */
    private function createQuickDatabaseBackup(string $filePath): void
    {
        $connection = config('database.connections.mysql');
        $database = $connection['database'];
        $username = $connection['username'];
        $password = $connection['password'];
        $host = $connection['host'];
        $port = $connection['port'] ?? 3306;

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $mysqldumpCommand = $isWindows ? 'mysqldump.exe' : 'mysqldump';

        $command = sprintf(
            '%s --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($mysqldumpCommand),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            escapeshellarg($filePath)
        );

        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            throw new \Exception('فشل في إنشاء backup لقاعدة البيانات: ' . implode("\n", $output));
        }
    }

    /**
     * إنشاء backup سريع للملفات
     */
    private function createQuickFilesBackup(string $destPath): void
    {
        $sourcePath = storage_path('app/public');
        
        if (!is_dir($sourcePath)) {
            return;
        }

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $this->copyDirectory($sourcePath, $destPath);
    }

    /**
     * إنشاء backup سريع للإعدادات
     */
    private function createQuickConfigBackup(string $destPath): void
    {
        $sourcePath = base_path('config');
        
        if (!is_dir($sourcePath)) {
            return;
        }

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $this->copyDirectory($sourcePath, $destPath);
    }

    /**
     * تنظيف الملفات المؤقتة بعد الاستعادة
     */
    private function cleanupRestoreTempFiles(Backup $backup, string $tempFilePath, string $extractedPath): void
    {
        try {
            // حذف الملف المضغوط المؤقت فقط (ليس المجلد إذا كان ملف SQL)
            if (file_exists($tempFilePath) && is_file($tempFilePath)) {
                @unlink($tempFilePath);
                $this->log($backup, 'info', 'تم حذف الملف المضغوط المؤقت');
            }

            // حذف المجلد المستخرج فقط إذا كان مجلداً وليس ملفاً
            if (is_dir($extractedPath)) {
                $this->deleteDirectory($extractedPath);
                $this->log($backup, 'info', 'تم حذف المجلد المستخرج');
            } elseif (is_file($extractedPath)) {
                // إذا كان ملف SQL مباشرة، احذف المجلد الأصلي (parent directory)
                $parentDir = dirname($extractedPath);
                if (is_dir($parentDir) && strpos($parentDir, 'restore_') !== false) {
                    $this->deleteDirectory($parentDir);
                    $this->log($backup, 'info', 'تم حذف المجلد المستخرج (من ملف SQL)');
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup restore temp files: ' . $e->getMessage());
            $this->log($backup, 'warning', 'فشل حذف الملفات المؤقتة: ' . $e->getMessage());
        }
    }

    /**
     * حذف مجلد بشكل متكرر
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    /**
     * تنظيف النسخ المنتهية الصلاحية
     */
    public function cleanupExpiredBackups(): int
    {
        $expiredBackups = Backup::expired()->get();
        $count = 0;

        foreach ($expiredBackups as $backup) {
            try {
                $this->deleteBackup($backup);
                $count++;
            } catch (\Exception $e) {
                \Log::error('Error deleting expired backup: ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * الحصول على حجم النسخة
     */
    public function getBackupSize(Backup $backup): int
    {
        return $backup->file_size ?? 0;
    }

    /**
     * الحصول على إجمالي حجم النسخ
     */
    public function getTotalBackupSize(): int
    {
        return Backup::completed()->sum('file_size');
    }

    /**
     * الحصول على إحصائيات النسخ
     */
    public function getBackupStats(): array
    {
        return [
            'total' => Backup::count(),
            'completed' => Backup::completed()->count(),
            'failed' => Backup::failed()->count(),
            'pending' => Backup::where('status', 'pending')->count(),
            'running' => Backup::where('status', 'running')->count(),
            'total_size' => $this->getTotalBackupSize(),
            'expired' => Backup::expired()->count(),
        ];
    }

    /**
     * استعادة قاعدة البيانات
     */
    private function restoreDatabase(string $filePath): void
    {
        $connection = config('database.connections.mysql');
        $database = $connection['database'];
        $username = $connection['username'];
        $password = $connection['password'];
        $host = $connection['host'];
        $port = $connection['port'] ?? 3306;

        // تحديد أمر MySQL حسب نظام التشغيل
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $mysqlCommand = $isWindows ? 'mysql.exe' : 'mysql';

        // التحقق من وجود ملف SQL
        if (!file_exists($filePath)) {
            throw new \Exception("ملف قاعدة البيانات غير موجود: {$filePath}");
        }

        // التحقق من أن المسار هو ملف وليس مجلد
        if (is_dir($filePath)) {
            throw new \Exception("المسار المحدد هو مجلد وليس ملف: {$filePath}. يجب تحديد مسار ملف SQL مباشرة.");
        }

        if (!is_file($filePath)) {
            throw new \Exception("المسار المحدد ليس ملفاً صالحاً: {$filePath}");
        }

        // التحقق من أن الملف هو ملف SQL
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'sql') {
            Log::warning("تحذير: الملف ليس بامتداد .sql: {$filePath}");
        }

        $fileSize = filesize($filePath);
        Log::info("Starting database restore from: {$filePath} ({$fileSize} bytes)");

        // بناء الأمر
        $command = sprintf(
            '%s --user=%s --password=%s --host=%s --port=%s %s',
            escapeshellarg($mysqlCommand),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database)
        );

        // على Windows، نستخدم الملف مباشرة في الأمر بدلاً من pipe لتجنب Broken pipe
        // نستخدم --source option إذا كان متاحاً
        if ($isWindows) {
            // استخدام --source option (أفضل وأكثر استقراراً)
            $commandWithFile = $command . ' --source=' . escapeshellarg($filePath);
            
            Log::info("Executing MySQL restore command on Windows using --source option...");
            exec($commandWithFile . ' 2>&1', $output, $returnVar);
            
            Log::info("MySQL process completed with return code: {$returnVar}");
            
            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output) ?: 'خطأ غير معروف';
                Log::error("Database restore failed. Return code: {$returnVar}. Error: {$errorMessage}");
                throw new \Exception('فشل في استعادة قاعدة البيانات: ' . $errorMessage);
            }
            
            Log::info("Database restore completed successfully!");
        } else {
            // على Linux/Unix، نستخدم shell redirect
            Log::info("Executing MySQL restore command on Linux/Unix...");
            $command .= ' < ' . escapeshellarg($filePath);
            exec($command . ' 2>&1', $output, $returnVar);

            Log::info("MySQL process completed with return code: {$returnVar}");

            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output) ?: 'خطأ غير معروف';
                Log::error("Database restore failed. Return code: {$returnVar}. Error: {$errorMessage}");
                throw new \Exception('فشل في استعادة قاعدة البيانات: ' . $errorMessage);
            }

            Log::info("Database restore completed successfully!");
        }
    }

    /**
     * استعادة الملفات
     */
    private function restoreFiles(string $filePath): void
    {
        $destDir = storage_path('app/public');
        
        // التأكد من وجود المجلد الهدف
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // نسخ الملفات مع الحفاظ على البنية
        $this->copyDirectory($filePath, $destDir);
    }

    /**
     * استعادة الإعدادات
     */
    private function restoreConfig(string $filePath): void
    {
        if (!is_dir($filePath)) {
            throw new \Exception("مسار الإعدادات غير موجود: {$filePath}");
        }

        $files = glob($filePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
            $destPath = base_path('config/' . basename($file));
                
                // التأكد من وجود المجلد الهدف
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
            copy($file, $destPath);
            }
        }
    }

    /**
     * استعادة كاملة
     */
    private function restoreFull(string $filePath): void
    {
        $this->restoreDatabase($filePath . '/database.sql');
        $this->restoreFiles($filePath . '/files');
        $this->restoreConfig($filePath . '/config');
    }

    /**
     * نسخ مجلد
     */
    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item, $destPath);
            }
        }
    }

    /**
     * البحث عن ملف SQL في المجلد المستخرج
     */
    private function findSqlFile(string $directory): ?string
    {
        // التحقق من أن المسار موجود
        if (!is_dir($directory) && !is_file($directory)) {
            return null;
        }

        // إذا كان ملف SQL مباشرة
        if (is_file($directory) && pathinfo($directory, PATHINFO_EXTENSION) === 'sql') {
            return $directory;
        }

        // البحث عن ملف .sql في المجلد
        $files = glob($directory . '/*.sql');
        if (!empty($files)) {
            return $files[0];
        }
        
        // البحث في المجلدات الفرعية
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'sql') {
                    return $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error searching for SQL file: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * استخراج إلى مجلد
     */
    private function extractToDirectory(string $archivePath, string $destDir): void
    {
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) === true) {
            $zip->extractTo($destDir);
            $zip->close();
        }
    }

    /**
     * إضافة سجل
     */
    private function log(Backup $backup, string $level, string $message, array $context = []): void
    {
        BackupLog::create([
            'backup_id' => $backup->id,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}

