<?php

namespace App\Http\Controllers\Admin\Domain;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ClientDomain;
use App\Models\WhmAccount;
use App\Services\Client\ClientAssetService;
use App\Services\Domain\DomainCommandCenterService;
use App\Services\Whm\WhmAccountService;
use Illuminate\Http\Request;

class DomainHubController extends Controller
{
    public function __construct(
        protected DomainCommandCenterService $commandCenter,
        protected ClientAssetService $clientAssets,
        protected WhmAccountService $whmAccounts
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $forceRefresh = $request->boolean('refresh');
        $payload = $this->commandCenter->build($forceRefresh);

        $filters = [
            'q' => $request->query('q', ''),
            'source' => $request->query('source', 'all'),
            'status' => $request->query('status', 'all'),
            'user_id' => $request->query('user_id', ''),
            'sort' => $request->query('sort', 'name'),
            'dir' => $request->query('dir', 'asc'),
        ];

        $rows = $this->commandCenter->filterRows($payload['rows'], $filters);

        $clientUsers = User::query()->orderBy('name')->select(['id', 'name', 'email'])->limit(500)->get();

        return view('admin.domains.index', [
            'rows' => $rows,
            'stats' => $payload['stats'],
            'errors' => $payload['errors'],
            'configured' => $payload['configured'],
            'filters' => $filters,
            'totalBeforeFilter' => count($payload['rows']),
            'clientUsers' => $clientUsers,
        ]);
    }

    public function assignClient(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:253',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $userId = isset($validated['user_id']) && $validated['user_id'] !== ''
            ? (int) $validated['user_id']
            : null;

        $result = $this->clientAssets->assignDomain($userId, $validated['domain_name']);

        if ($request->wantsJson() || $request->ajax()) {
            $client = $result['domain']?->client;

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'client_label' => $client ? $client->name : null,
                'html' => view('admin.domains.partials.client-cell', [
                    'row' => [
                        'name' => \App\Models\ClientDomain::normalizeName($validated['domain_name']),
                        'client' => $client,
                    ],
                ])->render(),
            ], $result['success'] ? 200 : 422);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function whmDns(string $domain)
    {
        $name = ClientDomain::normalizeName($domain);
        $account = WhmAccount::query()->where('domain', $name)->first();

        if ($account === null) {
            return response()->view('admin.domains.partials.whm-dns', [
                'records' => [],
                'error' => 'لا يوجد حساب WHM لهذا النطاق',
            ], 404);
        }

        $result = $this->whmAccounts->dnsZoneForDomain($name);

        return view('admin.domains.whm-dns', [
            'records' => $result['records'] ?? [],
            'error' => ($result['success'] ?? false) ? null : ($result['message'] ?? 'فشل جلب السجلات'),
            'domain' => $name,
        ]);
    }
}
