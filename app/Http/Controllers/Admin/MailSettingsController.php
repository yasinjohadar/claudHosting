<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Mail\MailSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailSettingsController extends Controller
{
    public function __construct(
        protected MailSettingsService $mailSettings
    ) {}

    public function index()
    {
        $this->mailSettings->initializeDefaults();
        $settings = $this->mailSettings->getSettings();

        return view('admin.pages.mail-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mail_enabled' => 'nullable',
            'mailer' => 'required|string|in:smtp,log',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:500',
            'encryption' => 'nullable|string|in:none,tls,ssl',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
        ]);

        $settings = $this->mailSettings->getSettings();
        if (($validated['password'] ?? '') === '') {
            $validated['password'] = $settings['password'] ?? '';
        }
        if (($validated['encryption'] ?? 'none') === 'none') {
            $validated['encryption'] = '';
        }

        $validated['mail_enabled'] = $request->has('mail_enabled');
        $this->mailSettings->updateSettings($validated);
        $this->mailSettings->applyRuntimeConfig();

        return redirect()->route('admin.mail-settings.index')->with('success', 'تم حفظ إعدادات البريد بنجاح.');
    }

    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);

        $this->mailSettings->applyRuntimeConfig();

        try {
            Mail::raw('This is a test email from SMTP settings page.', function ($message) use ($validated) {
                $message->to($validated['test_email'])->subject('SMTP Test Email');
            });

            return redirect()->route('admin.mail-settings.index')->with('success', 'تم إرسال رسالة الاختبار بنجاح.');
        } catch (\Throwable $exception) {
            return redirect()->route('admin.mail-settings.index')->with('error', 'فشل اختبار SMTP: '.$exception->getMessage());
        }
    }
}
