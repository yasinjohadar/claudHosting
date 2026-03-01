<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageOrderRequest;
use App\Models\Customer;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageOrderRequestController extends Controller
{
    public function __construct(protected WhmcsApiService $whmcsApi)
    {
        $this->middleware('auth');
    }

    /**
     * قائمة طلبات الباقات
     */
    public function index(Request $request)
    {
        $query = PackageOrderRequest::with(['product', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orderRequests = $query->paginate(15)->withQueryString();

        return view('admin.order-requests.index', compact('orderRequests'));
    }

    /**
     * تفاصيل طلب واحد
     */
    public function show($id)
    {
        $orderRequest = PackageOrderRequest::with(['product', 'user'])->findOrFail($id);

        return view('admin.order-requests.show', compact('orderRequest'));
    }

    /**
     * تحديث حالة الطلب
     */
    public function update(Request $request, $id)
    {
        $orderRequest = PackageOrderRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,contacted,converted,cancelled',
        ]);

        $orderRequest->update(['status' => $request->status]);

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', 'تم تحديث حالة الطلب.');
    }

    /**
     * تحويل الطلب إلى WHMCS (إنشاء عميل إن لزم + إنشاء طلب)
     */
    public function convertToWhmcs($id)
    {
        $orderRequest = PackageOrderRequest::with('product')->findOrFail($id);

        if ($orderRequest->status === PackageOrderRequest::STATUS_CONVERTED) {
            return redirect()->back()->with('info', 'تم تحويل هذا الطلب مسبقاً إلى WHMCS.');
        }

        $product = $orderRequest->product;
        if (! $product || ! $product->whmcs_id) {
            return redirect()->back()->with('error', 'المنتج غير مرتبط بـ WHMCS (whmcs_id مفقود).');
        }

        $clientId = null;

        if ($orderRequest->user_id) {
            $orderRequest->load(['user.customer']);
            if ($orderRequest->user && $orderRequest->user->customer && $orderRequest->user->customer->whmcs_id) {
                $clientId = (int) $orderRequest->user->customer->whmcs_id;
            }
        }

        if (! $clientId) {
            $customer = Customer::where('email', $orderRequest->email)->whereNotNull('whmcs_id')->first();
            if ($customer) {
                $clientId = (int) $customer->whmcs_id;
            }
        }

        if (! $clientId) {
            $nameParts = explode(' ', trim($orderRequest->name), 2);
            $firstname = $nameParts[0] ?? $orderRequest->name;
            $lastname = $nameParts[1] ?? '';
            $password = Str::random(12);
            $addResult = $this->whmcsApi->addCustomer([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $orderRequest->email,
                'phonenumber' => $orderRequest->phone ?? '',
                'password' => $password,
                'country' => config('whmcs.default_country', 'US'),
            ]);
            if (! ($addResult['success'] ?? false)) {
                return redirect()->back()->with('error', 'فشل إنشاء العميل في WHMCS: ' . ($addResult['message'] ?? 'خطأ غير معروف'));
            }
            $clientId = (int) ($addResult['data']['clientid'] ?? $addResult['data']['id'] ?? 0);
            if ($clientId <= 0) {
                return redirect()->back()->with('error', 'لم يُرجع WHMCS معرف العميل.');
            }
            Customer::create([
                'user_id' => $orderRequest->user_id,
                'whmcs_id' => $clientId,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'fullname' => $orderRequest->name,
                'email' => $orderRequest->email,
                'phonenumber' => $orderRequest->phone,
                'country' => config('whmcs.default_country', 'US'),
                'status' => 'Active',
            ]);
        }

        $billingCycleMap = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semiannually' => 'Semi-Annually',
            'annually' => 'Annually',
            'biennially' => 'Biennially',
            'triennially' => 'Triennially',
        ];
        $billingCycle = $billingCycleMap[$orderRequest->billing_cycle] ?? 'Monthly';

        $orderResult = $this->whmcsApi->addOrder([
            'clientid' => $clientId,
            'pid' => $product->whmcs_id,
            'billingcycle' => $billingCycle,
            'noemail' => true,
        ]);

        if (! ($orderResult['success'] ?? false)) {
            return redirect()->back()->with('error', 'فشل إنشاء الطلب في WHMCS: ' . ($orderResult['message'] ?? 'خطأ غير معروف'));
        }

        $orderRequest->update([
            'status' => PackageOrderRequest::STATUS_CONVERTED,
            'whmcs_order_id' => $orderResult['orderid'] ?? null,
            'whmcs_client_id' => $clientId,
        ]);

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', 'تم تحويل الطلب إلى WHMCS بنجاح.');
    }
}
