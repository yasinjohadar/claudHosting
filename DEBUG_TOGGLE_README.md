# تصحيح نظام التبديل للمستخدمين

## المشكلة المبلغ عنها
عند الضغط على التبديل وتحديث الصفحة، يعود التبديل لحالته السابقة.

## الحلول المطبقة

### 1. إصلاح Cast في Model
```php
// في app/Models/User.php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean', // إضافة هذا السطر
    ];
}
```

### 2. إصلاح المسار في JavaScript
```javascript
// تغيير من
fetch(`/admin/users/${userId}/toggle-status`, {

// إلى
fetch(`/users/${userId}/toggle-status`, {
```

### 3. تحسين معالجة الأخطاء
- إضافة فحص `response.ok`
- تحسين رسائل الخطأ
- إضافة تسجيل العمليات

### 4. إصلاح Middleware
```php
// فصل مسار toggle-status عن middleware check.user.active
Route::middleware(['auth'])->group(function () {
    Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});
```

### 5. تحسين دالة toggleStatus
- إضافة `$user->refresh()` للتأكد من التحديث
- إضافة تسجيل العمليات
- تحسين رسائل الخطأ

## كيفية الاختبار

### 1. اختبار التبديل
1. انتقل إلى صفحة قائمة المستخدمين
2. انقر على التبديل لأي مستخدم
3. أكد العملية
4. تحقق من رسالة النجاح
5. **لا تحدث الصفحة** - تحقق من أن التبديل بقي في حالته الجديدة

### 2. اختبار الفلترة
1. استخدم فلتر "الحالة النشطة"
2. تحقق من أن المستخدمين يتم تصفيتهم بشكل صحيح

### 3. اختبار الحماية
1. حاول إلغاء تفعيل حسابك
2. تحقق من رسالة الخطأ

## معلومات التصحيح

### Debug Information
تم إضافة معلومات تصحيح مؤقتة في الصفحة:
- ID المستخدم
- قيمة `is_active` الحالية

### سجلات النظام
تحقق من سجلات Laravel:
```bash
tail -f storage/logs/laravel.log
```

### اختبار API مباشرة
```bash
curl -X POST http://your-domain/users/1/toggle-status \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"is_active": false}'
```

## إزالة معلومات التصحيح

بعد التأكد من أن النظام يعمل بشكل صحيح، قم بإزالة السطر التالي من `resources/views/admin/pages/users/index.blade.php`:

```php
<!-- Debug info (remove in production) -->
<small class="text-muted d-block">
    ID: {{ $user->id }} | 
    is_active: {{ $user->is_active ? 'true' : 'false' }}
</small>
```

## التحقق من قاعدة البيانات

تأكد من أن حقل `is_active` موجود في جدول `users`:

```sql
DESCRIBE users;
```

يجب أن ترى:
```
is_active tinyint(1) NOT NULL DEFAULT 1
```

## إعادة تشغيل النظام

بعد التحديثات، قم بتنفيذ:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
``` 