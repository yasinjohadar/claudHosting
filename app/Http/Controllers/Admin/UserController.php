<?php

namespace App\Http\Controllers\Admin;

use HashContext;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // جلب آخر جلسات المستخدمين
        $sessions = DB::table('sessions')
            ->orderByDesc('last_activity')
            ->get()
            ->groupBy('user_id');

        // بدء استعلام المستخدمين
        $usersQuery = User::query();

        // فلترة حسب البحث (name, email, phone)
        if ($request->filled('query')) {
            $search = $request->input('query');
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $usersQuery->where('status', $request->input('status'));
        }

        // فلترة حسب الحالة النشطة
        if ($request->filled('is_active')) {
            $usersQuery->where('is_active', $request->input('is_active'));
        }

        // تنفيذ الاستعلام
        $users = $usersQuery->paginate(10);

        return view("admin.pages.users.index", compact("users", "roles", "sessions"));
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
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,banned',
            'is_active' => 'boolean',
            'roles' => 'array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'status.required' => 'حالة المستخدم مطلوبة',
            'photo.image' => 'يجب أن يكون الملف صورة',
            'photo.mimes' => 'نوع الصورة غير مدعوم',
            'photo.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        // معالجة الصورة
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('users/photos', $photoName, 'public');
        }

        // إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'is_active' => $request->has('is_active'),
            'photo' => $photoPath,
            'created_by' => auth()->id(), // المستخدم الذي أنشأ هذا الحساب
        ]);

        // تعيين الأدوار
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route("users.index")->with("success" , "تم إضافة مستخدم جديد بنجاح");
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return view("admin.pages.users.profile" , compact("user"));
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
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,banned',
            'is_active' => 'boolean',
            'roles' => 'array',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'status.required' => 'حالة المستخدم مطلوبة',
            'photo.image' => 'يجب أن يكون الملف صورة',
            'photo.mimes' => 'نوع الصورة غير مدعوم',
            'photo.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        // تجهيز البيانات للتحديث
        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
            'is_active' => $request->has('is_active'),
        ];

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
