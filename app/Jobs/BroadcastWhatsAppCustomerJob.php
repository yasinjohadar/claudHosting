<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastWhatsAppCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppBroadcast $broadcast,
        public Customer $customer,
        public string $message,
        public string $type = 'text',
        public ?int $delaySeconds = null,
        public int $messageIndex = 0
    ) {
        if ($this->delaySeconds === null) {
            $settingsService = app(\App\Services\WhatsApp\WhatsAppSettingsService::class);
            $this->delaySeconds = $settingsService->calculateDelay();
        }

        if ($this->delaySeconds !== null && $this->delaySeconds > 0) {
            $this->delay($this->delaySeconds);
        }
    }

    private function getCustomerPhone(): string
    {
        $phone = trim((string) ($this->customer->phonenumber ?? ''));
        if ($phone !== '' && ! str_starts_with($phone, '+')) {
            $phone = '+'.ltrim($phone, '0');
        }

        return $phone;
    }

    public function handle(SendWhatsAppMessage $sendService): void
    {
        $phone = $this->getCustomerPhone();

        try {
            if ($this->broadcast->status === WhatsAppBroadcast::STATUS_PENDING) {
                $this->broadcast->update(['status' => WhatsAppBroadcast::STATUS_PROCESSING]);
            }

            $outboundMessage = $sendService->sendTextSync($phone, $this->message);

            if ($outboundMessage->status !== WhatsAppMessage::STATUS_SENT) {
                throw new Exception(
                    data_get($outboundMessage->error, 'message', 'فشل إرسال الرسالة للمستلم.')
                );
            }

            $recipient = WhatsAppBroadcastRecipient::where('broadcast_id', $this->broadcast->id)
                ->where('customer_id', $this->customer->id)
                ->first();
            if ($recipient) {
                $recipient->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            $this->broadcast->increment('sent_count');

            Log::channel('whatsapp')->info('Broadcast message sent to customer', [
                'broadcast_id' => $this->broadcast->id,
                'customer_id' => $this->customer->id,
                'phone' => $phone,
            ]);
        } catch (Exception $e) {
            $recipient = WhatsAppBroadcastRecipient::where('broadcast_id', $this->broadcast->id)
                ->where('customer_id', $this->customer->id)
                ->first();
            if ($recipient) {
                $recipient->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            }

            $this->broadcast->increment('failed_count');

            Log::channel('whatsapp')->error('Failed to send broadcast message to customer', [
                'broadcast_id' => $this->broadcast->id,
                'customer_id' => $this->customer->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        } finally {
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
