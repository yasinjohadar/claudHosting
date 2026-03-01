<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\InvoicesExport;
use App\Exports\ProductsExport;
use App\Exports\TicketsExport;
use App\Services\ReportService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth');
    }

    /**
     * Display reports dashboard
     */
    public function index()
    {
        $stats = $this->reportService->getDatabaseStats();
        $recentActivities = $this->reportService->getRecentActivities(10);
        $topCustomers = $this->reportService->getTopCustomers(5);

        return view('reports.index', compact('stats', 'recentActivities', 'topCustomers'));
    }

    /**
     * Display customers report
     */
    public function customers(Request $request)
    {
        $filters = $request->only(['status', 'country', 'search']);
        $customers = \App\Models\Customer::query();

        if (!empty($filters['status'])) {
            $customers->where('status', $filters['status']);
        }
        if (!empty($filters['country'])) {
            $customers->where('country', $filters['country']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $customers->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $customers->paginate(15);
        $countries = \App\Models\Customer::distinct()->pluck('country');

        return view('reports.customers', compact('customers', 'filters', 'countries'));
    }

    /**
     * Display invoices report
     */
    public function invoices(Request $request)
    {
        $filters = $request->only(['status', 'paymentmethod', 'date_from', 'date_to']);
        $invoices = \App\Models\Invoice::query();

        if (!empty($filters['status'])) {
            $invoices->where('status', $filters['status']);
        }
        if (!empty($filters['paymentmethod'])) {
            $invoices->where('paymentmethod', $filters['paymentmethod']);
        }
        if (!empty($filters['date_from'])) {
            $invoices->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $invoices->where('date', '<=', $filters['date_to']);
        }

        $invoices = $invoices->paginate(15);
        $paymentMethods = \App\Models\Invoice::distinct()->pluck('paymentmethod');

        return view('reports.invoices', compact('invoices', 'filters', 'paymentMethods'));
    }

    /**
     * Display products report
     */
    public function products(Request $request)
    {
        $filters = $request->only(['type', 'status', 'search']);
        $products = \App\Models\Product::query();

        if (!empty($filters['type'])) {
            $products->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $products->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $products->where('name', 'like', "%{$filters['search']}%");
        }

        $products = $products->paginate(15);
        $types = \App\Models\Product::distinct()->pluck('type');

        return view('reports.products', compact('products', 'filters', 'types'));
    }

    /**
     * Display tickets report
     */
    public function tickets(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'department', 'date_from', 'date_to']);
        $tickets = \App\Models\Ticket::query();

        if (!empty($filters['status'])) {
            $tickets->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $tickets->where('priority', $filters['priority']);
        }
        if (!empty($filters['department'])) {
            $tickets->where('department', $filters['department']);
        }
        if (!empty($filters['date_from'])) {
            $tickets->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $tickets->where('date', '<=', $filters['date_to']);
        }

        $tickets = $tickets->paginate(15);
        $departments = \App\Models\Ticket::distinct()->pluck('department');

        return view('reports.tickets', compact('tickets', 'filters', 'departments'));
    }

    /**
     * Export customers to Excel
     */
    public function exportCustomers(Request $request)
    {
        $filters = $request->only(['status', 'country', 'search']);

        return Excel::download(
            new CustomersExport($filters),
            'customers_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export invoices to Excel
     */
    public function exportInvoices(Request $request)
    {
        $filters = $request->only(['status', 'paymentmethod', 'date_from', 'date_to']);

        return Excel::download(
            new InvoicesExport($filters),
            'invoices_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export products to Excel
     */
    public function exportProducts(Request $request)
    {
        $filters = $request->only(['type', 'status', 'search']);

        return Excel::download(
            new ProductsExport($filters),
            'products_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export tickets to Excel
     */
    public function exportTickets(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'department', 'date_from', 'date_to']);

        return Excel::download(
            new TicketsExport($filters),
            'tickets_' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }
}
