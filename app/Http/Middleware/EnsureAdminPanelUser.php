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
            return redirect()->route('register');
        }

        if (! Auth::user()->isAdminPanelUser()) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
