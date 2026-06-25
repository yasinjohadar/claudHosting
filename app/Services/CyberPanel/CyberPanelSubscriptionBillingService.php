<?php

namespace App\Services\CyberPanel;

use App\Models\Customer;
use App\Models\CyberPanelWebsite;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\GeneratesInvoiceNumbers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CyberPanelSubscriptionBillingService
{
    use GeneratesInvoiceNumbers;

    public function __construct(
        protected CyberPanelSettingsService $settings
    ) {}

    /**
     * @return array{renewal_amount: float, invoice_due_days: int, subscription_years: int}
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

    public function subscriptionYears(): int
    {
        return max(1, (int) $this->billingConfig()['subscription_years']);
    }

    public function resolveCustomer(CyberPanelWebsite $website): ?Customer
    {
        if ($website->customer_id) {
            return Customer::find($website->customer_id);
        }

        $website->loadMissing('client.customer');

        if ($website->client?->customer) {
            return $website->client->customer;
        }

        if ($website->user_id) {
            return Customer::where('user_id', $website->user_id)->first();
        }

        if ($website->email) {
            return Customer::where('email', $website->email)->first();
        }

        return null;
    }

    public function endDateFromStart(Carbon $start): Carbon
    {
        return $start->copy()->addYears($this->subscriptionYears());
    }

    public function ensureSubscriptionDates(CyberPanelWebsite $website): void
    {
        if ($website->subscription_ends_at !== null) {
            return;
        }

        $start = $website->joined_at ?? $website->created_at ?? now();

        if ($website->joined_at === null) {
            $website->joined_at = $start;
        }

        $website->subscription_ends_at = $this->endDateFromStart($start);
        $website->save();
    }

    public function extendSubscriptionEnd(CyberPanelWebsite $website): Carbon
    {
        $base = $website->subscription_ends_at ?? $website->joined_at ?? now();

        if ($base->isPast()) {
            $base = now();
        }

        return $this->endDateFromStart($base);
    }

    /**
     * @return array{success: bool, message: string, invoice?: Invoice}
     */
    public function createSubscriptionInvoice(CyberPanelWebsite $website, string $reason, ?float $amountOverride = null): array
    {
        $customer = $this->resolveCustomer($website);

        if ($customer === null) {
            return [
                'success' => false,
                'message' => 'لا يمكن إنشاء فاتورة — اربط الموقع بعميل له ملف customer.',
            ];
        }

        $amount = $this->renewalAmount($amountOverride);
        $dueDays = (int) $this->billingConfig()['invoice_due_days'];
        $now = now();

        $description = $reason === 'initial'
            ? "اشتراك CyberPanel — {$website->domain} ({$website->package})"
            : "تجديد اشتراك CyberPanel — {$website->domain} ({$website->package})";

        try {
            $invoice = DB::transaction(function () use ($website, $customer, $amount, $dueDays, $now, $description) {
                $number = $this->generateInvoiceNumber();

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'cyberpanel_website_id' => $website->id,
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
                    'notes' => "موقع CyberPanel: {$website->domain}",
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description,
                    'amount' => $amount,
                    'taxed' => false,
                ]);

                return $invoice;
            });

            return ['success' => true, 'message' => 'تم إنشاء الفاتورة', 'invoice' => $invoice];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'فشل إنشاء الفاتورة: '.$e->getMessage()];
        }
    }

    /**
     * @return array{success: bool, message: string, website?: CyberPanelWebsite, invoice?: Invoice}
     */
    public function bootstrapNewWebsite(CyberPanelWebsite $website, ?float $amountOverride = null): array
    {
        $this->ensureSubscriptionDates($website->fresh());

        $invoiceResult = $this->createSubscriptionInvoice($website->fresh(), 'initial', $amountOverride);

        if (! ($invoiceResult['success'] ?? false)) {
            return [
                'success' => true,
                'message' => 'تم ضبط الاشتراك. '.$invoiceResult['message'],
                'website' => $website->fresh(),
            ];
        }

        return [
            'success' => true,
            'message' => 'تم ضبط الاشتراك وإنشاء الفاتورة الأولى',
            'website' => $website->fresh(),
            'invoice' => $invoiceResult['invoice'],
        ];
    }
}
