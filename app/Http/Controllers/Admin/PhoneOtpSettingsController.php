<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvolutionInstance;
use App\Services\Auth\PhoneOtpSettingsService;
use App\Services\Auth\PhoneOtpWhatsAppSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneOtpSettingsController extends Controller
{
    public function __construct(
        private PhoneOtpSettingsService $settingsService,
        private PhoneOtpWhatsAppSender $sender
    ) {
        $this->middleware(['auth', 'role:admin']);
    }

    public function edit(): View
    {
        $settings = $this->settingsService->getSettings();
        $health = $this->sender->buildHealthReport();

        return view('admin.pages.settings.phone-otp.edit', [
            'settings' => $settings,
            'health' => $health,
            'evolutionPoolCount' => EvolutionInstance::rotationPoolCount(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'evolution_message_template' => 'nullable|string|max:1000',
            'ttl_seconds' => 'nullable|integer|min:60|max:3600',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'resend_cooldown_seconds' => 'nullable|integer|min:30|max:600',
            'code_length' => 'nullable|integer|min:4|max:8',
            'rate_limit_max_per_phone' => 'nullable|integer|min:1|max:50',
            'rate_limit_window_minutes' => 'nullable|integer|min:1|max:1440',
            'login_enabled' => 'nullable|boolean',
            'reset_password_enabled' => 'nullable|boolean',
        ]);

        $this->settingsService->updateSettings([
            'enabled' => $request->boolean('enabled'),
            'delivery_channel' => 'evolution',
            'evolution_message_template' => $validated['evolution_message_template'] ?? 'رمز التحقق الخاص بك هو: {code}',
            'ttl_seconds' => $validated['ttl_seconds'] ?? 300,
            'max_attempts' => $validated['max_attempts'] ?? 5,
            'resend_cooldown_seconds' => $validated['resend_cooldown_seconds'] ?? 60,
            'code_length' => $validated['code_length'] ?? 6,
            'rate_limit_max_per_phone' => $validated['rate_limit_max_per_phone'] ?? 3,
            'rate_limit_window_minutes' => $validated['rate_limit_window_minutes'] ?? 15,
            'login_enabled' => $request->boolean('login_enabled'),
            'reset_password_enabled' => $request->boolean('reset_password_enabled'),
        ]);

        return redirect()
            ->route('admin.settings.phone-otp.edit')
            ->with('success', 'تم حفظ إعدادات OTP بنجاح.');
    }

    public function restoreDefaults(): RedirectResponse
    {
        $this->settingsService->restoreDefaults();

        return redirect()
            ->route('admin.settings.phone-otp.edit')
            ->with('success', 'تمت استعادة الإعدادات الافتراضية.');
    }
}
