<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PackageOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    /**
     * عرض قائمة الباقات (المنتجات المعروضة في الموقع)
     */
    public function index()
    {
        $products = Product::where('hidden', false)
            ->where('status', 'Active')
            ->orderBy('gid')
            ->orderBy('name')
            ->get();

        return view('frontend.pages.packages', compact('products'));
    }

    /**
     * عرض تفاصيل باقة واحدة
     */
    public function show($id)
    {
        $product = Product::where('id', $id)
            ->where('hidden', false)
            ->where('status', 'Active')
            ->firstOrFail();

        return view('frontend.pages.package-detail', compact('product'));
    }

    /**
     * عرض نموذج طلب الباقة (للمستخدم المسجّل فقط)
     */
    public function orderForm($id)
    {
        $product = Product::where('id', $id)
            ->where('hidden', false)
            ->where('status', 'Active')
            ->firstOrFail();

        $pricing = $product->pricing;
        $firstCurrency = is_array($pricing) ? reset($pricing) : null;
        $availableCycles = [];
        if (is_array($firstCurrency)) {
            $cycleLabels = PackageOrderRequest::billingCycles();
            foreach (['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'] as $key) {
                if (! empty($firstCurrency[$key]) && $firstCurrency[$key] !== '-1.00') {
                    $availableCycles[$key] = [
                        'label' => $cycleLabels[$key] ?? $key,
                        'price' => $firstCurrency[$key],
                    ];
                }
            }
        }
        if (empty($availableCycles)) {
            $availableCycles['monthly'] = ['label' => 'شهري', 'price' => $product->price];
        }

        return view('frontend.pages.package-order', compact('product', 'availableCycles'));
    }

    /**
     * حفظ طلب الباقة (المستخدم يجب أن يكون مسجّلاً — يتم أخذ الاسم والبريد من الحساب)
     */
    public function storeOrder(Request $request)
    {
        $rules = [
            'phone' => 'nullable|string|max:50',
            'product_id' => 'required|exists:products,id',
            'billing_cycle' => 'required|in:monthly,quarterly,semiannually,annually,biennially,triennially',
            'notes' => 'nullable|string|max:1000',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $product = Product::where('id', $request->product_id)
            ->where('hidden', false)
            ->where('status', 'Active')
            ->firstOrFail();

        $user = auth()->user();

        $orderRequest = PackageOrderRequest::create([
            'product_id' => $product->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $request->phone,
            'billing_cycle' => $request->billing_cycle,
            'notes' => $request->notes,
            'status' => PackageOrderRequest::STATUS_PENDING,
            'user_id' => $user->id,
        ]);

        Log::info('طلب باقة جديد', ['id' => $orderRequest->id, 'product' => $product->name, 'email' => $orderRequest->email]);

        return redirect()->route('frontend.package-detail', $product->id)
            ->with('success', 'تم استلام طلبك بنجاح. سنتواصل معك قريباً.');
    }
}
