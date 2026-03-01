<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get customers statistics
     */
    public function getCustomersStats()
    {
        return [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'Active')->count(),
            'inactive' => Customer::where('status', 'Inactive')->count(),
            'by_country' => Customer::groupBy('country')
                ->selectRaw('country, count(*) as total')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'country')
                ->toArray(),
        ];
    }

    /**
     * Get invoices statistics
     */
    public function getInvoicesStats()
    {
        return [
            'total' => Invoice::count(),
            'paid' => Invoice::where('status', 'Paid')->count(),
            'unpaid' => Invoice::where('status', 'Unpaid')->count(),
            'overdue' => Invoice::where('status', 'Overdue')->count(),
            'total_revenue' => Invoice::sum('total'),
            'paid_revenue' => Invoice::where('status', 'Paid')->sum('total'),
            'unpaid_revenue' => Invoice::where('status', 'Unpaid')->sum('total'),
            'by_payment_method' => Invoice::groupBy('paymentmethod')
                ->selectRaw('paymentmethod, count(*) as count, sum(total) as total')
                ->get()
                ->map(function ($item) {
                    return [
                        'method' => $item->paymentmethod ?: 'No Payment',
                        'count' => $item->count,
                        'total' => $item->total,
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * Get products statistics
     */
    public function getProductsStats()
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('status', 'Active')->count(),
            'inactive' => Product::where('status', 'Inactive')->count(),
            'hidden' => Product::where('hidden', true)->count(),
            'by_type' => Product::groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Get tickets statistics
     */
    public function getTicketsStats()
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::whereIn('status', ['Open', 'In Progress'])->count(),
            'closed' => Ticket::where('status', 'Closed')->count(),
            'on_hold' => Ticket::where('status', 'On Hold')->count(),
            'by_priority' => Ticket::groupBy('priority')
                ->selectRaw('priority, count(*) as count')
                ->pluck('count', 'priority')
                ->toArray(),
            'by_department' => Ticket::groupBy('department')
                ->selectRaw('department, count(*) as count')
                ->pluck('count', 'department')
                ->toArray(),
        ];
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats()
    {
        return [
            'customers' => $this->getCustomersStats(),
            'invoices' => $this->getInvoicesStats(),
            'products' => $this->getProductsStats(),
            'tickets' => $this->getTicketsStats(),
        ];
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 20)
    {
        $customers = Customer::latest('synced_at')->limit($limit / 4)->get()
            ->map(fn($c) => [
                'type' => 'customer',
                'description' => 'تحديث العميل: ' . $c->fullname,
                'date' => $c->synced_at,
            ]);

        $invoices = Invoice::latest('synced_at')->limit($limit / 4)->get()
            ->map(fn($i) => [
                'type' => 'invoice',
                'description' => 'تحديث الفاتورة: ' . $i->invoice_number,
                'date' => $i->synced_at,
            ]);

        $products = Product::latest('synced_at')->limit($limit / 4)->get()
            ->map(fn($p) => [
                'type' => 'product',
                'description' => 'تحديث المنتج: ' . $p->name,
                'date' => $p->synced_at,
            ]);

        $tickets = Ticket::latest('synced_at')->limit($limit / 4)->get()
            ->map(fn($t) => [
                'type' => 'ticket',
                'description' => 'تحديث التذكرة: ' . $t->subject,
                'date' => $t->synced_at,
            ]);

        return $customers->concat($invoices)->concat($products)->concat($tickets)
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    /**
     * Get top customers by invoices
     */
    public function getTopCustomers($limit = 10)
    {
        return Customer::withCount(['invoices as total_invoices' => function ($query) {
            $query->where('status', 'Paid');
        }])
            ->withSum(['invoices as total_spent' => function ($query) {
                $query->where('status', 'Paid');
            }], 'total')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }
}
