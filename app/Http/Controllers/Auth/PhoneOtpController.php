<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Http\Controllers\Controller;
use App\Services\Auth\PhoneOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PhoneOtpController extends Controller
{
    public function __construct(
        private PhoneOtpService $otpService
    ) {}

    public function showVerify(Request $request): View|RedirectResponse
    {
        $purpose = OtpPurpose::tryFrom((string) $request->query('purpose', ''));
        $phone = (string) $request->query('phone', '');

        if (! $purpose || $phone === '') {
            return redirect()->route('login')->with('error', 'جلسة التحقق غير صالحة.');
        }

        return view('auth.phone-otp-verify', [
            'purpose' => $purpose,
            'phone' => $phone,
            'phoneDisplay' => '+'.$phone,
            'resendCooldownRemaining' => $this->otpService->getResendCooldownRemaining($phone, $purpose),
        ]);
    }

    public function send(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => 'required|string',
            'phone' => 'required|string',
            'country_code' => 'nullable|string',
        ]);

        $purpose = OtpPurpose::tryFrom($validated['purpose']);
        if (! $purpose) {
            return $this->otpError($request, 'نوع التحقق غير صالح.');
        }

        $phone = $validated['phone'];
        if (! empty($validated['country_code'])) {
            $phone = $this->otpService->formatPhoneDisplay(
                (string) $validated['country_code'],
                $phone
            );
        }

        try {
            $result = $this->otpService->send($phone, $purpose, $request->user(), $request->ip());
        } catch (\InvalidArgumentException $e) {
            return $this->otpSendError($request, $e->getMessage(), $purpose, $phone);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, ...$result]);
        }

        $normalizedPhone = $this->otpService->normalizePhone($phone);

        return redirect()
            ->route('phone-otp.verify', [
                'purpose' => $purpose->value,
                'phone' => $normalizedPhone,
            ])
            ->with('status', 'تم إرسال رمز التحقق إلى واتساب.');
    }

    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => 'required|string',
            'phone' => 'required|string',
            'code' => 'required|string|min:4|max:8',
        ]);

        $purpose = OtpPurpose::tryFrom($validated['purpose']);
        if (! $purpose) {
            return $this->otpError($request, 'نوع التحقق غير صالح.');
        }

        try {
            $this->otpService->verify($validated['phone'], $purpose, $validated['code']);
            $this->otpService->markSessionVerified($validated['phone'], $purpose);
        } catch (\InvalidArgumentException $e) {
            return $this->otpError($request, $e->getMessage(), $validated);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => session('phone_otp_after_verify_url')]);
        }

        $redirect = session('phone_otp_after_verify_url');
        if ($redirect) {
            session()->forget('phone_otp_after_verify_url');

            return redirect()->to($redirect)->with('status', 'تم التحقق من الرقم بنجاح.');
        }

        return redirect()->route('login')->with('status', 'تم التحقق من الرقم بنجاح.');
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function otpError(Request $request, string $message, array $input = []): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->withInput($input)->withErrors(['code' => $message]);
    }

    private function otpSendError(Request $request, string $message, OtpPurpose $purpose, string $phone): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        try {
            $normalizedPhone = $this->otpService->normalizePhone($phone);
        } catch (InvalidArgumentException) {
            return back()->with('error', $message);
        }

        return redirect()->route('phone-otp.verify', [
            'purpose' => $purpose->value,
            'phone' => $normalizedPhone,
        ])->with('error', $message);
    }
}
