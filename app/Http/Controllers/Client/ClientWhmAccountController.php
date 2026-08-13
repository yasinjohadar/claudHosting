<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Services\Whm\WhmAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientWhmAccountController extends Controller
{
    public function __construct(
        protected WhmAccountService $accounts
    ) {
        $this->middleware('auth');
    }

    public function openCpanel(WhmAccount $account): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->accounts->userOwnsAccount($user, $account), 403);

        if ($account->status === 'suspended') {
            return redirect()
                ->route('client.services')
                ->withFragment('hosting')
                ->with('error', 'الحساب معلّق حاليًا. تواصل مع الدعم لإعادة تفعيله قبل فتح cPanel.');
        }

        $result = $this->accounts->portalCpanelLoginUrl($user, $account);

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('client.services')
                ->withFragment('hosting')
                ->with('error', $result['message'] ?? 'تعذر فتح cPanel.');
        }

        return redirect()->away($result['url']);
    }

    public function updatePassword(Request $request, WhmAccount $account): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->accounts->userOwnsAccount($user, $account), 403);

        if (in_array($account->status, ['terminated', 'suspended'], true)) {
            $message = $account->status === 'suspended'
                ? 'لا يمكن تغيير كلمة المرور لحساب معلّق.'
                : 'لا يمكن تغيير كلمة المرور لحساب محذوف.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|max:64|confirmed',
        ], [
            'password.required' => 'أدخل كلمة المرور الجديدة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $result = $this->accounts->updatePassword($account, $validated['password']);

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if ($result['success']) {
            $session = $this->accounts->portalCpanelLoginUrl($user, $account);
            if ($session['success'] ?? false) {
                $payload['cpanel_url'] = $session['url'];
                $payload['message'] = ($result['message'] ?? 'تم تغيير كلمة المرور').' — جاري فتح cPanel…';
            }
        }

        if ($request->expectsJson()) {
            return response()->json($payload, $result['success'] ? 200 : 422);
        }

        if ($result['success'] && ! empty($payload['cpanel_url'])) {
            return redirect()->away($payload['cpanel_url']);
        }

        return redirect()
            ->route('client.services')
            ->withFragment('hosting')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function updateEmail(Request $request, WhmAccount $account): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($this->accounts->userOwnsAccount($user, $account), 403);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $result = $this->accounts->updateContactEmail($account, $validated['email']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'email' => $account->fresh()->display_email,
            ], $result['success'] ? 200 : 422);
        }

        return redirect()
            ->route('client.services')
            ->withFragment('hosting')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
