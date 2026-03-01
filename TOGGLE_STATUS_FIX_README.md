# إصلاح مشكلة زر is_active

## المشكلة
كان زر تبديل حالة المستخدم `is_active` يعود إلى الحالة السابقة بعد التحديث، مما يعني أن التحديث لم يتم حفظه بشكل صحيح.

## الأسباب المحتملة
1. **مشكلة في JavaScript**: عدم التعامل مع الاستجابة بشكل صحيح
2. **مشكلة في Controller**: عدم إرجاع القيمة المحدثة بشكل صحيح
3. **مشكلة في قاعدة البيانات**: عدم حفظ التحديث
4. **مشكلة في Model**: عدم تعريف الحقل بشكل صحيح

## الإصلاحات المطبقة

### 1. تحسين Controller
```php
// في UserController.php - toggleStatus method
public function toggleStatus(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);
        
        // التحقق من أن المستخدم لا يحاول إلغاء تفعيل نفسه
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إلغاء تفعيل حسابك'
            ], 400);
        }
        
        // تبديل الحالة
        $newStatus = !$user->is_active;
        
        // تحديث الحالة باستخدام update للتأكد من التحديث
        $user->update(['is_active' => $newStatus]);
        
        // إعادة تحميل المستخدم للتأكد من الحصول على القيمة المحدثة
        $user->refresh();
        
        $status = $user->is_active ? 'مفعل' : 'غير مفعل';
        
        return response()->json([
            'success' => true,
            'message' => "تم تحديث حالة المستخدم إلى: {$status}",
            'is_active' => (bool) $user->is_active
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث حالة المستخدم: ' . $e->getMessage()
        ], 500);
    }
}
```

### 2. تحسين JavaScript
```javascript
// في index.blade.php
document.addEventListener('DOMContentLoaded', function() {
    const toggleSwitches = document.querySelectorAll('.toggle-status');
    
    toggleSwitches.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const isActive = this.checked;
            const label = this.nextElementSibling;
            
            // منع التبديل المتكرر
            this.disabled = true;
            
            // رسالة التأكيد
            const confirmMessage = isActive 
                ? 'هل أنت متأكد من تفعيل هذا المستخدم؟' 
                : 'هل أنت متأكد من إلغاء تفعيل هذا المستخدم؟';
            
            if (!confirm(confirmMessage)) {
                this.checked = !isActive;
                this.disabled = false;
                return;
            }
            
            // إرسال الطلب
            const url = `/users/${userId}/toggle-status`;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    is_active: isActive
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث النص
                    label.textContent = data.is_active ? 'نشط' : 'غير نشط';
                    
                    // إظهار رسالة نجاح
                    showAlert(data.message || 'تم تحديث حالة المستخدم بنجاح', 'success');
                    
                    // تحديث حالة التبديل بناءً على الاستجابة الفعلية من الخادم
                    this.checked = Boolean(data.is_active);
                    
                    // تحديث data attribute للاستخدام المستقبلي
                    this.dataset.isActive = data.is_active;
                    
                    console.log('Toggle updated successfully:', {
                        userId: userId,
                        newStatus: data.is_active,
                        checked: this.checked
                    });
                } else {
                    // إرجاع التبديل إلى حالته السابقة
                    this.checked = !isActive;
                    showAlert(data.message || 'حدث خطأ أثناء تحديث حالة المستخدم', 'error');
                }
                
                // إعادة تفعيل التبديل
                this.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isActive;
                showAlert('حدث خطأ أثناء تحديث حالة المستخدم: ' + error.message, 'error');
                this.disabled = false;
            });
        });
    });
});
```

### 3. التأكد من Model
```php
// في User.php
protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'is_active' => 'boolean',
];

protected $fillable = [
    'name',
    'username',
    'email',
    'phone',
    'password',
    'status',
    'is_active',
    'photo',
    'created_by',
    'last_login_at',
    'last_login_ip',
    'last_login_user_agent',
];
```

### 4. التأكد من Route
```php
// في web.php
Route::middleware(['auth'])->group(function () {
    Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});
```

## التحسينات المضافة

### 1. منع التبديل المتكرر
- إضافة `disabled = true` أثناء الطلب
- إعادة تفعيل الزر بعد الانتهاء

### 2. تحسين التعامل مع الاستجابة
- استخدام `Boolean()` للتأكد من القيمة
- تحديث `data attribute` للاستخدام المستقبلي

### 3. تحسين التحديث في قاعدة البيانات
- استخدام `update()` بدلاً من `save()`
- إضافة `refresh()` للتأكد من القيمة المحدثة

### 4. تحسين التسجيل
- إضافة تسجيل مفصل للأخطاء
- تسجيل العمليات الناجحة

## الاختبار

### 1. اختبار التفعيل
1. انتقل إلى صفحة المستخدمين
2. اضغط على زر التبديل لتفعيل مستخدم غير مفعل
3. تأكد من أن الزر يبقى في حالة "مفعل"
4. تأكد من ظهور رسالة النجاح

### 2. اختبار إلغاء التفعيل
1. اضغط على زر التبديل لإلغاء تفعيل مستخدم مفعل
2. تأكد من أن الزر يبقى في حالة "غير مفعل"
3. تأكد من ظهور رسالة النجاح

### 3. اختبار الحماية
1. حاول إلغاء تفعيل حسابك الشخصي
2. تأكد من ظهور رسالة الخطأ
3. تأكد من عدم تغيير حالة الزر

## النتائج المتوقعة

✅ **زر التبديل يعمل بشكل صحيح**  
✅ **الحالة تُحفظ في قاعدة البيانات**  
✅ **الواجهة تُحدث بشكل فوري**  
✅ **رسائل النجاح والخطأ تظهر**  
✅ **منع التبديل المتكرر**  
✅ **حماية المستخدم من إلغاء تفعيل نفسه**  

## ملاحظات مهمة

1. **تأكد من وجود عمود `is_active`** في جدول `users`
2. **تأكد من أن الـ migration تم تشغيله** بشكل صحيح
3. **تأكد من وجود CSRF token** في الصفحة
4. **تأكد من أن المستخدم مُسجل دخول** قبل استخدام الزر

## استكشاف الأخطاء

### إذا لم يعمل الزر:
1. تحقق من console في المتصفح للأخطاء
2. تحقق من network tab للطلبات
3. تحقق من logs في Laravel
4. تأكد من صحة الـ route

### إذا لم تُحفظ التغييرات:
1. تحقق من قاعدة البيانات مباشرة
2. تأكد من أن الـ Model يحتوي على `is_active` في `fillable`
3. تأكد من أن الـ cast محدد بشكل صحيح 