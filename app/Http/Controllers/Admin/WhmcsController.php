<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhmcsApiService;
use App\Services\CustomerService;
use App\Services\ProductService;
use App\Services\InvoiceService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhmcsController extends Controller
{
    protected $whmcsApiService;
    protected $customerService;
    protected $productService;
    protected $invoiceService;
    protected $ticketService;

    public function __construct(
        WhmcsApiService $whmcsApiService,
        CustomerService $customerService,
        ProductService $productService,
        InvoiceService $invoiceService,
        TicketService $ticketService
    ) {
        $this->whmcsApiService = $whmcsApiService;
        $this->customerService = $customerService;
        $this->productService = $productService;
        $this->invoiceService = $invoiceService;
        $this->ticketService = $ticketService;
        
        // التحقق من الصلاحيات
        $this->middleware('permission:whmcs-manage')->only(['index', 'testConnection', 'syncData']);
        $this->middleware('permission:whmcs-sync-customers')->only(['syncCustomers']);
        $this->middleware('permission:whmcs-sync-products')->only(['syncProducts']);
        $this->middleware('permission:whmcs-sync-invoices')->only(['syncInvoices']);
        $this->middleware('permission:whmcs-sync-tickets')->only(['syncTickets']);
    }

    /**
     * عرض صفحة إدارة WHMCS
     */
    public function index()
    {
        // الحصول على إحصائيات المزامنة
        $stats = [
            'customers' => \App\Models\Customer::count(),
            'products' => \App\Models\Product::count(),
            'invoices' => \App\Models\Invoice::count(),
            'tickets' => \App\Models\Ticket::count(),
            'last_sync' => Cache::get('whmcs_last_sync', 'لم تتم المزامنة بعد'),
        ];

        // الحصول على حالة الاتصال
        $connectionStatus = Cache::get('whmcs_connection_status', 'غير معروف');
        $connectionStatusColor = $connectionStatus === 'متصل' ? 'success' : 'danger';

        return view('admin.whmcs.index', compact('stats', 'connectionStatus', 'connectionStatusColor'));
    }

    /**
     * اختبار الاتصال بـ WHMCS API
     */
    public function testConnection(Request $request)
    {
        try {
            // محاولة الحصول على قائمة العملاء للتحقق من الاتصال
            $response = $this->whmcsApiService->getCustomers(1, 1);

            if ($response) {
                // تحديث حالة الاتصال في التخزين المؤقت
                Cache::put('whmcs_connection_status', 'متصل', now()->addHours(1));
                
                return response()->json([
                    'success' => true,
                    'message' => 'تم الاتصال بـ WHMCS API بنجاح',
                    'status' => 'متصل',
                    'status_color' => 'success'
                ]);
            } else {
                // تحديث حالة الاتصال في التخزين المؤقت
                Cache::put('whmcs_connection_status', 'غير متصل', now()->addHours(1));
                
                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال بـ WHMCS API',
                    'status' => 'غير متصل',
                    'status_color' => 'danger'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error testing WHMCS connection', ['error' => $e->getMessage()]);
            
            // تحديث حالة الاتصال في التخزين المؤقت
            Cache::put('whmcs_connection_status', 'غير متصل', now()->addHours(1));
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء اختبار الاتصال: ' . $e->getMessage(),
                'status' => 'غير متصل',
                'status_color' => 'danger'
            ]);
        }
    }

    /**
     * مزامنة جميع البيانات من WHMCS
     */
    public function syncData(Request $request)
    {
        $results = [
            'success' => true,
            'message' => 'تمت المزامنة بنجاح',
            'details' => []
        ];

        try {
            // مزامنة العملاء
            if ($request->has('sync_customers')) {
                $customerResult = $this->customerService->syncCustomersFromWhmcs();
                $results['details']['customers'] = $customerResult;
                
                if (!$customerResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة العملاء';
                }
            }

            // مزامنة المنتجات
            if ($request->has('sync_products')) {
                $productResult = $this->productService->syncProductsFromWhmcs();
                $results['details']['products'] = $productResult;
                
                if (!$productResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة المنتجات';
                }
            }

            // مزامنة الفواتير
            if ($request->has('sync_invoices')) {
                $invoiceResult = $this->invoiceService->syncInvoicesFromWhmcs();
                $results['details']['invoices'] = $invoiceResult;
                
                if (!$invoiceResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة الفواتير';
                }
            }

            // مزامنة التذاكر
            if ($request->has('sync_tickets')) {
                $ticketResult = $this->ticketService->syncTicketsFromWhmcs();
                $results['details']['tickets'] = $ticketResult;
                
                if (!$ticketResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة التذاكر';
                }
            }

            // مزامنة خدمات العملاء
            if ($request->has('sync_customer_products')) {
                $customerProductResult = $this->productService->syncCustomerProductsFromWhmcs();
                $results['details']['customer_products'] = $customerProductResult;
                
                if (!$customerProductResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة خدمات العملاء';
                }
            }

            // مزامنة المدفوعات
            if ($request->has('sync_payments')) {
                $paymentResult = $this->invoiceService->syncPaymentsFromWhmcs();
                $results['details']['payments'] = $paymentResult;
                
                if (!$paymentResult['success']) {
                    $results['success'] = false;
                    $results['message'] = 'حدث خطأ أثناء مزامنة المدفوعات';
                }
            }

            // تحديث وقت المزامنة الأخير
            Cache::put('whmcs_last_sync', now()->format('Y-m-d H:i:s'), now()->addDays(1));

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Error syncing WHMCS data', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة البيانات: ' . $e->getMessage(),
                'details' => []
            ]);
        }
    }

    /**
     * مزامنة العملاء فقط
     */
    public function syncCustomers()
    {
        try {
            $result = $this->customerService->syncCustomersFromWhmcs();
            
            // تحديث وقت المزامنة الأخير
            Cache::put('whmcs_last_sync', now()->format('Y-m-d H:i:s'), now()->addDays(1));
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error syncing customers from WHMCS', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة العملاء: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة المنتجات فقط
     */
    public function syncProducts()
    {
        try {
            $result = $this->productService->syncProductsFromWhmcs();
            
            // تحديث وقت المزامنة الأخير
            Cache::put('whmcs_last_sync', now()->format('Y-m-d H:i:s'), now()->addDays(1));
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error syncing products from WHMCS', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة المنتجات: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة الفواتير فقط
     */
    public function syncInvoices()
    {
        try {
            $result = $this->invoiceService->syncInvoicesFromWhmcs();
            
            // تحديث وقت المزامنة الأخير
            Cache::put('whmcs_last_sync', now()->format('Y-m-d H:i:s'), now()->addDays(1));
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error syncing invoices from WHMCS', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة الفواتير: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * مزامنة التذاكر فقط
     */
    public function syncTickets()
    {
        try {
            $result = $this->ticketService->syncTicketsFromWhmcs();
            
            // تحديث وقت المزامنة الأخير
            Cache::put('whmcs_last_sync', now()->format('Y-m-d H:i:s'), now()->addDays(1));
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error syncing tickets from WHMCS', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء مزامنة التذاكر: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * عرض صفحة إعدادات WHMCS
     */
    public function settings()
    {
        $settings = [
            'api_url' => config('whmcs.api_url'),
            'api_identifier' => config('whmcs.api_identifier'),
            'api_secret' => config('whmcs.api_secret'),
            'access_token' => config('whmcs.access_token'),
        ];

        return view('admin.whmcs.settings', compact('settings'));
    }

    /**
     * تحديث إعدادات WHMCS
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'api_url' => 'required|url',
            'api_identifier' => 'required_without:access_token',
            'api_secret' => 'required_without:access_token',
            'access_token' => 'required_without:api_identifier,api_secret',
        ]);

        try {
            // تحديث ملف .env
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // تحديث أو إضافة المتغيرات
            $envVariables = [
                'WHMCS_API_URL' => $request->api_url,
                'WHMCS_API_IDENTIFIER' => $request->api_identifier ?? '',
                'WHMCS_API_SECRET' => $request->api_secret ?? '',
                'WHMCS_ACCESS_TOKEN' => $request->access_token ?? '',
            ];

            foreach ($envVariables as $key => $value) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}\n";
                }
            }

            file_put_contents($envPath, $envContent);

            // مسح ذاكرة التخزين المؤقت للإعدادات
            Cache::forget('whmcs_connection_status');

            return redirect()->back()->with('success', 'تم تحديث إعدادات WHMCS بنجاح');
        } catch (\Exception $e) {
            Log::error('Error updating WHMCS settings', ['error' => $e->getMessage()]);
            
            return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage());
        }
    }
}