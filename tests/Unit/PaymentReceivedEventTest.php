<?php

namespace Tests\Unit;

use App\Events\PaymentReceived;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\Billing\InvoicePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentReceivedEventTest extends TestCase
{
    public function test_submit_client_payment_dispatches_payment_received_event(): void
    {
        Event::fake([PaymentReceived::class]);

        $invoice = new Invoice([
            'id' => 15,
            'customer_id' => 30,
            'whmcs_id' => null,
            'whmcs_client_id' => null,
            'status' => 'Unpaid',
            'total' => 350,
        ]);
        $invoice->id = 15;

        $customer = new Customer([
            'id' => 30,
            'email' => 'client@example.com',
            'phonenumber' => '966500000000',
        ]);
        $customer->id = 30;
        $invoice->setRelation('customer', $customer);

        $user = new User;
        $user->id = 77;

        $payment = new Payment([
            'id' => 9,
            'invoice_id' => 15,
            'customer_id' => 30,
            'amount' => 120,
            'status' => Payment::STATUS_PENDING,
            'paymentmethod' => 'banktransfer',
        ]);
        $payment->id = 9;

        DB::shouldReceive('transaction')
            ->once()
            ->andReturn($payment);

        $service = $this->partialMock(InvoicePaymentService::class, function ($mock) {
            $mock->shouldReceive('payableBalance')->once()->andReturn(350.0);
        });

        $service->submitClientPayment($invoice, $user, ['amount' => 120]);

        Event::assertDispatched(PaymentReceived::class, function (PaymentReceived $event) use ($invoice, $customer) {
            return $event->initiatedByClient === true
                && $event->invoice->id === $invoice->id
                && $event->customer->id === $customer->id
                && (float) $event->payment->amount === 120.0;
        });
    }
}
