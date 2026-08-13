<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Concerns\ManagesWhmWordpressSiteActions;
use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\Wordpress\WhmWordpressDiscoveryService;
use App\Services\Whm\Wordpress\WhmWordpressPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientWhmWordpressSiteController extends Controller
{
    use ManagesWhmWordpressSiteActions;

    public function __construct(
        protected WhmAccountService $accounts,
        protected WhmWordpressDiscoveryService $discovery,
        protected WhmWordpressPortalService $portal
    ) {
        $this->middleware('auth');
    }

    public function index(WhmAccount $account): View|RedirectResponse
    {
        $this->authorizeAccount($account);

        if ($this->discovery->needsRefresh($account)) {
            $scan = $this->discovery->discover($account);
            session()->flash('wp_scan_warnings', $scan['warnings'] ?? []);
            if (! empty($scan['message'])) {
                session()->flash('info', $scan['message']);
            }
        }

        $sites = $this->discovery->sitesForAccount($account);

        return view('client.pages.hosting.wordpress.index', [
            'account' => $account,
            'sites' => $sites,
            'warnings' => session('wp_scan_warnings', []),
        ]);
    }

    public function scan(WhmAccount $account): RedirectResponse
    {
        $this->authorizeAccount($account);

        $result = $this->discovery->discover($account);

        return redirect()
            ->route('client.hosting.wordpress.index', $account)
            ->with('success', $result['message'])
            ->with('wp_scan_warnings', $result['warnings'] ?? []);
    }

    public function show(WhmAccount $account, WhmWordpressSite $site): View
    {
        $this->guardWhmWordpressSite($account, $site);

        return view('client.pages.hosting.wordpress.show', $this->buildWhmWordpressShowData($account, $site, 'client'));
    }

    public function open(WhmAccount $account, WhmWordpressSite $site): RedirectResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        return $this->awayOrBack($this->portal->openSiteUrl($site), $account);
    }

    public function wpAdmin(WhmAccount $account, WhmWordpressSite $site): RedirectResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        return $this->awayOrBack($this->portal->wpAdminUrl($site), $account, $site);
    }

    public function manager(WhmAccount $account, WhmWordpressSite $site): RedirectResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        return $this->awayOrBack($this->portal->managerUrl($site), $account, $site);
    }

    protected function authorizeAccount(WhmAccount $account): void
    {
        abort_unless($this->accounts->userOwnsAccount(auth()->user(), $account), 403);
        abort_if($account->status === 'terminated', 404);
    }

    protected function guardWhmWordpressSite(WhmAccount $account, WhmWordpressSite $site): void
    {
        $this->authorizeAccount($account);
        abort_unless((int) $site->whm_account_id === (int) $account->id, 404);
    }

    /**
     * @param  array{success: bool, message: string, url?: string}  $result
     */
    protected function awayOrBack(array $result, WhmAccount $account, ?WhmWordpressSite $site = null): RedirectResponse
    {
        if (($result['success'] ?? false) && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        $redirect = $site
            ? redirect()->route('client.hosting.wordpress.show', [$account, $site])
            : redirect()->route('client.hosting.wordpress.index', $account);

        return $redirect->with('error', $result['message'] ?? 'تعذر تنفيذ العملية');
    }
}
