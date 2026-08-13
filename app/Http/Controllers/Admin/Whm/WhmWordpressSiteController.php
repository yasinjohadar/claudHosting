<?php

namespace App\Http\Controllers\Admin\Whm;

use App\Http\Controllers\Concerns\ManagesWhmWordpressSiteActions;
use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Models\WhmWordpressSite;
use App\Services\Whm\Wordpress\WhmWordpressDiscoveryService;
use App\Services\Whm\Wordpress\WhmWordpressPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WhmWordpressSiteController extends Controller
{
    use ManagesWhmWordpressSiteActions;

    public function __construct(
        protected WhmWordpressDiscoveryService $discovery,
        protected WhmWordpressPortalService $portal
    ) {}

    public function index(WhmAccount $account): View
    {
        if ($this->discovery->needsRefresh($account)) {
            $scan = $this->discovery->discover($account);
            session()->flash('wp_scan_warnings', $scan['warnings'] ?? []);
            session()->flash('info', $scan['message'] ?? null);
        }

        $sites = $this->discovery->sitesForAccount($account, true);

        return view('admin.whm.wordpress.index', [
            'account' => $account,
            'sites' => $sites,
            'warnings' => session('wp_scan_warnings', []),
        ]);
    }

    public function scan(WhmAccount $account): RedirectResponse
    {
        $result = $this->discovery->discover($account);

        return redirect()
            ->route('admin.whm.accounts.wordpress.index', $account)
            ->with('success', $result['message'])
            ->with('wp_scan_warnings', $result['warnings'] ?? []);
    }

    public function show(WhmAccount $account, WhmWordpressSite $site): View
    {
        $this->guardWhmWordpressSite($account, $site);

        return view('admin.whm.wordpress.show', $this->buildWhmWordpressShowData($account, $site, 'admin'));
    }

    public function open(WhmAccount $account, WhmWordpressSite $site): RedirectResponse
    {
        $this->guardWhmWordpressSite($account, $site);

        return $this->awayOrBack($this->portal->openSiteUrl($site), $account, $site);
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

    protected function guardWhmWordpressSite(WhmAccount $account, WhmWordpressSite $site): void
    {
        abort_unless((int) $site->whm_account_id === (int) $account->id, 404);
    }

    /**
     * @param  array{success: bool, message: string, url?: string}  $result
     */
    protected function awayOrBack(array $result, WhmAccount $account, WhmWordpressSite $site): RedirectResponse
    {
        if (($result['success'] ?? false) && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()
            ->route('admin.whm.accounts.wordpress.show', [$account, $site])
            ->with('error', $result['message'] ?? 'تعذر تنفيذ العملية');
    }
}
