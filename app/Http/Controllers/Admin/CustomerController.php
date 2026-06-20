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
        $clients = $this->paginateClients($request);
        $configured = app(\App\Services\Whm\WhmApiService::class)->isConfigured();
        $stats = $this->clientStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.customers.partials.list-results', compact('clients'))->render(),
                'total' => $clients->total(),
            ]);
        }

        return view('admin.customers.index', compact('clients', 'configured', 'stats'));
    }

    /**
     * @return array<string, int>
     */
    protected function clientStats(): array
    {
        return [
            'total' => User::count(),
            'with_whm' => User::whereHas('whmAccounts')->count(),
            'without_whm' => User::whereDoesntHave('whmAccounts')->count(),
            'active' => User::where('is_active', true)->where('status', 'active')->count(),
            'inactive' => User::where(function ($q) {
                $q->where('is_active', false)
                    ->orWhereIn('status', ['inactive', 'banned']);
            })->count(),
        ];
    }

    protected function paginateClients(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->buildClientsQuery($request);

        return $query->paginate(15)->withQueryString();
    }

    protected function buildClientsQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query()
            ->withCount([
                'whmAccounts',
                'clientDomains',
                'whmAccounts as whm_active_accounts_count' => fn ($q) => $q->where('status', '!=', 'terminated'),
            ])
            ->with(['whmAccounts' => fn ($q) => $q->orderByDesc('joined_at')->limit(3)]);

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->q).'%';
            $query->where(function ($qb) use ($term) {
                $qb->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('username', 'like', $term);
            });
        }

        if ($request->boolean('has_whm')) {
            $query->whereHas('whmAccounts');
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive', 'banned'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sort = (string) $request->query('sort', 'name');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($sort === 'created') {
            $query->orderBy('created_at', $dir);
        } elseif ($sort === 'whm') {
            $query->orderBy('whm_accounts_count', $dir);
        } else {
            $query->orderBy('name', $dir);
        }

        return $query;
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

        if ((int) $user->id === (int) auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك.');
        }

        if ($user->whmAccounts()->where('status', '!=', 'terminated')->exists()) {
            return back()->with('error', 'لا يمكن حذف مستخدم مرتبط بحسابات استضافة نشطة — ألغِ الربط من WHM أولاً.');
        }

        $user->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'تم حذف العميل بنجاح.');
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
