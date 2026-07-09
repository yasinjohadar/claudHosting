<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OfferedService;
use App\Support\GeneratesInvoiceNumbers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerServiceController extends Controller
{
    use GeneratesInvoiceNumbers;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $records = $this->paginateCustomerServices($request);
        $customers = Customer::orderBy('fullname')->get(['id', 'fullname', 'email']);
        $catalogServices = OfferedService::with('serviceType')->active()->ordered()->get();
        $stats = $this->customerServiceStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.customer-services.partials.list-results', compact('records'))->render(),
                'total' => $records->total(),
            ]);
        }

        return view('admin.customer-services.index', compact('records', 'customers', 'catalogServices', 'stats'));
    }

    protected function paginateCustomerServices(Request $request)
    {
        return $this->buildCustomerServicesQuery($request)
            ->orderByDesc('subscribed_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    protected function buildCustomerServicesQuery(Request $request)
    {
        $query = CustomerService::with(['customer', 'offeredService.serviceType', 'invoice']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('offered_service_id')) {
            $query->where('offered_service_id', $request->offered_service_id);
        }

        if ($request->filled('search')) {
            $search = '%'.trim((string) $request->search).'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('fullname', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        return $query;
    }

    /**
     * @return array<string, int>
     */
    protected function customerServiceStats(): array
    {
        return [
            'total' => CustomerService::count(),
            'active' => CustomerService::where('status', CustomerService::STATUS_ACTIVE)->count(),
            'pending' => CustomerService::where('status', CustomerService::STATUS_PENDING)->count(),
            'overdue' => CustomerService::where('status', CustomerService::STATUS_OVERDUE)->count(),
        ];
    }

    public function create(Request $request)
    {
        $selectedCustomerId = $request->get('customer_id');
        $selectedOfferedServiceId = $request->get('offered_service_id');
        $customers = $this->resolveCustomersForSelect(old('customer_id', $selectedCustomerId));
        $catalogServices = OfferedService::with('serviceType')->active()->ordered()->get();

        return view('admin.customer-services.create', compact(
            'customers',
            'catalogServices',
            'selectedCustomerId',
            'selectedOfferedServiceId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRecord($request);
        $catalog = OfferedService::findOrFail($validated['offered_service_id']);

        $validated['name'] = $validated['name'] ?: $catalog->name;
        $validated['currency'] = $validated['currency'] ?? $catalog->currency ?? 'SAR';
        $validated['execution_duration'] = $validated['execution_duration'] ?? $catalog->execution_duration;
        $validated['execution_days'] = $validated['execution_days'] ?? $catalog->execution_days;

        if (empty($validated['price'])) {
            $validated['price'] = $catalog->price;
        }

        if (empty($validated['amount_due'])) {
            $validated['amount_due'] = $validated['price'];
        }

        CustomerService::create($validated);

        return redirect()->route('admin.customer-services.index')
            ->with('success', 'تم تسجيل خدمة العميل بنجاح');
    }

    public function show(CustomerService $customerService)
    {
        $customerService->load(['customer', 'offeredService.serviceType', 'invoice', 'invoiceItems.invoice']);

        return view('admin.customer-services.show', ['record' => $customerService]);
    }

    public function edit(CustomerService $customerService)
    {
        $customers = $this->resolveCustomersForSelect(old('customer_id', $customerService->customer_id));
        $catalogServices = OfferedService::with('serviceType')->ordered()->get();

        return view('admin.customer-services.edit', [
            'record' => $customerService,
            'customers' => $customers,
            'catalogServices' => $catalogServices,
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $term = trim((string) $request->get('q', $request->get('search', '')));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%'.$term.'%';
        $domain = ltrim(strtolower($term), '@');

        $customers = Customer::query()
            ->where(function ($q) use ($like, $domain) {
                $q->where('fullname', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('lastname', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if ($domain !== '') {
                    $q->orWhere('email', 'like', '%@'.$domain.'%');
                }
            })
            ->orderBy('fullname')
            ->limit(25)
            ->get(['id', 'fullname', 'email']);

        return response()->json($customers->map(fn (Customer $customer) => [
            'value' => (string) $customer->id,
            'label' => trim($customer->fullname).' ('.$customer->email.')',
            'email' => $customer->email,
        ]));
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

    public function update(Request $request, CustomerService $customerService)
    {
        $validated = $this->validateRecord($request);
        $customerService->update($validated);

        return redirect()->route('admin.customer-services.show', $customerService)
            ->with('success', 'تم تحديث خدمة العميل بنجاح');
    }

    public function destroy(CustomerService $customerService)
    {
        $customerService->delete();

        return redirect()->route('admin.customer-services.index')
            ->with('success', 'تم حذف سجل الخدمة بنجاح');
    }

    public function createInvoice(CustomerService $customerService)
    {
        if ($customerService->invoice_id && $customerService->invoice) {
            return redirect()->route('admin.invoices.show', $customerService->invoice_id)
                ->with('info', 'يوجد فاتورة مرتبطة مسبقاً بهذه الخدمة.');
        }

        DB::beginTransaction();

        try {
            $customer = $customerService->customer;
            $amount = (float) ($customerService->amount_due > 0 ? $customerService->amount_due : $customerService->price);

            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'whmcs_client_id' => $customer->whmcs_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'date' => Carbon::now(),
                'duedate' => $customerService->renewal_at ?? Carbon::now()->addDays(7),
                'subtotal' => $amount,
                'tax' => 0,
                'total' => $amount,
                'status' => 'Unpaid',
                'notes' => 'فاتورة خدمة: '.$customerService->name,
            ]);

            $invoice->update(['invoicenum' => $invoice->invoice_number]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'offered_service_id' => $customerService->offered_service_id,
                'customer_service_id' => $customerService->id,
                'description' => $customerService->name,
                'amount' => $amount,
                'taxed' => false,
            ]);

            $customerService->update(['invoice_id' => $invoice->id]);

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice->id)
                ->with('success', 'تم إنشاء الفاتورة وربطها بالخدمة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRecord(Request $request): array
    {
        $statuses = array_keys(CustomerService::statusOptions());

        $data = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'offered_service_id' => 'required|exists:offered_services,id',
            'name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'execution_duration' => 'nullable|string|max:255',
            'execution_days' => 'nullable|integer|min:0|max:3650',
            'subscribed_at' => 'nullable|date',
            'renewal_at' => 'nullable|date|after_or_equal:subscribed_at',
            'amount_due' => 'nullable|numeric|min:0',
            'status' => 'required|in:'.implode(',', $statuses),
            'notes' => 'nullable|string|max:5000',
        ])->validate();

        if (empty($data['subscribed_at'])) {
            $data['subscribed_at'] = now()->toDateString();
        }

        return $data;
    }
}
