<?php

namespace App\Services\Whm;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WhmAccount;
use App\Support\GeneratesInvoiceNumbers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WhmSubscriptionBillingService
{
    use GeneratesInvoiceNumbers;

    public function __construct(
        protected WhmSettingsService $settings
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

    public function resolveCustomer(WhmAccount $account): ?Customer
    {
        if ($account->customer_id) {
            return Customer::find($account->customer_id);
        }

        $account->loadMissing('client.customer');

        if ($account->client?->customer) {
            return $account->client->customer;
        }

        if ($account->user_id) {
            return Customer::where('user_id', $account->user_id)->first();
        }

        if ($account->display_email) {
            return Customer::where('email', $account->display_email)->first();
        }

        return null;
    }

    public function endDateFromStart(Carbon $start): Carbon
    {
        return $start->copy()->addYears($this->subscriptionYears());
    }

    public function ensureSubscriptionDates(WhmAccount $account): void
    {
        if ($account->subscription_ends_at !== null) {
            return;
        }

        $start = $account->joined_at ?? $account->created_at ?? now();

        if ($account->joined_at === null) {
            $account->joined_at = $start;
        }

        $account->subscription_ends_at = $this->endDateFromStart($start);
        $account->save();
    }

    public function extendSubscriptionEnd(WhmAccount $account): Carbon
    {
        $base = $account->subscription_ends_at ?? $account->joined_at ?? now();

        if ($base->isPast()) {
            $base = now();
        }

        return $this->endDateFromStart($base);
    }

    /**
     * @return array{success: bool, message: string, invoice?: Invoice}
     */
    public function createSubscriptionInvoice(WhmAccount $account, string $reason, ?float $amountOverride = null): array
    {
        $customer = $this->resolveCustomer($account);

        if ($customer === null) {
            return [
                'success' => false,
                'message' => 'لا يمكن إنشاء فاتورة — اربط الحساب بعميل له ملف customer أو أضف بريداً مطابقاً.',
            ];
        }

        $amount = $this->renewalAmount($amountOverride);
        $dueDays = (int) $this->billingConfig()['invoice_due_days'];
        $now = now();

        $description = $reason === 'initial'
            ? "اشتراك استضافة — {$account->domain} ({$account->package})"
            : "تجديد اشتراك استضافة — {$account->domain} ({$account->package})";

        try {
            $invoice = DB::transaction(function () use ($account, $customer, $amount, $dueDays, $now, $description) {
                $number = $this->generateInvoiceNumber();

                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'whm_account_id' => $account->id,
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
                    'notes' => "حساب WHM: {$account->username}",
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description,
                    'amount' => $amount,
                    'taxed' => false,
                ]);

                return $invoice;
            });

            return [
                'success' => true,
                'message' => 'تم إنشاء الفاتورة',
                'invoice' => $invoice,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'فشل إنشاء الفاتورة: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{success: bool, message: string, account?: WhmAccount, invoice?: Invoice}
     */
    public function bootstrapNewAccount(WhmAccount $account, ?float $amountOverride = null): array
    {
        $this->ensureSubscriptionDates($account->fresh());

        $invoiceResult = $this->createSubscriptionInvoice($account->fresh(), 'initial', $amountOverride);

        if (! ($invoiceResult['success'] ?? false)) {
            return [
                'success' => true,
                'message' => 'تم ضبط الاشتراك. '.$invoiceResult['message'],
                'account' => $account->fresh(),
            ];
        }

        return [
            'success' => true,
            'message' => 'تم ضبط الاشتراك وإنشاء الفاتورة الأولى',
            'account' => $account->fresh(),
            'invoice' => $invoiceResult['invoice'],
        ];
    }
}
