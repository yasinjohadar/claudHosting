<?php

namespace Tests\Unit;

use App\Events\PaymentReceived;
use App\Listeners\SendPaymentWhatsappListener;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Mockery;
use Tests\TestCase;

class SendPaymentWhatsappListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_listener_sends_whatsapp_when_enabled_and_phone_exists(): void
    {
        $settingsService = Mockery::mock(WhatsAppSettingsService::class);
        $settingsService->shouldReceive('getSettings')
            ->once()
            ->andReturn([
                'whatsapp_enabled' => true,
                'send_payment_notifications' => true,
            ]);

        $sendService = Mockery::mock(SendWhatsAppMessage::class);
        $sendService->shouldReceive('sendText')
            ->once()
            ->with(
                '966500000001',
                Mockery::pattern('/تم استلام دفعتك بقيمة/'),
                false
            );

        $listener = new SendPaymentWhatsappListener($settingsService, $sendService);

        $customer = new Customer([
            'id' => 41,
            'phonenumber' => '966500000001',
        ]);
        $customer->id = 41;

        $invoice = new Invoice([
            'id' => 6,
            'invoice_number' => 'INV-6',
            'total' => 1000,
        ]);
        $invoice->id = 6;

        $payment = new Payment([
            'id' => 81,
            'amount' => 250,
            'status' => Payment::STATUS_PENDING,
            'paymentmethod' => 'banktransfer',
        ]);
        $payment->id = 81;

        $event = new PaymentReceived($payment, $invoice, $customer, true);
        $listener->handle($event);

        $this->assertTrue(true);
    }
}
