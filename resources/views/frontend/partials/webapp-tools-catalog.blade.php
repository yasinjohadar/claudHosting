@php
    $webappCategories = [
        [
            'title' => 'واجهات أمامية (Frontend)',
            'icon' => 'fas fa-palette',
            'desc' => 'واجهات تفاعلية سريعة ومتجاوبة مع تجربة مستخدم احترافية.',
            'tools' => [
                ['name' => 'React.js', 'icon' => 'devicon-react-original', 'type' => 'devicon', 'task' => 'مكونات تفاعلية وSPA مع إدارة حالة فعّالة.'],
                ['name' => 'Vue.js', 'icon' => 'devicon-vuejs-plain', 'type' => 'devicon', 'task' => 'إطار مرن للواجهات مع تكامل سلس مع Laravel.'],
                ['name' => 'Next.js', 'icon' => 'devicon-nextjs-plain', 'type' => 'devicon', 'task' => 'SSR/SSG وأداء محسّن لمحركات البحث.'],
                ['name' => 'TypeScript', 'icon' => 'devicon-typescript-plain', 'type' => 'devicon', 'task' => 'كود آمن وقابل للصيانة مع typing صارم.'],
                ['name' => 'HTML5 / CSS3', 'icon' => 'devicon-html5-plain', 'type' => 'devicon', 'task' => 'هيكل دلالي وتنسيقات حديثة ومتجاوبة.'],
                ['name' => 'Tailwind CSS', 'icon' => 'devicon-tailwindcss-plain', 'type' => 'devicon', 'task' => 'تصميم utility-first سريع ومتسق.'],
            ],
        ],
        [
            'title' => 'خلفية وواجهات برمجية (Backend & API)',
            'icon' => 'fas fa-server',
            'desc' => 'منطق الأعمال، مصادقة، وواجهات REST/GraphQL قابلة للتوسع.',
            'tools' => [
                ['name' => 'Laravel', 'icon' => 'devicon-laravel-plain', 'type' => 'devicon', 'task' => 'إطار PHP قوي للتطبيقات والمنصات المؤسسية.'],
                ['name' => 'PHP', 'icon' => 'devicon-php-plain', 'type' => 'devicon', 'task' => 'تطوير خلفي موثوق مع بيئة استضافة واسعة.'],
                ['name' => 'Node.js', 'icon' => 'devicon-nodejs-plain', 'type' => 'devicon', 'task' => 'خدمات real-time وAPIs عالية الأداء.'],
                ['name' => 'Express.js', 'icon' => 'devicon-express-original', 'type' => 'devicon', 'task' => 'بناء REST APIs خفيفة ومرنة.'],
                ['name' => 'REST API', 'icon' => 'fas fa-plug', 'type' => 'fa', 'task' => 'تصميم endpoints موثّقة وآمنة للتكامل.'],
                ['name' => 'GraphQL', 'icon' => 'https://cdn.simpleicons.org/graphql/E10098', 'type' => 'img', 'task' => 'استعلامات مرنة للواجهات المعقدة.'],
            ],
        ],
        [
            'title' => 'قواعد البيانات والتخزين',
            'icon' => 'fas fa-database',
            'desc' => 'تصميم مخططات، استعلامات محسّنة، ونسخ احتياطي آمن.',
            'tools' => [
                ['name' => 'MySQL', 'icon' => 'devicon-mysql-plain', 'type' => 'devicon', 'task' => 'قواعد علائقية مستقرة للتطبيقات والمتاجر.'],
                ['name' => 'PostgreSQL', 'icon' => 'devicon-postgresql-plain', 'type' => 'devicon', 'task' => 'بيانات معقدة مع JSON وامتثال ACID.'],
                ['name' => 'MongoDB', 'icon' => 'devicon-mongodb-plain', 'type' => 'devicon', 'task' => 'مستندات مرنة للمحتوى الديناميكي.'],
                ['name' => 'Redis', 'icon' => 'devicon-redis-plain', 'type' => 'devicon', 'task' => 'Cache وجلسات وصفوف مهام سريعة.'],
                ['name' => 'SQLite', 'icon' => 'https://cdn.simpleicons.org/sqlite/003B57', 'type' => 'img', 'task' => 'تخزين خفيف للمشاريع الصغيرة والاختبار.'],
                ['name' => 'Prisma / Eloquent', 'icon' => 'fas fa-layer-group', 'type' => 'fa', 'task' => 'ORM منظم للوصول الآمن للبيانات.'],
            ],
        ],
        [
            'title' => 'تصميم، نشر، وجودة',
            'icon' => 'fas fa-rocket',
            'desc' => 'من التصميم إلى الإنتاج مع أدوات تعاون واختبار موثوقة.',
            'tools' => [
                ['name' => 'Figma', 'icon' => 'https://cdn.simpleicons.org/figma/F24E1E', 'type' => 'img', 'task' => 'نماذج UI/UX وتسليم تصميم للمطورين.'],
                ['name' => 'Git / GitHub', 'icon' => 'devicon-github-original', 'type' => 'devicon', 'task' => 'إصدارات، مراجعة كود، وتعاون الفريق.'],
                ['name' => 'Bootstrap', 'icon' => 'devicon-bootstrap-plain', 'type' => 'devicon', 'task' => 'مكونات جاهزة للواجهات السريعة.'],
                ['name' => 'Vite / Webpack', 'icon' => 'devicon-vitejs-plain', 'type' => 'devicon', 'task' => 'بناء أصول أمامية سريع للتطوير والإنتاج.'],
                ['name' => 'Jest / PHPUnit', 'icon' => 'https://cdn.simpleicons.org/jest/C21325', 'type' => 'img', 'task' => 'اختبارات آلية لضمان استقرار الإصدارات.'],
                ['name' => 'SSL & CDN', 'icon' => 'fas fa-lock', 'type' => 'fa', 'task' => 'نشر آمن مع HTTPS وتسريع المحتوى.'],
            ],
        ],
    ];
@endphp

<section class="section-padding security-tools-section webapp-tools-section" id="webapp-tools">
    <div class="security-tools-section__bg" aria-hidden="true">
        <div class="security-tools-section__circuit"></div>
        <div class="security-tools-section__glow security-tools-section__glow--1"></div>
        <div class="security-tools-section__glow security-tools-section__glow--2"></div>
    </div>
    <div class="container position-relative security-tools-section__inner">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">التقنيات</span>
            <h2>تقنيات تطوير الويب — شاملة</h2>
            <p>من الواجهة إلى قاعدة البيانات والنشر: أدوات حديثة نعتمد عليها في كل مشروع ويب.</p>
        </div>

        @foreach ($webappCategories as $catIndex => $category)
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
