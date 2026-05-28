<?php

namespace Tests\Unit;

use App\Events\PaymentReceived;
use App\Listeners\SendPaymentEmailListener;
use App\Mail\PaymentReceiptMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendPaymentEmailListenerTest extends TestCase
{
    public function test_listener_queues_receipt_email_to_customer_email(): void
    {
        Mail::fake();

        $customer = new Customer([
            'id' => 12,
            'email' => 'paying-client@example.com',
            'firstname' => 'Test',
            'lastname' => 'Client',
        ]);
        $customer->id = 12;

        $invoice = new Invoice([
            'id' => 22,
            'invoice_number' => 'INV-22',
            'total' => 500,
        ]);
        $invoice->id = 22;
        $invoice->setRelation('customer', $customer);

        $payment = new Payment([
            'id' => 3,
            'invoice_id' => 22,
            'customer_id' => 12,
            'amount' => 100,
            'paymentmethod' => 'banktransfer',
            'status' => Payment::STATUS_PENDING,
        ]);
        $payment->id = 3;

        $event = new PaymentReceived($payment, $invoice, $customer, true);

        $listener = app(SendPaymentEmailListener::class);
        $listener->handle($event);

        Mail::assertQueued(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }
}
