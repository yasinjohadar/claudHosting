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

            $user = Auth::user();
            $intended = $request->session()->pull('url.intended');

            if ($user && $user->isAdminPanelUser()) {
                if ($intended && $this->isAdminUrl($intended)) {
                    return redirect()->to($intended);
                }

                return redirect()->route('admin.dashboard');
            }

            if ($intended && ! $this->isAdminUrl($intended)) {
                return redirect()->to($intended);
            }

            return redirect()->route('client.dashboard');
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

    protected function isAdminUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_starts_with($path, '/admin')
            || str_starts_with($path, '/users')
            || str_starts_with($path, '/roles');
    }
}