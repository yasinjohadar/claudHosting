<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\WhmAccount;
use App\Services\Client\ClientAssetService;
use App\Services\Client\ClientBillingService;
use App\Services\Whm\Wordpress\WhmWordpressDiscoveryService;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected ClientAssetService $clientAssets,
        protected ClientBillingService $billing,
        protected WhmWordpressDiscoveryService $whmWordpress
    ) {
        $this->middleware('auth');
    }

    public function dashboard(): View
    {
        $user = auth()->user();
        $summary = $this->clientAssets->portalSummary($user->id);
        $coolifyProjects = $this->clientAssets->coolifyProjectsForUser($user->id);
        if (count($coolifyProjects) === 1) {
            $summary['first_coolify_project_uuid'] = $coolifyProjects[0]['uuid'] ?? null;
        }

        $wordpressSites = $this->clientAssets->wordpressSitesForUser($user->id);
        if ($wordpressSites->count() === 1) {
            $summary['first_wordpress_site_uuid'] = $wordpressSites->first()->uuid;
        }

        return view('client.pages.dashboard', compact('user', 'summary'));
    }

    public function services(): View
    {
        $user = auth()->user();

        $domains = $this->clientAssets->domainsForUser($user->id);
        $projects = $this->clientAssets->coolifyProjectsForUser($user->id);
        $wordpressSites = $this->clientAssets->wordpressSitesForUser($user->id);
        $whmWordpressSites = $this->whmWordpress->sitesForUser($user->id);
        $hosting = WhmAccount::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'terminated')
            ->orderByDesc('joined_at')
            ->get();

        return view('client.pages.services', compact(
            'user',
            'domains',
            'projects',
            'wordpressSites',
            'whmWordpressSites',
            'hosting'
        ));
    }

    public function invoices(): View
    {
        $user = auth()->user();
        $hasCustomerProfile = $this->billing->hasCustomerProfile($user);
        $invoices = $this->billing->invoicesForUser($user);

        return view('client.pages.invoices.index', compact('user', 'invoices', 'hasCustomerProfile'));
    }

    public function invoiceShow(Invoice $invoice): View
    {
        $user = auth()->user();

        abort_unless($this->billing->userCanViewInvoice($user, $invoice), 403, 'لا يمكنك عرض هذه الفاتورة');

        $invoice->load(['items', 'payments']);

        return view('client.pages.invoices.show', compact('user', 'invoice'));
    }
}
