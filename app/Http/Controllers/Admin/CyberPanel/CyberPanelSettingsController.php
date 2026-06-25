<?php

namespace App\Http\Controllers\Admin\CyberPanel;

use App\Http\Controllers\Controller;
use App\Services\CyberPanel\CyberPanelApiService;
use App\Services\CyberPanel\CyberPanelSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CyberPanelSettingsController extends Controller
{
    public function __construct(
        protected CyberPanelApiService $api,
        protected CyberPanelSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->settings->initializeDefaults();
        $form = $this->settings->getFormSettings();
        $configured = $this->api->isConfigured();
        $connected = false;
        $message = null;

        if ($configured) {
            $ping = $this->api->ping();
            $connected = (bool) ($ping['success'] ?? false);
            $message = $ping['message'] ?? null;
        }

        return view('admin.cyberpanel.settings.index', compact(
            'form',
            'configured',
            'connected',
            'message'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:500',
            'port' => 'required|integer|min:1|max:65535',
            'admin_user' => 'required|string|max:64',
            'admin_password' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:2000',
            'api_style' => 'required|in:cloud,legacy',
            'default_package' => 'required|string|max:128',
            'default_php_version' => 'required|string|max:32',
            'default_owner' => 'required|string|max:64',
            'default_domain_suffix' => 'nullable|string|max:253',
            'timeout' => 'nullable|integer|min:10|max:180',
            'verify_ssl' => 'nullable|boolean',
            'renewal_amount' => 'nullable|numeric|min:0',
            'invoice_due_days' => 'nullable|integer|min:1|max:90',
            'subscription_years' => 'nullable|integer|min:1|max:10',
        ]);

        $existing = $this->settings->getFormSettings();
        if (empty($validated['admin_password']) && ! ($existing['has_password'] ?? false) && empty($validated['api_token'])) {
            return back()->withInput()->with('error', 'كلمة مرور المدير مطلوبة عند الإعداد لأول مرة (أو الصق API Token يدوياً إن وُجد)');
        }

        $this->settings->updateSettings([
            'host' => $validated['host'],
            'port' => $validated['port'],
            'admin_user' => $validated['admin_user'],
            'admin_password' => $validated['admin_password'] ?? null,
            'api_token' => $validated['api_token'] ?? null,
            'api_style' => $validated['api_style'],
            'default_package' => $validated['default_package'],
            'default_php_version' => $validated['default_php_version'],
            'default_owner' => $validated['default_owner'],
            'default_domain_suffix' => $validated['default_domain_suffix'] ?? '',
            'timeout' => $validated['timeout'] ?? 60,
            'verify_ssl' => $request->boolean('verify_ssl'),
            'renewal_amount' => $validated['renewal_amount'] ?? 0,
            'invoice_due_days' => $validated['invoice_due_days'] ?? 7,
            'subscription_years' => $validated['subscription_years'] ?? 1,
        ]);

        $this->api->refreshConnection();
        $this->api->clearPackagesCache();

        return redirect()->route('admin.cyberpanel.settings.index')
            ->with('success', 'تم حفظ إعدادات CyberPanel بنجاح');
    }

    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->api->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'احفظ عنوان اللوحة وبيانات المدير من صفحة الإعدادات أولاً',
            ]);
        }

        $ping = $this->api->ping();
        $packages = $this->api->listPackages();
        $websites = $this->api->listWebsites();

        return response()->json([
            'success' => (bool) ($ping['success'] ?? false),
            'message' => $ping['message'] ?? (($ping['success'] ?? false) ? 'الاتصال ناجح' : 'فشل الاتصال'),
            'packages_count' => count($packages['packages'] ?? []),
            'websites_count' => count($websites['websites'] ?? []),
            'panel_url' => $this->api->getPanelUrl(),
        ]);
    }
}
