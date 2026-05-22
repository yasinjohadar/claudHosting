<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageOrderRequest;
use App\Services\Coolify\HostProvisioningService;
use App\Services\Whm\WhmAccountService;
use Illuminate\Http\Request;

class PackageOrderRequestController extends Controller
{
    public function __construct(
        protected WhmAccountService $whmAccounts,
        protected HostProvisioningService $hostProvisioning
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = PackageOrderRequest::with(['product', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orderRequests = $query->paginate(15)->withQueryString();

        return view('admin.order-requests.index', compact('orderRequests'));
    }

    public function show($id)
    {
        $orderRequest = PackageOrderRequest::with(['product', 'user', 'coolifyWordpressSite', 'whmAccount'])->findOrFail($id);
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
