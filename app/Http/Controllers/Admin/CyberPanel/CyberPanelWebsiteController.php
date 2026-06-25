<?php

namespace App\Http\Controllers\Admin\CyberPanel;

use App\Http\Controllers\Controller;
use App\Models\CyberPanelWebsite;
use App\Models\User;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelSettingsService;
use App\Services\CyberPanel\CyberPanelWebsiteService;
use App\Services\CyberPanel\CyberPanelWordpressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CyberPanelWebsiteController extends Controller
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelWebsiteService $websites,
        protected CyberPanelSettingsService $settings,
        protected CyberPanelWordpressService $wordpress
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $items = $this->filteredQuery($request)->paginate(20)->withQueryString();
        $configured = $this->api->isConfigured();
        $supportsCloud = $this->api->supportsCloudOperations();
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();

        $stats = [
            'total' => CyberPanelWebsite::count(),
            'active' => CyberPanelWebsite::where('status', 'active')->count(),
            'wordpress' => CyberPanelWebsite::whereHas('wordpressSite', fn ($q) => $q->where('status', 'running'))->count(),
            'linked' => CyberPanelWebsite::whereNotNull('user_id')->count(),
        ];

        return view('admin.cyberpanel.websites.index', compact('items', 'configured', 'supportsCloud', 'clientUsers', 'stats'));
    }

    /**
     * @return Builder<CyberPanelWebsite>
     */
    protected function filteredQuery(Request $request): Builder
    {
        $query = CyberPanelWebsite::with(['client', 'wordpressSite'])->orderByDesc('joined_at')->orderBy('domain');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->q).'%';
            $query->where(function ($qb) use ($term) {
                $qb->where('domain', 'like', $term)
                    ->orWhere('owner', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        return $query;
    }

    public function sync()
    {
        $result = $this->websites->syncFromRemote();

        return redirect()->route('admin.cyberpanel.websites.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function create()
    {
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();
        $config = $this->settings->getConnectionConfig();
        $packages = $this->api->isConfigured() ? $this->websites->listPackagesForForms() : [];

        return view('admin.cyberpanel.websites.create', [
            'clientUsers' => $clientUsers,
            'defaultPackage' => $config['default_package'],
            'defaultPhp' => $config['default_php_version'],
            'defaultOwner' => $config['default_owner'],
            'packages' => $packages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:253',
            'owner' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'package' => 'required|string|max:128',
            'php_version' => 'nullable|string|max:32',
            'owner_password' => 'nullable|string|min:8|max:64',
            'user_id' => 'nullable|exists:users,id',
            'install_wordpress' => 'nullable|boolean',
            'ssl' => 'nullable|boolean',
        ]);

        $validated['install_wordpress'] = $request->boolean('install_wordpress');
        $validated['ssl'] = $request->boolean('ssl') ? 1 : 0;

        $result = $this->websites->createManual($validated);

        if (! ($result['success'] ?? false)) {
            return back()->with('error', $result['message'])->withInput();
        }

        return redirect()->route('admin.cyberpanel.websites.show', $result['website'])
            ->with('success', $result['message']);
    }

    public function show(CyberPanelWebsite $website)
    {
        $website->load(['client', 'wordpressSite', 'invoices' => fn ($q) => $q->latest('date')->limit(5)]);
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();
        $packages = $this->api->isConfigured() ? $this->websites->listPackagesForForms() : [];
        $panelUrl = $this->api->getPanelUrl();
        $supportsCloud = $this->api->supportsCloudOperations();

        return view('admin.cyberpanel.websites.show', compact('website', 'clientUsers', 'packages', 'panelUrl', 'supportsCloud'));
    }

    public function destroy(CyberPanelWebsite $website)
    {
        $result = $this->websites->terminate($website);

        return redirect()->route('admin.cyberpanel.websites.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function toggleStatus(Request $request, CyberPanelWebsite $website)
    {
        $result = $website->status === 'suspended'
            ? $this->websites->unsuspend($website)
            : $this->websites->suspend($website);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function changePackage(Request $request, CyberPanelWebsite $website)
    {
        $validated = $request->validate(['package' => 'required|string|max:128']);
        $result = $this->websites->changePackage($website, $validated['package']);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function assignClient(Request $request, CyberPanelWebsite $website)
    {
        $validated = $request->validate(['user_id' => 'nullable|exists:users,id']);
        $userId = isset($validated['user_id']) && $validated['user_id'] !== ''
            ? (int) $validated['user_id']
            : null;

        $result = $this->websites->assignClient($userId, $website);

        if ($request->wantsJson() || $request->ajax()) {
            $website->load('client');

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'html' => view('admin.cyberpanel.websites.partials.client-cell', [
                    'client' => $website->client,
                ])->render(),
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function renew(CyberPanelWebsite $website)
    {
        $result = $this->websites->renewSubscription($website);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function installWordpress(Request $request, CyberPanelWebsite $website)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'admin_user' => 'nullable|string|max:64',
            'admin_email' => 'nullable|email|max:255',
        ]);

        $result = $this->wordpress->installOnWebsite($website, $validated);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function panelRedirect()
    {
        if (! $this->api->isConfigured()) {
            return back()->with('error', 'CyberPanel غير مضبوط');
        }

        return redirect()->away($this->api->getPanelUrl());
    }
}
