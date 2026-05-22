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
use App\Services\CoolifyApiService;
use App\Services\Whm\WhmApiService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        protected WhmApiService $whmApiService,
        protected CoolifyApiService $coolifyApiService
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $whmConnected = $this->whmApiService->isConfigured() && ($this->whmApiService->ping()['success'] ?? false);
        $coolifyStats = $this->coolifyApiService->getDashboardStats();

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

        $latestCustomers = Customer::orderBy('date_created', 'desc')->take(5)->get();
        $latestInvoices = Invoice::orderBy('date', 'desc')->take(5)->get();
        $latestTickets = Ticket::orderBy('date', 'desc')->take(5)->get();
        $unpaidInvoices = Invoice::where('status', 'Unpaid')->orderBy('duedate', 'asc')->take(5)->get();
        $urgentTickets = Ticket::whereIn('priority', ['High', 'Urgent'])
            ->where('status', '!=', 'Closed')
            ->orderBy('date', 'asc')
            ->take(5)
            ->get();

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
            'whmConnected',
            'coolifyStats',
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

    private function getMonthlyRevenue()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return Payment::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');
    }

    private function getYearlyRevenue()
    {
        return Payment::whereYear('date', Carbon::now()->year)->sum('amount');
    }

    private function getTotalRevenue()
    {
        return Payment::sum('amount');
    }

    private function getMonthlyLabels()
    {
        return [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر',
        ];
    }

    private function getMonthlyRevenueData()
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        for ($month = 1; $month <= 12; $month++) {
            $data[] = Payment::whereMonth('date', $month)->whereYear('date', $currentYear)->sum('amount');
        }

        return $data;
    }

    private function getMonthlyTicketsData()
    {
        $data = [];
        $currentYear = Carbon::now()->year;
        for ($month = 1; $month <= 12; $month++) {
            $data[] = Ticket::whereMonth('date', $month)->whereYear('date', $currentYear)->count();
        }

        return $data;
    }

    private function getTicketsByDepartmentLabels()
    {
        return Ticket::distinct()->pluck('department')->toArray();
    }

    private function getTicketsByDepartmentData()
    {
        $data = [];
        foreach (Ticket::distinct()->pluck('department') as $department) {
            $data[] = Ticket::where('department', $department)->count();
        }

        return $data;
    }

    private function getInvoicesByStatusData()
    {
        $data = [];
        foreach (['Paid', 'Unpaid', 'Cancelled', 'Refunded'] as $status) {
            $data[] = Invoice::where('status', $status)->count();
        }

        return $data;
    }

    private function getCustomersByStatusData()
    {
        $data = [];
        foreach (['Active', 'Inactive', 'Closed'] as $status) {
            $data[] = Customer::where('status', $status)->count();
        }

        return $data;
    }

    private function getTopSellingProductsLabels()
    {
        return Product::orderBy('sales_count', 'desc')->take(5)->pluck('name')->toArray();
    }

    private function getTopSellingProductsData()
    {
        return Product::orderBy('sales_count', 'desc')->take(5)->pluck('sales_count')->toArray();
    }
}
