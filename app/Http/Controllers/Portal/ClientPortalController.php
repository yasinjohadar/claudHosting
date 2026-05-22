<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Services\Client\ClientAssetService;
use App\Services\Whm\WhmAccountService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected ClientAssetService $clientAssets,
        protected WhmAccountService $whmAccounts
    ) {
        $this->middleware('auth');
    }

    public function dashboard(): View
    {
        $user = auth()->user();
        $summary = $this->clientAssets->portalSummary($user->id);

        return view('portal.dashboard', compact('user', 'summary'));
    }

    public function domains(): View
    {
        $user = auth()->user();
        $domains = $this->clientAssets->domainsForUser($user->id);

        return view('portal.domains.index', compact('user', 'domains'));
    }

    public function domainShow(string $domain)
    {
        $user = auth()->user();
        $row = $this->clientAssets->domainDetailForUser($user, $domain);

        if ($row === null) {
            abort(403, 'لا يمكنك عرض هذا النطاق');
        }

        return view('portal.domains.show', compact('user', 'row', 'domain'));
    }

    public function projects(): View
    {
        $user = auth()->user();
        $projects = $this->clientAssets->coolifyProjectsForUser($user->id);

        return view('portal.projects.index', compact('user', 'projects'));
    }

    public function projectShow(string $uuid)
    {
        $user = auth()->user();

        if (! $this->clientAssets->userOwnsCoolifyProject($user->id, $uuid)) {
            abort(403, 'لا يمكنك عرض هذا المشروع');
        }

        $detail = $this->clientAssets->coolifyProjectDetailForUser($user, $uuid);

        return view('portal.projects.show', [
            'user' => $user,
            'uuid' => $uuid,
            'project' => $detail['project'],
            'resources' => $detail['resources'],
        ]);
    }

    public function hosting(): View
    {
        $user = auth()->user();
        $accounts = WhmAccount::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'terminated')
            ->orderByDesc('joined_at')
            ->get();

        return view('portal.hosting.index', compact('user', 'accounts'));
    }

    public function hostingShow(WhmAccount $account)
    {
        $user = auth()->user();

        if (! $this->whmAccounts->userOwnsAccount($user, $account)) {
            abort(403, 'لا يمكنك عرض هذا الحساب');
        }

        $summary = $this->whmAccounts->getCachedSummary($account);
        if ($summary === null && $account->status !== 'terminated') {
            $refreshed = $this->whmAccounts->refreshSummary($account, true);
            $summary = $refreshed['summary'] ?? null;
        }

        $sslBadge = $account->status !== 'terminated'
            ? $this->whmAccounts->formatSslBadgeForDomain($account)
            : null;

        return view('portal.hosting.show', compact('user', 'account', 'summary', 'sslBadge'));
    }

    public function hostingCpanel(WhmAccount $account)
    {
        $user = auth()->user();
        $result = $this->whmAccounts->portalCpanelLoginUrl($user, $account);

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('portal.hosting.show', $account)
                ->with('error', $result['message']);
        }

        return redirect()->away($result['url']);
    }
}
