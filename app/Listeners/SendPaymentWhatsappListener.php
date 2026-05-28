<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentWhatsappListener implements ShouldQueue
{
    public function __construct(
        private WhatsAppSettingsService $settingsService,
        private SendWhatsAppMessage $sendWhatsAppMessage
    ) {}

    public function handle(PaymentReceived $event): void
    {
        $settings = $this->settingsService->getSettings();

        if (! ($settings['whatsapp_enabled'] ?? false)) {
            return;
        }

        if (! ($settings['send_payment_notifications'] ?? true)) {
            return;
        }

        if (! $event->initiatedByClient) {
            return;
        }

        $phone = trim((string) ($event->customer->phonenumber ?? ''));
        if ($phone === '') {
            Log::channel('whatsapp')->warning('Payment WhatsApp notification skipped: missing customer phone.', [
                'payment_id' => $event->payment->id,
                'customer_id' => $event->customer->id,
            ]);

            return;
        }

        try {
            $balance = (float) $event->invoice->balance;
        } catch (\Throwable) {
            $balance = max(0, (float) $event->invoice->total - (float) $event->payment->amount);
        }

        $message = sprintf(
            'تم استلام دفعتك بقيمة %s على الفاتورة رقم %s. المتبقي: %s. يمكنك مراجعة الفاتورة من بوابة العميل.',
            number_format((float) $event->payment->amount, 2),
            $event->invoice->invoice_number,
            number_format($balance, 2)
        );

        try {
            $this->sendWhatsAppMessage->sendText($phone, $message, false);
        } catch (\Throwable $exception) {
            Log::channel('whatsapp')->error('Failed sending payment WhatsApp notification.', [
                'payment_id' => $event->payment->id,
                'customer_id' => $event->customer->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
