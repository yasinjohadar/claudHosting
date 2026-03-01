# اختبار نظام التبديل للمستخدمين

## الخطوات للاختبار:

### 1. فتح Developer Tools
- اضغط F12 في المتصفح
- انتقل إلى تبويب Console

### 2. اختبار التبديل
1. انتقل إلى صفحة قائمة المستخدمين
2. ستظهر رسائل في Console:
   - "Toggle script loaded"
   - "Found toggle switches: X" (حيث X عدد التبديلات)

### 3. اختبار النقر على التبديل
1. انقر على أي تبديل
2. ستظهر رسائل في Console:
   - "Toggle clicked: {userId: X, isActive: true/false}"
   - "Sending request to: /users/X/toggle-status"
   - "Response status: 200"
   - "Response data: {...}"

### 4. التحقق من السجلات
```bash
tail -f storage/logs/laravel.log
```

ستظهر رسائل مثل:
```
[2024-XX-XX XX:XX:XX] local.INFO: Toggle status request received {"user_id":"1","request_data":{"is_active":false},"auth_user":1}
[2024-XX-XX XX:XX:XX] local.INFO: User found {"user_id":1,"user_name":"Admin","current_is_active":true}
[2024-XX-XX XX:XX:XX] local.INFO: User status updated {"user_id":1,"user_name":"Admin","old_status":true,"new_status":false,"toggled_by":1}
```

### 5. اختبار قاعدة البيانات
```bash
php artisan tinker
```
```php
$user = App\Models\User::find(1);
echo "User: " . $user->name . "\n";
echo "is_active: " . ($user->is_active ? 'true' : 'false') . "\n";
```

## إذا لم يعمل:

### 1. تحقق من المسارات
```bash
php artisan route:list | grep toggle
```

### 2. تحقق من Middleware
```bash
php artisan route:list | grep users
```

### 3. تحقق من قاعدة البيانات
```sql
SELECT id, name, is_active FROM users;
```

### 4. تحقق من CSRF Token
في Console، اكتب:
```javascript
console.log(document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
```

### 5. اختبار API مباشرة
```bash
curl -X POST http://localhost:8000/users/1/toggle-status \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: YOUR_CSRF_TOKEN" \
  -d '{"is_active": false}'
```

## النتائج المتوقعة:

✅ **إذا عمل النظام بشكل صحيح:**
- التبديل يتغير عند النقر
- رسالة نجاح تظهر
- التبديل يبقى في حالته الجديدة بعد التحديث
- سجلات تظهر في laravel.log

❌ **إذا لم يعمل:**
- تحقق من Console للأخطاء
- تحقق من Network tab في Developer Tools
- تحقق من سجلات Laravel 