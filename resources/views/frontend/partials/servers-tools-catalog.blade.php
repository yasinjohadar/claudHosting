@php
    $serversCategories = [
        [
            'title' => 'أنظمة التشغيل والبنية',
            'icon' => 'fab fa-linux',
            'desc' => 'تجهيز خوادم Linux مستقرة مع تحديثات أمنية وإدارة خدمات.',
            'tools' => [
                ['name' => 'Linux', 'icon' => 'devicon-linux-plain', 'type' => 'devicon', 'task' => 'أساس تشغيل الخوادم مع مرونة واستقرار عالٍ.'],
                ['name' => 'Ubuntu Server', 'icon' => 'devicon-ubuntu-plain', 'type' => 'devicon', 'task' => 'توزيعة LTS شائعة للاستضافة والتطبيقات.'],
                ['name' => 'Debian / AlmaLinux', 'icon' => 'https://cdn.simpleicons.org/redhat/EE0000', 'type' => 'img', 'task' => 'بدائل enterprise مع دعم طويل الأمد.'],
                ['name' => 'systemd', 'icon' => 'fas fa-cogs', 'type' => 'fa', 'task' => 'إدارة الخدمات والتشغيل التلقائي عند الإقلاع.'],
                ['name' => 'SSH / SFTP', 'icon' => 'fas fa-terminal', 'type' => 'fa', 'task' => 'وصول آمن للإدارة ونقل الملفات.'],
                ['name' => 'Cron Jobs', 'icon' => 'fas fa-clock', 'type' => 'fa', 'task' => 'مهام مجدولة للنسخ الاحتياطي والصيانة.'],
            ],
        ],
        [
            'title' => 'خوادم الويب وبيئات التشغيل',
            'icon' => 'fas fa-globe',
            'desc' => 'تثبيت وضبط Nginx/Apache مع PHP و Node و Python حسب المشروع.',
            'tools' => [
                ['name' => 'Nginx', 'icon' => 'devicon-nginx-original', 'type' => 'devicon', 'task' => 'Reverse proxy وخادم ويب عالي الأداء.'],
                ['name' => 'Apache', 'icon' => 'devicon-apache-plain', 'type' => 'devicon', 'task' => 'خادم ويب كلاسيكي مع .htaccess وmodular.'],
                ['name' => 'PHP-FPM', 'icon' => 'devicon-php-plain', 'type' => 'devicon', 'task' => 'تشغيل Laravel و WordPress بكفاءة.'],
                ['name' => 'Node.js', 'icon' => 'devicon-nodejs-plain', 'type' => 'devicon', 'task' => 'تشغيل APIs وخدمات JavaScript على الخادم.'],
                ['name' => 'Python / Gunicorn', 'icon' => 'devicon-python-plain', 'type' => 'devicon', 'task' => 'تشغيل تطبيقات Django/Flask في الإنتاج.'],
                ['name' => 'PM2 / Supervisor', 'icon' => 'fas fa-sync-alt', 'type' => 'fa', 'task' => 'إبقاء العمليات تعمل وإعادة تشغيلها تلقائياً.'],
            ],
        ],
        [
            'title' => 'قواعد البيانات والتخزين',
            'icon' => 'fas fa-database',
            'desc' => 'تثبيت وضبط قواعد البيانات مع نسخ احتياطي واستعادة.',
            'tools' => [
                ['name' => 'MySQL', 'icon' => 'devicon-mysql-plain', 'type' => 'devicon', 'task' => 'قاعدة علائقية للمواقع والمتاجر الإلكترونية.'],
                ['name' => 'PostgreSQL', 'icon' => 'devicon-postgresql-plain', 'type' => 'devicon', 'task' => 'بيانات معقدة مع امتثال ACID وامتدادات.'],
                ['name' => 'MongoDB', 'icon' => 'devicon-mongodb-plain', 'type' => 'devicon', 'task' => 'مستندات مرنة للتطبيقات الحديثة.'],
                ['name' => 'Redis', 'icon' => 'devicon-redis-plain', 'type' => 'devicon', 'task' => 'Cache وجلسات وصفوف مهام سريعة.'],
                ['name' => 'Backup / rsync', 'icon' => 'fas fa-hdd', 'type' => 'fa', 'task' => 'نسخ احتياطي مجدول واستعادة عند الحاجة.'],
                ['name' => 'Object Storage', 'icon' => 'https://cdn.simpleicons.org/amazons3/569A31', 'type' => 'img', 'task' => 'تخزين ملفات ونسخ خارج السيرفر.'],
            ],
        ],
        [
            'title' => 'أمان، نشر، ومراقبة',
            'icon' => 'fas fa-shield-alt',
            'desc' => 'حماية الخادم، SSL، DNS، ومراقبة الأداء والتوفر.',
            'tools' => [
                ['name' => 'Let\'s Encrypt / SSL', 'icon' => 'https://cdn.simpleicons.org/letsencrypt/003A70', 'type' => 'img', 'task' => 'شهادات HTTPS مجانية وتجديد تلقائي.'],
                ['name' => 'UFW / Firewall', 'icon' => 'fas fa-fire-alt', 'type' => 'fa', 'task' => 'تقييد المنافذ وحماية الوصول للخادم.'],
                ['name' => 'Docker', 'icon' => 'devicon-docker-plain', 'type' => 'devicon', 'task' => 'عزل الخدمات وتشغيل حاويات على VPS.'],
                ['name' => 'Git / Deploy', 'icon' => 'devicon-git-plain', 'type' => 'devicon', 'task' => 'نشر آمن من المستودع إلى الإنتاج.'],
                ['name' => 'DNS / Cloudflare', 'icon' => 'https://cdn.simpleicons.org/cloudflare/F38020', 'type' => 'img', 'task' => 'ربط النطاقات مع CDN وحماية DNS.'],
                ['name' => 'Monitoring', 'icon' => 'fas fa-chart-line', 'type' => 'fa', 'task' => 'مراقبة CPU/RAM/Uptime وتنبيهات الأعطال.'],
            ],
        ],
    ];
@endphp

<section class="section-padding security-tools-section servers-tools-section" id="servers-tools">
    <div class="security-tools-section__bg" aria-hidden="true">
        <div class="security-tools-section__circuit"></div>
        <div class="security-tools-section__glow security-tools-section__glow--1"></div>
        <div class="security-tools-section__glow security-tools-section__glow--2"></div>
    </div>
    <div class="container position-relative security-tools-section__inner">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">التقنيات</span>
            <h2>تقنيات إدارة السيرفرات — شاملة</h2>
            <p>من Linux و Nginx إلى قواعد البيانات و SSL: أدوات موثوقة لتشغيل خوادم آمنة ومستقرة.</p>
        </div>

        @foreach ($serversCategories as $catIndex => $category)
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
