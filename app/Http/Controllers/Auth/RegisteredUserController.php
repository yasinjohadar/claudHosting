<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\WhmcsApiService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(protected WhmcsApiService $whmcsApi)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     * Creates User, then attempts to create WHMCS client and link Customer record.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $nameParts = explode(' ', trim($request->name), 2);
        $firstname = $nameParts[0] ?? $request->name;
        $lastname = $nameParts[1] ?? '';
        $country = config('whmcs.default_country', 'US');

        $addResult = $this->whmcsApi->addCustomer([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $user->email,
            'password' => $request->password,
            'country' => $country,
        ]);

        if ($addResult['success'] ?? false) {
            $clientId = (int) ($addResult['data']['clientid'] ?? $addResult['data']['id'] ?? 0);
            if ($clientId > 0) {
                Customer::create([
                    'user_id' => $user->id,
                    'whmcs_id' => $clientId,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'fullname' => $request->name,
                    'email' => $user->email,
                    'country' => $country,
                    'status' => 'Active',
                ]);
            }
        } else {
            $existingCustomer = Customer::where('email', $user->email)->first();
            if ($existingCustomer) {
                $existingCustomer->update(['user_id' => $user->id]);
            } else {
                Customer::create([
                    'user_id' => $user->id,
                    'whmcs_id' => null,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'fullname' => $request->name,
                    'email' => $user->email,
                    'country' => $country,
                    'status' => 'Active',
                ]);
            }
        }

        return redirect(route('dashboard', absolute: false));
    }
}
