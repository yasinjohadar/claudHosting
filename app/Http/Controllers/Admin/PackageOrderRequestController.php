<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageOrderRequest;
use App\Services\Coolify\HostProvisioningService;
use App\Services\CyberPanel\CyberPanelWebsiteService;
use App\Services\Whm\WhmAccountService;
use Illuminate\Http\Request;

class PackageOrderRequestController extends Controller
{
    public function __construct(
        protected WhmAccountService $whmAccounts,
        protected CyberPanelWebsiteService $cyberpanelWebsites,
        protected HostProvisioningService $hostProvisioning
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $orderRequests = $this->paginateOrderRequests($request);
        $stats = $this->orderRequestStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.order-requests.partials.list-results', compact('orderRequests'))->render(),
                'total' => $orderRequests->total(),
            ]);
        }

        return view('admin.order-requests.index', compact('orderRequests', 'stats'));
    }

    /**
     * @return array<string, int>
     */
    protected function orderRequestStats(): array
    {
        return [
            'total' => PackageOrderRequest::count(),
            'pending' => PackageOrderRequest::where('status', PackageOrderRequest::STATUS_PENDING)->count(),
            'contacted' => PackageOrderRequest::where('status', PackageOrderRequest::STATUS_CONTACTED)->count(),
            'converted' => PackageOrderRequest::where('status', PackageOrderRequest::STATUS_CONVERTED)->count(),
        ];
    }

    protected function paginateOrderRequests(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->buildOrderRequestsQuery($request)->paginate(15)->withQueryString();
    }

    protected function buildOrderRequestsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = PackageOrderRequest::with(['product', 'user'])->latest();

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->q).'%';
            $query->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, PackageOrderRequest::statuses())) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_cycle') && array_key_exists($request->billing_cycle, PackageOrderRequest::billingCycles())) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        return $query;
    }

    public function show($id)
    {
        $orderRequest = PackageOrderRequest::with(['product', 'user', 'coolifyWordpressSite', 'whmAccount', 'cyberpanelWebsite'])->findOrFail($id);
        $this->hostProvisioning->syncOrderProvisionStatus($orderRequest);
        $orderRequest->refresh();

        return view('admin.order-requests.show', compact('orderRequest'));
    }

    public function update(Request $request, $id)
    {
        $orderRequest = PackageOrderRequest::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,contacted,converted,cancelled',
        ]);

        $orderRequest->update(['status' => $request->status]);

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', 'تم تحديث حالة الطلب.');
    }

    public function provisionWhm($id)
    {
        $orderRequest = PackageOrderRequest::with('product')->findOrFail($id);
        $result = $this->whmAccounts->createFromOrder($orderRequest);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', $result['message']);
    }

    public function provisionCyberPanel($id)
    {
        $orderRequest = PackageOrderRequest::with('product')->findOrFail($id);
        $result = $this->cyberpanelWebsites->createFromOrder($orderRequest);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', $result['message']);
    }

    public function provisionHosting($id)
    {
        $orderRequest = PackageOrderRequest::with('product')->findOrFail($id);
        $result = $this->hostProvisioning->provisionFromOrder($orderRequest);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('admin.order-requests.show', $id)
            ->with('success', $result['message'].' — تتبع الحالة من صفحة موقع WordPress.');
    }
}
