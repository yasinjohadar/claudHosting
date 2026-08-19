<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        $message = $this->buildMessage($event, $balance);

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

    /**
     * The notification text, from the editable template when one exists.
     *
     * Falls back to the wording this listener has always sent, so the customer sees the same
     * message whether or not the templates migration has run — and a template that renders to
     * nothing (all its variables unresolvable) falls back rather than sending a blank message.
     */
    private function buildMessage(PaymentReceived $event, float $balance): string
    {
        $variables = [
            'payment_amount' => number_format((float) $event->payment->amount, 2),
            'invoice_number' => (string) $event->invoice->invoice_number,
            // Computed above, including the fallback path, so it is passed rather than left to
            // the renderer's own (query-backed) lookup.
            'invoice_balance' => number_format($balance, 2),
        ];

        $template = $this->resolveTemplate();

        if ($template !== null) {
            $rendered = trim($template->render($variables, [
                'user' => $event->customer->user ?? null,
                'customer' => $event->customer,
                'invoice' => $event->invoice,
                'payment' => $event->payment,
            ]));

            if ($rendered !== '') {
                return $rendered;
            }

            Log::channel('whatsapp')->warning('Payment WhatsApp template rendered empty; using the built-in wording.', [
                'template_id' => $template->id,
            ]);
        }

        return sprintf(
            'تم استلام دفعتك بقيمة %s على الفاتورة رقم %s. المتبقي: %s. يمكنك مراجعة الفاتورة من بوابة العميل.',
            $variables['payment_amount'],
            $variables['invoice_number'],
            $variables['invoice_balance']
        );
    }

    private function resolveTemplate(): ?WhatsAppMessageTemplate
    {
        // The table may not exist yet on an install that has not migrated, and a listener must
        // not turn that into a failed queue job.
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return null;
        }

        try {
            return WhatsAppMessageTemplate::findBySlug(WhatsAppMessageTemplate::SLUG_PAYMENT_RECEIVED);
        } catch (\Throwable) {
            return null;
        }
    }
}
