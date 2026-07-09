<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\PhoneField;
use App\Support\UserAddressField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ClientProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(): View
    {
        return view('client.pages.profile-show', [
            'user' => auth()->user(),
        ]);
    }

    public function edit(): View
    {
        return view('client.pages.profile-edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,'.$user->id,
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], PhoneField::validationRules(), UserAddressField::validationRules()), [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'photo.image' => 'يجب أن يكون الملف صورة',
            'photo.mimes' => 'نوع الصورة غير مدعوم',
            'photo.max' => 'حجم الصورة يجب أن يكون أقل من 5 ميجابايت',
        ]);

        PhoneField::assertValidPair($request->country_code, $request->phone);
        $phoneData = PhoneField::normalizeForStorage($request->country_code, $request->phone);
        PhoneField::assertUniqueE164($phoneData['e164'] ?? null, (int) $user->id);

        $updateData = array_merge([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $phoneData['phone'] ?? null,
            'country_code' => $phoneData['country_code'] ?? null,
        ], UserAddressField::fromRequest($request));

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');

            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $baseName = Str::slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME));
            $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->guessExtension() ?: 'jpg');
            $photoName = time().'_'.($baseName !== '' ? $baseName : 'photo').'.'.$extension;
            $path = $photo->storeAs('users/photos', $photoName, 'public');

            if ($path === false) {
                return back()
                    ->withErrors(['photo' => 'تعذر حفظ الصورة. حاول مرة أخرى.'])
                    ->withInput();
            }

            $updateData['photo'] = $path;
        }

        $user->update($updateData);
        $user->refresh();

        return redirect()
            ->route('client.profile.show')
            ->with('success', 'تم تحديث ملفك الشخصي بنجاح');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'كلمة المرور الحالية غير صحيحة',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('client.profile.show')
            ->with('success', 'تم تحديث كلمة المرور بنجاح');
    }
}
