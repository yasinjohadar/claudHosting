<?php

namespace App\Services\Backup;

use App\Models\Backup;
use App\Services\Mail\MailTemplateResolver;
use App\Services\Mail\TemplateRendererService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BackupNotificationService
{
    public function __construct(
        protected MailTemplateResolver $templateResolver,
        protected TemplateRendererService $renderer
    ) {}

    /**
     * إشعار اكتمال النسخ
     */
    public function notifyBackupCompleted(Backup $backup): void
    {
        $this->sendEmailNotification($backup, 'completed');
        $this->createSystemNotification($backup, 'completed');
        $this->sendWebhookNotification($backup, 'completed');
    }

    /**
     * إشعار فشل النسخ
     */
    public function notifyBackupFailed(Backup $backup, string $error): void
    {
        $this->sendEmailNotification($backup, 'failed');
        $this->createSystemNotification($backup, 'failed');
        $this->sendWebhookNotification($backup, 'failed');
    }

    /**
     * إرسال إشعار بريد إلكتروني
     */
    public function sendEmailNotification(Backup $backup, string $type): void
    {
        $admin = $backup->creator ?? \App\Models\User::where('role', 'admin')->first();
        
        if (!$admin || !$admin->email) {
            return;
        }

        $template = $this->templateResolver->resolve($type === 'completed' ? 'backup.completed' : 'backup.failed');
        $context = [
            'backup_name' => $backup->name,
            'backup_type' => $backup->backup_type,
            'backup_size' => $backup->getFileSize(),
            'completed_at' => optional($backup->completed_at)->format('Y-m-d H:i:s') ?: now()->format('Y-m-d H:i:s'),
            'error_message' => $backup->error_message ?? '—',
        ];
        $subject = $this->renderer->render($template['subject'], $context);
        $message = $this->renderer->render($template['body_text'], $context);

        try {
            Mail::raw($message, function ($mail) use ($admin, $subject) {
                $mail->to($admin->email)
                     ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Error sending backup email notification: ' . $e->getMessage());
        }
    }

    /**
     * إرسال Webhook
     */
    public function sendWebhookNotification(Backup $backup, string $type): void
    {
        $webhookUrl = config('backup.webhook_url');
        
        if (!$webhookUrl) {
            return;
        }

        try {
            Http::post($webhookUrl, [
                'type' => $type,
                'backup_id' => $backup->id,
                'name' => $backup->name,
                'status' => $backup->status,
                'backup_type' => $backup->backup_type,
                'file_size' => $backup->file_size,
                'error_message' => $backup->error_message,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending backup webhook notification: ' . $e->getMessage());
        }
    }

    /**
     * إنشاء إشعار نظام
     */
    public function createSystemNotification(Backup $backup, string $type): void
    {
        // يمكن إضافة نظام إشعارات لاحقاً
        // حالياً يتم الاعتماد على البريد الإلكتروني فقط
        Log::info("Backup notification: {$type}", [
            'backup_id' => $backup->id,
            'backup_name' => $backup->name,
        ]);
    }
}

