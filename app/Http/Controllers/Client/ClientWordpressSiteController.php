<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Admin\Coolify\CoolifyWordpressSiteController;
use App\Services\Client\ClientAssetService;
use Illuminate\View\View;

class ClientWordpressSiteController extends CoolifyWordpressSiteController
{
    public function __construct(
        \App\Services\CoolifyApiService $coolify,
        \App\Services\Coolify\CoolifySettingsService $settings,
        \App\Services\Coolify\WordpressSiteProvisioningService $provisioning,
        \App\Services\Coolify\WordpressManagementService $wpManagement,
        \App\Services\Coolify\WordpressCloudflareService $wordpressCloudflare,
        \App\Services\Coolify\WordpressProvisioningProgress $provisioningProgress,
        protected ClientAssetService $clientAssets,
        \App\Services\Coolify\WordpressServiceComponentLifecycleService $componentLifecycle
    ) {
        parent::__construct($coolify, $settings, $provisioning, $wpManagement, $wordpressCloudflare, $provisioningProgress, $clientAssets, $componentLifecycle);
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();
        $sites = $this->clientAssets->wordpressSitesForUser($user->id);

        return view('client.pages.wordpress-sites.index', compact('user', 'sites'));
    }

    public function show(string $uuid): View
    {
        $site = $this->resolveAuthorizedWordpressSite($uuid);

        return view('client.pages.wordpress-sites.show', $this->buildShowViewData($site, 'client'));
    }
}
