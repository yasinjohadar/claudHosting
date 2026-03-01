# استضافة كلاودسوفت | CloudSoft Hosting

موقع استضافة مواقع سحابية — باقات مرنة، دعم فني مستمر، وبنية تحتية موثوقة. يعرض الباقات، المدونة، آراء العملاء ومشاريعهم مع دعم كامل للعربية (RTL) والوضع الليلي.

## التشغيل المحلي

المشروع ثابت (HTML + CSS + JS) ولا يحتاج خادم بناء. يمكنك:

1. **فتح الملفات مباشرة**: افتح `index.html` في المتصفح.
2. **استخدام خادم محلي** (مُفضّل لتجنب مشاكل CORS مع بعض المتصفحات):
   - مع Python: `python -m http.server 8000` ثم افتح `http://localhost:8000`
   - مع Node: `npx serve .` أو `npx live-server`

## هيكل المشروع

```
├── index.html          # الصفحة الرئيسية
├── about.html           # حول الشركة
├── courses.html         # قائمة الباقات
├── course-detail.html   # تفاصيل باقة (قالب)
├── blog.html            # المدونة
├── blog-detail.html     # مقال (قالب)
├── contact.html         # تواصل معنا
├── assets/
│   ├── css/style.css    # التنسيقات
│   ├── js/main.js       # السكربتات
│   └── images/          # الصور والأيقونات (SVG مؤقتة)
└── README.md
```

## إعدادات يجب تنفيذها قبل النشر

### 1. نموذج التواصل (Formspree)

- سجّل حساباً على [Formspree](https://formspree.io) وأنشئ نموذجاً جديداً.
- في `contact.html` استبدل `YOUR_FORM_ID` في قيمة `action` الخاصة بالـ `<form>` بمعرّف النموذج الذي يعطيك إياه Formspree، مثلاً:
  `action="https://formspree.io/f/abcdexyz"`

### 2. الصور

- المجلد `assets/images/` يحتوي حالياً على صور SVG مؤقتة.
- لاستخدام صور حقيقية: ضع ملفاتك (مثلاً `logo.png`, `trainer.png`, …) ثم حدّث مسارات الصور في ملفات HTML من `.svg` إلى الامتداد المناسب (راجع `assets/images/README.md`).

### 3. روابط السوشيال ميديا

- ابحث في المشروع عن `https://facebook.com`, `https://youtube.com`, … واستبدلها بروابط حساباتك الفعلية (صفحة فيسبوك، قناة يوتيوب، إلخ).

### 4. Open Graph و Twitter

- جميع الصفحات تستخدم `og:url`, `og:image`, `twitter:image` و `canonical` مع النطاق `https://cloudsofthosting.com`. عند النشر على نطاق آخر حدّث القيم في كل صفحة.

## النشر

يمكنك رفع المجلد كما هو إلى أي استضافة ثابتة، مثل:

- **GitHub Pages**: ارفع المشروع إلى مستودع ثم فعّل Pages من الإعدادات.
- **Netlify** / **Vercel**: اسحب المجلد أو اربط المستودع واختر مجلد الجذر (root).

## المتصفحات المدعومة

المتصفحات الحديثة التي تدعم CSS Variables و `backdrop-filter` و ES6+.

## الترخيص

جميع الحقوق محفوظة © CloudSoft Hosting.
