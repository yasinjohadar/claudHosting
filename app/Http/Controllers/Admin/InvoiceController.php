<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GeneratesInvoiceNumbers;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CustomerService;
use App\Models\OfferedService;
use App\Models\Product;
use App\Services\Billing\InvoicePaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    use GeneratesInvoiceNumbers;

    public function __construct(
        protected InvoicePaymentService $paymentService
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $invoices = $this->paginateInvoices($request);
        $stats = $this->invoiceStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.invoices.partials.list-results', compact('invoices'))->render(),
                'total' => $invoices->total(),
            ]);
        }

        return view('admin.invoices.index', compact('invoices', 'stats'));
    }

    protected function paginateInvoices(Request $request)
    {
        return $this->buildInvoicesQuery($request)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    protected function buildInvoicesQuery(Request $request)
    {
        $query = Invoice::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
                $q->where('invoice_number', 'like', $search)
                    ->orWhere('invoicenum', 'like', $search)
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

    /**
     * @return array<string, int|float>
     */
    protected function invoiceStats(): array
    {
        return [
            'total' => Invoice::count(),
            'paid' => Invoice::where('status', 'Paid')->count(),
            'unpaid' => Invoice::where('status', 'Unpaid')->count(),
            'cancelled' => Invoice::where('status', 'Cancelled')->count(),
        ];
    }

    public function create(Request $request)
    {
        $selectedCustomerId = old('customer_id', $request->get('customer_id'));
        $customers = $this->resolveCustomersForSelect($selectedCustomerId);
        $products = Product::orderBy('name')->get();
        $offeredServices = OfferedService::with('serviceType')->active()->ordered()->get();
        $customerServices = CustomerService::with(['customer', 'offeredService'])
            ->whereNull('invoice_id')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.invoices.create', compact(
            'customers',
            'products',
            'offeredServices',
            'customerServices',
            'selectedCustomerId'
        ));
    }

    public function store(Request $request)
    {
        $validator = $this->validateInvoiceRequest($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            [$subtotal, $tax, $total] = $this->calculateTotals($request);

            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'whmcs_client_id' => $customer->whmcs_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoicenum' => null,
                'date' => Carbon::parse($request->date),
                'duedate' => Carbon::parse($request->duedate),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'Unpaid',
                'paymentmethod' => $request->paymentmethod,
                'notes' => $request->notes,
            ]);

            $invoice->update(['invoicenum' => $invoice->invoice_number]);

            $this->syncInvoiceItems($invoice, $request->items);

            DB::commit();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم إنشاء الفاتورة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء الفاتورة: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $invoice = Invoice::with(['customer', 'items', 'payments'])->findOrFail($id);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $selectedCustomerId = old('customer_id', $invoice->customer_id);
        $customers = $this->resolveCustomersForSelect($selectedCustomerId);
        $products = Product::orderBy('name')->get();
        $offeredServices = OfferedService::with('serviceType')->ordered()->get();
        $customerServices = CustomerService::with(['customer', 'offeredService'])
            ->where(function ($q) use ($invoice) {
                $q->whereNull('invoice_id')->orWhere('invoice_id', $invoice->id);
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.invoices.edit', compact(
            'invoice',
            'customers',
            'products',
            'offeredServices',
            'customerServices',
            'selectedCustomerId'
        ));
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = $this->validateInvoiceRequest($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($invoice->status === 'Paid') {
            return redirect()->back()->with('error', 'لا يمكن تعديل فاتورة مدفوعة.');
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            [$subtotal, $tax, $total] = $this->calculateTotals($request);

            $invoice->update([
                'customer_id' => $customer->id,
                'whmcs_client_id' => $customer->whmcs_id,
                'date' => Carbon::parse($request->date),
                'duedate' => Carbon::parse($request->duedate),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'paymentmethod' => $request->paymentmethod,
                'notes' => $request->notes,
            ]);

            $invoice->items()->delete();
            $this->syncInvoiceItems($invoice, $request->items);

            DB::commit();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم تحديث بيانات الفاتورة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات الفاتورة: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        DB::beginTransaction();

        try {
            $invoice->payments()->delete();
            $invoice->items()->delete();
            $invoice->delete();

            DB::commit();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم حذف الفاتورة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف الفاتورة: ' . $e->getMessage());
        }
    }

    public function markPaid($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'Paid') {
            return redirect()->route('admin.invoices.index')
                ->with('info', 'الفاتورة مدفوعة مسبقاً.');
        }

        try {
            $this->paymentService->markInvoiceFullyPaid($invoice, auth()->user());

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم تحديث حالة الفاتورة إلى مدفوعة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة الفاتورة: '.$e->getMessage());
        }
    }

    public function addPayment(Request $request, $id)
    {
        $invoice = Invoice::with('payments')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'paymentmethod' => 'required|string|max:50',
            'transid' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $this->paymentService->applyPayment($invoice, [
                'amount' => (float) $request->amount,
                'paymentmethod' => $request->paymentmethod,
                'transid' => $request->transid,
                'notes' => $request->notes,
                'recorded_by_user_id' => auth()->id(),
                'initiated_by' => InvoicePaymentService::INITIATED_ADMIN,
            ]);

            return redirect()->route('admin.invoices.show', $id)
                ->with('success', 'تم تسجيل الدفعة بنجاح.');
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    protected function resolveCustomersForSelect($customerId)
    {
        if (! $customerId) {
            return collect();
        }

        return Customer::query()
            ->where('id', $customerId)
            ->get(['id', 'fullname', 'email']);
    }

    private function validateInvoiceRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'duedate' => 'required|date|after_or_equal:date',
            'paymentmethod' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.offered_service_id' => 'nullable|exists:offered_services,id',
            'items.*.customer_service_id' => 'nullable|exists:customer_services,id',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
    }

    private function calculateTotals(Request $request): array
    {
        $subtotal = 0;

        foreach ($request->items as $item) {
            $subtotal += (float) $item['amount'];
        }

        $tax = (float) ($request->tax ?? 0);

        return [$subtotal, $tax, $subtotal + $tax];
    }

    private function syncInvoiceItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            $offeredServiceId = ! empty($item['offered_service_id']) ? (int) $item['offered_service_id'] : null;
            $customerServiceId = ! empty($item['customer_service_id']) ? (int) $item['customer_service_id'] : null;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'offered_service_id' => $offeredServiceId,
                'customer_service_id' => $customerServiceId,
                'description' => $item['description'],
                'amount' => $item['amount'],
                'taxed' => ! empty($item['taxed']),
            ]);

            if ($customerServiceId) {
                CustomerService::where('id', $customerServiceId)->update([
                    'invoice_id' => $invoice->id,
                    'amount_due' => $item['amount'],
                ]);
            }
        }
    }

}
