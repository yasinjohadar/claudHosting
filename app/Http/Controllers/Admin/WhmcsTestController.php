<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhmcsApiService;
use App\Services\WhmcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhmcsTestController extends Controller
{
    protected $whmcsApiService;
    protected $whmcsService;

    public function __construct(WhmcsApiService $whmcsApiService, WhmcsService $whmcsService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->whmcsService = $whmcsService;
        // $this->middleware('permission:whmcs.test'); // معطل مؤقتاً للاختبار
    }

    /**
     * عرض صفحة اختبار الاتصال بـ WHMCS
     */
    public function index()
    {
        $serverIp = null;
        try {
            $response = Http::timeout(4)->get('https://api.ipify.org');
            if ($response->successful()) {
                $serverIp = trim($response->body());
            }
        } catch (\Exception $e) {
            // ignore
        }

        return view('admin.whmcs.test', compact('serverIp'));
    }

    /**
     * اختبار الاتصال بـ WHMCS (باستخدام GetCurrencies نفس طريقة التشخيص الناجحة)
     */
    public function testConnection(Request $request)
    {
        try {
            $response = $this->whmcsService->call('GetCurrencies', []);

            if (isset($response['result']) && $response['result'] === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم الاتصال بنجاح بـ WHMCS',
                    'details' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل الاتصال بـ WHMCS',
                'error' => $response['message'] ?? 'خطأ غير معروف'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة الاتصال بـ WHMCS',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * اختبار جلب العملاء من WHMCS
     */
    public function testGetCustomers(Request $request)
    {
        try {
            $raw = $this->whmcsApiService->makeRequest('GetClients', [
                'limitnum' => 5
            ]);

            if (isset($raw['success']) && !$raw['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب العملاء',
                    'error' => $raw['message'] ?? 'خطأ غير معروف'
                ]);
            }

            $response = isset($raw['success']) ? ($raw['data'] ?? []) : $raw;

            if (isset($response['result']) && $response['result'] == 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب العملاء بنجاح',
                    'count' => count($response['clients']['client'] ?? []),
                    'data' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب العملاء',
                'error' => $response['message'] ?? 'خطأ غير معروف'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العملاء',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * اختبار جلب المنتجات من WHMCS
     */
    public function testGetProducts(Request $request)
    {
        try {
            $raw = $this->whmcsApiService->makeRequest('GetProducts', [
                'limitnum' => 5
            ]);

            if (isset($raw['success']) && !$raw['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب المنتجات',
                    'error' => $raw['message'] ?? 'خطأ غير معروف'
                ]);
            }

            $response = isset($raw['success']) ? ($raw['data'] ?? []) : $raw;

            if (isset($response['result']) && $response['result'] == 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب المنتجات بنجاح',
                    'count' => count($response['products']['product'] ?? []),
                    'data' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب المنتجات',
                'error' => $response['message'] ?? 'خطأ غير معروف'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنتجات',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * اختبار جلب الفواتير من WHMCS
     */
    public function testGetInvoices(Request $request)
    {
        try {
            $raw = $this->whmcsApiService->makeRequest('GetInvoices', [
                'limitnum' => 5
            ]);

            if (isset($raw['success']) && !$raw['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب الفواتير',
                    'error' => $raw['message'] ?? 'خطأ غير معروف'
                ]);
            }

            $response = isset($raw['success']) ? ($raw['data'] ?? []) : $raw;

            if (isset($response['result']) && $response['result'] == 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب الفواتير بنجاح',
                    'count' => count($response['invoices']['invoice'] ?? []),
                    'data' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب الفواتير',
                'error' => $response['message'] ?? 'خطأ غير معروف'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفواتير',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * اختبار جلب التذاكر من WHMCS
     */
    public function testGetTickets(Request $request)
    {
        try {
            $raw = $this->whmcsApiService->makeRequest('GetTickets', [
                'limitnum' => 5
            ]);

            if (isset($raw['success']) && !$raw['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب التذاكر',
                    'error' => $raw['message'] ?? 'خطأ غير معروف'
                ]);
            }

            $response = isset($raw['success']) ? ($raw['data'] ?? []) : $raw;

            if (isset($response['result']) && $response['result'] == 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب التذاكر بنجاح',
                    'count' => count($response['tickets']['ticket'] ?? []),
                    'data' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل جلب التذاكر',
                'error' => $response['message'] ?? 'خطأ غير معروف'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب التذاكر',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة العملاء
     */
    public function syncCustomers(Request $request)
    {
        try {
            $count = $this->whmcsApiService->syncCustomers();
            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$count} من العملاء بنجاح",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة العملاء',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة المنتجات
     */
    public function syncProducts(Request $request)
    {
        try {
            $count = $this->whmcsApiService->syncProducts();
            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$count} من المنتجات بنجاح",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة المنتجات',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * مزامنة الفواتير
     */
    public function syncInvoices(Request $request)
    {
        try {
            $count = $this->whmcsApiService->syncInvoices();
            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$count} من الفواتير بنجاح",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة الفواتير',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة التذاكر
     */
    public function syncTickets(Request $request)
    {
        try {
            $count = $this->whmcsApiService->syncTickets();
            return response()->json([
                'success' => true,
                'message' => "تمت مزامنة {$count} من التذاكر بنجاح",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة التذاكر',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * صفحة المزامنة الكاملة
     */
    public function syncPage()
    {
        return view('admin.whmcs.sync');
    }

    /**
     * تنفيذ المزامنة الكاملة (عملاء → منتجات عملاء + جهات اتصال → فواتير → تذاكر → كتالوج المنتجات)
     */
    public function fullSync(Request $request)
    {
        try {
            $stats = $this->whmcsApiService->fullSync();
            $message = sprintf(
                'تمت المزامنة: %d عميل، %d فاتورة، %d تذكرة، %d منتج (كتالوج).',
                $stats['customers'],
                $stats['invoices'],
                $stats['tickets'],
                $stats['catalog_products']
            );
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message, 'stats' => $stats]);
            }
            return redirect()->route('admin.whmcs.sync')->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->route('admin.whmcs.sync')->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}