# وثائق نظام WHMCS

## مقدمة

نظام WHMCS هو نظام متكامل لإدارة العملاء والمنتجات والفواتير والتذاكر الدعم الفني، مصمم للتكامل مع نظام WHMCS الأصلي لتوفير واجهة إدارة مركزية وسهلة الاستخدام.

## المتطلبات

- PHP 8.0 أو أحدث
- Laravel 9 أو أحدث
- MySQL 5.7 أو أحدث
- نظام WHMCS مثبت ومتاح للوصول عبر API

## التثبيت

1. استنساخ المستودع:
   ```bash
   git clone [repository-url]
   ```

2. تثبيت الاعتماديات:
   ```bash
   composer install
   npm install
   ```

3. إعداد ملف البيئة:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. تكوين قاعدة البيانات في ملف `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=whmcs_system
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. تشغيل الترحيلات:
   ```bash
   php artisan migrate
   ```

6. تثبيت الحزم الإضافية:
   ```bash
   composer require spatie/laravel-permission
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

7. تكوين إعدادات WHMCS في ملف `config/whmcs.php` أو في ملف `.env`:
   ```
   WHMCS_URL=https://your-whmcs-url.com/
   WHMCS_IDENTIFIER=your-identifier
   WHMCS_SECRET=your-secret
   WHMCS_ACCESS_TOKEN=your-access-token
   ```

8. إنشاء مستخدم إداري:
   ```bash
   php artisan tinker
   ```
   ```
   use App\Models\User;
   use Spatie\Permission\Models\Role;
   use Spatie\Permission\Models\Permission;
   
   $user = User::create([
       'name' => 'Admin User',
       'email' => 'admin@example.com',
       'password' => bcrypt('password')
   ]);
   
   $role = Role::create(['name' => 'admin']);
   $user->assignRole('admin');
   ```

## الميزات

### إدارة العملاء
- عرض قائمة العملاء
- إضافة/تعديل/حذف العملاء
- مزامنة العملاء مع WHMCS
- عرض تفاصيل العميل والمنتجات المرتبطة به

### إدارة المنتجات
- عرض قائمة المنتجات
- إضافة/تعديل/حذف المنتجات
- مزامنة المنتجات مع WHMCS
- تصنيف المنتجات حسب المجموعات

### إدارة الفواتير
- عرض قائمة الفواتير
- إنشاء/تعديل/حذف الفواتير
- مزامنة الفواتير مع WHMCS
- تسجيل المدفوعات على الفواتير

### إدارة التذاكر
- عرض قائمة التذاكر
- إنشاء/تعديل/حذف التذاكر
- مزامنة التذاكر مع WHMCS
- إضافة الردود والملاحظات على التذاكر

### التقارير والإحصائيات
- تقارير المبيعات
- تقارير العملاء
- تقارير المنتجات
- تقارير الفواتير
- تقارير التذاكر
- تقارير المدفوعات

### لوحة التحكم
- عرض إحصائيات عامة
- عرض أحدث العملاء والفواتير والتذاكر
- عرض حالة الاتصال بـ WHMCS

## هيكل النظام

### النماذج (Models)
- `Customer`: نموذج العملاء
- `Product`: نموذج المنتجات
- `Invoice`: نموذج الفواتير
- `Ticket`: نموذج التذاكر
- `Payment`: نموذج المدفوعات

### وحدات التحكم (Controllers)
- `CustomerController`: التحكم في عمليات العملاء
- `ProductController`: التحكم في عمليات المنتجات
- `InvoiceController`: التحكم في عمليات الفواتير
- `TicketController`: التحكم في عمليات التذاكر
- `ReportController`: التحكم في التقارير
- `DashboardController`: التحكم في لوحة التحكم

### الخدمات (Services)
- `WhmcsApiService`: خدمة الاتصال بـ WHMCS API

### العروض (Views)
- `admin/customers/*`: عروض صفحات العملاء
- `admin/products/*`: عروض صفحات المنتجات
- `admin/invoices/*`: عروض صفحات الفواتير
- `admin/tickets/*`: عروض صفحات التذاكر
- `admin/reports/*`: عروض صفحات التقارير
- `admin/dashboard.blade.php`: عرض لوحة التحكم

## استخدام WHMCS API

### إعدادات الاتصال
يتم تكوين إعدادات الاتصال بـ WHMCS في ملف `config/whmcs.php`:

```php
return [
    'url' => env('WHMCS_URL', 'https://your-whmcs-url.com/'),
    'identifier' => env('WHMCS_IDENTIFIER', ''),
    'secret' => env('WHMCS_SECRET', ''),
    'access_token' => env('WHMCS_ACCESS_TOKEN', ''),
];
```

### استخدام الخدمة
```php
use App\Services\WhmcsApiService;

$whmcsService = new WhmcsApiService();

// جلب العملاء
$response = $whmcsService->makeRequest('GetClients', []);

// جلب المنتجات
$response = $whmcsService->makeRequest('GetProducts', []);

// جلب الفواتير
$response = $whmcsService->makeRequest('GetInvoices', []);

// جلب التذاكر
$response = $whmcsService->makeRequest('GetTickets', []);
```

## الصلاحيات (Permissions)

يستخدم النظام حزمة Spatie Laravel Permission لإدارة الصلاحيات:

### صلاحيات العملاء
- `customers.view`: عرض العملاء
- `customers.create`: إنشاء عملاء
- `customers.edit`: تعديل العملاء
- `customers.delete`: حذف العملاء
- `customers.sync`: مزامنة العملاء مع WHMCS

### صلاحيات المنتجات
- `products.view`: عرض المنتجات
- `products.create`: إنشاء منتجات
- `products.edit`: تعديل المنتجات
- `products.delete`: حذف المنتجات
- `products.sync`: مزامنة المنتجات مع WHMCS

### صلاحيات الفواتير
- `invoices.view`: عرض الفواتير
- `invoices.create`: إنشاء فواتير
- `invoices.edit`: تعديل الفواتير
- `invoices.delete`: حذف الفواتير
- `invoices.sync`: مزامنة الفواتير مع WHMCS

### صلاحيات التذاكر
- `tickets.view`: عرض التذاكر
- `tickets.create`: إنشاء تذاكر
- `tickets.edit`: تعديل التذاكر
- `tickets.delete`: حذف التذاكر
- `tickets.sync`: مزامنة التذاكر مع WHMCS

### صلاحيات التقارير
- `reports.view`: عرض التقارير

## استكشاف الأخطاء وإصلاحها

### مشاكل الاتصال بـ WHMCS
1. تأكد من صحة إعدادات الاتصال في ملف `config/whmcs.php`
2. تأكد من أن معرف المستخدم والسر صحيحين في WHMCS
3. تأكد من أن عنوان URL لـ WHMCS صحيح ويمكن الوصول إليه

### مشاكل قاعدة البيانات
1. تأكد من تشغيل جميع الترحيلات: `php artisan migrate`
2. تأكد من صحة إعدادات قاعدة البيانات في ملف `.env`
3. تأكد من وجود الصلاحيات المناسبة للمستخدم على قاعدة البيانات

### مشاكل الصلاحيات
1. تأكد من تثبيت حزمة Spatie Laravel Permission
2. تأكد من تشغيل ترحيلات الحزمة: `php artisan migrate`
3. تأكد من تعيين الصلاحيات المناسبة للمستخدمين

## التحديثات

### تحديث النظام
1. احصل على أحدث إصدار من المستودع:
   ```bash
   git pull origin main
   ```

2. تحديث الاعتماديات:
   ```bash
   composer update
   npm update
   ```

3. تشغيل الترحيلات الجديدة:
   ```bash
   php artisan migrate
   ```

4. مسح ذاكرة التخزين المؤقت:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

## الدعم

في حالة وجود أي مشاكل أو استفسارات، يرجى التواصل من خلال:
- البريد الإلكتروني: support@example.com
- نظام التذاكر: https://your-whmcs-url.com/