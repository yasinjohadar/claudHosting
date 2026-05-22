<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\GeneratesInvoiceNumbers;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    use GeneratesInvoiceNumbers;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
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

        $invoices = $query->orderByDesc('date')->paginate(10);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::orderBy('email')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.invoices.create', compact('customers', 'products'));
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
        $customers = Customer::orderBy('email')->get();
        $products = Product::orderBy('name')->get();

        return view('admin.invoices.edit', compact('invoice', 'customers', 'products'));
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

        DB::beginTransaction();

        try {
            $balance = $invoice->balance;

            if ($balance > 0) {
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'whmcs_invoice_id' => $invoice->whmcs_id,
                    'whmcs_client_id' => $invoice->whmcs_client_id,
                    'date' => Carbon::now(),
                    'amount' => $balance,
                    'fees' => 0,
                    'paymentmethod' => $invoice->paymentmethod ?? 'manual',
                    'transid' => 'MANUAL-' . $invoice->id . '-' . time(),
                    'status' => 'Completed',
                ]);
            }

            $invoice->update([
                'status' => 'Paid',
                'datepaid' => Carbon::now(),
            ]);

            DB::commit();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم تحديث حالة الفاتورة إلى مدفوعة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة الفاتورة: ' . $e->getMessage());
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

        $amount = (float) $request->amount;
        $balance = $invoice->balance;

        if ($amount > $balance) {
            return redirect()->back()->with('error', 'المبلغ أكبر من المتبقي للفاتورة.');
        }

        DB::beginTransaction();

        try {
            $transId = $request->transid ?: 'PAY-' . $invoice->id . '-' . time();

            Payment::create([
                'invoice_id' => $invoice->id,
                'whmcs_invoice_id' => $invoice->whmcs_id,
                'whmcs_client_id' => $invoice->whmcs_client_id,
                'date' => Carbon::now(),
                'amount' => $amount,
                'fees' => 0,
                'paymentmethod' => $request->paymentmethod,
                'transid' => $transId,
                'status' => 'Completed',
            ]);

            if ($balance - $amount <= 0) {
                $invoice->update(['status' => 'Paid', 'datepaid' => Carbon::now()]);
            }

            DB::commit();

            return redirect()->route('admin.invoices.show', $id)
                ->with('success', 'تم تسجيل الدفعة بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
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
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'amount' => $item['amount'],
                'taxed' => !empty($item['taxed']),
            ]);
        }
    }

}
