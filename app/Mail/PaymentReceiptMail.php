<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Mail\MailTemplateResolver;
use App\Services\Mail\TemplateRendererService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Invoice $invoice,
        public Customer $customer
    ) {}

    public function build(): self
    {
        $invoice = $this->invoice;
        $invoice->loadMissing('payments');
        $totalPaid = (float) $invoice->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');
        $template = app(MailTemplateResolver::class)->resolve('payment.received');
        $renderer = app(TemplateRendererService::class);
        $context = [
            'user_name' => $this->customer->full_name ?: $this->customer->firstname ?: 'العميل',
            'email' => $this->customer->email ?? '',
            'phone' => $this->customer->phonenumber ?? '',
            'invoice_number' => $invoice->invoice_number,
            'payment_amount' => number_format((float) $this->payment->amount, 2),
            'balance' => number_format((float) $invoice->balance, 2),
        ];

        return $this
            ->subject($renderer->render($template['subject'], $context))
            ->view('emails.payments.receipt')
            ->with([
                'payment' => $this->payment,
                'invoice' => $invoice,
                'customer' => $this->customer,
                'totalPaid' => $totalPaid,
                'balance' => (float) $invoice->balance,
                'templateBody' => $renderer->render($template['body_html'], $context),
            ]);
    }
}
