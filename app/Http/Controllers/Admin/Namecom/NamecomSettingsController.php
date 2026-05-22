<?php

namespace App\Http\Controllers\Admin\Namecom;

use App\Http\Controllers\Controller;
use App\Services\Namecom\NamecomSettingsService;
use App\Services\NamecomApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NamecomSettingsController extends Controller
{
    public function __construct(
        protected NamecomApiService $namecom,
        protected NamecomSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->settings->initializeDefaults();
        $form = $this->settings->getFormSettings();
        $configured = $this->namecom->isConfigured();
        $connected = $configured && $this->namecom->ping();

        return view('admin.namecom.settings.index', compact(
            'form',
            'configured',
            'connected'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'username' => 'nullable|string|max:255',
            'api_token' => 'nullable|string|max:2000',
            'api_base' => 'nullable|string|max:255',
            'timeout' => 'nullable|integer|min:5|max:120',
            'cache_ttl' => 'nullable|integer|min:60|max:3600',
        ]);

        $existing = $this->settings->getFormSettings();
        $username = trim((string) ($validated['username'] ?? $existing['username'] ?? ''));

        if ($username === '' && empty($validated['api_token']) && ! ($existing['has_token'] ?? false)) {
            return back()->withInput()->with('error', 'اسم المستخدم وتوكن API مطلوبان عند الإعداد لأول مرة');
        }

        $this->settings->updateSettings([
            'username' => $validated['username'] ?? $existing['username'] ?? '',
            'api_token' => $validated['api_token'] ?? null,
            'api_base' => $validated['api_base'] ?? $existing['api_base'] ?? config('namecom.defaults.api_base'),
            'timeout' => $validated['timeout'] ?? 30,
            'cache_ttl' => $validated['cache_ttl'] ?? 600,
        ]);

        $this->namecom->refreshConnection();
        $this->namecom->clearCaches();

        return redirect()->route('admin.namecom.settings.index')
            ->with('success', 'تم حفظ إعدادات name.com بنجاح');
    }

    public function testConnection(Request $request): JsonResponse
    {
        if (! $this->namecom->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى حفظ اسم المستخدم وتوكن API أولاً',
            ]);
        }

        if ($this->namecom->ping()) {
            $meta = $this->namecom->listAllDomainsWithMeta(true);

            return response()->json([
                'success' => true,
                'message' => 'الاتصال ناجح',
                'domains_count' => count($meta['domains'] ?? []),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'فشل الاتصال — تحقق من اسم المستخدم والتوكن',
        ]);
    }
}
