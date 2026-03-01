# نظام التقارير المتكامل - WHMCS System Reports

## نظرة عامة
نظام تقارير متكامل مع إمكانية تصدير البيانات إلى Excel، يقدم تحليلات شاملة لجميع السجلات في نظام WHMCS.

## الميزات الرئيسية

### 1. لوحة التحكم الرئيسية للتقارير (`/admin/reports`)
- **إحصائيات عامة:**
  - إجمالي العملاء (نشطين وغير نشطين)
  - إجمالي الإيرادات والمبالغ المدفوعة
  - إجمالي المنتجات والخدمات
  - إجمالي التذاكر والحالات

- **أفضل العملاء:** عرض العملاء الأعلى إنفاقاً
- **آخر النشاطات:** سجل بأحدث التحديثات على جميع البيانات
- **توزيع البيانات:** رسوم بيانية لحالات الفواتير والتذاكر

### 2. تقارير العملاء (`/admin/reports/customers`)
**الفلاتر:**
- البحث بالاسم أو البريد الإلكتروني
- تصفية حسب الحالة (نشط/غير نشط)
- تصفية حسب الدول

**التصدير:** تحميل البيانات كملف Excel بصيغة منسقة

### 3. تقارير الفواتير (`/admin/reports/invoices`)
**الفلاتر:**
- تصفية حسب نطاق التاريخ
- تصفية حسب الحالة (مدفوعة، غير مدفوعة، متأخرة)
- تصفية حسب طريقة الدفع

**المعلومات:**
- رقم الفاتورة والعميل
- المبالغ والضرائب والإجمالي
- حالة الدفع

### 4. تقارير المنتجات (`/admin/reports/products`)
**الفلاتر:**
- البحث بالاسم
- تصفية حسب النوع
- تصفية حسب الحالة

**المعلومات:**
- تفاصيل المنتج والوصف
- نوع الدفع والتكرار
- الكمية والحالة

### 5. تقارير التذاكر (`/admin/reports/tickets`)
**الفلاتر:**
- تصفية حسب نطاق التاريخ
- تصفية حسب الحالة (مفتوح، قيد الإجراء، مغلق، معلق)
- تصفية حسب الأولوية والقسم

**المعلومات:**
- الموضوع والعميل
- الأولوية والحالة والقسم
- تواريخ الإنشاء وآخر الردود

## تصدير البيانات إلى Excel

### الملفات والفئات
```
app/Exports/
├── CustomersExport.php      # تصدير العملاء
├── InvoicesExport.php       # تصدير الفواتير
├── ProductsExport.php       # تصدير المنتجات
└── TicketsExport.php        # تصدير التذاكر
```

### خصائص ملفات Excel
- **رؤوس مُنسقة:** بخلفية زرقاء ونصوص بيضاء غامقة
- **دعم الفلاتر:** يتم تطبيق جميع فلاتر البحث عند التصدير
- **تواريخ منسقة:** جميع التواريخ بصيغة YYYY-MM-DD HH:MM
- **الأرقام المالية:** معلومات مالية منسقة بـ $

### أمثلة الاستخدام

**تصدير العملاء:**
```
GET /admin/reports/export/customers?status=Active&country=US
```

**تصدير الفواتير بنطاق تاريخ:**
```
GET /admin/reports/export/invoices?date_from=2025-01-01&date_to=2025-01-31&status=Paid
```

**تصدير التذاكر حسب القسم:**
```
GET /admin/reports/export/tickets?department=Support&status=Open
```

## Routes

```php
// عرض التقارير
GET    /admin/reports                 // لوحة التقارير الرئيسية
GET    /admin/reports/customers       // تقرير العملاء
GET    /admin/reports/invoices        // تقرير الفواتير
GET    /admin/reports/products        // تقرير المنتجات
GET    /admin/reports/tickets         // تقرير التذاكر

// تصدير البيانات
GET    /admin/reports/export/customers   // تصدير العملاء
GET    /admin/reports/export/invoices    // تصدير الفواتير
GET    /admin/reports/export/products    // تصدير المنتجات
GET    /admin/reports/export/tickets     // تصدير التذاكر
```

## الخدمات

### ReportService (`app/Services/ReportService.php`)

#### الدوال الرئيسية:

**`getDatabaseStats()`**
- يجمع إحصائيات شاملة لكل العناصر
- يرجع مصفوفة تحتوي على:
  - إحصائيات العملاء
  - إحصائيات الفواتير
  - إحصائيات المنتجات
  - إحصائيات التذاكر

**`getRecentActivities($limit = 20)`**
- يجمع آخر التحديثات على البيانات
- يرتبها حسب الوقت

**`getTopCustomers($limit = 10)`**
- يستخرج العملاء الأعلى إنفاقاً
- يشمل إجمالي المبيعات لكل عميل

**`getCustomersStats()`**
- إحصائيات مفصلة عن العملاء
- توزيع حسب الدول

**`getInvoicesStats()`**
- إحصائيات الفواتير والإيرادات
- توزيع حسب طريقة الدفع

**`getProductsStats()`**
- إحصائيات المنتجات والخدمات
- توزيع حسب النوع

**`getTicketsStats()`**
- إحصائيات التذاكر
- توزيع حسب الأولوية والقسم

## التحكم (Controller)

### ReportController (`app/Http/Controllers/ReportController.php`)

جميع الدوال محمية بـ `auth` middleware.

#### الدوال الرئيسية:

```php
public function index()                    // عرض لوحة التقارير الرئيسية
public function customers(Request $request) // عرض تقرير العملاء مع الفلاتر
public function invoices(Request $request)  // عرض تقرير الفواتير مع الفلاتر
public function products(Request $request)  // عرض تقرير المنتجات مع الفلاتر
public function tickets(Request $request)   // عرض تقرير التذاكر مع الفلاتر

public function exportCustomers(Request $request)  // تصدير العملاء
public function exportInvoices(Request $request)   // تصدير الفواتير
public function exportProducts(Request $request)   // تصدير المنتجات
public function exportTickets(Request $request)    // تصدير التذاكر
```

## المتطلبات

```json
{
  "require": {
    "maatwebsite/excel": "^3.1"
  }
}
```

## التثبيت

```bash
# تثبيت المكتبات
composer require maatwebsite/excel

# مسح الـ cache
php artisan config:clear
php artisan cache:clear

# بدء الخادم
php artisan serve
```

## الاستخدام

### من خلال الويب

1. انتقل إلى `/admin/reports`
2. اختر التقرير المطلوب
3. طبق الفلاتر حسب احتياجك
4. اضغط على زر "تصدير إلى Excel"

### من خلال API

```bash
# تصدير العملاء
curl "http://localhost:8000/admin/reports/export/customers"

# تصدير الفواتير مع فلاتر
curl "http://localhost:8000/admin/reports/export/invoices?date_from=2025-01-01&status=Paid"

# تصدير التذاكر
curl "http://localhost:8000/admin/reports/export/tickets?status=Open&priority=High"
```

## ملاحظات مهمة

- جميع التقارير **محمية بالمصادقة** - يجب تسجيل الدخول
- الفلاتر **اختيارية** - يمكن تصدير كل البيانات بدون فلاتر
- البيانات **مُحدثة تلقائياً** - تظهر أحدث البيانات من قاعدة البيانات
- الملفات المُصدَّرة **مُؤقتة** - يتم حذفها بعد التحميل

## التطوير المستقبلي

- [ ] إضافة رسوم بيانية متقدمة
- [ ] جدولة التقارير اليومية/الأسبوعية/الشهرية
- [ ] إرسال التقارير عبر البريد الإلكتروني
- [ ] دعم صيغ تصدير إضافية (PDF, CSV)
- [ ] الإشعارات المخصصة حسب الأداء

## الدعم والمساعدة

للمزيد من المعلومات، راجع:
- [Maatwebsite Excel Documentation](https://docs.laravel-excel.com/)
- [Laravel Query Builder](https://laravel.com/docs/queries)
- [Laravel Blade Templates](https://laravel.com/docs/blade)
