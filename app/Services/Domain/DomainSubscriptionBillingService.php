<?php

namespace App\Services\Domain;

use App\Models\ClientDomain;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\WhmcsDomain;
use App\Support\GeneratesInvoiceNumbers;
use Illuminate\Support\Facades\DB;

class DomainSubscriptionBillingService
{
    use GeneratesInvoiceNumbers;

    public function __construct(
        protected DomainSettingsService $settings
    ) {}

    /**
     * @return array{renewal_amount: float, invoice_due_days: int}
     */
    public function billingConfig(): array
    {
        return $this->settings->getBillingConfig();
    }

    public function renewalAmount(?float $override = null): float
    {
        if ($override !== null) {
            return max(0, $override);
        }

        return (float) $this->billingConfig()['renewal_amount'];
    }

    public function resolveAmount(string $domainName, ?float $override = null): float
    {
        if ($override !== null) {
            return max(0, $override);
        }

        $normalized = ClientDomain::normalizeName($domainName);
        $whmcs = WhmcsDomain::query()
            ->whereRaw('LOWER(domain) = ?', [$normalized])
            ->orderByDesc('id')
            ->first();

        if ($whmcs !== null && (float) $whmcs->recurringamount > 0) {
            return (float) $whmcs->recurringamount;
        }

        return $this->renewalAmount();
    }

    public function resolveCustomer(User $user): ?Customer
    {
        $user->loadMissing('customer');

        if ($user->customer) {
            return $user->customer;
        }

        return Customer::where('user_id', $user->id)->first()
            ?? ($user->email ? Customer::where('email', $user->email)->first() : null);
    }

    /**
     * @return array{success: bool, message: string, invoice?: Invoice}
     */
    public function createLinkInvoice(ClientDomain $domain, ?float $amountOverride = null): array
    {
        $domain->loadMissing('client');

        if ($domain->user_id === null || $domain->client === null) {
            return [
                'success' => false,
                'message' => 'لا يوجد عميل مربوط بالنطاق.',
            ];
        }

        $customer = $this->resolveCustomer($domain->client);

        if ($customer === null) {
            return [
                'success' => false,
                'message' => 'لا يمكن إنشاء فاتورة — أنشئ ملف customer للمستخدم أو اربطه بعميل موجود.',
            ];
        }

        $amount = $this->resolveAmount($domain->domain_name, $amountOverride);
        $dueDays = (int) $this->billingConfig()['invoice_due_days'];
        $now = now();
        $description = "تسجيل / ربط نطاق — {$domain->domain_name}";

        try {
            $invoice = DB::transaction(function () use ($domain, $customer, $amount, $dueDays, $now, $description) {
                $number = $this->generateInvoiceNumber();

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'client_domain_id' => $domain->id,
                    'whmcs_client_id' => $customer->whmcs_id,
                    'invoice_number' => $number,
                    'invoicenum' => $number,
                    'date' => $now,
                    'duedate' => $now->copy()->addDays($dueDays),
                    'subtotal' => $amount,
                    'tax' => 0,
                    'total' => $amount,
                    'status' => 'Unpaid',
                    'paymentmethod' => 'banktransfer',
                    'notes' => "نطاق: {$domain->domain_name}",
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description,
                    'amount' => $amount,
                    'taxed' => false,
                ]);

                return $invoice;
            });

            $label = $invoice->invoice_number;

            return [
                'success' => true,
                'message' => "تم إنشاء الفاتورة {$label} بمبلغ ".number_format($amount, 2),
                'invoice' => $invoice,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'فشل إنشاء الفاتورة: '.$e->getMessage(),
            ];
        }
    }
}
