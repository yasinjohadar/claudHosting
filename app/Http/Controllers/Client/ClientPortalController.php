<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ClientAssetService;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected ClientAssetService $clientAssets
    ) {
        $this->middleware('auth');
    }

    public function dashboard(): View
    {
        $user = auth()->user();
        $summary = $this->clientAssets->portalSummary($user->id);

        return view('client.pages.dashboard', compact('user', 'summary'));
    }
}
