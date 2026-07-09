<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordResetDeliveryService;
use App\Services\Auth\PhoneOtpService;
use App\Enums\OtpPurpose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private PasswordResetDeliveryService $resetDelivery
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        $otpService = app(PhoneOtpService::class);

        return view('auth.forgot-password', [
            'whatsappAvailable' => $this->resetDelivery->isWhatsAppAvailable(),
            'whatsappOtpAvailable' => $otpService->isAvailableFor(OtpPurpose::ResetPassword),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $channel = $request->input('channel', 'email');
        $countryCodes = array_keys(config('country_codes.list', []));

        $request->validate([
            'channel' => ['required', Rule::in(['email', 'whatsapp', 'whatsapp_otp'])],
            'email' => [Rule::requiredIf($channel === 'email'), 'nullable', 'email'],
            'country_code' => [
                Rule::requiredIf(in_array($channel, ['whatsapp', 'whatsapp_otp'], true)),
                'nullable',
                Rule::in($countryCodes),
            ],
            'phone' => [
                Rule::requiredIf(in_array($channel, ['whatsapp', 'whatsapp_otp'], true)),
                'nullable',
                'string',
                'regex:/^[0-9]{6,14}$/',
            ],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'country_code.required' => 'رمز الدولة مطلوب.',
            'country_code.in' => 'رمز الدولة غير صالح.',
            'phone.required' => 'رقم الجوال مطلوب.',
            'phone.regex' => 'أدخل رقم الجوال بدون رمز الدولة وبدون صفر في البداية.',
        ]);

        if ($channel === 'whatsapp' && ! $this->resetDelivery->isWhatsAppAvailable()) {
            return back()->withInput()->withErrors([
                'phone' => 'إرسال رابط الاستعادة عبر الواتساب غير متاح حالياً.',
            ]);
        }

        if ($channel === 'whatsapp_otp' && ! app(PhoneOtpService::class)->isAvailableFor(OtpPurpose::ResetPassword)) {
            return back()->withInput()->withErrors([
                'phone' => 'استعادة كلمة المرور عبر رمز OTP غير متاحة حالياً.',
            ]);
        }

        if ($channel === 'whatsapp_otp') {
            return app(PhonePasswordResetOtpController::class)->send($request);
        }

        try {
            if ($channel === 'whatsapp') {
                $result = $this->resetDelivery->sendViaWhatsAppParts(
                    (string) $request->input('country_code'),
                    (string) $request->input('phone')
                );
            } else {
                $result = $this->resetDelivery->sendViaEmail((string) $request->input('email'));
            }
        } catch (\InvalidArgumentException $e) {
            $field = $channel === 'whatsapp' ? 'phone' : 'email';

            return back()->withInput()->withErrors([$field => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Password reset delivery failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            $field = $channel === 'whatsapp' ? 'phone' : 'email';

            return back()->withInput()->withErrors([
                $field => 'تعذّر إرسال بيانات الدخول. حاول لاحقاً أو استخدم البريد الإلكتروني.',
            ]);
        }

        $status = $result['status'];
        $delivery = $result['delivery'];

        $messages = [
            Password::RESET_LINK_SENT => PasswordResetDeliveryService::buildSuccessMessage($delivery),
            Password::INVALID_USER => $channel === 'whatsapp'
                ? 'لا يمكننا العثور على حساب مرتبط بهذا الرقم.'
                : 'لا يمكننا العثور على مستخدم بهذا البريد الإلكتروني.',
            Password::RESET_THROTTLED => 'يرجى الانتظار قبل إعادة المحاولة.',
        ];

        $message = $messages[$status] ?? trans($status, [], 'ar');

        if ($status === Password::RESET_LINK_SENT) {
            $contact = $channel === 'whatsapp'
                ? ($delivery['whatsapp_recipient']
                    ?? $this->resetDelivery->formatPhoneForDisplay(
                        (string) $request->input('country_code'),
                        (string) $request->input('phone')
                    ))
                : $request->input('email');

            return back()
                ->with('status', $message)
                ->with('reset_channel', $channel)
                ->with('reset_contact', $contact)
                ->with('reset_delivery', $delivery);
        }

        $field = $channel === 'whatsapp' ? 'phone' : 'email';

        return back()
            ->withInput($request->only('channel', 'country_code', 'phone', 'email'))
            ->withErrors([$field => $message]);
    }
}
