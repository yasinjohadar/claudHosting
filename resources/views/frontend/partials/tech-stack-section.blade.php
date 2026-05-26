@php
    $technologies = [
        ['icon' => 'fab fa-cpanel', 'type' => 'fa', 'name' => 'cPanel', 'tag' => 'لوحة تحكم'],
        ['icon' => 'devicon-docker-plain', 'type' => 'devicon', 'name' => 'Docker', 'tag' => 'حاويات'],
        ['icon' => 'devicon-kubernetes-plain', 'type' => 'devicon', 'name' => 'Kubernetes', 'tag' => 'تنسيق'],
        ['icon' => 'devicon-nodejs-plain-wordmark', 'type' => 'devicon', 'name' => 'Node.js', 'tag' => 'Backend'],
        ['icon' => 'devicon-python-plain', 'type' => 'devicon', 'name' => 'Python', 'tag' => 'تطبيقات'],
        ['icon' => 'devicon-spring-plain', 'type' => 'devicon', 'name' => 'Spring Boot', 'tag' => 'Java'],
        ['icon' => 'devicon-php-plain', 'type' => 'devicon', 'name' => 'PHP', 'tag' => 'ويب'],
        ['icon' => 'devicon-laravel-plain', 'type' => 'devicon', 'name' => 'Laravel', 'tag' => 'Framework'],
        ['icon' => 'devicon-wordpress-plain', 'type' => 'devicon', 'name' => 'WordPress', 'tag' => 'CMS'],
        ['icon' => 'devicon-react-original', 'type' => 'devicon', 'name' => 'React', 'tag' => 'Frontend'],
        ['icon' => 'devicon-vuejs-plain', 'type' => 'devicon', 'name' => 'Vue.js', 'tag' => 'Frontend'],
        ['icon' => 'devicon-angularjs-plain', 'type' => 'devicon', 'name' => 'Angular', 'tag' => 'Frontend'],
        ['icon' => 'devicon-nextjs-plain', 'type' => 'devicon', 'name' => 'Next.js', 'tag' => 'Full-stack'],
        ['icon' => 'devicon-typescript-plain', 'type' => 'devicon', 'name' => 'TypeScript', 'tag' => 'لغة'],
        ['icon' => 'devicon-mysql-plain', 'type' => 'devicon', 'name' => 'MySQL', 'tag' => 'قواعد بيانات'],
        ['icon' => 'devicon-postgresql-plain', 'type' => 'devicon', 'name' => 'PostgreSQL', 'tag' => 'قواعد بيانات'],
        ['icon' => 'devicon-mongodb-plain', 'type' => 'devicon', 'name' => 'MongoDB', 'tag' => 'NoSQL'],
        ['icon' => 'devicon-redis-plain', 'type' => 'devicon', 'name' => 'Redis', 'tag' => 'كاش'],
        ['icon' => 'devicon-nginx-original', 'type' => 'devicon', 'name' => 'Nginx', 'tag' => 'خادم ويب'],
        ['icon' => 'devicon-n8n-plain', 'type' => 'devicon', 'name' => 'n8n', 'tag' => 'أتمتة'],
        ['icon' => 'devicon-go-plain-wordmark', 'type' => 'devicon', 'name' => 'Go', 'tag' => 'أداء عالٍ'],
        ['icon' => 'fas fa-plug', 'type' => 'fa', 'name' => 'REST / API', 'tag' => 'تكامل'],
        ['icon' => 'fab fa-git-alt', 'type' => 'fa', 'name' => 'Git', 'tag' => 'نشر'],
        ['icon' => 'fas fa-shield-halved', 'type' => 'fa', 'name' => 'SSL / HTTPS', 'tag' => 'أمان'],
    ];

    $highlights = [
        [
            'title' => 'حاويات وسحابة',
            'desc' => 'Docker وبيئات معزولة للنشر السريع والآمن مع إمكانية التوسع.',
            'icons' => ['devicon-docker-plain', 'devicon-kubernetes-plain', 'devicon-nginx-original'],
            'accent' => '#2496ed',
        ],
        [
            'title' => 'تطبيقات ويب حديثة',
            'desc' => 'Node.js وPython وPHP وLaravel وSpring Boot — جاهزة للتشغيل.',
            'icons' => ['devicon-nodejs-plain-wordmark', 'devicon-python-plain', 'devicon-laravel-plain', 'devicon-spring-plain'],
            'accent' => '#0057B8',
        ],
        [
            'title' => 'واجهات وتجارب',
            'desc' => 'React وVue وAngular وNext.js لمواقع سريعة وتفاعلية.',
            'icons' => ['devicon-react-original', 'devicon-vuejs-plain', 'devicon-nextjs-plain'],
            'accent' => '#61dafb',
        ],
        [
            'title' => 'أتمتة وذكاء تشغيلي',
            'desc' => 'n8n وتدفقات العمل والربط مع APIs وخدمات الطرف الثالث.',
            'icons' => ['devicon-n8n-plain', 'devicon-typescript-plain'],
            'accent' => '#ea4b71',
        ],
    ];
@endphp

<section class="tech-stack-section section-padding" id="tech-stack" aria-labelledby="tech-stack-title">
    <div class="tech-stack-bg" aria-hidden="true">
        <div class="tech-stack-grid"></div>
        <div class="tech-stack-glow tech-stack-glow--1"></div>
        <div class="tech-stack-glow tech-stack-glow--2"></div>
    </div>

    <div class="container position-relative">
        <div class="section-header animate-on-scroll tech-stack-header">
            <span class="section-badge section-badge--light">بنية تقنية مرنة</span>
            <h2 id="tech-stack-title">التقنيات التي ندعمها في الاستضافة</h2>
            <p>من لوحات التحكم الكلاسيكية إلى الحاويات والأطر الحديثة — بيئة جاهزة لمشاريعك دون قيود تقنية</p>
            <div class="tech-stack-stats animate-on-scroll">
                <div class="tech-stack-stat">
                    <span class="tech-stack-stat-num">25+</span>
                    <span class="tech-stack-stat-label">تقنية مدعومة</span>
                </div>
                <div class="tech-stack-stat">
                    <span class="tech-stack-stat-num">Docker</span>
                    <span class="tech-stack-stat-label">نشر معزول</span>
                </div>
                <div class="tech-stack-stat">
                    <span class="tech-stack-stat-num">24/7</span>
                    <span class="tech-stack-stat-label">مراقبة وتشغيل</span>
                </div>
            </div>
        </div>

        <div class="tech-marquee-wrap animate-on-scroll" dir="ltr">
            <div class="tech-marquee-fade tech-marquee-fade--start" aria-hidden="true"></div>
            <div class="tech-marquee-fade tech-marquee-fade--end" aria-hidden="true"></div>
            <div class="tech-marquee tech-marquee--forward">
                <div class="tech-marquee-track">
                    @foreach(array_merge($technologies, $technologies) as $tech)
                    <div class="tech-chip">
                        <span class="tech-chip-icon" aria-hidden="true">
                            @if(($tech['type'] ?? '') === 'devicon')
                            <i class="{{ $tech['icon'] }} colored"></i>
                            @else
                            <i class="{{ $tech['icon'] }}"></i>
                            @endif
                        </span>
                        <span class="tech-chip-text">
                            <strong>{{ $tech['name'] }}</strong>
                            <small>{{ $tech['tag'] }}</small>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="tech-marquee tech-marquee--reverse">
                <div class="tech-marquee-track">
                    @foreach(array_merge(array_reverse($technologies), array_reverse($technologies)) as $tech)
                    <div class="tech-chip tech-chip--alt">
                        <span class="tech-chip-icon" aria-hidden="true">
                            @if(($tech['type'] ?? '') === 'devicon')
                            <i class="{{ $tech['icon'] }} colored"></i>
                            @else
                            <i class="{{ $tech['icon'] }}"></i>
                            @endif
                        </span>
                        <span class="tech-chip-text">
                            <strong>{{ $tech['name'] }}</strong>
                            <small>{{ $tech['tag'] }}</small>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-4 tech-highlights">
            @foreach($highlights as $i => $card)
            <div class="col-lg-3 col-md-6">
                <article class="tech-highlight-card animate-on-scroll animate-delay-{{ ($i % 4) + 1 }}"
                    style="--tech-accent: {{ $card['accent'] }}">
                    <div class="tech-highlight-icons">
                        @foreach($card['icons'] as $iconClass)
                        <span class="tech-highlight-icon"><i class="{{ $iconClass }} colored"></i></span>
                        @endforeach
                    </div>
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['desc'] }}</p>
                    <span class="tech-highlight-line" aria-hidden="true"></span>
                </article>
            </div>
            @endforeach
        </div>

        <p class="tech-stack-footnote animate-on-scroll text-center">
            <i class="fas fa-circle-check text-primary"></i>
            لا تجد تقنيتك؟ <a href="{{ url('/contact') }}">تواصل معنا</a> — نساعدك في اختيار البيئة الأنسب أو إعداد حل مخصص.
        </p>
    </div>
</section>
