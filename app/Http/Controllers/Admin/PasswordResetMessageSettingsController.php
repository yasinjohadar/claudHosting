<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessageTemplate;
use App\Services\Auth\PasswordResetMessageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetMessageSettingsController extends Controller
{
    public function __construct(
        private PasswordResetMessageSettingsService $settingsService
    ) {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function edit(): View
    {
        $settings = $this->settingsService->getSettings();
        $whatsappTemplates = WhatsAppMessageTemplate::active()
            ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
            ->orderBy('name')
            ->get(['id', 'name']);
        $placeholders = [
            'customer_name',
            'customer_name_ar',
            'customer_name_en',
            'customer_email',
            'company_name',
            'phone',
            'customer_phone',
            'customer_city',
            'customer_country',
            'user_name',
            'email',
            'password',
            'new_password',
            'login_url',
            'admin_instructions',
            'app_name',
            'reset_url',
            'reset_link',
        ];

        $whatsappBody = trim((string) ($settings['whatsapp_body'] ?? ''));
        $usesLegacyLinkTemplate = $this->settingsService->usesLegacyLinkTemplate($whatsappBody);

        return view('admin.pages.settings.password-reset-message.edit', compact(
            'settings',
            'whatsappTemplates',
            'placeholders',
            'usesLegacyLinkTemplate'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_template_id' => 'nullable|exists:whatsapp_message_templates,id',
            'whatsapp_body' => 'nullable|string|max:10000',
            'email_subject' => 'required|string|max:255',
            'email_body' => 'nullable|string|max:50000',
            'admin_instructions' => 'nullable|string|max:10000',
        ]);

        $this->settingsService->updateSettings([
            'whatsapp_template_id' => $validated['whatsapp_template_id'] ?? '',
            'whatsapp_body' => $validated['whatsapp_body'] ?? '',
            'email_subject' => $validated['email_subject'],
            'email_body' => $validated['email_body'] ?? '',
            'admin_instructions' => $validated['admin_instructions'] ?? '',
        ]);

        return redirect()
            ->route('admin.settings.password-reset-message.edit')
            ->with('success', 'تم حفظ رسالة إعادة تعيين كلمة المرور بنجاح.');
    }

    public function restoreDefaults(): RedirectResponse
    {
        $this->settingsService->restoreDefaults();

        return redirect()
            ->route('admin.settings.password-reset-message.edit')
            ->with('success', 'تمت استعادة الرسائل الافتراضية.');
    }
}
