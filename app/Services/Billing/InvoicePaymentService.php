<?php

namespace App\Services\Billing;

use App\Events\PaymentReceived;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoicePaymentService
{
    public const INITIATED_ADMIN = 'admin';

    public const INITIATED_CLIENT = 'client';

    public function applyPayment(Invoice $invoice, array $data): Payment
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ الدفعة يجب أن يكون أكبر من صفر.');
        }

        $balance = $this->payableBalance($invoice);

        if ($amount > $balance) {
            throw new InvalidArgumentException('المبلغ أكبر من المتبقي للفاتورة.');
        }

        return DB::transaction(function () use ($invoice, $data, $amount) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'whmcs_invoice_id' => $invoice->whmcs_id,
                'whmcs_client_id' => $invoice->whmcs_client_id,
                'date' => $data['date'] ?? Carbon::now(),
                'amount' => $amount,
                'fees' => (float) ($data['fees'] ?? 0),
                'paymentmethod' => $data['paymentmethod'] ?? 'banktransfer',
                'transid' => $data['transid'] ?? ('PAY-'.$invoice->id.'-'.time()),
                'status' => $data['status'] ?? Payment::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'initiated_by' => $data['initiated_by'] ?? self::INITIATED_ADMIN,
                'recorded_by_user_id' => $data['recorded_by_user_id'] ?? null,
            ]);

            $this->syncInvoicePaymentStatus($invoice->fresh(['payments']));

            return $payment;
        });
    }

    public function markInvoiceFullyPaid(Invoice $invoice, ?User $admin = null, ?string $method = null): ?Payment
    {
        if ($invoice->status === 'Paid') {
            return null;
        }

        $balance = $this->payableBalance($invoice);

        if ($balance <= 0) {
            $invoice->update(['status' => 'Paid', 'datepaid' => Carbon::now()]);

            return null;
        }

        return $this->applyPayment($invoice, [
            'amount' => $balance,
            'paymentmethod' => $method ?? $invoice->paymentmethod ?? 'manual',
            'transid' => 'MANUAL-'.$invoice->id.'-'.time(),
            'status' => Payment::STATUS_COMPLETED,
            'initiated_by' => self::INITIATED_ADMIN,
            'recorded_by_user_id' => $admin?->id,
        ]);
    }

    public function submitClientPayment(Invoice $invoice, User $user, array $data): Payment
    {
        if (in_array($invoice->status, ['Paid', 'Cancelled'], true)) {
            throw new InvalidArgumentException('لا يمكن سداد هذه الفاتورة.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        $balance = $this->payableBalance($invoice);

        if ($amount <= 0 || $amount > $balance) {
            throw new InvalidArgumentException('المبلغ غير صالح أو أكبر من المتبقي.');
        }

        $payment = DB::transaction(function () use ($invoice, $user, $data, $amount) {
            return Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'whmcs_invoice_id' => $invoice->whmcs_id,
                'whmcs_client_id' => $invoice->whmcs_client_id,
                'date' => Carbon::now(),
                'amount' => $amount,
                'fees' => 0,
                'paymentmethod' => 'banktransfer',
                'transid' => 'CLIENT-'.$invoice->id.'-'.time(),
                'status' => Payment::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'initiated_by' => self::INITIATED_CLIENT,
                'recorded_by_user_id' => $user->id,
            ]);
        });

        $customer = $invoice->customer ?: Customer::query()->find($invoice->customer_id);
        if (! $customer) {
            throw new InvalidArgumentException('تعذر تحديد العميل المرتبط بهذه الفاتورة.');
        }

        event(new PaymentReceived(
            payment: $payment,
            invoice: $invoice->fresh(['payments', 'customer']) ?? $invoice,
            customer: $customer,
            initiatedByClient: true
        ));

        return $payment;
    }

    public function confirmPayment(Payment $payment, User $admin): Payment
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new InvalidArgumentException('هذه الدفعة ليست قيد الانتظار.');
        }

        return DB::transaction(function () use ($payment, $admin) {
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'date' => Carbon::now(),
                'recorded_by_user_id' => $admin->id,
            ]);

            $invoice = $payment->invoice()->with('payments')->first();
            if ($invoice) {
                $this->syncInvoicePaymentStatus($invoice);
            }

            return $payment->fresh();
        });
    }

    public function rejectPayment(Payment $payment, User $admin, ?string $reason = null): Payment
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new InvalidArgumentException('هذه الدفعة ليست قيد الانتظار.');
        }

        $notes = trim(($payment->notes ? $payment->notes."\n" : '').'سبب الرفض: '.($reason ?: '—'));

        $payment->update([
            'status' => Payment::STATUS_CANCELLED,
            'notes' => $notes,
            'recorded_by_user_id' => $admin->id,
        ]);

        return $payment->fresh();
    }

    public function payableBalance(Invoice $invoice): float
    {
        $paidAmount = $invoice->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        return max(0, (float) $invoice->total - (float) $paidAmount);
    }

    public function syncInvoicePaymentStatus(Invoice $invoice): void
    {
        if ($this->payableBalance($invoice) <= 0 && $invoice->status !== 'Cancelled') {
            $invoice->update([
                'status' => 'Paid',
                'datepaid' => Carbon::now(),
            ]);
        } elseif ($invoice->status === 'Paid' && $this->payableBalance($invoice) > 0) {
            $invoice->update([
                'status' => 'Unpaid',
                'datepaid' => null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statistics(array $filters = []): array
    {
        $query = Payment::query();

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $completedQuery = (clone $query)->where('status', Payment::STATUS_COMPLETED);
        $pendingQuery = (clone $query)->where('status', Payment::STATUS_PENDING);

        $now = Carbon::now();

        return [
            'total_completed' => (float) (clone $completedQuery)->sum('amount'),
            'completed_count' => (clone $completedQuery)->count(),
            'pending_count' => (clone $pendingQuery)->count(),
            'pending_amount' => (float) (clone $pendingQuery)->sum('amount'),
            'month_completed' => (float) (clone $completedQuery)
                ->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->sum('amount'),
            'month_count' => (clone $completedQuery)
                ->whereBetween('date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
            'today_completed' => (float) (clone $completedQuery)
                ->whereDate('date', $now->toDateString())
                ->sum('amount'),
        ];
    }
}
