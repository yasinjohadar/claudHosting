<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoicePaymentService;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class InvoicePaymentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_payable_balance_excludes_pending_payments(): void
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->total = 1000;

        $paymentsRelation = Mockery::mock();
        $paymentsRelation->shouldReceive('where')
            ->once()
            ->with('status', Payment::STATUS_COMPLETED)
            ->andReturnSelf();
        $paymentsRelation->shouldReceive('sum')
            ->once()
            ->with('amount')
            ->andReturn(300);

        $invoice->shouldReceive('payments')->once()->andReturn($paymentsRelation);

        $service = new InvoicePaymentService;
        $this->assertSame(700.0, $service->payableBalance($invoice));
    }

    public function test_apply_payment_rejects_amount_greater_than_balance(): void
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->total = 500;

        $paymentsRelation = Mockery::mock();
        $paymentsRelation->shouldReceive('where')
            ->with('status', Payment::STATUS_COMPLETED)
            ->andReturnSelf();
        $paymentsRelation->shouldReceive('sum')
            ->with('amount')
            ->andReturn(400);

        $invoice->shouldReceive('payments')->andReturn($paymentsRelation);

        $service = new InvoicePaymentService;

        $this->expectException(InvalidArgumentException::class);
        $service->applyPayment($invoice, ['amount' => 200]);
    }

    public function test_payment_status_constants(): void
    {
        $this->assertSame('Completed', Payment::STATUS_COMPLETED);
        $this->assertSame('Pending', Payment::STATUS_PENDING);
    }
}
