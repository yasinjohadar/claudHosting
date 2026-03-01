<?php

namespace App\Services\Backup;

use App\Models\Backup;
use ZipArchive;

class BackupCompressionService
{
    /**
     * ضغط النسخة
     */
    public function compress(Backup $backup, string $type = 'zip'): string
    {
        $source = $backup->file_path;
        if (!$source || !file_exists($source)) {
            throw new \Exception('مسار الملف غير موجود');
        }

        return match($type) {
            'zip' => $this->compressZip($source, $backup),
            'gzip' => $this->compressGzip($source, $backup),
            'tar' => $this->compressTar($source, $backup),
            default => throw new \Exception('نوع الضغط غير معروف'),
        };
    }

    /**
     * ضغط ZIP
     */
    public function compressZip(string $source, Backup $backup): string
    {
        // استخدام اسم النسخة مع إضافة extension
        $backupName = $this->getBackupFileName($backup, 'zip');
        $destination = storage_path('app/backups/' . $backupName);
        
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('فشل في إنشاء ملف ZIP');
        }

        if (is_dir($source)) {
            $this->addDirectoryToZip($source, $zip, '');
        } else {
            $zip->addFile($source, basename($source));
        }

        $zip->close();

        return $destination;
    }

    /**
     * ضغط GZIP
     */
    public function compressGzip(string $source, Backup $backup): string
    {
        // استخدام اسم النسخة مع إضافة extension
        $backupName = $this->getBackupFileName($backup, 'gz');
        $destination = storage_path('app/backups/' . $backupName);
        
        if (is_dir($source)) {
            // إذا كان مجلد، نضغطه أولاً كـ tar ثم gzip
            $tempTarName = 'temp_' . pathinfo($backupName, PATHINFO_FILENAME) . '_' . time() . '.tar';
            $tarPath = storage_path('app/backups/' . $tempTarName);
            // إنشاء tar مؤقت بدون استخدام compressTar (لأنه سيستخدم اسم النسخة)
            try {
                $phar = new \PharData($tarPath);
                $phar->buildFromDirectory($source);
            } catch (\Exception $e) {
                throw new \Exception('فشل في إنشاء ملف TAR مؤقت: ' . $e->getMessage());
            }
            $source = $tarPath;
        }

        $fp_in = fopen($source, 'rb');
        $fp_out = gzopen($destination, 'wb9');

        if (!$fp_in || !$fp_out) {
            throw new \Exception('فشل في إنشاء ملف GZIP');
        }

        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 8192));
        }

        fclose($fp_in);
        gzclose($fp_out);

        if (isset($tarPath) && file_exists($tarPath)) {
            unlink($tarPath);
        }

        return $destination;
    }

    /**
     * ضغط TAR
     */
    public function compressTar(string $source, Backup $backup): string
    {
        // استخدام اسم النسخة مع إضافة extension
        $backupName = $this->getBackupFileName($backup, 'tar');
        $destination = storage_path('app/backups/' . $backupName);
        
        try {
            $phar = new \PharData($destination);
            
            if (is_dir($source)) {
                $phar->buildFromDirectory($source);
            } else {
                $phar->addFile($source, basename($source));
            }
        } catch (\Exception $e) {
            throw new \Exception('فشل في إنشاء ملف TAR: ' . $e->getMessage());
        }

        return $destination;
    }

    /**
     * فك الضغط
     */
    public function decompress(string $file, string $destination): string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return match($extension) {
            'zip' => $this->decompressZip($file, $destination),
            'gz' => $this->decompressGzip($file, $destination),
            'tar' => $this->decompressTar($file, $destination),
            default => throw new \Exception('نوع الضغط غير معروف'),
        };
    }

    /**
     * فك ضغط ZIP
     */
    private function decompressZip(string $file, string $destination): string
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($file) === true) {
            $zip->extractTo($destination);
            $zip->close();
        }

        return $destination;
    }

    /**
     * فك ضغط GZIP
     */
    private function decompressGzip(string $file, string $destination): string
    {
        $fp_in = gzopen($file, 'rb');
        $fp_out = fopen($destination, 'wb');

        if (!$fp_in || !$fp_out) {
            throw new \Exception('فشل في فك ضغط GZIP');
        }

        while (!gzeof($fp_in)) {
            fwrite($fp_out, gzread($fp_in, 8192));
        }

        gzclose($fp_in);
        fclose($fp_out);

        return $destination;
    }

    /**
     * فك ضغط TAR
     */
    private function decompressTar(string $file, string $destination): string
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $phar = new \PharData($file);
        $phar->extractTo($destination);

        return $destination;
    }

    /**
     * الحصول على نسبة الضغط
     */
    public function getCompressionRatio(string $file): float
    {
        // سيتم تنفيذ هذا لاحقاً
        return 0.0;
    }

    /**
     * الحصول على اسم الملف مع extension
     */
    private function getBackupFileName(Backup $backup, string $extension): string
    {
        $name = $backup->name;
        
        // إزالة أي extension موجود
        $nameWithoutExt = pathinfo($name, PATHINFO_FILENAME);
        
        // إضافة extension الجديد
        return $nameWithoutExt . '.' . $extension;
    }

    /**
     * إضافة مجلد إلى ZIP
     */
    private function addDirectoryToZip(string $dir, ZipArchive $zip, string $zipDir): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $zipPath = $zipDir . ($zipDir ? '/' : '') . $file;

            if (is_dir($filePath)) {
                $zip->addEmptyDir($zipPath);
                $this->addDirectoryToZip($filePath, $zip, $zipPath);
            } else {
                $zip->addFile($filePath, $zipPath);
            }
        }
    }
}

