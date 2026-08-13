<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@gmail.com')->first()
            ?? User::query()->orderBy('id')->first();

        $customer = Customer::query()->orderBy('id')->first();

        if (! $customer) {
            $customer = Customer::create([
                'whmcs_id' => 900001,
                'firstname' => 'ياسين',
                'lastname' => 'جوخدار',
                'email' => 'demo-client@example.com',
                'companyname' => 'CloudSoft Demo',
                'phonenumber' => '+963900000000',
                'country' => 'SY',
                'status' => 'Active',
                'date_created' => now(),
            ]);
            $this->command?->info('تم إنشاء عميل تجريبي للمدفوعات.');
        }

        $invoices = Invoice::query()
            ->whereNotNull('customer_id')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        if ($invoices->isEmpty()) {
            $invoices = collect([
                $this->makeInvoice($customer, 'INV-SEED-0001', 1265, 'Unpaid', 'paypal'),
                $this->makeInvoice($customer, 'INV-SEED-0002', 450, 'Paid', 'banktransfer'),
                $this->makeInvoice($customer, 'INV-SEED-0003', 890.50, 'Unpaid', 'creditcard'),
            ]);
            $this->command?->info('تم إنشاء فواتير تجريبية للمدفوعات.');
        }

        $samples = [
            [
                'status' => Payment::STATUS_COMPLETED,
                'paymentmethod' => 'paypal',
                'amount_ratio' => 1,
                'fees' => 5.00,
                'days_ago' => 2,
                'notes' => 'دفعة مكتملة عبر PayPal',
                'initiated_by' => 'admin',
                'mark_invoice_paid' => true,
            ],
            [
                'status' => Payment::STATUS_PENDING,
                'paymentmethod' => 'banktransfer',
                'amount_ratio' => 1,
                'fees' => 0,
                'days_ago' => 1,
                'notes' => 'بانتظار مراجعة التحويل البنكي',
                'initiated_by' => 'client',
                'mark_invoice_paid' => false,
            ],
            [
                'status' => Payment::STATUS_COMPLETED,
                'paymentmethod' => 'cash',
                'amount_ratio' => 0.5,
                'fees' => 0,
                'days_ago' => 5,
                'notes' => 'دفعة جزئية نقدًا',
                'initiated_by' => 'admin',
                'mark_invoice_paid' => false,
            ],
            [
                'status' => Payment::STATUS_FAILED,
                'paymentmethod' => 'creditcard',
                'amount_ratio' => 1,
                'fees' => 2.50,
                'days_ago' => 3,
                'notes' => 'فشل الدفع — بطاقة مرفوضة',
                'initiated_by' => 'client',
                'mark_invoice_paid' => false,
            ],
            [
                'status' => Payment::STATUS_REFUNDED,
                'paymentmethod' => 'paypal',
                'amount_ratio' => 1,
                'fees' => 0,
                'days_ago' => 10,
                'notes' => 'تم استرداد المبلغ للعميل',
                'initiated_by' => 'admin',
                'mark_invoice_paid' => false,
            ],
            [
                'status' => Payment::STATUS_COMPLETED,
                'paymentmethod' => 'banktransfer',
                'amount_ratio' => 1,
                'fees' => 0,
                'days_ago' => 0,
                'notes' => 'دفعة اليوم — مكتملة',
                'initiated_by' => 'admin',
                'mark_invoice_paid' => true,
            ],
            [
                'status' => Payment::STATUS_PENDING,
                'paymentmethod' => 'other',
                'amount_ratio' => 0.75,
                'fees' => 0,
                'days_ago' => 0,
                'notes' => 'طلب دفع معلّق للمراجعة اليوم',
                'initiated_by' => 'client',
                'mark_invoice_paid' => false,
            ],
            [
                'status' => Payment::STATUS_CANCELLED,
                'paymentmethod' => 'stripe',
                'amount_ratio' => 1,
                'fees' => 1.20,
                'days_ago' => 7,
                'notes' => 'أُلغيت من العميل قبل الإتمام',
                'initiated_by' => 'client',
                'mark_invoice_paid' => false,
            ],
        ];

        $created = 0;

        DB::transaction(function () use ($invoices, $samples, $admin, &$created) {
            foreach ($samples as $index => $sample) {
                $invoice = $invoices[$index % $invoices->count()];
                $amount = round(max(1, (float) $invoice->total * $sample['amount_ratio']), 2);
                $date = Carbon::now()->subDays($sample['days_ago'])->setTime(10 + ($index % 8), 15 + $index);

                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'whmcs_id' => null,
                    'whmcs_invoice_id' => null,
                    'whmcs_client_id' => null,
                    'date' => $date,
                    'amount' => $amount,
                    'fees' => $sample['fees'],
                    'paymentmethod' => $sample['paymentmethod'],
                    'transid' => 'SEED-' . strtoupper(substr(md5($invoice->id . '-' . $index . '-' . now()->timestamp), 0, 10)),
                    'status' => $sample['status'],
                    'notes' => $sample['notes'],
                    'recorded_by_user_id' => $admin?->id,
                    'initiated_by' => $sample['initiated_by'],
                ]);

                if ($sample['mark_invoice_paid'] && $sample['status'] === Payment::STATUS_COMPLETED) {
                    $invoice->update([
                        'status' => 'Paid',
                        'datepaid' => $date,
                        'paymentmethod' => $sample['paymentmethod'],
                        'credit' => $amount,
                    ]);
                }

                $created++;
                unset($payment);
            }
        });

        $this->command?->info("تم إنشاء {$created} مدفوعة تجريبية.");
    }

    protected function makeInvoice(Customer $customer, string $number, float $total, string $status, string $method): Invoice
    {
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'whmcs_id' => null,
            'whmcs_client_id' => $customer->whmcs_id,
            'invoice_number' => $number,
            'date' => now()->subDays(20),
            'duedate' => now()->addDays(10),
            'datepaid' => $status === 'Paid' ? now()->subDays(5) : null,
            'subtotal' => $total,
            'credit' => $status === 'Paid' ? $total : 0,
            'tax' => 0,
            'taxrate' => 0,
            'tax2' => 0,
            'taxrate2' => 0,
            'total' => $total,
            'status' => $status,
            'paymentmethod' => $method,
            'notes' => 'فاتورة تجريبية من PaymentSeeder',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'باقة استضافة تجريبية',
            'amount' => $total,
            'taxed' => false,
        ]);

        return $invoice;
    }
}
