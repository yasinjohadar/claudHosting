<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Client\ClientAssetService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected ClientAssetService $clientAssets)
    {
        $this->middleware('auth');
    }

    /**
     * عملاء الاستضافة = مستخدمو النظام المرتبطون بحسابات WHM/cPanel.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount('whmAccounts')
            ->with(['whmAccounts' => fn ($q) => $q->orderByDesc('joined_at')->limit(5)])
            ->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->q).'%';
            $query->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if ($request->boolean('has_whm')) {
            $query->whereHas('whmAccounts');
        }

        $clients = $query->paginate(15)->withQueryString();
        $configured = app(\App\Services\Whm\WhmApiService::class)->isConfigured();

        return view('admin.customers.index', compact('clients', 'configured'));
    }

    public function create()
    {
        return redirect()
            ->route('users.create')
            ->with('success', 'لإضافة عميل جديد، أنشئ مستخدماً في النظام ثم اربطه بحسابات cPanel من قسم WHM.');
    }

    public function store(Request $request)
    {
        return redirect()->route('users.create');
    }

    public function show($id)
    {
        $client = User::with([
            'whmAccounts' => fn ($q) => $q->orderByDesc('joined_at'),
            'clientCoolifyTeam',
        ])
            ->withCount('whmAccounts')
            ->findOrFail($id);

        $configured = app(\App\Services\Whm\WhmApiService::class)->isConfigured();
        $coolifyConfigured = app(\App\Services\CoolifyApiService::class)->isConfigured();
        $clientDomains = $this->clientAssets->domainsForUser($client->id);
        $clientProjects = $this->clientAssets->coolifyProjectsForUser($client->id);
        $coolifyTeamLink = $client->clientCoolifyTeam;

        return view('admin.customers.show', compact(
            'client',
            'configured',
            'coolifyConfigured',
            'clientDomains',
            'clientProjects',
            'coolifyTeamLink'
        ));
    }

    public function edit($id)
    {
        return redirect()->route('users.edit', $id);
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('users.edit', $id);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->whmAccounts()->where('status', '!=', 'terminated')->exists()) {
            return back()->with('error', 'لا يمكن حذف مستخدم مرتبط بحسابات استضافة نشطة — ألغِ الربط من WHM أولاً.');
        }

        return redirect()
            ->route('users.index')
            ->with('error', 'حذف العملاء يتم من صفحة المستخدمين.');
    }

    public function productSuspend(Request $request, string $id, int $serviceId)
    {
        return back()->with('error', 'إدارة خدمات WHMCS لم تعد متاحة — استخدم حسابات WHM/cPanel.');
    }

    public function productUnsuspend(string $id, int $serviceId)
    {
        return back()->with('error', 'إدارة خدمات WHMCS لم تعد متاحة — استخدم حسابات WHM/cPanel.');
    }

    public function productTerminate(string $id, int $serviceId)
    {
        return back()->with('error', 'إدارة خدمات WHMCS لم تعد متاحة — استخدم حسابات WHM/cPanel.');
    }
}
