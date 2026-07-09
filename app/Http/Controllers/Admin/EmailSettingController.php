<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Services\SmtpConnectionTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailSettingController extends Controller
{
    public function __construct(
        private SmtpConnectionTestService $connectionTester
    ) {}

    public function index()
    {
        $settings = EmailSetting::orderBy('created_at', 'desc')->get();
        $activeSettings = EmailSetting::getActive();
        $providers = EmailSetting::getProviderPresets();

        return view('admin.pages.settings.email.index', compact('settings', 'activeSettings', 'providers'));
    }

    public function create()
    {
        $providers = EmailSetting::getProviderPresets();

        return view('admin.pages.settings.email.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        $validated['mail_mailer'] = 'smtp';
        $validated['is_active'] = false;

        EmailSetting::create($validated);

        return redirect()
            ->route('admin.settings.email.index')
            ->with('success', 'تم إضافة إعدادات البريد الإلكتروني بنجاح');
    }

    public function edit(EmailSetting $emailSetting)
    {
        $providers = EmailSetting::getProviderPresets();

        return view('admin.pages.settings.email.edit', compact('emailSetting', 'providers'));
    }

    public function update(Request $request, EmailSetting $emailSetting)
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
        ]);

        if (empty($validated['mail_password'])) {
            unset($validated['mail_password']);
        }

        $emailSetting->update($validated);

        return redirect()
            ->route('admin.settings.email.index')
            ->with('success', 'تم تحديث إعدادات البريد الإلكتروني بنجاح');
    }

    public function destroy(EmailSetting $emailSetting)
    {
        if ($emailSetting->is_active) {
            return back()->with('error', 'لا يمكن حذف الإعدادات النشطة');
        }

        $emailSetting->delete();

        return redirect()
            ->route('admin.settings.email.index')
            ->with('success', 'تم حذف إعدادات البريد الإلكتروني بنجاح');
    }

    public function activate(EmailSetting $emailSetting)
    {
        $emailSetting->activate();

        return back()->with('success', 'تم تفعيل إعدادات البريد الإلكتروني بنجاح');
    }

    public function test(Request $request, EmailSetting $emailSetting)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $emailSetting->applyToConfig();

            Mail::raw('هذا بريد اختبار من كلاودسوفت. إذا استلمت هذه الرسالة، فإن إعدادات SMTP تعمل بشكل صحيح.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('اختبار إعدادات البريد الإلكتروني - كلاودسوفت');
            });

            $emailSetting->update([
                'test_results' => [
                    'status' => 'success',
                    'type' => 'send',
                    'message' => 'تم إرسال البريد الاختباري بنجاح',
                    'tested_email' => $request->test_email,
                    'tested_at' => now()->toDateTimeString(),
                ],
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال البريد الاختباري بنجاح إلى '.$request->test_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Email test failed', [
                'error' => $e->getMessage(),
                'setting_id' => $emailSetting->id,
            ]);

            $emailSetting->update([
                'test_results' => [
                    'status' => 'failed',
                    'type' => 'send',
                    'message' => $e->getMessage(),
                    'tested_email' => $request->test_email,
                    'tested_at' => now()->toDateTimeString(),
                ],
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال البريد الاختباري: '.$e->getMessage(),
            ], 500);
        }
    }

    public function testConnection(EmailSetting $emailSetting)
    {
        $result = $this->connectionTester->test($emailSetting->toConnectionConfig());

        $emailSetting->update([
            'test_results' => [
                'status' => $result['success'] ? 'success' : 'failed',
                'type' => 'connection',
                'message' => $result['message'],
                'tested_at' => now()->toDateTimeString(),
            ],
            'last_tested_at' => now(),
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function testConnectionTemp(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'email_setting_id' => 'nullable|exists:email_settings,id',
        ]);

        $password = $validated['mail_password'] ?? null;
        if (empty($password) && ! empty($validated['email_setting_id'])) {
            $existing = EmailSetting::find($validated['email_setting_id']);
            $password = $existing?->mail_password;
        }

        if (empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إدخال كلمة المرور لاختبار الاتصال.',
            ], 422);
        }

        $result = $this->connectionTester->test([
            'host' => $validated['mail_host'],
            'port' => (int) $validated['mail_port'],
            'encryption' => $validated['mail_encryption'],
            'username' => $validated['mail_username'],
            'password' => $password,
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function getProviderPreset($provider)
    {
        $presets = EmailSetting::getProviderPresets();

        if (isset($presets[$provider])) {
            return response()->json($presets[$provider]);
        }

        return response()->json(['error' => 'Provider not found'], 404);
    }

    public function testTemp(Request $request)
    {
        $validated = $request->validate([
            'mail_host' => 'required|string|max:255',
            'mail_port' => 'required|integer',
            'mail_username' => 'required|string|max:255',
            'mail_password' => 'required|string',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
            'test_email' => 'required|email',
        ]);

        try {
            $port = (int) $validated['mail_port'];

            config([
                'mail.mailers.smtp.scheme' => EmailSetting::resolveMailScheme($port, $validated['mail_encryption']),
                'mail.mailers.smtp.host' => $validated['mail_host'],
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.username' => $validated['mail_username'],
                'mail.mailers.smtp.password' => $validated['mail_password'],
                'mail.from.address' => $validated['mail_from_address'],
                'mail.from.name' => $validated['mail_from_name'],
            ]);

            Mail::raw('هذا بريد اختبار من كلاودسوفت. إذا استلمت هذه الرسالة، فإن إعدادات SMTP تعمل بشكل صحيح.', function ($message) use ($validated) {
                $message->to($validated['test_email'])
                    ->subject('اختبار إعدادات البريد الإلكتروني - كلاودسوفت');
            });

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال البريد الاختباري بنجاح إلى '.$validated['test_email'],
            ]);
        } catch (\Exception $e) {
            Log::error('Email test failed (temp)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال البريد الاختباري: '.$e->getMessage(),
            ], 500);
        }
    }
}
