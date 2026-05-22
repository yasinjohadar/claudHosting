<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CustomerProduct;
use App\Support\PackageFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة المنتجات
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $products = Product::orderBy('name', 'asc')->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    /**
     * عرض نموذج إضافة منتج جديد
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.products.create', [
            'featureIcons' => PackageFeatures::iconCatalog(),
            'packageFeatures' => [],
        ]);
    }

    /**
     * حفظ منتج جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'gid' => 'required|integer',
            'description' => 'nullable|string',
            'paytype' => 'required|string|max:20',
            'msetupfee' => 'required|numeric|min:0',
            'qsetupfee' => 'required|numeric|min:0',
            'ssetupfee' => 'required|numeric|min:0',
            'asetupfee' => 'required|numeric|min:0',
            'bsetupfee' => 'required|numeric|min:0',
            'monthly' => 'required|numeric|min:0',
            'quarterly' => 'required|numeric|min:0',
            'semiannually' => 'required|numeric|min:0',
            'annually' => 'required|numeric|min:0',
            'biennially' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'package_features' => 'nullable|array|max:'.(int) config('package_features.max_items', 20),
            'package_features.*.icon' => ['required', 'string', Rule::in(array_keys(PackageFeatures::iconCatalog()))],
            'package_features.*.text' => 'required|string|max:'.(int) config('package_features.max_text_length', 500),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
            $packageFeatures = PackageFeatures::normalize($request->input('package_features', []));

            // إنشاء بيانات التسعير
            $pricing = [
                'USD' => [
                    'msetupfee' => $request->msetupfee,
                    'qsetupfee' => $request->qsetupfee,
                    'ssetupfee' => $request->ssetupfee,
                    'asetupfee' => $request->asetupfee,
                    'bsetupfee' => $request->bsetupfee,
                    'monthly' => $request->monthly,
                    'quarterly' => $request->quarterly,
                    'semiannually' => $request->semiannually,
                    'annually' => $request->annually,
                    'biennially' => $request->biennially,
                ]
            ];
            
            // إنشاء المنتج في النظام المحلي
            $product = Product::create([
                'whmcs_id' => null,
                'type' => $request->type,
                'gid' => $request->gid,
                'name' => $request->name,
                'description' => $request->description,
                'package_features' => $packageFeatures ?: null,
                'paytype' => $request->paytype,
                'pricing' => $pricing,
                'currency' => 1,
                'status' => $request->status,
                'sales_count' => 0,
                'synced_at' => null,
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.products.index')
                ->with('success', 'تم إضافة المنتج بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل المنتج
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        
        // الحصول على العملاء الذين اشتروا هذا المنتج
        $customerProducts = CustomerProduct::where('product_id', $id)
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.products.show', compact('product', 'customerProducts'));
    }

    /**
     * عرض نموذج تعديل المنتج
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', [
            'product' => $product,
            'featureIcons' => PackageFeatures::iconCatalog(),
            'packageFeatures' => $product->package_features ?? $product->resolvedPackageFeatures(),
        ]);
    }

    /**
     * تحديث بيانات المنتج
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'gid' => 'required|integer',
            'description' => 'nullable|string',
            'paytype' => 'required|string|max:20',
            'msetupfee' => 'required|numeric|min:0',
            'qsetupfee' => 'required|numeric|min:0',
            'ssetupfee' => 'required|numeric|min:0',
            'asetupfee' => 'required|numeric|min:0',
            'bsetupfee' => 'required|numeric|min:0',
            'monthly' => 'required|numeric|min:0',
            'quarterly' => 'required|numeric|min:0',
            'semiannually' => 'required|numeric|min:0',
            'annually' => 'required|numeric|min:0',
            'biennially' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
            'package_features' => 'nullable|array|max:'.(int) config('package_features.max_items', 20),
            'package_features.*.icon' => ['required', 'string', Rule::in(array_keys(PackageFeatures::iconCatalog()))],
            'package_features.*.text' => 'required|string|max:'.(int) config('package_features.max_text_length', 500),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
            $packageFeatures = PackageFeatures::normalize($request->input('package_features', []));

            // إنشاء بيانات التسعير
            $pricing = [
                'USD' => [
                    'msetupfee' => $request->msetupfee,
                    'qsetupfee' => $request->qsetupfee,
                    'ssetupfee' => $request->ssetupfee,
                    'asetupfee' => $request->asetupfee,
                    'bsetupfee' => $request->bsetupfee,
                    'monthly' => $request->monthly,
                    'quarterly' => $request->quarterly,
                    'semiannually' => $request->semiannually,
                    'annually' => $request->annually,
                    'biennially' => $request->biennially,
                ]
            ];
            
            // تحديث المنتج في النظام المحلي
            $product->update([
                'type' => $request->type,
                'gid' => $request->gid,
                'name' => $request->name,
                'description' => $request->description,
                'package_features' => $packageFeatures ?: null,
                'paytype' => $request->paytype,
                'pricing' => $pricing,
                'status' => $request->status,
            ]);
            
            if ($request->filled('whm_provision_json')) {
                $decoded = json_decode($request->whm_provision_json, true);
                $product->whm_provision = is_array($decoded) ? $decoded : null;
                $product->save();
            }

            DB::commit();
            
            return redirect()->route('admin.products.index')
                ->with('success', 'تم تحديث بيانات المنتج بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات المنتج: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف المنتج
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->delete();
            DB::commit();
            return redirect()->route('admin.products.index')
                ->with('success', 'تم حذف المنتج بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage());
        }
    }
    
}