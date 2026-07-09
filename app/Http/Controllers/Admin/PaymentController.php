<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Billing\InvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        protected InvoicePaymentService $paymentService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $payments = $this->paginatePayments($request);
        $stats = $this->paymentService->statistics([
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'customer_id' => $request->customer_id,
        ]);

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.payments.partials.list-results', compact('payments'))->render(),
                'total' => $payments->total(),
            ]);
        }

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    protected function paginatePayments(Request $request)
    {
        return $this->buildPaymentsQuery($request)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    protected function buildPaymentsQuery(Request $request)
    {
        $query = Payment::with(['invoice', 'customer', 'recordedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('paymentmethod')) {
            $query->where('paymentmethod', $request->paymentmethod);
        }

        if ($request->filled('initiated_by')) {
            $query->where('initiated_by', $request->initiated_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('transid', 'like', $search)
                    ->orWhereHas('invoice', fn ($iq) => $iq->where('invoice_number', 'like', $search))
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('fullname', 'like', $search)
                            ->orWhere('firstname', 'like', $search)
                            ->orWhere('lastname', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        return $query;
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice.items', 'customer', 'recordedBy']);

        return view('admin.payments.show', compact('payment'));
    }

    public function confirm(Payment $payment)
    {
        try {
            $this->paymentService->confirmPayment($payment, auth()->user());

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', 'تم تأكيد الدفعة بنجاح.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Payment $payment)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->paymentService->rejectPayment($payment, auth()->user(), $request->reason);

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', 'تم رفض الدفعة.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function proof(Payment $payment)
    {
        if (! $payment->proof_path || ! Storage::disk('local')->exists($payment->proof_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($payment->proof_path);
    }
}
