<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\CustomerProduct;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->middleware('permission:reports.view');
    }

    /**
     * عرض صفحة التقارير الرئيسية
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * تقرير المبيعات
     */
    public function sales(Request $request)
    {
        $period = $request->input('period', 'month'); // day, week, month, year
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // الحصول على الفواتير المدفوعة في الفترة الحالية
        $currentInvoices = Invoice::where('status', 'Paid')
            ->whereBetween('datepaid', [$startDate, $endDate])
            ->get();
        
        // الحصول على الفواتير المدفوعة في الفترة السابقة
        $previousInvoices = Invoice::where('status', 'Paid')
            ->whereBetween('datepaid', [$previousStartDate, $previousEndDate])
            ->get();
        
        // حساب الإجماليات
        $currentTotal = $currentInvoices->sum('total');
        $previousTotal = $previousInvoices->sum('total');
        
        // حساب نسبة التغيير
        $changePercentage = $previousTotal > 0 
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 2) 
            : 100;
        
        // الحصول على الفواتير حسب الحالة
        $invoiceStatuses = Invoice::select('status')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(total) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        
        // الحصول على المنتجات الأكثر مبيعاً
        $topProducts = Product::withCount(['customers' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('customer_products.created_at', [$startDate, $endDate]);
        }])
        ->orderBy('customers_count', 'desc')
        ->take(10)
        ->get();
        
        // الحصول على أفضل العملاء
        $topCustomers = Customer::select('customers.*')
            ->selectRaw('sum(invoices.total) as total_spent')
            ->join('invoices', 'customers.whmcs_id', '=', 'invoices.whmcs_client_id')
            ->where('invoices.status', 'Paid')
            ->whereBetween('invoices.datepaid', [$startDate, $endDate])
            ->groupBy('customers.id')
            ->orderBy('total_spent', 'desc')
            ->take(10)
            ->get();
        
        // بيانات الرسم البياني للمبيعات اليومية/الشهرية
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourTotal = Invoice::where('status', 'Paid')
                    ->whereBetween('datepaid', [$hourStart, $hourEnd])
                    ->sum('total');
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourTotal
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayTotal = Invoice::where('status', 'Paid')
                    ->whereBetween('datepaid', [$dayStart, $dayEnd])
                    ->sum('total');
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayTotal
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayTotal = Invoice::where('status', 'Paid')
                    ->whereBetween('datepaid', [$dayStart, $dayEnd])
                    ->sum('total');
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayTotal
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthTotal = Invoice::where('status', 'Paid')
                    ->whereBetween('datepaid', [$monthStart, $monthEnd])
                    ->sum('total');
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthTotal
                ];
            }
        }
        
        return view('admin.reports.sales', [
            'currentTotal' => $currentTotal,
            'previousTotal' => $previousTotal,
            'changePercentage' => $changePercentage,
            'invoiceStatuses' => $invoiceStatuses,
            'topProducts' => $topProducts,
            'topCustomers' => $topCustomers,
            'chartData' => $chartData,
            'period' => $period
        ]);
    }

    /**
     * تقرير العملاء
     */
    public function customers(Request $request)
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // الحصول على العملاء الجدد في الفترة الحالية
        $currentCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // الحصول على العملاء الجدد في الفترة السابقة
        $previousCustomers = Customer::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();
        
        // حساب نسبة التغيير
        $changePercentage = $previousCustomers > 0 
            ? round((($currentCustomers - $previousCustomers) / $previousCustomers) * 100, 2) 
            : 100;
        
        // إجمالي العملاء
        $totalCustomers = Customer::count();
        
        // العملاء النشطين (لديهم منتجات نشطة)
        $activeCustomers = Customer::whereHas('products', function($query) {
            $query->where('status', 'Active');
        })->count();
        
        // العملاء غير النشطين
        $inactiveCustomers = $totalCustomers - $activeCustomers;
        
        // العملاء حسب البلد
        $customersByCountry = Customer::select('country')
            ->selectRaw('count(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();
        
        // العملاء حipher
        $customersByCity = Customer::select('city')
            ->selectRaw('count(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();
        
        // بيانات الرسم البياني للعملاء الجدد
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourCount = Customer::whereBetween('created_at', [$hourStart, $hourEnd])->count();
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourCount
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayCount = Customer::whereBetween('created_at', [$dayStart, $dayEnd])->count();
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayCount
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayCount = Customer::whereBetween('created_at', [$dayStart, $dayEnd])->count();
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayCount
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthCount = Customer::whereBetween('created_at', [$monthStart, $monthEnd])->count();
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthCount
                ];
            }
        }
        
        return view('admin.reports.customers', [
            'currentCustomers' => $currentCustomers,
            'previousCustomers' => $previousCustomers,
            'changePercentage' => $changePercentage,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'inactiveCustomers' => $inactiveCustomers,
            'customersByCountry' => $customersByCountry,
            'customersByCity' => $customersByCity,
            'chartData' => $chartData,
            'period' => $period
        ]);
    }

    /**
     * تقرير المنتجات
     */
    public function products(Request $request)
    {
        $period = $request->input('period', 'month');
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // إجمالي المنتجات
        $totalProducts = Product::count();
        
        // المنتجات النشطة
        $activeProducts = Product::where('status', 'Active')->count();
        
        // المنتجات غير النشطة
        $inactiveProducts = $totalProducts - $activeProducts;
        
        // المنتجات المباعة في الفترة الحالية
        $currentSales = CustomerProduct::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // المنتجات المباعة في الفترة السابقة
        $previousSales = CustomerProduct::whereBetween('created_at', [$previousStartDate, $previousEndDate])->count();
        
        // حساب نسبة التغيير
        $changePercentage = $previousSales > 0 
            ? round((($currentSales - $previousSales) / $previousSales) * 100, 2) 
            : 100;
        
        // المنتجات الأكثر مبيعاً
        $topProducts = Product::withCount(['customers' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('customer_products.created_at', [$startDate, $endDate]);
        }])
        ->orderBy('customers_count', 'desc')
        ->take(10)
        ->get();
        
        // المنتجات حسب الفئة
        $productsByGroup = Product::select('product_group')
            ->selectRaw('count(*) as count')
            ->whereNotNull('product_group')
            ->groupBy('product_group')
            ->orderBy('count', 'desc')
            ->get();
        
        // المنتجات حسب الحالة
        $productsByStatus = Product::select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get();
        
        // بيانات الرسم البياني للمبيعات
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourCount = CustomerProduct::whereBetween('created_at', [$hourStart, $hourEnd])->count();
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourCount
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayCount = CustomerProduct::whereBetween('created_at', [$dayStart, $dayEnd])->count();
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayCount
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayCount = CustomerProduct::whereBetween('created_at', [$dayStart, $dayEnd])->count();
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayCount
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthCount = CustomerProduct::whereBetween('created_at', [$monthStart, $monthEnd])->count();
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthCount
                ];
            }
        }
        
        return view('admin.reports.products', [
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'inactiveProducts' => $inactiveProducts,
            'currentSales' => $currentSales,
            'previousSales' => $previousSales,
            'changePercentage' => $changePercentage,
            'topProducts' => $topProducts,
            'productsByGroup' => $productsByGroup,
            'productsByStatus' => $productsByStatus,
            'chartData' => $chartData,
            'period' => $period
        ]);
    }

    /**
     * تقرير الفواتير
     */
    public function invoices(Request $request)
    {
        $period = $request->input('period', 'month');
        $status = $request->input('status', 'all');
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // بناء الاستعلام الأساسي
        $query = Invoice::whereBetween('created_at', [$startDate, $endDate]);
        
        // تطبيق فلتر الحالة إذا تم تحديده
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // الحصول على الفواتير في الفترة الحالية
        $currentInvoices = $query->get();
        
        // بناء الاستعلام الأساسي للفترة السابقة
        $previousQuery = Invoice::whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        
        // تطبيق فلتر الحالة إذا تم تحديده
        if ($status !== 'all') {
            $previousQuery->where('status', $status);
        }
        
        // الحصول على الفواتير في الفترة السابقة
        $previousInvoices = $previousQuery->get();
        
        // حساب الإجماليات
        $currentCount = $currentInvoices->count();
        $currentTotal = $currentInvoices->sum('total');
        $previousCount = $previousInvoices->count();
        $previousTotal = $previousInvoices->sum('total');
        
        // حساب نسبة التغيير
        $countChangePercentage = $previousCount > 0 
            ? round((($currentCount - $previousCount) / $previousCount) * 100, 2) 
            : 100;
            
        $totalChangePercentage = $previousTotal > 0 
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 2) 
            : 100;
        
        // الفواتير حسب الحالة
        $invoicesByStatus = Invoice::select('status')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(total) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        
        // الفواتير حسب طريقة الدفع
        $invoicesByPaymentMethod = Invoice::select('paymentmethod')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(total) as total')
            ->whereNotNull('paymentmethod')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('paymentmethod')
            ->get();
        
        // الفواتير غير المدفوعة
        $unpaidInvoices = Invoice::where('status', 'Unpaid')
            ->whereBetween('duedate', [$now, $now->copy()->addDays(30)])
            ->orderBy('duedate', 'asc')
            ->take(10)
            ->get();
        
        // بيانات الرسم البياني للفواتير
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourQuery = Invoice::whereBetween('created_at', [$hourStart, $hourEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $hourQuery->where('status', $status);
                }
                
                $hourCount = $hourQuery->count();
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourCount
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Invoice::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $dayQuery->where('status', $status);
                }
                
                $dayCount = $dayQuery->count();
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayCount
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Invoice::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $dayQuery->where('status', $status);
                }
                
                $dayCount = $dayQuery->count();
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayCount
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthQuery = Invoice::whereBetween('created_at', [$monthStart, $monthEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $monthQuery->where('status', $status);
                }
                
                $monthCount = $monthQuery->count();
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthCount
                ];
            }
        }
        
        return view('admin.reports.invoices', [
            'currentCount' => $currentCount,
            'currentTotal' => $currentTotal,
            'previousCount' => $previousCount,
            'previousTotal' => $previousTotal,
            'countChangePercentage' => $countChangePercentage,
            'totalChangePercentage' => $totalChangePercentage,
            'invoicesByStatus' => $invoicesByStatus,
            'invoicesByPaymentMethod' => $invoicesByPaymentMethod,
            'unpaidInvoices' => $unpaidInvoices,
            'chartData' => $chartData,
            'period' => $period,
            'status' => $status
        ]);
    }

    /**
     * تقرير التذاكر
     */
    public function tickets(Request $request)
    {
        $period = $request->input('period', 'month');
        $status = $request->input('status', 'all');
        $department = $request->input('department', 'all');
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // بناء الاستعلام الأساسي
        $query = Ticket::whereBetween('created_at', [$startDate, $endDate]);
        
        // تطبيق فلتر الحالة إذا تم تحديده
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // تطبيق فلتر القسم إذا تم تحديده
        if ($department !== 'all') {
            $query->where('department', $department);
        }
        
        // الحصول على التذاكر في الفترة الحالية
        $currentTickets = $query->get();
        
        // بناء الاستعلام الأساسي للفترة السابقة
        $previousQuery = Ticket::whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        
        // تطبيق فلتر الحالة إذا تم تحديده
        if ($status !== 'all') {
            $previousQuery->where('status', $status);
        }
        
        // تطبيق فلتر القسم إذا تم تحديده
        if ($department !== 'all') {
            $previousQuery->where('department', $department);
        }
        
        // الحصول على التذاكر في الفترة السابقة
        $previousTickets = $previousQuery->get();
        
        // حساب الإجماليات
        $currentCount = $currentTickets->count();
        $previousCount = $previousTickets->count();
        
        // حساب نسبة التغيير
        $changePercentage = $previousCount > 0 
            ? round((($currentCount - $previousCount) / $previousCount) * 100, 2) 
            : 100;
        
        // التذاكر حسب الحالة
        $ticketsByStatus = Ticket::select('status')
            ->selectRaw('count(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        
        // التذاكر حسب القسم
        $ticketsByDepartment = Ticket::select('department')
            ->selectRaw('count(*) as count')
            ->whereNotNull('department')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('department')
            ->get();
        
        // التذاكر حسب الأولوية
        $ticketsByPriority = Ticket::select('priority')
            ->selectRaw('count(*) as count')
            ->whereNotNull('priority')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('priority')
            ->get();
        
        // التذاكر المفتوحة منذ فترة طويلة
        $oldOpenTickets = Ticket::where('status', 'Open')
            ->where('created_at', '<', $now->copy()->subDays(7))
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get();
        
        // بيانات الرسم البياني للتذاكر
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourQuery = Ticket::whereBetween('created_at', [$hourStart, $hourEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $hourQuery->where('status', $status);
                }
                
                // تطبيق فلتر القسم إذا تم تحديده
                if ($department !== 'all') {
                    $hourQuery->where('department', $department);
                }
                
                $hourCount = $hourQuery->count();
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourCount
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Ticket::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $dayQuery->where('status', $status);
                }
                
                // تطبيق فلتر القسم إذا تم تحديده
                if ($department !== 'all') {
                    $dayQuery->where('department', $department);
                }
                
                $dayCount = $dayQuery->count();
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayCount
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Ticket::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $dayQuery->where('status', $status);
                }
                
                // تطبيق فلتر القسم إذا تم تحديده
                if ($department !== 'all') {
                    $dayQuery->where('department', $department);
                }
                
                $dayCount = $dayQuery->count();
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayCount
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthQuery = Ticket::whereBetween('created_at', [$monthStart, $monthEnd]);
                
                // تطبيق فلتر الحالة إذا تم تحديده
                if ($status !== 'all') {
                    $monthQuery->where('status', $status);
                }
                
                // تطبيق فلتر القسم إذا تم تحديده
                if ($department !== 'all') {
                    $monthQuery->where('department', $department);
                }
                
                $monthCount = $monthQuery->count();
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthCount
                ];
            }
        }
        
        return view('admin.reports.tickets', [
            'currentCount' => $currentCount,
            'previousCount' => $previousCount,
            'changePercentage' => $changePercentage,
            'ticketsByStatus' => $ticketsByStatus,
            'ticketsByDepartment' => $ticketsByDepartment,
            'ticketsByPriority' => $ticketsByPriority,
            'oldOpenTickets' => $oldOpenTickets,
            'chartData' => $chartData,
            'period' => $period,
            'status' => $status,
            'department' => $department
        ]);
    }

    /**
     * تقرير المدفوعات
     */
    public function payments(Request $request)
    {
        $period = $request->input('period', 'month');
        $method = $request->input('method', 'all');
        $now = Carbon::now();
        
        // تحديد نطاق التاريخ بناءً على الفترة المحددة
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = $now->copy()->subDay()->startOfDay();
                $previousEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $previousStartDate = $now->copy()->subWeek()->startOfWeek();
                $previousEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // بناء الاستعلام الأساسي
        $query = Payment::whereBetween('created_at', [$startDate, $endDate]);
        
        // تطبيق فلتر طريقة الدفع إذا تم تحديده
        if ($method !== 'all') {
            $query->where('method', $method);
        }
        
        // الحصول على المدفوعات في الفترة الحالية
        $currentPayments = $query->get();
        
        // بناء الاستعلام الأساسي للفترة السابقة
        $previousQuery = Payment::whereBetween('created_at', [$previousStartDate, $previousEndDate]);
        
        // تطبيق فلتر طريقة الدفع إذا تم تحديده
        if ($method !== 'all') {
            $previousQuery->where('method', $method);
        }
        
        // الحصول على المدفوعات في الفترة السابقة
        $previousPayments = $previousQuery->get();
        
        // حساب الإجماليات
        $currentCount = $currentPayments->count();
        $currentTotal = $currentPayments->sum('amount');
        $previousCount = $previousPayments->count();
        $previousTotal = $previousPayments->sum('amount');
        
        // حساب نسبة التغيير
        $countChangePercentage = $previousCount > 0 
            ? round((($currentCount - $previousCount) / $previousCount) * 100, 2) 
            : 100;
            
        $totalChangePercentage = $previousTotal > 0 
            ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 2) 
            : 100;
        
        // المدفوعات حسب طريقة الدفع
        $paymentsByMethod = Payment::select('method')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(amount) as total')
            ->whereNotNull('method')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('method')
            ->get();
        
        // المدفوعات حسب الحالة
        $paymentsByStatus = Payment::select('status')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        
        // آخر المدفوعات
        $recentPayments = Payment::with(['customer', 'invoice'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // بيانات الرسم البياني للمدفوعات
        $chartData = [];
        
        if ($period === 'day') {
            // بيانات بالساعة
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStart = $now->copy()->hour($hour)->minute(0)->second(0);
                $hourEnd = $now->copy()->hour($hour)->minute(59)->second(59);
                
                $hourQuery = Payment::whereBetween('created_at', [$hourStart, $hourEnd]);
                
                // تطبيق فلتر طريقة الدفع إذا تم تحديده
                if ($method !== 'all') {
                    $hourQuery->where('method', $method);
                }
                
                $hourTotal = $hourQuery->sum('amount');
                
                $chartData[] = [
                    'label' => $hour . ':00',
                    'value' => $hourTotal
                ];
            }
        } elseif ($period === 'week') {
            // بيانات بالأيام
            for ($day = 0; $day < 7; $day++) {
                $dayDate = $now->copy()->startOfWeek()->addDays($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Payment::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر طريقة الدفع إذا تم تحديده
                if ($method !== 'all') {
                    $dayQuery->where('method', $method);
                }
                
                $dayTotal = $dayQuery->sum('amount');
                
                $chartData[] = [
                    'label' => $dayDate->format('D'),
                    'value' => $dayTotal
                ];
            }
        } elseif ($period === 'month') {
            // بيانات بالأيام
            $daysInMonth = $now->daysInMonth;
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dayDate = $now->copy()->day($day);
                $dayStart = $dayDate->copy()->startOfDay();
                $dayEnd = $dayDate->copy()->endOfDay();
                
                $dayQuery = Payment::whereBetween('created_at', [$dayStart, $dayEnd]);
                
                // تطبيق فلتر طريقة الدفع إذا تم تحديده
                if ($method !== 'all') {
                    $dayQuery->where('method', $method);
                }
                
                $dayTotal = $dayQuery->sum('amount');
                
                $chartData[] = [
                    'label' => $day,
                    'value' => $dayTotal
                ];
            }
        } else {
            // بيانات بالشهور
            for ($month = 1; $month <= 12; $month++) {
                $monthDate = $now->copy()->month($month);
                $monthStart = $monthDate->copy()->startOfMonth();
                $monthEnd = $monthDate->copy()->endOfMonth();
                
                $monthQuery = Payment::whereBetween('created_at', [$monthStart, $monthEnd]);
                
                // تطبيق فلتر طريقة الدفع إذا تم تحديده
                if ($method !== 'all') {
                    $monthQuery->where('method', $method);
                }
                
                $monthTotal = $monthQuery->sum('amount');
                
                $chartData[] = [
                    'label' => $monthDate->format('M'),
                    'value' => $monthTotal
                ];
            }
        }
        
        return view('admin.reports.payments', [
            'currentCount' => $currentCount,
            'currentTotal' => $currentTotal,
            'previousCount' => $previousCount,
            'previousTotal' => $previousTotal,
            'countChangePercentage' => $countChangePercentage,
            'totalChangePercentage' => $totalChangePercentage,
            'paymentsByMethod' => $paymentsByMethod,
            'paymentsByStatus' => $paymentsByStatus,
            'recentPayments' => $recentPayments,
            'chartData' => $chartData,
            'period' => $period,
            'method' => $method
        ]);
    }
}