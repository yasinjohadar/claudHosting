<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\InvoicePaymentService;
use App\Services\Client\ClientBillingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class ClientPaymentController extends Controller
{
    public function __construct(
        protected ClientBillingService $billing,
        protected InvoicePaymentService $paymentService
    ) {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();
        $payments = $this->paymentsForUser($user);

        return view('client.pages.payments.index', compact('user', 'payments'));
    }

    public function payForm(Invoice $invoice): View
    {
        $user = auth()->user();
        abort_unless($this->billing->userCanViewInvoice($user, $invoice), 403);

        if (in_array($invoice->status, ['Paid', 'Cancelled'], true) || $invoice->balance <= 0) {
            abort(403, 'لا يمكن سداد هذه الفاتورة.');
        }

        $bank = config('billing.bank');

        return view('client.pages.invoices.pay', compact('user', 'invoice', 'bank'));
    }

    public function payStore(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        abort_unless($this->billing->userCanViewInvoice($user, $invoice), 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'local');
        }

        try {
            $this->paymentService->submitClientPayment($invoice, $user, [
                'amount' => (float) $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'proof_path' => $proofPath,
            ]);

            return redirect()->route('client.payments.index')
                ->with('success', 'تم إرسال إبلاغ الدفع. سيتم مراجعته من الإدارة.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * @return LengthAwarePaginator<Payment>|Collection<int, Payment>
     */
    protected function paymentsForUser($user): LengthAwarePaginator|Collection
    {
        $customerId = $this->billing->customerIdForUser($user);

        if ($customerId === null) {
            return collect();
        }

        return Payment::query()
            ->with(['invoice'])
            ->where('customer_id', $customerId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15);
    }
}
