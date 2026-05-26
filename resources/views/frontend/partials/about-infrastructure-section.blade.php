@php
    $categories = [
        [
            'title' => 'أنظمة التشغيل ولوحات التحكم',
            'desc' => 'أنظمة مستقرة ولوحات تحكم معروفة لإدارة سهلة للخوادم والمواقع.',
            'badge' => ['icon' => 'devicon-linux-plain', 'type' => 'devicon'],
            'items' => [
                ['icon' => 'devicon-ubuntu-plain', 'type' => 'devicon', 'name' => 'Ubuntu'],
                ['icon' => 'https://cdn.simpleicons.org/almalinux/0F4266', 'type' => 'img', 'name' => 'AlmaLinux'],
                ['icon' => 'fab fa-cpanel', 'type' => 'fa', 'name' => 'cPanel'],
                ['icon' => 'fab fa-cpanel', 'type' => 'fa', 'name' => 'WHM', 'hint' => 'Web Host Manager'],
                ['icon' => 'https://cdn.simpleicons.org/plesk/52BBE6', 'type' => 'img', 'name' => 'Plesk'],
                ['icon' => 'devicon-nginx-original', 'type' => 'devicon', 'name' => 'NGINX'],
                ['icon' => 'devicon-apache-plain', 'type' => 'devicon', 'name' => 'Apache'],
            ],
        ],
        [
            'title' => 'الحاويات والبنية السحابية',
            'desc' => 'بنية سحابية على الحاويات وبيئات VPS للمرونة والتوسع.',
            'badge' => ['icon' => 'devicon-docker-plain', 'type' => 'devicon'],
            'items' => [
                ['icon' => 'devicon-docker-plain', 'type' => 'devicon', 'name' => 'Docker'],
                ['icon' => 'devicon-kubernetes-plain', 'type' => 'devicon', 'name' => 'Kubernetes'],
                ['icon' => 'https://cdn.simpleicons.org/kvm/FF6600', 'type' => 'img', 'name' => 'KVM / VPS'],
                ['icon' => 'devicon-digitalocean-plain', 'type' => 'devicon', 'name' => 'Cloud VPS'],
                ['icon' => 'https://cdn.simpleicons.org/haproxy/009639', 'type' => 'img', 'name' => 'Load Balancing'],
                ['icon' => 'devicon-cloudflare-plain-wordmark', 'type' => 'devicon', 'name' => 'CDN'],
            ],
        ],
        [
            'title' => 'قواعد البيانات وأنظمة التخزين',
            'desc' => 'تخزين وقواعد بيانات عالية الأداء للمواقع والمتاجر.',
            'badge' => ['icon' => 'devicon-mysql-plain', 'type' => 'devicon'],
            'items' => [
                ['icon' => 'devicon-mysql-plain', 'type' => 'devicon', 'name' => 'MySQL'],
                ['icon' => 'https://cdn.simpleicons.org/mariadb/003545', 'type' => 'img', 'name' => 'MariaDB'],
                ['icon' => 'devicon-postgresql-plain', 'type' => 'devicon', 'name' => 'PostgreSQL'],
                ['icon' => 'devicon-mongodb-plain', 'type' => 'devicon', 'name' => 'MongoDB'],
                ['icon' => 'devicon-redis-plain', 'type' => 'devicon', 'name' => 'Redis'],
                ['icon' => 'devicon-amazonwebservices-plain-wordmark', 'type' => 'devicon', 'name' => 'Object Storage'],
                ['icon' => 'https://cdn.simpleicons.org/minio/C72C48', 'type' => 'img', 'name' => 'MinIO'],
            ],
        ],
        [
            'title' => 'الأمان والمراقبة',
            'desc' => 'حماية متعددة الطبقات ومراقبة مستمرة لاستقرار الخدمات.',
            'badge' => ['icon' => 'devicon-grafana-original', 'type' => 'devicon'],
            'items' => [
                ['icon' => 'https://cdn.simpleicons.org/cloudflare/F38020', 'type' => 'img', 'name' => 'WAF'],
                ['icon' => 'devicon-prometheus-original', 'type' => 'devicon', 'name' => 'Prometheus'],
                ['icon' => 'devicon-grafana-original', 'type' => 'devicon', 'name' => 'Grafana'],
                ['icon' => 'https://cdn.simpleicons.org/backblaze/EE8034', 'type' => 'img', 'name' => 'نسخ احتياطي'],
                ['icon' => 'https://cdn.simpleicons.org/letsencrypt/003A70', 'type' => 'img', 'name' => "Let's Encrypt"],
                ['icon' => 'https://cdn.simpleicons.org/openssl/721412', 'type' => 'img', 'name' => 'SSL / TLS'],
            ],
        ],
        [
            'title' => 'تطبيقات ومنصات التطوير',
            'desc' => 'أطر ولغات حديثة جاهزة للنشر على بيئات الاستضافة المعزولة.',
            'badge' => ['icon' => 'devicon-nodejs-plain-wordmark', 'type' => 'devicon'],
            'items' => [
                ['icon' => 'devicon-nodejs-plain-wordmark', 'type' => 'devicon', 'name' => 'Node.js'],
                ['icon' => 'devicon-python-plain', 'type' => 'devicon', 'name' => 'Python'],
                ['icon' => 'devicon-php-plain', 'type' => 'devicon', 'name' => 'PHP'],
                ['icon' => 'devicon-laravel-plain', 'type' => 'devicon', 'name' => 'Laravel'],
                ['icon' => 'devicon-spring-plain', 'type' => 'devicon', 'name' => 'Spring Boot'],
                ['icon' => 'devicon-wordpress-plain', 'type' => 'devicon', 'name' => 'WordPress'],
                ['icon' => 'devicon-react-original', 'type' => 'devicon', 'name' => 'React'],
                ['icon' => 'devicon-vuejs-plain', 'type' => 'devicon', 'name' => 'Vue.js'],
                ['icon' => 'devicon-angularjs-plain', 'type' => 'devicon', 'name' => 'Angular'],
                ['icon' => 'devicon-nextjs-plain', 'type' => 'devicon', 'name' => 'Next.js'],
                ['icon' => 'devicon-typescript-plain', 'type' => 'devicon', 'name' => 'TypeScript'],
                ['icon' => 'https://cdn.simpleicons.org/n8n/EA4B71', 'type' => 'img', 'name' => 'n8n'],
                ['icon' => 'devicon-go-plain-wordmark', 'type' => 'devicon', 'name' => 'Go'],
            ],
        ],
    ];
@endphp

<section class="infra-tech-section section-padding" id="infrastructure" style="background: var(--clr-bg-secondary);">
    <div class="container">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">بنيتنا التقنية</span>
            <h2>البنية التحتية والتقنيات المستخدمة</h2>
            <p>نستخدم أحدث التقنيات في الخوادم، الشبكات، وقواعد البيانات لضمان أفضل أداء وأعلى مستوى من الأمان لخدمات الاستضافة</p>
        </div>

        <div class="row g-4 infra-tech-cards">
            @foreach ($categories as $i => $category)
                <div class="col-lg-6 {{ $loop->last && count($categories) % 2 === 1 ? 'col-12' : '' }}">
                    <article class="infra-card glass-panel animate-on-scroll animate-delay-{{ ($i % 4) + 1 }}">
                        <header class="infra-card__head">
                            <div class="infra-card__badge" aria-hidden="true">
                                @if (($category['badge']['type'] ?? '') === 'devicon')
                                    <i class="{{ $category['badge']['icon'] }} colored"></i>
                                @elseif (($category['badge']['type'] ?? '') === 'img')
                                    <img src="{{ $category['badge']['icon'] }}" alt="" width="28" height="28" loading="lazy" decoding="async">
                                @else
                                    <i class="{{ $category['badge']['icon'] }}"></i>
                                @endif
                            </div>
                            <div class="infra-card__intro">
                                <h3 class="infra-card__title">{{ $category['title'] }}</h3>
                                <p class="infra-card__desc">{{ $category['desc'] }}</p>
                            </div>
                        </header>
                        <div class="infra-tech-grid">
                            @foreach ($category['items'] as $item)
                                @include('frontend.partials.tech-logo-chip', [
                                    'icon' => $item['icon'],
                                    'type' => $item['type'] ?? 'devicon',
                                    'name' => $item['name'],
                                    'hint' => $item['hint'] ?? null,
                                ])
                            @endforeach
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <p class="infra-tech-footnote animate-on-scroll text-center">
            <i class="fas fa-circle-check" style="color: var(--clr-primary);"></i>
            جميع الشعارات بألوانها الرسمية — بيئة جاهزة لنشر مشروعك دون قيود تقنية.
        </p>
    </div>
</section>
