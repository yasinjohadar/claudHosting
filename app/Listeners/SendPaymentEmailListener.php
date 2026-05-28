<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Mail\PaymentReceiptMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPaymentEmailListener implements ShouldQueue
{
    public function handle(PaymentReceived $event): void
    {
        $email = trim((string) $event->customer->email);

        if ($email === '') {
            return;
        }

        try {
            $payment = $event->payment->fresh() ?? $event->payment;

            Mail::to($email)->queue(new PaymentReceiptMail(
                payment: $payment,
                invoice: $event->invoice->fresh(['payments', 'customer']) ?? $event->invoice,
                customer: $event->customer
            ));
        } catch (\Throwable $exception) {
            Log::error('Failed to queue payment receipt email.', [
                'payment_id' => $event->payment->id,
                'customer_id' => $event->customer->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
