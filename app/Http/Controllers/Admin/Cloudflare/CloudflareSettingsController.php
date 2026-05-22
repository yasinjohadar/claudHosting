<?php

namespace App\Http\Controllers\Admin\Cloudflare;

use App\Http\Controllers\Controller;
use App\Services\Cloudflare\CloudflareSettingsService;
use App\Services\CloudflareApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflareSettingsController extends Controller
{
    public function __construct(
        protected CloudflareApiService $cloudflare,
        protected CloudflareSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->settings->initializeDefaults();
        $accountIdCleared = $this->settings->clearInvalidAccountIdIfNeeded();
        if ($accountIdCleared) {
            $this->cloudflare->refreshConnection();
        }
        $form = $this->settings->getFormSettings();
        $configured = $this->cloudflare->isConfigured();
        $connected = $configured && $this->cloudflare->ping();
        $accountId = $connected ? $this->cloudflare->getAccountId() : null;
        $tokenPermissions = $configured
            ? $this->cloudflare->getTokenPermissionsSummary()
            : null;

        return view('admin.cloudflare.settings.index', compact(
            'form',
            'configured',
            'connected',
            'accountId',
            'tokenPermissions',
            'accountIdCleared'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_token' => 'nullable|string|max:2000',
            'account_id' => ['nullable', 'string', 'max:64', 'regex:/^$|^[a-f0-9]{32}$/i'],
            'timeout' => 'nullable|integer|min:5|max:120',
            'cache_ttl' => 'nullable|integer|min:60|max:3600',
        ], [
            'account_id.regex' => 'Account ID يجب أن يكون 32 حرفاً hex (مثل 8ac849e8...) أو اتركه فارغاً',
        ]);

        $existing = $this->settings->getFormSettings();
        if (empty($validated['api_token']) && ! $existing['has_token']) {
            return back()->withInput()->with('error', 'رمز API مطلوب عند الإعداد لأول مرة');
        }

        $this->settings->updateSettings([
            'api_token' => $validated['api_token'] ?? null,
            'account_id' => $validated['account_id'] ?? '',
            'timeout' => $validated['timeout'] ?? 30,
            'cache_ttl' => $validated['cache_ttl'] ?? 600,
        ]);

        $this->cloudflare->refreshConnection();
        $this->cloudflare->clearCaches();

        return redirect()->route('admin.cloudflare.settings.index')
            ->with('success', 'تم حفظ إعدادات Cloudflare بنجاح');
    }

    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى حفظ رمز API أولاً',
            ]);
        }

        if ($this->cloudflare->ping()) {
            $accountId = $this->cloudflare->getAccountId();
            $zones = $this->cloudflare->listAllZones();

            return response()->json([
                'success' => true,
                'message' => 'الاتصال ناجح',
                'account_id' => $accountId,
                'zones_count' => count($zones),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'فشل الاتصال — تحقق من الرمز والصلاحيات',
        ]);
    }
}
