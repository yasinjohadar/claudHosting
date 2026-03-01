<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\Backup\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد المحاولات
     */
    public $tries = 1;

    /**
     * Timeout بالثواني (10 دقائق)
     */
    public $timeout = 600;

    public function __construct(
        public Backup $backup,
        public array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            // تحديث النسخة من قاعدة البيانات للتأكد من أحدث حالة
            $this->backup->refresh();
            
            // إنشاء النسخة باستخدام backup_id
            $backupService->createBackup(array_merge($this->options, [
                'backup_id' => $this->backup->id,
            ]));
        } catch (\Exception $e) {
            Log::error('Error creating backup in job: ' . $e->getMessage(), [
                'backup_id' => $this->backup->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->backup->refresh();
            $this->backup->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            
            // إرسال إشعار بالفشل
            try {
                $notificationService = app(\App\Services\Backup\BackupNotificationService::class);
                $notificationService->notifyBackupFailed($this->backup, $e->getMessage());
            } catch (\Exception $notificationException) {
                Log::error('Error sending backup failure notification: ' . $notificationException->getMessage());
            }
        }
    }
}
