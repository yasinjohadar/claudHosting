<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelUser
{
    /**
     * يسمح فقط لمستخدمي لوحة الإدارة (دور admin أو صلاحية user-list).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (! $user->isAdminPanelUser()) {
            if ($user->hasRole('client')) {
                return redirect()->route('client.dashboard');
            }

            return redirect()->route('home');
        }

        return $next($request);
    }
}
