# خطوات الاستخدام - نظام التقارير

## الخطوة 1: التأكد من التثبيت
```bash
# تحقق من تثبيت laravel/excel
composer show | grep excel

# النتيجة المتوقعة:
# maatwebsite/excel    3.1.67  Laravel Excel Wrapper
```

## الخطوة 2: أوامر مفيدة

### فحص إعدادات WHMCS
```bash
php artisan whmcs:check
```

**المخرجات المتوقعة:**
```
Checking WHMCS configuration...
API URL: https://clients.claudsoft.com/includes/api.php
API URL reachable (HTTP 200)
Authentication configured: identifier/secret
Basic WHMCS configuration checks passed.
Next steps: run sync jobs or configure webhooks for real-time updates.
```

### عرض جميع Routes
```bash
# عرض تقارير Routes فقط
php artisan route:list --path=reports

# النتيجة:
GET|HEAD admin/reports ........................ admin.reports.index
GET|HEAD admin/reports/customers ............. admin.reports.customers
GET|HEAD admin/reports/invoices .............. admin.reports.invoices
GET|HEAD admin/reports/products .............. admin.reports.products
GET|HEAD admin/reports/tickets ............... admin.reports.tickets
GET|HEAD admin/reports/export/customers ...... admin.reports.export.customers
GET|HEAD admin/reports/export/invoices ....... admin.reports.export.invoices
GET|HEAD admin/reports/export/products ....... admin.reports.export.products
GET|HEAD admin/reports/export/tickets ........ admin.reports.export.tickets
```

## الخطوة 3: الوصول إلى التقارير

### عبر الويب
1. قم بتسجيل الدخول إلى `/login`
2. انتقل إلى `/admin/reports`
3. اختر التقرير المطلوب

### URLs المباشرة
```
لوحة التقارير الرئيسية:
http://localhost:8000/admin/reports

تقرير العملاء:
http://localhost:8000/admin/reports/customers

تقرير الفواتير:
http://localhost:8000/admin/reports/invoices

تقرير المنتجات:
http://localhost:8000/admin/reports/products

تقرير التذاكر:
http://localhost:8000/admin/reports/tickets
```

## الخطوة 4: استخدام الفلاتر والبحث

### مثال 1: تصدير العملاء من دول معينة
```
http://localhost:8000/admin/reports/export/customers?country=US&status=Active
```

### مثال 2: تصدير الفواتير بنطاق تاريخ
```
http://localhost:8000/admin/reports/export/invoices?date_from=2025-01-01&date_to=2025-01-31&status=Paid
```

### مثال 3: تصدير التذاكر العاجلة
```
http://localhost:8000/admin/reports/export/tickets?priority=Urgent&status=Open
```

### مثال 4: البحث عن عملاء محددين
```
http://localhost:8000/admin/reports/customers?search=ahmed@example.com
```

## الخطوة 5: الملفات المُنتجة

جميع ملفات Excel تُنتج باسم:
```
[نوع_التقرير]_[السنة-الشهر-اليوم_الساعة-الدقيقة-الثانية].xlsx

أمثلة:
- customers_2025-01-29_15-30-45.xlsx
- invoices_2025-01-29_15-30-45.xlsx
- products_2025-01-29_15-30-45.xlsx
- tickets_2025-01-29_15-30-45.xlsx
```

## الخطوة 6: محتوى الملفات

### ملف العملاء يحتوي على:
- معرف WHMCS
- الاسم الكامل
- البريد الإلكتروني
- اسم الشركة
- رقم الهاتف
- المدينة والولاية والدولة
- الحالة
- تواريخ الإنشاء والتحديث

### ملف الفواتير يحتوي على:
- معرف WHMCS
- رقم الفاتورة
- اسم العميل
- التاريخ وتاريخ الاستحقاق والدفع
- الإجمالي الفرعي والضريبة والإجمالي
- الحالة وطريقة الدفع

### ملف المنتجات يحتوي على:
- معرف WHMCS
- الاسم والنوع والوصف
- نوع الدفع
- تكرار الدفع والكمية
- الحالة
- تاريخ الإنشاء

### ملف التذاكر يحتوي على:
- معرف WHMCS ورقم التذكرة
- الموضوع والعميل
- الأولوية والقسم والحالة
- الموظف المسؤول
- تواريخ الإنشاء وآخر الردود

## الخطوة 7: استكشاف الأخطاء

### الخطأ: الملف غير موجود
```
Solution: تأكد من وجود مجلد storage/framework/cache
php artisan storage:link
```

### الخطأ: Permission Denied
```
Solution: تحقق من صلاحيات المستخدم
php artisan tinker
User::first()->hasRole('admin')  // تحقق من الدور
```

### الخطأ: قاعدة البيانات فارغة
```
Solution: قم بتشغيل المزامنة الأولى
1. انتقل إلى /admin/whmcs/test
2. اضغط على "مزامنة العملاء"
3. كرر لـ المنتجات والفواتير والتذاكر
```

## الخطوة 8: الحصول على الإحصائيات برمجياً

في أي Controller أو Service:

```php
use App\Services\ReportService;

$reportService = new ReportService();

// الحصول على جميع الإحصائيات
$stats = $reportService->getDatabaseStats();

// الحصول على أفضل العملاء
$topCustomers = $reportService->getTopCustomers(10);

// الحصول على آخر النشاطات
$activities = $reportService->getRecentActivities(20);

// إحصائيات محددة
$customerStats = $reportService->getCustomersStats();
$invoiceStats = $reportService->getInvoicesStats();
$productStats = $reportService->getProductsStats();
$ticketStats = $reportService->getTicketsStats();
```

## ملاحظات هامة

- ✅ جميع التقارير **محمية بالمصادقة**
- ✅ البيانات **مُحدثة فوراً** من قاعدة البيانات
- ✅ الفلاتر **اختيارية** ويمكن الجمع بينها
- ✅ ملفات Excel **منسقة بشكل احترافي**
- ✅ يتم **حذف ملفات مؤقتة** تلقائياً بعد التحميل
- ⚠️ يتطلب **اتصال إنترنت** لتحميل الملفات
- ⚠️ الملفات الكبيرة قد تستغرق **وقتاً أطول** في المعالجة

## قائمة التحقق قبل الاستخدام

- [ ] تثبيت `maatwebsite/excel`
- [ ] التحقق من صحة إعدادات WHMCS بـ `whmcs:check`
- [ ] التأكد من مزامنة البيانات الأولية
- [ ] التحقق من صلاحيات المستخدم
- [ ] اختبار تصدير ملف بسيط
- [ ] التحقق من التنسيق في ملف Excel المُصدَّر

## الدعم

للمساعدة أو الإبلاغ عن مشاكل:
1. تحقق من السجلات: `storage/logs/laravel.log`
2. جرب دالة Tinker: `php artisan tinker`
3. راجع الوثائق الكاملة: `REPORTS_SYSTEM_README.md`
