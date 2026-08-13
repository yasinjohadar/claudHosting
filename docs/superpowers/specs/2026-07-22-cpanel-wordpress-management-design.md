# تصميم: إدارة ووردبريس داخل cPanel (المرحلة 1)

**التاريخ:** 2026-07-22  
**النظام:** claudHosting (Laravel)  
**النطاق:** لوحة الإدارة + بوابة العميل  
**المرحلة:** 1 — اكتشاف خفيف + صفحة إدارة + مسح بحث

---

## الهدف

تمكين العميل (والأدمن) من اكتشاف مواقع ووردبريس داخل حسابات cPanel وإدارتها من لوحة الاستضافة، بغض النظر عن طريقة التثبيت: Softaculous، WP Toolkit، أو تثبيت يدوي.

تظهر المواقع في:
1. تبويب **WordPress** في `/client/services` (مع مواقع Coolify، مع تمييز المصدر)
2. زر/صفحة من كل حساب cPanel («إدارة ووردبريس»)

---

## غير مشمول في المرحلة 1

- إدارة إضافات/قوالب/مستخدمين داخل اللوحة (مثل Coolify) — مرحلة 2
- تثبيت ووردبريس جديد من اللوحة — مرحلة 2
- استنساخ / Staging / Push to live — مرحلة 2

---

## المعمارية

```
Client / Admin UI
        │
        ▼
WhmWordpressSiteController (admin + client scoped)
        │
        ▼
WhmWordpressDiscoveryService  ──┬── SoftaculousAdapter
                                ├── WpToolkitAdapter
                                └── ManualScanAdapter (UAPI Fileman / wp-config)
        │
        ▼
whm_wordpress_sites (جدول محلي للتخزين المؤقت للنتائج)
        │
        ▼
WhmWordpressPortalService  ── SSO Softaculous / WP Toolkit / sign_on wp-admin / فتح الموقع
```

الاعتماد على البنية الحالية:
- `WhmApiService::cpanelUapi()` و `createUserSession()`
- `WhmAccount` + ربط `user_id` للعميل
- نمط ملكية العميل كما في `ClientWhmAccountController`

---

## المكوّنات

### 1. نموذج `WhmWordpressSite`

| الحقل | الوصف |
|--------|--------|
| `whm_account_id` | حساب cPanel الأب |
| `source` | `softaculous` \| `wp_toolkit` \| `manual` |
| `external_id` | معرف Softaculous `insid` أو WP Toolkit id (nullable لليدوي) |
| `domain` | النطاق |
| `path` | مسار التثبيت (مثل `/home/user/public_html`) |
| `url` | رابط الموقع العام |
| `wp_version` | إن توفر |
| `title` | عنوان الموقع إن توفر |
| `metadata` | JSON (تفاصيل المصدر الخام) |
| `last_seen_at` | آخر اكتشاف |
| `status` | `active` \| `missing` \| `unknown` |

فهرس فريد مقترح: `(whm_account_id, source, external_id)` مع معالجة اليدوي عبر `(whm_account_id, path)`.

### 2. `WhmWordpressDiscoveryService`

- `discover(WhmAccount $account): Collection` — يشغّل كل المحوّلات المتاحة، يدمج النتائج، يحدّث الجدول، ويعلّم الغائبة كـ `missing`.
- كل محوّل يفشل بشكل مستقل (لا يوقف الباقي) ويعيد رسالة تحذيرية للواجهة.
- `discoverForUser(int $userId)` — كل حسابات المستخدم غير المنتهية.

### 3. المحوّلات

**SoftaculousAdapter**
- المصادقة عبر جلسة cPanel لمرة واحدة (`create_user_session`) ثم طلب Softaculous Enduser API (`act=installations&api=json`، تصفية WordPress sid=26). لا نخزّن كلمة مرور cPanel في التطبيق.
- استخراج: `insid`, url, path, version.
- إن فشل Softaculous (غير مثبت / 404) → تخطي + تحذير، دون إيقاف المحوّلات الأخرى.

**WpToolkitAdapter**
- عبر WHM/cPanel API أو أمر WP Toolkit إن كان متاحاً على السيرفر.
- إن فشل الاستدعاء (غير مثبت) → تخطي صامت + ملاحظة في السجل.

**ManualScanAdapter**
- مسح مجلدات الويب الشائعة (`public_html`, addon domains, subdomains) عبر UAPI (`Fileman` / `DirectoryListing` أو قراءة وجود `wp-config.php`).
- عمق محدود (مثلاً مستويان) وتجاهل `wp-content`/`node_modules` لتجنب البطء.
- زر «بحث» في الواجهة يشغّل هذا المسح صراحة (وقد يعيد تشغيل المحوّلات الأخرى أيضاً).

### 4. `WhmWordpressPortalService`

إجراءات المرحلة 1 لكل موقع حسب المصدر:

| الإجراء | Softaculous | WP Toolkit | يدوي |
|---------|-------------|------------|------|
| فتح الموقع | نعم | نعم | نعم |
| دخول wp-admin (`sign_on` أو رابط `/wp-admin`) | `sign_on` إن توفر وإلا `/wp-admin` | رابط toolkit أو `/wp-admin` | `/wp-admin` فقط |
| فتح Softaculous / Toolkit في cPanel | SSO `createUserSession` + app path | SSO إلى WP Toolkit | SSO إلى cPanel File Manager عند المسار |
| تحديث الاكتشاف | نعم | نعم | نعم (مسح) |

---

## الواجهات والمسارات

### العميل

| المسار | الوظيفة |
|--------|---------|
| `GET client/hosting/{account}/wordpress` | قائمة مواقع الحساب + زر بحث |
| `POST client/hosting/{account}/wordpress/scan` | تشغيل الاكتشاف/المسح |
| `GET client/hosting/{account}/wordpress/{site}` | صفحة إدارة الموقع |
| `GET .../open` | فتح الموقع |
| `GET .../wp-admin` | دخول لوحة ووردبريس |
| `GET .../manager` | فتح Softaculous/Toolkit/File Manager عبر SSO |

تبويب WordPress في `services.blade.php`:
- دمج `CoolifyWordpressSite` + `WhmWordpressSite` للعميل.
- عمود/شارة مصدر: Coolify / Softaculous / WP Toolkit / يدوي.
- زر «إدارة» يوجّه للمسار المناسب.
- العدد في التبويب = المجموع.

من تبويب cPanel: زر «ووردبريس» بجانب «فتح cPanel» و«إدارة».

### الأدمن

نفس القدرات تحت `admin/whm/accounts/{account}/wordpress/*` مع عدم تقييد الملكية، ورابط من صفحة عرض حساب WHM.

---

## تدفق البيانات

1. العميل يفتح تبويب WordPress أو صفحة ووردبريس للحساب.
2. إن لم يوجد اكتشاف حديث (`last_seen_at` أقدم من عتبة، مثلاً 6 ساعات) أو ضغط «بحث» → `discover()`.
3. النتائج تُعرض من الجدول المحلي (سرعة)، مع إمكانية تحديث فوري.
4. أي إجراء SSO يمر عبر `WhmAccountService` / `createUserSession` مع التحقق من `userOwnsAccount` للعميل.

---

## الصلاحيات والأخطاء

- العميل: فقط `WhmAccount` حيث `user_id = auth()->id()` وحالة غير `terminated`.
- الأدمن: أي حساب.
- Softaculous/Toolkit غير متاح: تحذير أصفر + استمرار المسح اليدوي.
- فشل WHM API: رسالة عربية واضحة + رابط إعدادات WHM للأدمن فقط.
- المسح اليدوي الطويل: حد زمني ورسالة «أُكمل جزئياً» إن لزم.

---

## الاختبار (يدوي للمرحلة 1)

1. حساب فيه Softaculous WP يظهر في التبويب والصفحة.
2. حساب بتثبيت يدوي يُكتشف بعد «بحث».
3. عميل لا يرى مواقع حسابات غيره.
4. أزرار فتح الموقع / wp-admin / المدير تعمل أو تعرض سبباً واضحاً.
5. تبويب WordPress يعرض Coolify + cPanel معاً بالعدد الصحيح.

---

## مراحل لاحقة (مرجع فقط)

- مرحلة 2: ترقية/نسخ احتياطي/إزالة عبر Softaculous API؛ عمليات WP Toolkit الغنية.
- مرحلة 3: إدارة إضافات/قوالب داخل اللوحة (إن توفّر API أو WP-CLI عبر السيرفر).
