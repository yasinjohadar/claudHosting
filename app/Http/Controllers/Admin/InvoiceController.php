<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->middleware('auth');
    }

    /**
     * عرض قائمة الفواتير
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Invoice::with('customer');

        // تصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // تصفية حسب التاريخ من
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        // تصفية حسب التاريخ إلى
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('date', 'desc')->paginate(10);
        $customers = Customer::all();

        return view('admin.invoices.index', compact('invoices', 'customers'));
    }

    /**
     * عرض نموذج إضافة فاتورة جديدة
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $customers = Customer::all();
        return view('admin.invoices.create', compact('customers'));
    }

    /**
     * حفظ فاتورة جديدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'duedate' => 'required|date|after_or_equal:date',
            'payment_method' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);

            // حساب المجموع الفرعي والضريبة والإجمالي
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['amount'];
            }

            $tax = $request->tax ?? 0;
            $total = $subtotal + $tax;

            // إنشاء الفاتورة في النظام المحلي
            $invoice = Invoice::create([
                'whmcs_id' => null, // سيتم تحديثه لاحقًا بعد إنشائه في WHMCS
                'whmcs_client_id' => $customer->whmcs_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'date' => Carbon::parse($request->date),
                'duedate' => Carbon::parse($request->duedate),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'Unpaid',
                'paymentmethod' => $request->payment_method,
                'notes' => $request->notes,
                'synced_at' => null, // لم تتم المزامنة بعد
            ]);

            // إضافة عناصر الفاتورة
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                ]);
            }

            // إنشاء الفاتورة في WHMCS
            $whmcsInvoice = $this->whmcsApiService->createInvoice([
                'userid' => $customer->whmcs_id,
                'date' => $request->date,
                'duedate' => $request->duedate,
                'paymentmethod' => $request->payment_method,
                'notes' => $request->notes,
                'itemdescription' => collect($request->items)->pluck('description')->toArray(),
                'itemamount' => collect($request->items)->pluck('amount')->toArray(),
                'itemtaxed' => array_fill(0, count($request->items), 0),
            ]);

            if ($whmcsInvoice && isset($whmcsInvoice['invoiceid'])) {
                // تحديث الفاتورة المحلية بمعرف WHMCS
                $invoice->whmcs_id = $whmcsInvoice['invoiceid'];
                $invoice->synced_at = Carbon::now();
                $invoice->save();
            }

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

    /**
     * عرض تفاصيل الفاتورة
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $invoice = Invoice::with('items', 'payments')->findOrFail($id);
        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * عرض نموذج تعديل الفاتورة
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $invoice = Invoice::findOrFail($id);
        $customers = Customer::all();
        return view('admin.invoices.edit', compact('invoice', 'customers'));
    }

    /**
     * تحديث بيانات الفاتورة
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'date' => 'required|date',
            'duedate' => 'required|date|after_or_equal:date',
            'payment_method' => 'required|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);

            // حساب المجموع الفرعي والضريبة والإجمالي
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['amount'];
            }

            $tax = $request->tax ?? 0;
            $total = $subtotal + $tax;

            // تحديث الفاتورة في النظام المحلي
            $invoice->update([
                'whmcs_client_id' => $customer->whmcs_id,
                'date' => Carbon::parse($request->date),
                'duedate' => Carbon::parse($request->duedate),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'paymentmethod' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            // حذف العناصر القديمة وإضافة الجديدة
            $invoice->items()->delete();
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                ]);
            }

            // تحديث الفاتورة في WHMCS إذا كانت غير مدفوعة
            if ($invoice->status == 'Unpaid' && $invoice->whmcs_id) {
                $this->whmcsApiService->updateInvoice($invoice->whmcs_id, [
                    'date' => $request->date,
                    'duedate' => $request->duedate,
                    'paymentmethod' => $request->payment_method,
                    'notes' => $request->notes,
                ]);

                $invoice->synced_at = Carbon::now();
                $invoice->save();
            }

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

    /**
     * حذف الفاتورة
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        DB::beginTransaction();

        try {
            // حذف الفاتورة من WHMCS إذا كانت غير مدفوعة ولديها معرف
            if ($invoice->status == 'Unpaid' && $invoice->whmcs_id) {
                $this->whmcsApiService->deleteInvoice($invoice->whmcs_id);
            }

            // حذف العناصر والفاتورة من النظام المحلي
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

    /**
     * تحديد الفاتورة كمدفوعة
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markPaid($id)
    {
        $invoice = Invoice::findOrFail($id);

        DB::beginTransaction();

        try {
            // تحديث حالة الفاتورة في النظام المحلي
            $invoice->update([
                'status' => 'Paid',
                'datepaid' => Carbon::now(),
            ]);

            // إنشاء سجل دفعة
            Payment::create([
                'whmcs_invoice_id' => $invoice->whmcs_id,
                'date' => Carbon::now(),
                'amount' => $invoice->total,
                'method' => $invoice->paymentmethod,
                'transaction_id' => 'MANUAL-' . time(),
                'notes' => 'دفعة يدوية من النظام',
            ]);

            // تحديث الفاتورة في WHMCS إذا كان لديها معرف
            if ($invoice->whmcs_id) {
                $this->whmcsApiService->addInvoicePayment(
                    (int) $invoice->whmcs_id,
                    (float) $invoice->total,
                    'MANUAL-' . time(),
                    $invoice->paymentmethod ?? ''
                );
                $invoice->synced_at = Carbon::now();
                $invoice->save();
            }

            DB::commit();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تم تحديث حالة الفاتورة إلى مدفوعة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث حالة الفاتورة: ' . $e->getMessage());
        }
    }

    /**
     * إضافة دفعة للفاتورة (من الواجهة + WHMCS)
     */
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
            $transId = $request->transid ?: 'MANUAL-' . $invoice->id . '-' . time();
            $gateway = $request->paymentmethod;
            Payment::create([
                'whmcs_id' => null,
                'invoice_id' => $invoice->id,
                'whmcs_invoice_id' => $invoice->whmcs_id,
                'whmcs_client_id' => $invoice->whmcs_client_id,
                'date' => Carbon::now(),
                'amount' => $amount,
                'fees' => 0,
                'paymentmethod' => $gateway,
                'transid' => $transId,
                'status' => 'Completed',
                'synced_at' => null,
            ]);
            if ($invoice->whmcs_id) {
                $this->whmcsApiService->addInvoicePayment(
                    (int) $invoice->whmcs_id,
                    $amount,
                    $transId,
                    $gateway
                );
            }
            $newBalance = $balance - $amount;
            if ($newBalance <= 0) {
                $invoice->update(['status' => 'Paid', 'datepaid' => Carbon::now()]);
            }
            $invoice->touch();
            DB::commit();
            return redirect()->route('admin.invoices.show', $id)
                ->with('success', 'تم تسجيل الدفعة بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة الفاتورة مع WHMCS
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync($id)
    {
        $invoice = Invoice::findOrFail($id);

        try {
            // مزامنة الفاتورة مع WHMCS
            $this->whmcsApiService->syncInvoice($invoice);

            return redirect()->route('admin.invoices.show', $id)
                ->with('success', 'تمت مزامنة الفاتورة مع WHMCS بنجاح');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة الفاتورة: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة جميع الفواتير مع WHMCS
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncAll()
    {
        try {
            $count = $this->whmcsApiService->syncInvoices();

            return redirect()->route('admin.invoices.index')
                ->with('success', 'تمت مزامنة ' . $count . ' فاتورة مع WHMCS بنجاح');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة الفواتير: ' . $e->getMessage());
        }
    }

    /**
     * توليد رقم فاتورة فريد
     *
     * @return string
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');

        // الحصول على آخر رقم فاتورة في الشهر الحالي
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $year . $month . $newNumber;
    }
}
