<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
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

    /** One attempt: failures are recorded per-recipient; retries would duplicate sends and inflate failed_count. */
    public $tries = 1;

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
        // Calculate delay if not provided (e.g. when dispatched from elsewhere)
        if ($this->delaySeconds === null) {
            $settingsService = app(\App\Services\WhatsApp\WhatsAppSettingsService::class);
            $this->delaySeconds = $settingsService->calculateDelay();
        }

        // Delay is absolute seconds from now (controller passes cumulative delay for broadcast)
        if ($this->delaySeconds !== null && $this->delaySeconds > 0) {
            $this->delay($this->delaySeconds);
        }
    }

    /**
     * Get E.164 phone number for the student (same logic as controller).
     */
    private function getStudentPhone(): string
    {
        $phone = $this->student->full_phone
            ?? (($this->student->country_code ?? '') . ($this->student->phone ?? ''))
            ?: $this->student->phone
            ?? '';

        if ($phone !== '' && strpos($phone, '+') !== 0) {
            $phone = '+' . ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Execute the job.
     */
    public function handle(
        SendWhatsAppMessage $sendService,
        BroadcastWhatsAppMessage $broadcastService
    ): void {
        $phone = $this->getStudentPhone();

        try {
            // Update broadcast status to processing if not already
            if ($this->broadcast->status === WhatsAppBroadcast::STATUS_PENDING) {
                $this->broadcast->update(['status' => WhatsAppBroadcast::STATUS_PROCESSING]);
            }

            // Send synchronously inside broadcast job so recipient status reflects real provider result.
            // Using queued send here creates a second async job and can mark recipient as sent prematurely.
            $outboundMessage = $sendService->sendTextSync($phone, $this->message);

            if ($outboundMessage->status !== \App\Models\WhatsAppMessage::STATUS_SENT) {
                throw new Exception(
                    data_get($outboundMessage->error, 'message', 'فشل إرسال الرسالة للمستلم.')
                );
            }

            // Update recipient record to sent
            $recipient = WhatsAppBroadcastRecipient::where('broadcast_id', $this->broadcast->id)
                ->where('user_id', $this->student->id)
                ->first();
            if ($recipient) {
                $recipient->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            // Increment sent count
            $this->broadcast->increment('sent_count');

            Log::info('Broadcast message sent successfully', [
                'broadcast_id' => $this->broadcast->id,
                'student_id' => $this->student->id,
                'phone' => $phone,
            ]);
        } catch (Exception $e) {
            // Update recipient record to failed
            $recipient = WhatsAppBroadcastRecipient::where('broadcast_id', $this->broadcast->id)
                ->where('user_id', $this->student->id)
                ->first();
            if ($recipient) {
                $recipient->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Increment failed count
            $this->broadcast->increment('failed_count');

            Log::error('Failed to send broadcast message', [
                'broadcast_id' => $this->broadcast->id,
                'student_id' => $this->student->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            // Do not rethrow: recipient is already marked failed; avoids queue FAIL spam and duplicate retries.
        } finally {
            // Update status to completed if all messages are sent (refresh to get latest counts)
            $this->broadcast->refresh();
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
