# نظام الصلاحيات - Laravel Permission System

## نظرة عامة
هذا المشروع مجهز بنظام صلاحيات متكامل باستخدام حزمة Spatie Laravel Permission.

## المكونات الأساسية

### 1. الحزم المثبتة
- `spatie/laravel-permission`: حزمة إدارة الصلاحيات والأدوار

### 2. النماذج (Models)
- `User.php`: يستخدم trait `HasRoles` لإدارة الأدوار والصلاحيات

### 3. Controllers
- `RoleController.php`: إدارة الأدوار
- `UserController.php`: إدارة المستخدمين

### 4. الصلاحيات المتاحة
```php
// صلاحيات الأدوار
role-list, role-create, role-edit, role-delete

// صلاحيات المستخدمين
user-list, user-create, user-edit, user-delete, user-show

// صلاحيات المنتجات (مثال)
product-list, product-create, product-edit, product-delete

// صلاحيات إضافية
dashboard-view, settings-manage, reports-view
```

## كيفية الاستخدام

### 1. التحقق من الصلاحيات في Controllers
```php
public function __construct()
{
    $this->middleware(['permission:user-list'])->only('index');
    $this->middleware(['permission:user-create'])->only(['create', 'store']);
    $this->middleware(['permission:user-edit'])->only(['edit', 'update']);
    $this->middleware(['permission:user-delete'])->only('destroy');
}
```

### 2. التحقق من الصلاحيات في Views
```php
@if(auth()->user()->hasPermissionTo('user-create'))
    <a href="{{ route('users.create') }}" class="btn btn-primary">إضافة مستخدم</a>
@endif
```

### 3. التحقق من الأدوار
```php
@if(auth()->user()->hasRole('admin'))
    <div class="admin-panel">
        <!-- محتوى لوحة الإدارة -->
    </div>
@endif
```

### 4. استخدام Helper
```php
use App\Helpers\PermissionHelper;

if (PermissionHelper::hasPermission('user-edit')) {
    // كود التعديل
}
```

## الإعداد الأولي

### 1. تشغيل الـ Migrations
```bash
php artisan migrate
```

### 2. تشغيل الـ Seeders
```bash
php artisan db:seed
```

### 3. بيانات الدخول الافتراضية
- **المدير**: admin@admin.com / 12345678
- **المستخدم العادي**: user@example.com / password

## إضافة صلاحيات جديدة

### 1. إضافة صلاحية في PermissionSeeder
```php
$permissions = [
    'new-feature-view',
    'new-feature-create',
    'new-feature-edit',
    'new-feature-delete',
];
```

### 2. إضافة middleware في Controller
```php
$this->middleware(['permission:new-feature-view'])->only('index');
```

### 3. إنشاء دور جديد
```php
$newRole = Role::create(['name' => 'editor']);
$newRole->syncPermissions(['new-feature-view', 'new-feature-edit']);
```

## أفضل الممارسات

1. **تسمية الصلاحيات**: استخدم نمط `resource-action` (مثل: user-create)
2. **تجميع الصلاحيات**: اجمع الصلاحيات المتعلقة في أدوار منطقية
3. **التحقق المزدوج**: تحقق من الصلاحيات في الـ Controller والـ View
4. **التوثيق**: وثق جميع الصلاحيات الجديدة
5. **الاختبار**: اكتب اختبارات للصلاحيات الجديدة

## الأمان

- جميع المسارات محمية بـ middleware الصلاحيات
- التحقق من الصلاحيات يتم على مستوى الخادم
- لا تعتمد فقط على التحقق في الـ Frontend

## استكشاف الأخطاء

### مشكلة: المستخدم لا يرى الصفحة
1. تحقق من وجود الصلاحية المطلوبة
2. تحقق من تعيين الدور للمستخدم
3. تحقق من middleware الصلاحيات

### مشكلة: خطأ 403
1. تحقق من صلاحيات المستخدم
2. تحقق من إعدادات middleware
3. تحقق من قاعدة البيانات

## التطوير المستقبلي

1. إضافة واجهة إدارة الصلاحيات
2. إضافة سجل للصلاحيات المستخدمة
3. إضافة نظام صلاحيات متقدم (مثل: صلاحيات على مستوى البيانات)
4. إضافة API للصلاحيات 