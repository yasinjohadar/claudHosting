<?php

namespace App\Http\Controllers\Admin\Domain;

use App\Http\Controllers\Controller;
use App\Models\WhmcsDomain;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;

class WhmcsDomainController extends Controller
{
    public function __construct(protected WhmcsApiService $whmcs)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = WhmcsDomain::query()->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = '%'.$request->q.'%';
            $query->where('domain', 'like', $q);
        }

        if ($request->filled('expiring')) {
            $query->whereNotNull('expirydate')
                ->where('expirydate', '>', now())
                ->where('expirydate', '<=', now()->addDays(30));
        }

        $domains = $query->orderByDesc('expirydate')->paginate(25)->withQueryString();

        $needsSync = WhmcsDomain::count() === 0;

        $stats = [
            'total' => WhmcsDomain::count(),
            'active' => WhmcsDomain::where('status', 'Active')->count(),
            'expiring' => WhmcsDomain::query()
                ->whereNotNull('expirydate')
                ->where('expirydate', '>', now())
                ->where('expirydate', '<=', now()->addDays(30))
                ->count(),
        ];

        return view('admin.domains.whmcs.index', compact('domains', 'needsSync', 'stats'));
    }

    public function show(WhmcsDomain $whmcs_domain)
    {
        $whmcs_domain->load('customer');

        return view('admin.domains.whmcs.show', ['domain' => $whmcs_domain]);
    }

    public function sync(Request $request)
    {
        $result = $this->whmcs->syncAllWhmcsDomains();

        return redirect()->route('admin.domains.whmcs.index')
            ->with('success', "تمت المزامنة: {$result['synced']} نطاقاً من {$result['customers']} عميل".($result['errors'] > 0 ? " ({$result['errors']} أخطاء)" : ''));
    }
}
