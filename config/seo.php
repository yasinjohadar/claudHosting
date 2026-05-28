<?php

return [

    'organization' => [
        'name' => 'استضافة كلاودسوفت',
        'legal_name' => 'CloudSoft Hosting',
        'url' => env('APP_URL', 'http://localhost'),
        'logo' => 'frontend/assets/images/logo.png',
        'email' => env('SEO_CONTACT_EMAIL', 'info@cloudsoft.host'),
        'phone' => env('SEO_CONTACT_PHONE', ''),
    ],

    'default_og_image' => 'frontend/assets/images/logo.png',

    /*
    |--------------------------------------------------------------------------
    | صفحات ثابتة — المفتاح = اسم الـ route
    |--------------------------------------------------------------------------
    */
    'pages' => [
        'home' => [
            'label' => 'الصفحة الرئيسية',
            'meta_title' => 'استضافة مواقع سحابية | باقات استضافة ودعم فني | كلاودسوفت',
            'meta_description' => 'استضافة كلاودسوفت — استضافة مواقع سحابية سريعة وآمنة مع SSL مجاني، نسخ احتياطي يومي، باقات مرنة للمواقع والمتاجر، ودعم فني عربي مستمر. ابدأ خلال دقائق.',
            'meta_keywords' => 'استضافة مواقع, استضافة سحابية, استضافة ويب, باقات استضافة, VPS, نطاقات, SSL',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'home',
            'sitemap' => ['priority' => '1.0', 'changefreq' => 'weekly'],
        ],

        'frontend.packages' => [
            'label' => 'الباقات',
            'meta_title' => 'باقات الاستضافة | خطط مرنة | استضافة كلاودسوفت',
            'meta_description' => 'باقات استضافة كلاودسوفت — خطط مرنة للمواقع الشخصية والمتاجر والشركات. استضافة سريعة وآمنة مع دعم فني ولوحة تحكم عربية. اختر باقتك وابدأ اليوم.',
            'meta_keywords' => 'باقات استضافة, استضافة مواقع, خطط استضافة, استضافة سحابية',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'packages_list',
            'sitemap' => ['priority' => '0.9', 'changefreq' => 'weekly'],
        ],

        'frontend.package-detail' => [
            'label' => 'تفاصيل الباقة (ديناميكي)',
            'meta_title' => '{name} | باقة استضافة | كلاودسوفت',
            'meta_description' => '{description}',
            'robots' => 'index,follow',
            'og_type' => 'product',
            'schema' => 'package',
        ],

        'frontend.package.order.form' => [
            'label' => 'طلب الباقة',
            'meta_title' => 'طلب الباقة | كلاودسوفت',
            'meta_description' => 'نموذج طلب باقة استضافة — للمستخدمين المسجّلين فقط.',
            'robots' => 'noindex,nofollow',
            'og_type' => 'website',
            'schema' => null,
        ],

        'frontend.about' => [
            'label' => 'حول الشركة',
            'meta_title' => 'حول استضافة كلاودسوفت | من نحن',
            'meta_description' => 'من نحن — منصة استضافة كلاودسوفت: بيئة سحابية آمنة وسريعة للمشاريع العربية، بنية تحتية حديثة، نسخ احتياطي مستمر، ودعم فني يهتم بكل تفاصيل مشروعك.',
            'meta_keywords' => 'استضافة كلاودسوفت, عن الشركة, استضافة عربية',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'about',
            'sitemap' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        ],

        'frontend.contact' => [
            'label' => 'تواصل معنا',
            'meta_title' => 'تواصل معنا | استضافة كلاودسوفت',
            'meta_description' => 'تواصل مع فريق كلاودسوفت — استفسارات الاستضافة، الباقات، النطاقات، والدعم الفني. نرد عليك بأسرع وقت ممكن.',
            'meta_keywords' => 'تواصل, دعم فني, استضافة',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'contact',
            'sitemap' => ['priority' => '0.7', 'changefreq' => 'monthly'],
        ],

        'frontend.domain-search' => [
            'label' => 'بحث النطاقات',
            'meta_title' => 'بحث وشراء النطاقات | استضافة كلاودسوفت',
            'meta_description' => 'ابحث عن نطاقك المثالي (.com, .net, .org) واحجزه بسهولة مع استضافة كلاودسوفت. نطاقات موثوقة وإعداد DNS آمن.',
            'meta_keywords' => 'نطاقات, دومين, بحث نطاق, شراء نطاق',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'domain_search',
            'sitemap' => ['priority' => '0.85', 'changefreq' => 'weekly'],
        ],

        'frontend.consultation' => [
            'label' => 'حجز استشارة',
            'meta_title' => 'حجز موعد واستشارة تقنية | كلاودسوفت',
            'meta_description' => 'احجز جلستك الاستشارية مع فريق كلاودسوفت — نقاش مباشر حول مشروعك، الاستضافة، المسار المهني أو أي سؤال تقني. نرتب معك الموعد المناسب.',
            'meta_keywords' => 'استشارة تقنية, حجز موعد, دعم',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'consultation',
            'sitemap' => ['priority' => '0.65', 'changefreq' => 'monthly'],
        ],

        'frontend.blog' => [
            'label' => 'المدونة',
            'meta_title' => 'المدونة | مقالات الاستضافة والتقنية | كلاودسوفت',
            'meta_description' => 'مدونة كلاودسوفت — مقالات ونصائح حول الاستضافة، الأمان، السيرفرات، وتطوير الويب. تابع آخر التحديثات.',
            'meta_keywords' => 'مدونة, استضافة, تقنية',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'blog_list',
            'sitemap' => ['priority' => '0.75', 'changefreq' => 'daily'],
        ],

        'frontend.service-detail-web' => [
            'label' => 'تطوير تطبيقات الويب',
            'meta_title' => 'تطوير تطبيقات الويب | كلاودسوفت',
            'meta_description' => 'تطوير تطبيقات الويب — تصميم وتطوير مواقع وتطبيقات حديثة بـ React و Laravel و Node.js. واجهات احترافية، أداء عالٍ، SEO وأمان.',
            'meta_keywords' => 'تطوير ويب, React, Laravel, مواقع',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'service',
            'service_name' => 'تطوير تطبيقات الويب',
            'breadcrumbs' => [
                ['name' => 'الرئيسية', 'url' => '/'],
                ['name' => 'تطوير تطبيقات الويب', 'url' => null],
            ],
            'sitemap' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        ],

        'frontend.service-detail-mobile' => [
            'label' => 'تطبيقات الجوال',
            'meta_title' => 'تطبيقات الجوال | Flutter | كلاودسوفت',
            'meta_description' => 'تطوير تطبيقات الجوال — أندرويد و iOS بـ Dart و Flutter. كود واحد لمنصتين، أداء عالٍ، ونشر على المتاجر.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'service',
            'service_name' => 'تطبيقات الجوال',
            'breadcrumbs' => [
                ['name' => 'الرئيسية', 'url' => '/'],
                ['name' => 'تطبيقات الجوال', 'url' => null],
            ],
            'sitemap' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        ],

        'frontend.service-detail-security' => [
            'label' => 'أمن المعلومات',
            'meta_title' => 'أمن المعلومات والسيبراني | كلاودسوفت',
            'meta_description' => 'أمن المعلومات — حماية الأنظمة والبيانات، تقييم الثغرات، SSL، WAF، ومراقبة أمنية لبيئة الاستضافة.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'service',
            'service_name' => 'أمن المعلومات',
            'breadcrumbs' => [
                ['name' => 'الرئيسية', 'url' => '/'],
                ['name' => 'أمن المعلومات', 'url' => null],
            ],
            'sitemap' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        ],

        'frontend.service-detail-servers' => [
            'label' => 'إدارة السيرفرات',
            'meta_title' => 'إدارة السيرفرات والاستضافة | كلاودسوفت',
            'meta_description' => 'إدارة السيرفرات — إعداد خوادم Linux و Nginx وقواعد البيانات و SSL والنسخ الاحتياطي. VPS وسحابة مع مراقبة وصيانة.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'service',
            'service_name' => 'إدارة السيرفرات',
            'breadcrumbs' => [
                ['name' => 'الرئيسية', 'url' => '/'],
                ['name' => 'إدارة السيرفرات', 'url' => null],
            ],
            'sitemap' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        ],

        'frontend.service-detail-devops' => [
            'label' => 'DevOps',
            'meta_title' => 'DevOps وتشغيل المنصات | كلاودسوفت',
            'meta_description' => 'DevOps — CI/CD، Docker و Kubernetes، IaC، سحابة AWS/Azure/GCP، ومراقبة Prometheus و Grafana.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'service',
            'service_name' => 'DevOps وتشغيل المنصات',
            'breadcrumbs' => [
                ['name' => 'الرئيسية', 'url' => '/'],
                ['name' => 'DevOps', 'url' => null],
            ],
            'sitemap' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        ],

        'frontend.projects' => [
            'label' => 'المشاريع',
            'meta_title' => 'المشاريع وأعمالنا | كلاودسوفت',
            'meta_description' => 'معرض مشاريع وأعمال كلاودسوفت في الاستضافة وتطوير الويب والبنية التحتية.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'webpage',
            'sitemap' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        ],

        'frontend.clients' => [
            'label' => 'العملاء',
            'meta_title' => 'عملاؤنا | كلاودسوفت',
            'meta_description' => 'شركات ومواقع تثق باستضافة كلاودسوفت — عملاؤنا وشركاؤنا في النجاح الرقمي.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'webpage',
            'sitemap' => ['priority' => '0.55', 'changefreq' => 'monthly'],
        ],

        'frontend.testimonials' => [
            'label' => 'آراء العملاء',
            'meta_title' => 'آراء عملائنا | كلاودسوفت',
            'meta_description' => 'آراء وتقييمات عملاء استضافة كلاودسوفت حول جودة الاستضافة والدعم الفني.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'webpage',
            'sitemap' => ['priority' => '0.55', 'changefreq' => 'monthly'],
        ],

        'frontend.videos' => [
            'label' => 'الفيديوهات',
            'meta_title' => 'فيديوهات تعليمية | استضافة كلاودسوفت',
            'meta_description' => 'فيديوهات تعليمية حول الاستضافة، الأمان، وتطوير الويب من فريق كلاودسوفت.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'webpage',
            'sitemap' => ['priority' => '0.5', 'changefreq' => 'monthly'],
        ],

        'frontend.project-detail' => [
            'label' => 'تفاصيل مشروع',
            'meta_title' => 'تفاصيل المشروع | كلاودسوفت',
            'meta_description' => 'تفاصيل مشروع من أعمال كلاودسوفت.',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'schema' => 'webpage',
        ],
    ],

    'sitemap_exclude_routes' => [
        'frontend.blog.show',
        'frontend.package.order.form',
        'frontend.package.order.store',
        'frontend.domain-search.post',
        'login',
        'register',
        'password.request',
    ],

];
