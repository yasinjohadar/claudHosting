<?php

namespace App\Http\Controllers\Admin;

use HashContext;
use App\Models\User;
use App\Support\PhoneField;
use App\Support\UserAddressField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     // يمكنه فقط رؤية قائمة المستخدمين (index)
    //     $this->middleware(['permission:user-list'])->only('index');

    //     // يمكنه فقط إنشاء مستخدم جديد (create + store)
    //     $this->middleware(['permission:user-create'])->only(['create', 'store']);

    //     // يمكنه فقط تعديل المستخدم (edit + update)
    //     $this->middleware(['permission:user-edit'])->only(['edit', 'update']);

    //     // يمكنه فقط حذف المستخدم (destroy)
    //     $this->middleware(['permission:user-delete'])->only('destroy');

    //     // يمكنه فقط رؤية ملف المستخدم (show)
    //     $this->middleware(['permission:user-show'])->only('show');
    // }

    public function __construct()
{
    // تأكد أن المستخدم مصادق أولًا ثم تحقق من الصلاحيات
    $this->middleware('auth');

    $this->middleware('permission:user-list')->only('index');
    $this->middleware('permission:user-create')->only(['create', 'store']);
    $this->middleware('permission:user-edit')->only(['edit', 'update']);
    $this->middleware('permission:user-delete')->only('destroy');
    $this->middleware('permission:user-show')->only('show');
}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $roles = Role::all();
        $sessions = $this->userSessionsGrouped();
        $users = $this->paginateUsers($request);
        $stats = $this->userStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.pages.users.partials.list-results', compact('users', 'sessions'))->render(),
                'total' => $users->total(),
            ]);
        }

        return view('admin.pages.users.index', compact('users', 'roles', 'sessions', 'stats'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    protected function userSessionsGrouped()
    {
        return DB::table('sessions')
            ->orderByDesc('last_activity')
            ->get()
            ->groupBy('user_id');
    }

    protected function paginateUsers(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->buildUsersQuery($request)->paginate(10)->withQueryString();
    }

    protected function buildUsersQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $usersQuery = User::query();

        if ($request->filled('query')) {
            $search = '%'.trim((string) $request->input('query')).'%';
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive', 'banned'], true)) {
            $usersQuery->where('status', $request->status);
        }

        if ($request->filled('is_active')) {
            $usersQuery->where('is_active', $request->boolean('is_active'));
        }

        return $usersQuery->withCount('whmAccounts');
    }

    /**
     * @return array<string, int>
     */
    protected function userStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'with_whm' => User::whereHas('whmAccounts')->count(),
        ];
    }





    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view("admin.pages.users.create" ,compact("roles"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // التحقق من صحة البيانات
        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'status' => 'required|in:active,inactive,banned',
            'is_active' => 'boolean',
            'roles' => 'array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], PhoneField::validationRules(), UserAddressField::validationRules()), [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'status.required' => 'حالة المستخدم مطلوبة',
            'photo.image' => 'يجب أن يكون الملف صورة',
            'photo.mimes' => 'نوع الصورة غير مدعوم',
            'photo.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        PhoneField::assertValidPair($request->country_code, $request->phone);
        $phoneData = PhoneField::normalizeForStorage($request->country_code, $request->phone);
        PhoneField::assertUniqueE164($phoneData['e164'] ?? null);

        // معالجة الصورة
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('users/photos', $photoName, 'public');
        }

        // إنشاء المستخدم
        $user = User::create(array_merge([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $phoneData['phone'] ?? null,
            'country_code' => $phoneData['country_code'] ?? null,
            'password' => Hash::make(Str::random(24)),
            'status' => $request->status,
            'is_active' => $request->has('is_active'),
            'photo' => $photoPath,
            'created_by' => auth()->id(),
        ], UserAddressField::fromRequest($request)));

        // تعيين الأدوار
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'تم إضافة مستخدم جديد بنجاح — يمكن تعيين كلمة المرور من قائمة المستخدمين.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['whmAccounts' => fn ($q) => $q->orderByDesc('joined_at')])
            ->with('whmAccounts.invoices')
            ->with(['cyberpanelWebsites' => fn ($q) => $q->orderByDesc('joined_at')])
            ->withCount(['whmAccounts', 'cyberpanelWebsites'])
            ->findOrFail($id);

        $whmConfigured = app(\App\Services\Whm\WhmApiService::class)->isConfigured();
        $cyberpanelConfigured = app(\App\Services\CyberPanel\CyberPanelApiService::class)->isConfigured();

        return view('admin.pages.users.profile', compact('user', 'whmConfigured', 'cyberpanelConfigured'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();
        return view("admin.pages.users.edit" ,compact("roles" , "user"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // التحقق من صحة البيانات
        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,banned',
            'is_active' => 'boolean',
            'roles' => 'array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], PhoneField::validationRules(), UserAddressField::validationRules()), [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'status.required' => 'حالة المستخدم مطلوبة',
            'photo.image' => 'يجب أن يكون الملف صورة',
            'photo.mimes' => 'نوع الصورة غير مدعوم',
            'photo.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        PhoneField::assertValidPair($request->country_code, $request->phone);
        $phoneData = PhoneField::normalizeForStorage($request->country_code, $request->phone);
        PhoneField::assertUniqueE164($phoneData['e164'] ?? null, (int) $id);

        // تجهيز البيانات للتحديث
        $updateData = array_merge([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $phoneData['phone'] ?? null,
            'country_code' => $phoneData['country_code'] ?? null,
            'status' => $request->status,
            'is_active' => $request->has('is_active'),
        ], UserAddressField::fromRequest($request));

        // تحديث كلمة المرور فقط إذا تم إدخالها
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // معالجة الصورة
        if ($request->hasFile('photo')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($user->photo) {
                \Storage::disk('public')->delete($user->photo);
            }

            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('users/photos', $photoName, 'public');
            $updateData['photo'] = $photoPath;
        }

        // تحديث المستخدم
        $user->update($updateData);

        // تحديث الأدوار
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'تم تحديث بيانات المستخدم بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $user = User::findOrFail($request->id);

        $user->delete();

        return redirect()->route("users.index")->with("success" , "تم حذف مستخدم جديد بنجاح");

    }



    public function updatePassword(Request $request, User $user)
{
    $request->validate([
        'password' => 'required|string|min:8|confirmed',
    ], [
        'password.required' => 'كلمة المرور مطلوبة',
        'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
    ]);

    $user->update([
        'password' => Hash::make($request->password),
    ]);

    return redirect()->route('users.index')->with('success', 'تم تحديث كلمة المرور بنجاح');
}

/**
 * تبديل حالة المستخدم (تفعيل/إلغاء تفعيل)
 */
public function toggleStatus(Request $request, $id)
{
    try {
        \Log::info('Toggle status request received', [
            'user_id' => $id,
            'request_data' => $request->all(),
            'request_method' => $request->method(),
            'request_url' => $request->url(),
            'request_headers' => $request->headers->all(),
            'auth_user' => auth()->id()
        ]);

        $user = User::findOrFail($id);

        \Log::info('User found', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'current_is_active' => $user->is_active
        ]);

        // التحقق من أن المستخدم لا يحاول إلغاء تفعيل نفسه
        if ($user->id === auth()->id()) {
            \Log::warning('User tried to deactivate themselves', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إلغاء تفعيل حسابك'
            ], 400);
        }

        // حفظ الحالة القديمة
        $oldStatus = $user->is_active;

        // تبديل الحالة
        $newStatus = !$user->is_active;

        // تحديث الحالة باستخدام update للتأكد من التحديث
        $user->update(['is_active' => $newStatus]);

        // إعادة تحميل المستخدم للتأكد من الحصول على القيمة المحدثة
        $user->refresh();

        \Log::info('User status updated', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'old_status' => $oldStatus,
            'new_status' => $user->is_active,
            'toggled_by' => auth()->id()
        ]);

        $status = $user->is_active ? 'مفعل' : 'غير مفعل';

        $response = [
            'success' => true,
            'message' => "تم تحديث حالة المستخدم إلى: {$status}",
            'is_active' => (bool) $user->is_active
        ];

        \Log::info('Toggle status response', [
            'user_id' => $user->id,
            'response' => $response
        ]);

        return response()->json($response);

    } catch (\Exception $e) {
        \Log::error('Error toggling user status', [
            'user_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'toggled_by' => auth()->id()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث حالة المستخدم: ' . $e->getMessage()
        ], 500);
    }
}


}
