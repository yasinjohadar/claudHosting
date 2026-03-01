<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\CustomerProduct;
use App\Models\Contact;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CustomerController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->middleware('auth');
    }

    /**
     * عرض قائمة العملاء
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $customers = Customer::orderBy('date_created', 'desc')->paginate(10);
        return view('admin.customers.index', compact('customers'));
    }

    /**
     * عرض نموذج إضافة عميل جديد
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * حفظ عميل جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'nullable|string|min:6|max:255',
            'companyname' => 'nullable|string|max:255',
            'phonenumber' => 'nullable|string|max:50',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'status' => 'nullable|in:Active,Inactive,Closed',
        ], [
            'firstname.required' => 'الاسم الأول مطلوب.',
            'lastname.required' => 'الاسم الأخير مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
            $status = $request->status ?: 'Active';

            // إنشاء العميل في النظام المحلي
            $customer = Customer::create([
                'whmcs_id' => null, // يُحدَّث لاحقاً إذا تم إنشاؤه في WHMCS
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'fullname' => $request->firstname . ' ' . $request->lastname,
                'email' => $request->email,
                'companyname' => $request->companyname,
                'address1' => $request->address1,
                'address2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'postcode' => $request->postcode,
                'country' => $request->country ?: 'US',
                'phonenumber' => $request->phonenumber,
                'status' => $status,
                'date_created' => Carbon::now(),
                'synced_at' => null,
            ]);

            $password = $request->filled('password') ? $request->password : 'tempPassword123';

            // إنشاء العميل في WHMCS (إن فشل نكمل بدون تحديث whmcs_id)
            $whmcsCustomer = $this->whmcsApiService->addCustomer([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'companyname' => $request->companyname,
                'address1' => $request->address1,
                'address2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'postcode' => $request->postcode,
                'country' => $request->country ?: 'US',
                'phonenumber' => $request->phonenumber,
                'password' => $password,
                'status' => $status,
            ]);

            $clientId = null;
            if (!empty($whmcsCustomer['success']) && !empty($whmcsCustomer['data'])) {
                $data = $whmcsCustomer['data'];
                $clientId = $data['clientid'] ?? $data['id'] ?? null;
            }
            if ($clientId) {
                $customer->whmcs_id = $clientId;
                $customer->synced_at = Carbon::now();
                $customer->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.customers.index')
                ->with('success', 'تم إضافة العميل بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة العميل: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل العميل
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        
        // الحصول على منتجات العميل
        $customerProducts = CustomerProduct::where('customer_id', $id)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        // جهات الاتصال (من العلاقة)
        $customer->load('contacts');
            
        // الحصول على فواتير العميل
        $invoices = Invoice::where('whmcs_client_id', $customer->whmcs_id)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();
            
        // الحصول على تذاكر العميل
        $tickets = Ticket::where('whmcs_client_id', $customer->whmcs_id)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();
            
        return view('admin.customers.show', compact('customer', 'customerProducts', 'invoices', 'tickets'));
    }

    /**
     * عرض نموذج تعديل العميل
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * تحديث بيانات العميل
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $id,
            'companyname' => 'nullable|string|max:255',
            'phonenumber' => 'nullable|string|max:50',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'status' => 'required|in:Active,Inactive,Closed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
            // تحديث العميل في النظام المحلي
            $customer->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'fullname' => $request->firstname . ' ' . $request->lastname,
                'email' => $request->email,
                'companyname' => $request->companyname,
                'address1' => $request->address1,
                'address2' => $request->address2,
                'city' => $request->city,
                'state' => $request->state,
                'postcode' => $request->postcode,
                'country' => $request->country,
                'phonenumber' => $request->phonenumber,
                'status' => $request->status,
            ]);
            
            // تحديث العميل في WHMCS إذا كان لديه معرف
            if ($customer->whmcs_id) {
                $this->whmcsApiService->updateCustomer($customer->whmcs_id, [
                    'firstname' => $request->firstname,
                    'lastname' => $request->lastname,
                    'email' => $request->email,
                    'companyname' => $request->companyname,
                    'address1' => $request->address1,
                    'address2' => $request->address2,
                    'city' => $request->city,
                    'state' => $request->state,
                    'postcode' => $request->postcode,
                    'country' => $request->country,
                    'phonenumber' => $request->phonenumber,
                    'status' => $request->status,
                ]);
                
                $customer->synced_at = Carbon::now();
                $customer->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.customers.index')
                ->with('success', 'تم تحديث بيانات العميل بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات العميل: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف العميل
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // حذف العميل من WHMCS إذا كان لديه معرف
            if ($customer->whmcs_id) {
                $this->whmcsApiService->deleteClient($customer->whmcs_id);
            }
            
            // حذف العميل من النظام المحلي
            $customer->delete();
            
            DB::commit();
            
            return redirect()->route('admin.customers.index')
                ->with('success', 'تم حذف العميل بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف العميل: ' . $e->getMessage());
        }
    }
    
    /**
     * مزامنة العميل مع WHMCS
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync($id)
    {
        $customer = Customer::findOrFail($id);
        
        try {
            // مزامنة العميل مع WHMCS
            $this->whmcsApiService->syncCustomer($customer);
            
            return redirect()->route('admin.customers.show', $id)
                ->with('success', 'تمت مزامنة العميل مع WHMCS بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة العميل: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة منتجات/خدمات العميل من WHMCS
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncProducts($id)
    {
        \Illuminate\Support\Facades\Log::info('مزامنة المنتجات: طلب وارد', ['customer_id' => $id]);
        $customer = Customer::findOrFail($id);
        if (! $customer->whmcs_id) {
            return redirect()->back()
                ->with('error', 'العميل غير مرتبط بـ WHMCS. قم بمزامنة العميل أولاً.');
        }
        try {
            $count = $this->whmcsApiService->syncCustomerProducts($customer);
            $message = 'تمت مزامنة منتجات العميل: ' . $count . ' خدمة/منتج';
            if ($count === 0) {
                $message .= '. إذا كان العميل لديه خدمات في WHMCS ولم تظهر، راجع سجل التطبيق (storage/logs/laravel.log) لمعرفة السبب.';
            }
            return redirect()->route('admin.customers.show', $id)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة المنتجات: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة جهات اتصال العميل من WHMCS
     */
    public function syncContacts($id)
    {
        $customer = Customer::findOrFail($id);
        if (! $customer->whmcs_id) {
            return redirect()->back()
                ->with('error', 'العميل غير مرتبط بـ WHMCS. قم بمزامنة العميل أولاً.');
        }
        try {
            $count = $this->whmcsApiService->syncCustomerContacts($customer);
            return redirect()->route('admin.customers.show', $id)
                ->with('success', 'تمت مزامنة جهات الاتصال: ' . $count . ' جهة اتصال');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة جهات الاتصال: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة كاملة لهذا العميل (تفاصيل + منتجات + جهات اتصال)
     */
    public function syncFull($id)
    {
        $customer = Customer::findOrFail($id);
        if (! $customer->whmcs_id) {
            return redirect()->back()->with('error', 'العميل غير مرتبط بـ WHMCS.');
        }
        try {
            $stats = $this->whmcsApiService->fullSyncCustomer($customer);
            $msg = 'تمت المزامنة: بيانات العميل';
            if ($stats['products']) {
                $msg .= '، ' . $stats['products'] . ' خدمة/منتج';
            }
            if ($stats['contacts']) {
                $msg .= '، ' . $stats['contacts'] . ' جهة اتصال';
            }
            return redirect()->route('admin.customers.show', $id)->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * تعليق خدمة/حساب (ModuleSuspend)
     */
    public function productSuspend(Request $request, string $id, int $serviceId)
    {
        $customer = Customer::findOrFail($id);
        $pivot = CustomerProduct::where('customer_id', $customer->id)->where('whmcs_service_id', $serviceId)->firstOrFail();
        $reason = $request->input('reason', '');
        try {
            $res = $this->whmcsApiService->moduleSuspend($serviceId, $reason ?: null);
            if (($res['result'] ?? '') === 'success') {
                $this->whmcsApiService->syncCustomerProducts($customer);
                return redirect()->route('admin.customers.show', $id)->with('success', 'تم تعليق الخدمة بنجاح.');
            }
            return redirect()->back()->with('error', $res['message'] ?? 'فشل تعليق الخدمة.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * إلغاء تعليق خدمة (ModuleUnsuspend)
     */
    public function productUnsuspend(string $id, int $serviceId)
    {
        $customer = Customer::findOrFail($id);
        CustomerProduct::where('customer_id', $customer->id)->where('whmcs_service_id', $serviceId)->firstOrFail();
        try {
            $res = $this->whmcsApiService->moduleUnsuspend($serviceId);
            if (($res['result'] ?? '') === 'success') {
                $this->whmcsApiService->syncCustomerProducts($customer);
                return redirect()->route('admin.customers.show', $id)->with('success', 'تم إلغاء تعليق الخدمة بنجاح.');
            }
            return redirect()->back()->with('error', $res['message'] ?? 'فشل إلغاء التعليق.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * إنهاء خدمة نهائياً (ModuleTerminate)
     */
    public function productTerminate(string $id, int $serviceId)
    {
        $customer = Customer::findOrFail($id);
        CustomerProduct::where('customer_id', $customer->id)->where('whmcs_service_id', $serviceId)->firstOrFail();
        try {
            $res = $this->whmcsApiService->moduleTerminate($serviceId);
            if (($res['result'] ?? '') === 'success') {
                $this->whmcsApiService->syncCustomerProducts($customer);
                return redirect()->route('admin.customers.show', $id)->with('success', 'تم إنهاء الخدمة.');
            }
            return redirect()->back()->with('error', $res['message'] ?? 'فشل إنهاء الخدمة.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * مزامنة جميع العملاء مع WHMCS
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncAll(Request $request)
    {
        try {
            $count = $this->whmcsApiService->syncCustomers();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تمت مزامنة ' . $count . ' عميل مع WHMCS بنجاح',
                    'count' => $count,
                ]);
            }

            return redirect()->route('admin.customers.index')
                ->with('success', 'تمت مزامنة ' . $count . ' عميل مع WHMCS بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء مزامنة العملاء: ' . $e->getMessage(),
                ], 422);
            }
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة العملاء: ' . $e->getMessage());
        }
    }
}