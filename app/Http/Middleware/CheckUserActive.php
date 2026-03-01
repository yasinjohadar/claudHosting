<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // التحقق من أن المستخدم نشط
            if (!$user->is_active) {
                Auth::logout();
                
                return redirect()->route('login')->withErrors([
                    'email' => 'تم إلغاء تفعيل حسابك. يرجى التواصل مع الإدارة.',
                ]);
            }
        }

        return $next($request);
    }
} 