<?php

namespace App\Http\Controllers\Admin\Whm;

use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmServerStatusService;
use App\Services\Whm\WhmSettingsService;
use Illuminate\Http\Request;

class WhmServerStatusController extends Controller
{
    public function __construct(
        protected WhmServerStatusService $serverStatus,
        protected WhmApiService $whmApi,
        protected WhmSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $configured = $this->whmApi->isConfigured();
        $form = $this->settings->getFormSettings();
        $host = $form['host'] ?? '';
        $proxyUser = $configured ? $this->serverStatus->resolveProxyUser() : '';
        $connected = false;
        $whmVersion = null;

        if ($configured) {
            $ping = $this->whmApi->ping();
            $connected = (bool) ($ping['success'] ?? false);
            $whmVersion = is_array($ping['data'] ?? null)
                ? ($ping['data']['version'] ?? json_encode($ping['data']))
                : ($ping['data'] ?? null);
        }

        $serverStatus = $configured
            ? $this->serverStatus->getStatus(null, $request->boolean('fresh'))
            : null;

        $accountsCount = WhmAccount::query()->where('status', '!=', 'terminated')->count();

        return view('admin.whm.server.index', compact(
            'configured',
            'host',
            'proxyUser',
            'connected',
            'whmVersion',
            'serverStatus',
            'accountsCount'
        ));
    }

    public function refresh(Request $request)
    {
        $proxyUser = $this->resolveProxyUserFromRequest($request);

        $status = $this->serverStatus->getStatus(
            $proxyUser,
            true
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($status);
        }

        return redirect()
            ->route('admin.whm.server.index', ['fresh' => 1])
            ->with(
                ($status['success'] ?? false) ? 'success' : 'error',
                ($status['success'] ?? false)
                    ? 'تم تحديث حالة السيرفر'
                    : ($status['message'] ?? 'فشل جلب حالة السيرفر')
            );
    }

    protected function resolveProxyUserFromRequest(Request $request): ?string
    {
        if ($request->filled('user')) {
            return (string) $request->input('user');
        }

        $accountId = $request->input('account_id');
        if ($accountId) {
            $account = WhmAccount::find($accountId);

            return $account?->username;
        }

        return null;
    }
}
