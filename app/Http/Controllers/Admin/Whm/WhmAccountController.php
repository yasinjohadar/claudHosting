<?php

namespace App\Http\Controllers\Admin\Whm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhmAccount;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmServerStatusService;
use App\Services\Whm\WhmSettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WhmAccountController extends Controller
{
    public function __construct(
        protected WhmApiService $whmApi,
        protected WhmAccountService $accounts,
        protected WhmServerStatusService $serverStatus,
        protected WhmSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $accounts = $this->filteredQuery($request)->paginate(20)->withQueryString();
        $configured = $this->whmApi->isConfigured();
        $packages = WhmAccount::query()
            ->whereNotNull('package')
            ->where('package', '!=', '')
            ->distinct()
            ->orderBy('package')
            ->pluck('package');

        $clientUsers = User::query()
            ->orderBy('name')
            ->select(['id', 'name', 'email'])
            ->limit(500)
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('admin.whm.accounts.partials.table-body', compact('accounts', 'configured'))->render(),
                'pagination' => view('admin.whm.accounts.partials.pagination', compact('accounts'))->render(),
                'total' => $accounts->total(),
            ]);
        }

        $serverStatus = $configured ? $this->serverStatus->getStatus() : null;

        return view('admin.whm.accounts.index', compact(
            'accounts',
            'configured',
            'packages',
            'clientUsers',
            'serverStatus'
        ));
    }

    /**
     * @return Builder<WhmAccount>
     */
    protected function filteredQuery(Request $request): Builder
    {
        $query = WhmAccount::with('client')
            ->orderByDesc('joined_at')
            ->orderBy('domain');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('package')) {
            $query->where('package', $request->package);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->q).'%';
            $query->where(function ($qb) use ($term) {
                $qb->where('domain', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('client', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        return $query;
    }

    public function sync()
    {
        $result = $this->accounts->syncFromWhm();

        return redirect()->route('admin.whm.accounts.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function create()
    {
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();
        $defaultPackage = $this->settings->getConnectionConfig()['default_package'] ?? 'default';
        $packages = $this->whmApi->isConfigured()
            ? $this->accounts->listPackagesForForms()
            : [];

        return view('admin.whm.accounts.create', compact('clientUsers', 'defaultPackage', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:16|regex:/^[a-z][a-z0-9]{0,15}$/i',
            'domain' => 'required|string|max:253',
            'password' => 'required|string|min:8|max:64',
            'package' => 'required|string|max:128',
            'contactemail' => 'nullable|email|max:255',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if (! $this->whmApi->isConfigured()) {
            return back()->with('error', 'إعدادات WHM غير مكتملة')->withInput();
        }

        if (WhmAccount::where('username', $validated['username'])->exists()) {
            return back()->with('error', 'اسم المستخدم مستخدم مسبقاً')->withInput();
        }

        $create = $this->whmApi->createAccount([
            'username' => $validated['username'],
            'domain' => $validated['domain'],
            'password' => $validated['password'],
            'plan' => $validated['package'],
            'contactemail' => $validated['contactemail'] ?? '',
        ]);

        if (! ($create['success'] ?? false)) {
            return back()->with('error', $create['message'] ?? 'فشل إنشاء الحساب')->withInput();
        }

        $meta = is_array($create['data'] ?? null) ? $create['data'] : [];
        $email = ! empty($validated['contactemail'])
            ? strtolower($validated['contactemail'])
            : $this->accounts->extractEmailFromWhm($meta);

        $customerId = null;
        if (! empty($validated['user_id'])) {
            $user = User::with('customer')->find($validated['user_id']);
            $customerId = $user?->customer?->id;
        }

        $account = WhmAccount::create([
            'user_id' => $validated['user_id'] ?? null,
            'customer_id' => $customerId,
            'username' => $validated['username'],
            'domain' => $validated['domain'],
            'email' => $email,
            'joined_at' => $this->accounts->extractJoinedAtFromWhm($meta) ?? now(),
            'package' => $validated['package'],
            'status' => 'active',
            'metadata' => $meta,
        ]);

        $bootstrap = $this->accounts->bootstrapNewAccountSubscription($account);
        $message = 'تم إنشاء حساب cPanel بنجاح';
        if (! empty($bootstrap['invoice'])) {
            $message .= ' وتم إنشاء فاتورة الاشتراك';
        }

        $redirect = redirect()->route('admin.whm.accounts.show', $account)->with('success', $message);

        if (! empty($bootstrap['invoice'])) {
            $redirect->with('invoice_id', $bootstrap['invoice']->id);
        }

        return $redirect;
    }

    public function show(WhmAccount $account)
    {
        $account->load(['client', 'invoices' => fn ($q) => $q->latest('date')->limit(5)]);
        $configured = $this->whmApi->isConfigured();
        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();
        $packages = $configured ? $this->accounts->listPackagesForForms() : [];

        $summary = $this->accounts->getCachedSummary($account);
        $summarySyncedAt = null;
        $meta = is_array($account->metadata) ? $account->metadata : [];
        if (! empty($meta['summary_synced_at'])) {
            try {
                $summarySyncedAt = \Carbon\Carbon::parse($meta['summary_synced_at'])->format('Y-m-d H:i');
            } catch (\Throwable) {
                $summarySyncedAt = $meta['summary_synced_at'];
            }
        }

        if ($configured && $account->status !== 'terminated' && $summary === null) {
            $refresh = $this->accounts->refreshSummary($account, true);
            if ($refresh['success'] ?? false) {
                $summary = $refresh['summary'] ?? null;
                $account->refresh();
                $meta = is_array($account->metadata) ? $account->metadata : [];
                if (! empty($meta['summary_synced_at'])) {
                    try {
                        $summarySyncedAt = \Carbon\Carbon::parse($meta['summary_synced_at'])->format('Y-m-d H:i');
                    } catch (\Throwable) {
                    }
                }
            }
        }

        $sslBadge = $configured && $account->status !== 'terminated'
            ? $this->accounts->formatSslBadgeForDomain($account)
            : null;

        $serverStatus = $configured
            ? $this->serverStatus->getStatus($account->status !== 'terminated' ? $account->username : null)
            : null;

        $billing = app(\App\Services\Whm\WhmSubscriptionBillingService::class)->billingConfig();

        return view('admin.whm.accounts.show', compact(
            'account',
            'configured',
            'clientUsers',
            'packages',
            'summary',
            'summarySyncedAt',
            'sslBadge',
            'serverStatus',
            'billing'
        ));
    }

    public function renew(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : null;
        $result = $this->accounts->renewSubscription($account, $amount);

        if (! ($result['success'] ?? false)) {
            return back()->with('error', $result['message']);
        }

        $redirect = back()->with('success', $result['message']);

        if (! empty($result['invoice'])) {
            $redirect->with('invoice_id', $result['invoice']->id);
        }

        return $redirect;
    }

    public function refreshSummary(WhmAccount $account)
    {
        $result = $this->accounts->refreshSummary($account, true);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function changePackage(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'package' => 'required|string|max:128',
        ]);

        $result = $this->accounts->changePackage($account, $validated['package']);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(Request $request, WhmAccount $account)
    {
        $result = $this->accounts->terminate($account, $request->boolean('keep_dns'));

        if ($result['success'] ?? false) {
            return redirect()
                ->route('admin.whm.accounts.index')
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function assignClient(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = isset($validated['user_id']) && $validated['user_id'] !== ''
            ? (int) $validated['user_id']
            : null;

        $result = $this->accounts->assignClient($account, $userId);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'client_label' => $result['client_label'] ?? null,
                'html' => view('admin.whm.accounts.partials.client-cell', [
                    'account' => $result['account'] ?? $account->fresh()->load('client'),
                ])->render(),
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cpanelLogin(Request $request, WhmAccount $account)
    {
        $result = $this->accounts->cpanelLoginUrl($account);

        if (! ($result['success'] ?? false)) {
            if (! empty($result['settings_url'])) {
                return redirect($result['settings_url'])->with('error', $result['message']);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            return back()->with('error', $result['message']);
        }

        return redirect()->away($result['url']);
    }

    public function toggleStatus(Request $request, WhmAccount $account)
    {
        if ($account->status === 'terminated') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير حالة حساب محذوف',
                'status' => $account->status,
            ], 422);
        }

        $validated = $request->validate([
            'active' => 'required|boolean',
        ]);

        $makeActive = (bool) $validated['active'];

        if ($makeActive && $account->status === 'active') {
            return response()->json([
                'success' => true,
                'message' => 'الحساب نشط بالفعل',
                'status' => 'active',
                'status_label' => $account->status_label,
                'html' => view('admin.whm.accounts.partials.status-toggle', compact('account'))->render(),
            ]);
        }

        if (! $makeActive && $account->status === 'suspended') {
            return response()->json([
                'success' => true,
                'message' => 'الحساب موقوف بالفعل',
                'status' => 'suspended',
                'status_label' => $account->status_label,
                'html' => view('admin.whm.accounts.partials.status-toggle', compact('account'))->render(),
            ]);
        }

        $result = $makeActive
            ? $this->accounts->unsuspend($account)
            : $this->accounts->suspend($account);

        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'فشل تحديث الحالة في WHM',
                'status' => $account->fresh()->status,
                'status_label' => $account->fresh()->status_label,
            ], 422);
        }

        $account->refresh();

        return response()->json([
            'success' => true,
            'message' => $makeActive
                ? 'تم تفعيل الحساب في WHM'
                : 'تم إيقاف الحساب في WHM',
            'status' => $account->status,
            'status_label' => $account->status_label,
            'html' => view('admin.whm.accounts.partials.status-toggle', compact('account'))->render(),
        ]);
    }

    public function updateEmail(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $result = $this->accounts->updateContactEmail($account, $validated['email']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'email' => $account->fresh()->display_email,
        ], $result['success'] ? 200 : 422);
    }

    public function updatePassword(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|max:64|confirmed',
        ]);

        $result = $this->accounts->updatePassword($account, $validated['password']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }

    public function renameUser(Request $request, WhmAccount $account)
    {
        $validated = $request->validate([
            'new_username' => ['required', 'string', 'max:16', 'regex:/^[a-z][a-z0-9]{0,15}$/i'],
        ]);

        $result = $this->accounts->renameUsername($account, $validated['new_username']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'username' => $result['account']?->username,
            'redirect_url' => $result['redirect_url'] ?? null,
        ], $result['success'] ? 200 : 422);
    }
}
