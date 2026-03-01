<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsAppBroadcast;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\BroadcastWhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class BroadcastWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // 30 seconds, 1 minute, 2 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WhatsAppBroadcast $broadcast,
        public User $student,
        public string $message,
        public string $type = 'text',
        public ?int $delaySeconds = null,
        public int $messageIndex = 0
    ) {
        // Calculate delay if not provided
        if ($this->delaySeconds === null) {
            $settingsService = app(\App\Services\WhatsApp\WhatsAppSettingsService::class);
            $this->delaySeconds = $settingsService->calculateDelay();
        }
        
        // Add delay to job if not first message
        if ($this->messageIndex > 0) {
            $this->delay($this->delaySeconds);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(
        SendWhatsAppMessage $sendService,
        BroadcastWhatsAppMessage $broadcastService
    ): void {
        try {
            // Update broadcast status to processing if not already
            if ($this->broadcast->status === WhatsAppBroadcast::STATUS_PENDING) {
                $this->broadcast->update(['status' => WhatsAppBroadcast::STATUS_PROCESSING]);
            }

            // Send message
            if ($this->type === 'template') {
                // For template messages, you might need to handle differently
                // For now, we'll treat it as text
                $sendService->sendText($this->student->phone, $this->message);
            } else {
                $sendService->sendText($this->student->phone, $this->message);
            }

            // Increment sent count
            $this->broadcast->increment('sent_count');

            Log::info('Broadcast message sent successfully', [
                'broadcast_id' => $this->broadcast->id,
                'student_id' => $this->student->id,
                'phone' => $this->student->phone,
            ]);
        } catch (Exception $e) {
            // Increment failed count
            $this->broadcast->increment('failed_count');

            Log::error('Failed to send broadcast message', [
                'broadcast_id' => $this->broadcast->id,
                'student_id' => $this->student->id,
                'phone' => $this->student->phone,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        } finally {
            // Update status to completed if all messages are sent
            $totalProcessed = $this->broadcast->sent_count + $this->broadcast->failed_count;
            if ($totalProcessed >= $this->broadcast->total_recipients) {
                $status = $this->broadcast->failed_count === $this->broadcast->total_recipients
                    ? WhatsAppBroadcast::STATUS_FAILED
                    : WhatsAppBroadcast::STATUS_COMPLETED;
                
                $this->broadcast->update(['status' => $status]);
            }
        }
    }
}
