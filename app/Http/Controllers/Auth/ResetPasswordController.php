<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * إلى أين يتم توجيه المستخدم بعد إعادة تعيين كلمة المرور.
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
        $this->middleware('guest');
    }
    
    /**
     * عرض نموذج إعادة تعيين كلمة المرور.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route()->parameter('token');

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }
}