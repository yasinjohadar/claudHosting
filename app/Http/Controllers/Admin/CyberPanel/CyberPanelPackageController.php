<?php

namespace App\Http\Controllers\Admin\CyberPanel;

use App\Http\Controllers\Controller;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelPackageService;
use Illuminate\Http\Request;

class CyberPanelPackageController extends Controller
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelPackageService $packages
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $configured = $this->api->isConfigured();
        $packages = [];
        $error = null;

        if ($configured) {
            $res = $this->api->listPackages();
            if ($res['success'] ?? false) {
                $packages = $res['packages'] ?? [];
            } else {
                $error = $res['message'] ?? 'فشل جلب الباقات';
            }
        }

        return view('admin.cyberpanel.packages.index', compact('packages', 'configured', 'error'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'packageName' => 'required|string|max:128',
            'diskSpace' => 'required|integer|min:100',
            'bandwidth' => 'required|integer|min:100',
            'emailAccounts' => 'required|integer|min:0',
            'dataBases' => 'required|integer|min:0',
            'ftpAccounts' => 'required|integer|min:0',
            'allowedDomains' => 'required|integer|min:0',
            'owner' => 'nullable|string|max:64',
        ]);

        if (! $this->api->isConfigured()) {
            return back()->with('error', 'إعدادات CyberPanel غير مكتملة')->withInput();
        }

        $result = $this->packages->createPackage($validated);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
