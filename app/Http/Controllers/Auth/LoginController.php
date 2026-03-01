<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * إلى أين يتم توجيه المستخدم بعد تسجيل الدخول.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * إنشاء مثيل جديد من وحدة التحكم.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    /**
     * عرض صفحة تسجيل الدخول.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }
    
    /**
     * معالجة طلب تسجيل الدخول.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            return redirect()->intended($this->redirectTo);
        }

        return back()->withErrors([
            'email' => 'بيانات الاعتماد المقدمة لا تطابق سجلاتنا.',
        ])->onlyInput('email');
    }
    
    /**
     * تسجيل خروج المستخدم من التطبيق.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}