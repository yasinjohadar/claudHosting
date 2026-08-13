<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * إلى أين يتم توجيه المستخدم بعد التسجيل.
     *
     * @var string
     */
    protected $redirectTo = '/client';

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
     * عرض نموذج التسجيل.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }
    
    /**
     * معالجة طلب التسجيل.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
            'is_active' => true,
        ]);

        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $user->assignRole('client');

        $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        Customer::create([
            'user_id' => $user->id,
            'whmcs_id' => null,
            'firstname' => $nameParts[0] ?? $user->name,
            'lastname' => $nameParts[1] ?? '',
            'fullname' => $user->name,
            'email' => $user->email,
            'country' => config('whmcs.default_country', 'SY'),
            'status' => 'Active',
            'date_created' => now(),
        ]);

        auth()->login($user);

        return redirect()->route('client.dashboard');
    }
}