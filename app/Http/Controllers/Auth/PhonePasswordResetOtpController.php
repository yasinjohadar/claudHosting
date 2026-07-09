<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasswordResetDeliveryService;
use App\Services\Auth\PhoneOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PhonePasswordResetOtpController extends Controller
{
    public function __construct(
        private PhoneOtpService $otpService,
        private PasswordResetDeliveryService $resetDelivery
    ) {}

    public function send(Request $request): RedirectResponse
    {
        if (! $this->otpService->isAvailableFor(OtpPurpose::ResetPassword)) {
            return back()->withErrors(['phone' => 'استعادة كلمة المرور عبر OTP غير متاحة.']);
        }

        $request->validate([
            'country_code' => 'required|string',
            'phone' => 'required|string|regex:/^[0-9]{6,14}$/',
        ]);

        $fullPhone = $this->otpService->formatPhoneDisplay(
            (string) $request->input('country_code'),
            (string) $request->input('phone')
        );

        $user = $this->resetDelivery->findUserByPhone($fullPhone);
        if (! $user) {
            return back()->withInput()->withErrors(['phone' => 'لا يوجد حساب مرتبط بهذا الرقم.']);
        }

        try {
            $this->otpService->send($fullPhone, OtpPurpose::ResetPassword, $user, $request->ip());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['phone' => $e->getMessage()]);
        }

        session([
            'password_reset_otp_user_id' => $user->id,
            'password_reset_otp_phone' => $this->otpService->normalizePhone($fullPhone),
        ]);

        return redirect()->route('phone-otp.verify', [
            'purpose' => OtpPurpose::ResetPassword->value,
            'phone' => session('password_reset_otp_phone'),
        ])->with('status', 'تم إرسال رمز التحقق إلى واتساب.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|min:4|max:8']);

        $phone = (string) session('password_reset_otp_phone');
        $userId = session('password_reset_otp_user_id');

        if ($phone === '' || ! $userId) {
            return redirect()->route('password.request')->with('error', 'انتهت الجلسة.');
        }

        try {
            $this->otpService->verify($phone, OtpPurpose::ResetPassword, (string) $request->input('code'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $user = User::query()->find($userId);
        if (! $user) {
            return redirect()->route('password.request')->with('error', 'تعذّر إكمال العملية.');
        }

        $token = Password::broker()->createToken($user);
        session()->forget(['password_reset_otp_user_id', 'password_reset_otp_phone']);

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);
    }
}
