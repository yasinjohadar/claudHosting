@php
    $mobileCategories = [
        [
            'title' => 'Flutter و Dart',
            'icon' => 'fas fa-mobile-alt',
            'desc' => 'إطار Cross-Platform لبناء تطبيقات أندرويد و iOS من كود واحد.',
            'tools' => [
                ['name' => 'Flutter', 'icon' => 'devicon-flutter-plain', 'type' => 'devicon', 'task' => 'واجهات سريعة مع Hot Reload وتجربة قريبة من Native.'],
                ['name' => 'Dart', 'icon' => 'devicon-dart-plain', 'type' => 'devicon', 'task' => 'لغة برمجة حديثة مُحسّنة لواجهات الجوال.'],
                ['name' => 'Material Design', 'icon' => 'https://cdn.simpleicons.org/materialdesign/757575', 'type' => 'img', 'task' => 'مكونات واجهة أندرويد الرسمية بتصميم متسق.'],
                ['name' => 'Cupertino', 'icon' => 'devicon-apple-original', 'type' => 'devicon', 'task' => 'عناصر iOS الأصلية لتجربة Apple متكاملة.'],
                ['name' => 'Widget Tree', 'icon' => 'fas fa-sitemap', 'type' => 'fa', 'task' => 'بناء واجهات declarative قابلة لإعادة الاستخدام.'],
                ['name' => 'Responsive Layout', 'icon' => 'fas fa-tablet-alt', 'type' => 'fa', 'task' => 'تكييف الشاشات لأحجام هواتف وأجهزة لوحية.'],
            ],
        ],
        [
            'title' => 'إدارة الحالة والبنية',
            'icon' => 'fas fa-layer-group',
            'desc' => 'تنظيم منطق التطبيق وحالة البيانات بطريقة قابلة للصيانة.',
            'tools' => [
                ['name' => 'GetX', 'icon' => 'https://cdn.simpleicons.org/getx/8A2BE2', 'type' => 'img', 'task' => 'State management، routing، وdependency injection خفيف.'],
                ['name' => 'Provider', 'icon' => 'fas fa-project-diagram', 'type' => 'fa', 'task' => 'إدارة حالة بسيطة وموصى بها من Flutter.'],
                ['name' => 'BLoC / Cubit', 'icon' => 'fas fa-stream', 'type' => 'fa', 'task' => 'فصل منطق الأعمال عن الواجهة في مشاريع كبيرة.'],
                ['name' => 'Riverpod', 'icon' => 'https://cdn.simpleicons.org/flutter/02569B', 'type' => 'img', 'task' => 'إدارة حالة آمنة مع compile-time checks.'],
                ['name' => 'Clean Architecture', 'icon' => 'fas fa-cubes', 'type' => 'fa', 'task' => 'طبقات domain/data/presentation منظمة.'],
                ['name' => 'Dependency Injection', 'icon' => 'fas fa-plug', 'type' => 'fa', 'task' => 'حقن التبعيات لتسهيل الاختبار والتوسع.'],
            ],
        ],
        [
            'title' => 'Backend وخدمات سحابية',
            'icon' => 'fas fa-cloud',
            'desc' => 'ربط التطبيق بالخوادم، المصادقة، والإشعارات الفورية.',
            'tools' => [
                ['name' => 'Firebase', 'icon' => 'devicon-firebase-plain', 'type' => 'devicon', 'task' => 'Auth، Firestore، Storage، وCloud Messaging.'],
                ['name' => 'REST API', 'icon' => 'fas fa-plug', 'type' => 'fa', 'task' => 'تكامل مع Laravel/Node backends عبر HTTP.'],
                ['name' => 'GraphQL', 'icon' => 'https://cdn.simpleicons.org/graphql/E10098', 'type' => 'img', 'task' => 'استعلامات مرنة للبيانات من التطبيق.'],
                ['name' => 'Supabase', 'icon' => 'https://cdn.simpleicons.org/supabase/3FCF8E', 'type' => 'img', 'task' => 'Backend-as-a-Service مع Postgres وRealtime.'],
                ['name' => 'Push Notifications', 'icon' => 'fas fa-bell', 'type' => 'fa', 'task' => 'إشعارات FCM/APNs للتفاعل مع المستخدمين.'],
                ['name' => 'Offline Storage', 'icon' => 'fas fa-database', 'type' => 'fa', 'task' => 'Hive/SQLite للعمل بدون اتصال.'],
            ],
        ],
        [
            'title' => 'أدوات التطوير والنشر',
            'icon' => 'fas fa-rocket',
            'desc' => 'من بيئة التطوير حتى رفع التطبيق على متاجر Google و Apple.',
            'tools' => [
                ['name' => 'Android Studio', 'icon' => 'devicon-android-plain', 'type' => 'devicon', 'task' => 'بناء APK/AAB واختبار على محاكيات أندرويد.'],
                ['name' => 'Xcode', 'icon' => 'https://cdn.simpleicons.org/xcode/147EFB', 'type' => 'img', 'task' => 'توقيع وبناء IPA لنشر تطبيقات iOS.'],
                ['name' => 'VS Code', 'icon' => 'devicon-vscode-plain', 'type' => 'devicon', 'task' => 'محرر خفيف مع امتدادات Flutter/Dart.'],
                ['name' => 'Git / GitHub', 'icon' => 'devicon-github-original', 'type' => 'devicon', 'task' => 'إصدارات، مراجعة كود، وتعاون الفريق.'],
                ['name' => 'Google Play', 'icon' => 'https://cdn.simpleicons.org/googleplay/414141', 'type' => 'img', 'task' => 'نشر وتحديث تطبيقات أندرويد على المتجر.'],
                ['name' => 'App Store', 'icon' => 'devicon-apple-original', 'type' => 'devicon', 'task' => 'رفع IPA ومراجعة Apple للنشر الرسمي.'],
            ],
        ],
    ];
@endphp

<section class="section-padding security-tools-section mobile-tools-section" id="mobile-tools">
    <div class="security-tools-section__bg" aria-hidden="true">
        <div class="security-tools-section__circuit"></div>
        <div class="security-tools-section__glow security-tools-section__glow--1"></div>
        <div class="security-tools-section__glow security-tools-section__glow--2"></div>
    </div>
    <div class="container position-relative security-tools-section__inner">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">التقنيات</span>
            <h2>تقنيات تطبيقات الجوال — شاملة</h2>
            <p>من Flutter و Dart إلى Firebase والنشر على المتاجر: أدوات حديثة لبناء تطبيقات احترافية.</p>
        </div>

        @foreach ($mobileCategories as $catIndex => $category)
            <div class="security-tools-category animate-on-scroll animate-delay-{{ ($catIndex % 4) + 1 }}">
                <header class="security-tools-category__head">
                    <div class="security-tools-category__icon" aria-hidden="true">
                        <i class="{{ $category['icon'] }}"></i>
                    </div>
                    <div>
                        <h3 class="security-tools-category__title">{{ $category['title'] }}</h3>
                        <p class="security-tools-category__desc">{{ $category['desc'] }}</p>
                    </div>
                </header>
                <div class="row g-3">
                    @foreach ($category['tools'] as $tool)
                        <div class="col-md-6 col-xl-4">
                            <article class="security-tool-card">
                                @include('frontend.partials.security-tool-icon', [
                                    'type' => $tool['type'] ?? 'fa',
                                    'icon' => $tool['icon'],
                                    'name' => $tool['name'],
                                ])
                                <h4 class="security-tool-card__name">{{ $tool['name'] }}</h4>
                                <p class="security-tool-card__task">{{ $tool['task'] }}</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
