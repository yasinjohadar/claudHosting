@php
    $securityCategories = [
        [
            'title' => 'حماية الحافة والشبكة',
            'icon' => 'fas fa-shield-alt',
            'desc' => 'طبقة أولى أمام خوادمك ومواقعك لصد الهجمات قبل وصولها للتطبيق.',
            'tools' => [
                [
                    'name' => 'Cloudflare',
                    'icon' => 'https://cdn.simpleicons.org/cloudflare/F38020',
                    'type' => 'img',
                    'task' => 'WAF، حماية DDoS، CDN، وإدارة DNS مع قواعد أمان على مستوى الحافة.',
                ],
                [
                    'name' => 'Fail2ban',
                    'icon' => 'fas fa-ban',
                    'type' => 'fa',
                    'task' => 'حظر عناوين IP بعد محاولات تسجيل دخول أو هجمات قوة غاشمة متكررة.',
                ],
                [
                    'name' => 'UFW / iptables',
                    'icon' => 'fas fa-fire',
                    'type' => 'fa',
                    'task' => 'جدار ناري على مستوى نظام التشغيل — فتح المنافذ الضرورية فقط وعزل الخدمات.',
                ],
                [
                    'name' => 'ModSecurity',
                    'icon' => 'fas fa-filter',
                    'type' => 'fa',
                    'task' => 'جدار تطبيقات ويب (WAF) على Nginx/Apache مع قواعد OWASP CRS.',
                ],
            ],
        ],
        [
            'title' => 'التشفير وشهادات SSL/TLS',
            'icon' => 'fas fa-lock',
            'desc' => 'تأمين النقل بين المستخدم والخادم وحماية البيانات أثناء الاتصال.',
            'tools' => [
                [
                    'name' => "Let's Encrypt",
                    'icon' => 'https://cdn.simpleicons.org/letsencrypt/003A70',
                    'type' => 'img',
                    'task' => 'إصدار وتجديد شهادات SSL مجانية تلقائياً عبر Certbot.',
                ],
                [
                    'name' => 'OpenSSL',
                    'icon' => 'https://cdn.simpleicons.org/openssl/721412',
                    'type' => 'img',
                    'task' => 'توليد المفاتيح، إدارة الشهادات، وضبط بروتوكولات TLS الآمنة.',
                ],
                [
                    'name' => 'Certbot',
                    'icon' => 'fas fa-certificate',
                    'type' => 'fa',
                    'task' => 'أتمتة تركيب الشهادات وربطها بـ Nginx/Apache مع تجديد دوري.',
                ],
                [
                    'name' => 'HSTS',
                    'icon' => 'fas fa-arrow-up',
                    'type' => 'fa',
                    'task' => 'إجبار المتصفح على HTTPS فقط وتقليل هجمات خفض التشفير (Downgrade).',
                ],
            ],
        ],
        [
            'title' => 'فحص الثغرات واختبار الاختراق',
            'icon' => 'fas fa-bug',
            'desc' => 'اكتشاف نقاط الضعف قبل المهاجمين ومعالجتها وفق أولوية الخطورة.',
            'tools' => [
                [
                    'name' => 'OWASP ZAP',
                    'icon' => 'https://cdn.simpleicons.org/owasp/0052A5',
                    'type' => 'img',
                    'task' => 'فحص ديناميكي (DAST) للتطبيقات والواجهات لاكتشاف XSS و SQLi وغيرها.',
                ],
                [
                    'name' => 'Nikto',
                    'icon' => 'fas fa-search',
                    'type' => 'fa',
                    'task' => 'مسح خوادم الويب للإعدادات الخاطئة والملفات الحساسة المكشوفة.',
                ],
                [
                    'name' => 'OpenVAS',
                    'icon' => 'fas fa-crosshairs',
                    'type' => 'fa',
                    'task' => 'تقييم شامل لثغرات الشبكة والأنظمة مع تقارير CVSS.',
                ],
                [
                    'name' => 'OWASP Top 10',
                    'icon' => 'fas fa-list-ol',
                    'type' => 'fa',
                    'task' => 'مرجع لأخطر مخاطر تطبيقات الويب — نُدمجه في مراجعات الكود والتدقيق.',
                ],
            ],
        ],
        [
            'title' => 'مراقبة أمنية وتنبيهات',
            'icon' => 'fas fa-chart-line',
            'desc' => 'رصد غير طبيعي في الوقت الفعلي والاستجابة السريعة للحوادث.',
            'tools' => [
                [
                    'name' => 'Prometheus',
                    'icon' => 'devicon-prometheus-original',
                    'type' => 'devicon',
                    'task' => 'جمع مقاييس الأداء والأحداث الأمنية من الخوادم والحاويات.',
                ],
                [
                    'name' => 'Grafana',
                    'icon' => 'devicon-grafana-original',
                    'type' => 'devicon',
                    'task' => 'لوحات مراقبة وتنبيهات فورية عند تجاوز عتبات مشبوهة.',
                ],
                [
                    'name' => 'Loki / ELK',
                    'icon' => 'fas fa-file-alt',
                    'type' => 'fa',
                    'task' => 'تجميع وتحليل سجلات الوصول والأخطاء لاكتشاف أنماط الهجوم.',
                ],
                [
                    'name' => 'auditd',
                    'icon' => 'fas fa-clipboard-list',
                    'type' => 'fa',
                    'task' => 'تدقيق تغييرات النظام والملفات الحساسة على Linux.',
                ],
            ],
        ],
        [
            'title' => 'حماية التطبيقات وواجهات API',
            'icon' => 'fas fa-code',
            'desc' => 'تأمين المنطق البرمجي ونقاط النهاية ضد الهجمات الشائعة.',
            'tools' => [
                [
                    'name' => 'JWT / OAuth 2.0',
                    'icon' => 'fas fa-key',
                    'type' => 'fa',
                    'task' => 'مصادقة آمنة للمستخدمين وخدمات API دون تخزين جلسات ثقيلة.',
                ],
                [
                    'name' => 'Rate Limiting',
                    'icon' => 'fas fa-tachometer-alt',
                    'type' => 'fa',
                    'task' => 'تحديد معدل الطلبات لمنع إساءة API وهجمات الحرمان من الخدمة.',
                ],
                [
                    'name' => 'Snyk / Dependabot',
                    'icon' => 'https://cdn.simpleicons.org/snyk/4C4A73',
                    'type' => 'img',
                    'task' => 'فحص تبعيات المشروع (dependencies) لاكتشاف مكتبات بثغرات معروفة.',
                ],
                [
                    'name' => 'CSP & Security Headers',
                    'icon' => 'fas fa-heading',
                    'type' => 'fa',
                    'task' => 'رؤوس HTTP أمنية (CSP, X-Frame-Options…) لتقليل XSS وسرقة الجلسات.',
                ],
            ],
        ],
        [
            'title' => 'إدارة الهوية والوصول (IAM)',
            'icon' => 'fas fa-user-shield',
            'desc' => 'من يصل إلى ماذا — بأقل صلاحيات ممكنة (Least Privilege).',
            'tools' => [
                [
                    'name' => '2FA / MFA',
                    'icon' => 'fas fa-mobile-alt',
                    'type' => 'fa',
                    'task' => 'طبقة تحقق ثانية للوحات التحكم والبريد والخدمات الحساسة.',
                ],
                [
                    'name' => 'SSH Keys',
                    'icon' => 'fas fa-terminal',
                    'type' => 'fa',
                    'task' => 'دخول الخوادم بمفاتيح مشفرة وتعطيل كلمات المرور الضعيفة.',
                ],
                [
                    'name' => 'HashiCorp Vault',
                    'icon' => 'https://cdn.simpleicons.org/hashicorp/844FBA',
                    'type' => 'img',
                    'task' => 'تخزين آمن للأسرار ومفاتيح API وشهادات دون تضمينها في الكود.',
                ],
                [
                    'name' => 'RBAC',
                    'icon' => 'fas fa-users-cog',
                    'type' => 'fa',
                    'task' => 'أدوار وصلاحيات دقيقة في لوحات cPanel والتطبيقات و Kubernetes.',
                ],
            ],
        ],
        [
            'title' => 'أمان الحاويات والبنية السحابية',
            'icon' => 'fab fa-docker',
            'desc' => 'حماية بيئات Docker و Kubernetes في الاستضافة المعزولة.',
            'tools' => [
                [
                    'name' => 'Trivy',
                    'icon' => 'https://cdn.simpleicons.org/aquasecurity/1904DA',
                    'type' => 'img',
                    'task' => 'فحص صور الحاويات لثغرات OS والحزم قبل النشر.',
                ],
                [
                    'name' => 'Docker Bench',
                    'icon' => 'fab fa-docker',
                    'type' => 'fa',
                    'task' => 'تدقيق إعدادات Docker وفق أفضل ممارسات CIS.',
                ],
                [
                    'name' => 'Network Policies',
                    'icon' => 'devicon-kubernetes-plain',
                    'type' => 'devicon',
                    'task' => 'عزل حركة الشبكة بين Pods في Kubernetes.',
                ],
                [
                    'name' => 'Secrets Management',
                    'icon' => 'fas fa-user-secret',
                    'type' => 'fa',
                    'task' => 'إدارة أسرار K8s و Coolify دون تسريب في متغيرات بيئة مكشوفة.',
                ],
            ],
        ],
        [
            'title' => 'النسخ الاحتياطي والاستعادة',
            'icon' => 'fas fa-cloud-upload-alt',
            'desc' => 'الجاهزية للحوادث — استرجاع البيانات بعد الاختراق أو العطل.',
            'tools' => [
                [
                    'name' => 'Restic / Borg',
                    'icon' => 'fas fa-database',
                    'type' => 'fa',
                    'task' => 'نسخ احتياطي مشفر ومتزايد مع إمكانية استعادة نقطة زمنية.',
                ],
                [
                    'name' => 'Backblaze B2 / S3',
                    'icon' => 'https://cdn.simpleicons.org/backblaze/EE8034',
                    'type' => 'img',
                    'task' => 'تخزين النسخ خارج الموقع (off-site) بعيد عن الخادم الأساسي.',
                ],
                [
                    'name' => 'نسخ قواعد البيانات',
                    'icon' => 'fas fa-server',
                    'type' => 'fa',
                    'task' => 'جدولة mysqldump/pg_dump مع تشفير الملفات قبل الرفع.',
                ],
                [
                    'name' => 'خطة DR',
                    'icon' => 'fas fa-undo',
                    'type' => 'fa',
                    'task' => 'إجراءات استعادة الكوارث موثقة ومختبرة دورياً.',
                ],
            ],
        ],
    ];
@endphp

<section class="section-padding security-tools-section" id="security-tools">
    <div class="security-tools-section__bg" aria-hidden="true">
        <div class="security-tools-section__circuit"></div>
        <div class="security-tools-section__glow security-tools-section__glow--1"></div>
        <div class="security-tools-section__glow security-tools-section__glow--2"></div>
    </div>
    <div class="container position-relative security-tools-section__inner">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">أدوات الأمن</span>
            <h2>الأدوات التي نعمل بها في أمن المعلومات</h2>
            <p>
                في <strong>استضافة كلاودسوفت</strong> نُدمج هذه الأدوات ضمن بنيتنا التحتية وخدمات الاستضافة —
                من الحافة إلى الخادم إلى التطبيق — لحماية مواقع عملائنا ومشاريعنا السحابية.
            </p>
        </div>

        @foreach ($securityCategories as $catIndex => $category)
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
                        <div class="col-md-6 col-xl-3">
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

        <div class="security-tools-footnote glass-panel animate-on-scroll">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <p class="mb-0">
                لا تقتصر القائمة على ما سبق — نُقيّم احتياج كل عميل ونُضيف أدوات مثل
                <strong>Burp Suite</strong>، <strong>Suricata</strong>، <strong>Wazuh</strong>، أو حلول SIEM عند الحاجة.
                جميع التطبيقات تتم وفق نطاق متفق عليه وتقرير أمني واضح.
            </p>
        </div>
    </div>
</section>
