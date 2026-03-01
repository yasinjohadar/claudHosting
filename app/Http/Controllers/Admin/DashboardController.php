<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->middleware('auth');
    }

    /**
     * عرض لوحة التحكم الرئيسية
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // التحقق من اتصال WHMCS
        $whmcsConnected = $this->checkWhmcsConnection();
        
        // الإحصائيات العامة
        $stats = [
            'total_customers' => Customer::count(),
            'total_products' => Product::count(),
            'total_invoices' => Invoice::count(),
            'total_tickets' => Ticket::count(),
            'total_users' => User::count(),
            'total_roles' => Role::count(),
            'revenue_monthly' => $this->getMonthlyRevenue(),
            'revenue_yearly' => $this->getYearlyRevenue(),
            'revenue_total' => $this->getTotalRevenue(),
        ];
        
        // آخر العملاء
        $latestCustomers = Customer::orderBy('date_created', 'desc')->take(5)->get();
        
        // آخر الفواتير
        $latestInvoices = Invoice::orderBy('date', 'desc')->take(5)->get();
        
        // آخر التذاكر
        $latestTickets = Ticket::orderBy('date', 'desc')->take(5)->get();
        
        // الفواتير غير المدفوعة
        $unpaidInvoices = Invoice::where('status', 'Unpaid')
            ->orderBy('duedate', 'asc')
            ->take(5)
            ->get();
        
        // التذاكر العاجلة
        $urgentTickets = Ticket::whereIn('priority', ['High', 'Urgent'])
            ->where('status', '!=', 'Closed')
            ->orderBy('date', 'asc')
            ->take(5)
            ->get();
        
        // بيانات الرسوم البيانية
        $monthlyRevenueLabels = $this->getMonthlyLabels();
        $monthlyRevenueData = $this->getMonthlyRevenueData();
        
        $monthlyTicketsLabels = $this->getMonthlyLabels();
        $monthlyTicketsData = $this->getMonthlyTicketsData();
        
        $ticketsByDepartmentLabels = $this->getTicketsByDepartmentLabels();
        $ticketsByDepartmentData = $this->getTicketsByDepartmentData();
        
        $invoicesByStatusLabels = ['مدفوعة', 'غير مدفوعة', 'ملغاة', 'مستردة'];
        $invoicesByStatusData = $this->getInvoicesByStatusData();
        
        $customersByStatusLabels = ['نشط', 'غير نشط', 'مغلق'];
        $customersByStatusData = $this->getCustomersByStatusData();
        
        $topSellingProductsLabels = $this->getTopSellingProductsLabels();
        $topSellingProductsData = $this->getTopSellingProductsData();
        
        return view('admin.dashboard', compact(
            'whmcsConnected',
            'stats',
            'latestCustomers',
            'latestInvoices',
            'latestTickets',
            'unpaidInvoices',
            'urgentTickets',
            'monthlyRevenueLabels',
            'monthlyRevenueData',
            'monthlyTicketsLabels',
            'monthlyTicketsData',
            'ticketsByDepartmentLabels',
            'ticketsByDepartmentData',
            'invoicesByStatusLabels',
            'invoicesByStatusData',
            'customersByStatusLabels',
            'customersByStatusData',
            'topSellingProductsLabels',
            'topSellingProductsData'
        ));
    }
    
    /**
     * التحقق من اتصال WHMCS
     *
     * @return bool
     */
    private function checkWhmcsConnection()
    {
        try {
            $response = $this->whmcsApiService->getProducts(1);
            return $response !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * الحصول على إيرادات الشهر الحالي
     *
     * @return float
     */
    private function getMonthlyRevenue()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        return Payment::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');
    }
    
    /**
     * الحصول على إيرادات السنة الحالية
     *
     * @return float
     */
    private function getYearlyRevenue()
    {
        $currentYear = Carbon::now()->year;
        
        return Payment::whereYear('date', $currentYear)
            ->sum('amount');
    }
    
    /**
     * الحصول على إجمالي الإيرادات
     *
     * @return float
     */
    private function getTotalRevenue()
    {
        return Payment::sum('amount');
    }
    
    /**
     * الحصول على تسميات الأشهر
     *
     * @return array
     */
    private function getMonthlyLabels()
    {
        return [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];
    }
    
    /**
     * الحصول على بيانات الإيرادات الشهرية
     *
     * @return array
     */
    private function getMonthlyRevenueData()
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        
        for ($month = 1; $month <= 12; $month++) {
            $revenue = Payment::whereMonth('date', $month)
                ->whereYear('date', $currentYear)
                ->sum('amount');
                
            $data[] = $revenue;
        }
        
        return $data;
    }
    
    /**
     * الحصول على بيانات التذاكر الشهرية
     *
     * @return array
     */
    private function getMonthlyTicketsData()
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        
        for ($month = 1; $month <= 12; $month++) {
            $tickets = Ticket::whereMonth('date', $month)
                ->whereYear('date', $currentYear)
                ->count();
                
            $data[] = $tickets;
        }
        
        return $data;
    }
    
    /**
     * الحصول على تسميات أقسام التذاكر
     *
     * @return array
     */
    private function getTicketsByDepartmentLabels()
    {
        return Ticket::distinct()->pluck('department')->toArray();
    }
    
    /**
     * الحصول على بيانات التذاكر حسب القسم
     *
     * @return array
     */
    private function getTicketsByDepartmentData()
    {
        $departments = Ticket::distinct()->pluck('department');
        $data = [];
        
        foreach ($departments as $department) {
            $count = Ticket::where('department', $department)->count();
            $data[] = $count;
        }
        
        return $data;
    }
    
    /**
     * الحصول على بيانات الفواتير حسب الحالة
     *
     * @return array
     */
    private function getInvoicesByStatusData()
    {
        $statuses = ['Paid', 'Unpaid', 'Cancelled', 'Refunded'];
        $data = [];
        
        foreach ($statuses as $status) {
            $count = Invoice::where('status', $status)->count();
            $data[] = $count;
        }
        
        return $data;
    }
    
    /**
     * الحصول على بيانات العملاء حسب الحالة
     *
     * @return array
     */
    private function getCustomersByStatusData()
    {
        $statuses = ['Active', 'Inactive', 'Closed'];
        $data = [];
        
        foreach ($statuses as $status) {
            $count = Customer::where('status', $status)->count();
            $data[] = $count;
        }
        
        return $data;
    }
    
    /**
     * الحصول على تسميات المنتجات الأكثر مبيعًا
     *
     * @return array
     */
    private function getTopSellingProductsLabels()
    {
        $products = Product::orderBy('sales_count', 'desc')->take(5)->get();
        return $products->pluck('name')->toArray();
    }
    
    /**
     * الحصول على بيانات المنتجات الأكثر مبيعًا
     *
     * @return array
     */
    private function getTopSellingProductsData()
    {
        $products = Product::orderBy('sales_count', 'desc')->take(5)->get();
        return $products->pluck('sales_count')->toArray();
    }
}