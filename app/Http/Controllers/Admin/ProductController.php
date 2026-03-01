<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\CustomerProduct;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProductController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
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
        return view('admin.products.create');
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
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
                'paytype' => $request->paytype,
                'pricing' => $pricing,
                'currency' => 1,
                'status' => $request->status,
                'sales_count' => 0,
                'synced_at' => null,
            ]);
            
            // إنشاء المنتج في WHMCS
            $whmcsProduct = $this->whmcsApiService->addProduct([
                'type' => $request->type,
                'gid' => $request->gid,
                'name' => $request->name,
                'description' => $request->description,
                'paytype' => $request->paytype,
                'pricing' => $pricing,
            ]);
            
            if ($whmcsProduct && ($whmcsProduct['success'] ?? false) && isset($whmcsProduct['pid'])) {
                $product->whmcs_id = $whmcsProduct['pid'];
                $product->synced_at = Carbon::now();
                $product->save();
            }
            
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
        return view('admin.products.edit', compact('product'));
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
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
                'paytype' => $request->paytype,
                'pricing' => $pricing,
                'status' => $request->status,
            ]);
            
            // تحديث المنتج في WHMCS إذا كان لديه معرف
            if ($product->whmcs_id) {
                $this->whmcsApiService->updateProduct($product->whmcs_id, [
                    'type' => $request->type,
                    'gid' => $request->gid,
                    'name' => $request->name,
                    'description' => $request->description,
                    'paytype' => $request->paytype,
                    'pricing' => $pricing,
                ]);
                
                $product->synced_at = Carbon::now();
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
            if ($product->whmcs_id) {
                try {
                    $this->whmcsApiService->deleteProduct($product->whmcs_id);
                } catch (\Exception $e) {
                    // تجاهل فشل الحذف من WHMCS والمتابعة في الحذف المحلي
                }
            }
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
    
    /**
     * مزامنة المنتج مع WHMCS
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync($id)
    {
        $product = Product::findOrFail($id);
        
        try {
            // مزامنة المنتج مع WHMCS
            $this->whmcsApiService->syncProduct($product);
            
            return redirect()->route('admin.products.show', $id)
                ->with('success', 'تمت مزامنة المنتج مع WHMCS بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة المنتج: ' . $e->getMessage());
        }
    }
    
    /**
     * مزامنة جميع المنتجات مع WHMCS
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncAll()
    {
        try {
            $count = $this->whmcsApiService->syncProducts();
            
            return redirect()->route('admin.products.index')
                ->with('success', 'تمت مزامنة ' . $count . ' منتج مع WHMCS بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة المنتجات: ' . $e->getMessage());
        }
    }
}