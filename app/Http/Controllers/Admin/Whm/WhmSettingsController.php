<?php

namespace App\Http\Controllers\Admin\Whm;

use App\Http\Controllers\Controller;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhmSettingsController extends Controller
{
    public function __construct(
        protected WhmApiService $whm,
        protected WhmSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->settings->initializeDefaults();
        $form = $this->settings->getFormSettings();
        $configured = $this->whm->isConfigured();
        $connected = false;
        $version = null;
        $message = null;

        if ($configured) {
            $ping = $this->whm->ping();
            $connected = (bool) ($ping['success'] ?? false);
            $version = $ping['data'] ?? null;
            $message = $ping['message'] ?? null;
        }

        return view('admin.whm.settings.index', compact(
            'form',
            'configured',
            'connected',
            'version',
            'message'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string|max:500',
            'username' => 'required|string|max:64',
            'api_token' => 'nullable|string|max:2000',
            'default_package' => 'required|string|max:128',
            'default_domain_suffix' => 'nullable|string|max:253',
            'timeout' => 'nullable|integer|min:10|max:180',
            'verify_ssl' => 'nullable|boolean',
            'renewal_amount' => 'nullable|numeric|min:0',
            'invoice_due_days' => 'nullable|integer|min:1|max:90',
            'subscription_years' => 'nullable|integer|min:1|max:10',
        ]);

        $existing = $this->settings->getFormSettings();
        if (empty($validated['api_token']) && ! ($existing['has_token'] ?? false)) {
            return back()->withInput()->with('error', 'رمز API مطلوب عند الإعداد لأول مرة');
        }

        $this->settings->updateSettings([
            'host' => $validated['host'],
            'username' => $validated['username'],
            'api_token' => $validated['api_token'] ?? null,
            'default_package' => $validated['default_package'],
            'default_domain_suffix' => $validated['default_domain_suffix'] ?? '',
            'timeout' => $validated['timeout'] ?? 60,
            'verify_ssl' => $request->boolean('verify_ssl'),
            'renewal_amount' => $validated['renewal_amount'] ?? 0,
            'invoice_due_days' => $validated['invoice_due_days'] ?? 7,
            'subscription_years' => $validated['subscription_years'] ?? 1,
        ]);

        $this->whm->refreshConnection();

        return redirect()->route('admin.whm.settings.index')
            ->with('success', 'تم حفظ إعدادات WHM بنجاح');
    }

    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->whm->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'احفظ عنوان WHM واسم المستخدم ورمز API من صفحة الإعدادات أولاً',
            ]);
        }

        $ping = $this->whm->ping();

        return response()->json([
            'success' => (bool) ($ping['success'] ?? false),
            'message' => $ping['message'] ?? ($ping['success'] ? 'الاتصال ناجح' : 'فشل الاتصال'),
            'data' => $ping['data'] ?? null,
        ]);
    }
}
