<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Auth\ClientImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ClientImpersonationController extends Controller
{
    public function __construct(protected ClientImpersonationService $impersonation) {}

    public function consume(Request $request, string $token): RedirectResponse
    {
        try {
            $this->impersonation->consume($token, $request->ip());

            return redirect()
                ->route('client.dashboard')
                ->with('success', 'تم تسجيل الدخول إلى لوحة العميل.');
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('login')
                ->with('error', $e->getMessage());
        }
    }

    public function stop(Request $request): RedirectResponse
    {
        $admin = $this->impersonation->stop();

        if ($admin) {
            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'تمت العودة إلى لوحة الإدارة.');
        }

        return redirect()->route('client.dashboard');
    }
}
