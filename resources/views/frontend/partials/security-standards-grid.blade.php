@php
    $standards = [
        [
            'name' => 'OWASP Top 10',
            'desc' => 'أخطر عشر مخاطر لتطبيقات الويب — مرجعنا في التدقيق والمراجعة.',
            'icon' => 'https://cdn.simpleicons.org/owasp/0052A5',
            'type' => 'img',
        ],
        [
            'name' => 'OWASP ASVS',
            'desc' => 'معيار التحقق من أمان التطبيقات (Application Security Verification Standard).',
            'icon' => 'https://cdn.simpleicons.org/owasp/0052A5',
            'type' => 'img',
        ],
        [
            'name' => 'CIS Benchmarks',
            'desc' => 'إعدادات آمنة معتمدة لأنظمة Linux والخوادم والحاويات.',
            'icon' => 'https://cdn.simpleicons.org/cisecurity/0086C9',
            'type' => 'img',
        ],
        [
            'name' => 'PCI DSS',
            'desc' => 'متطلبات أمان بيانات بطاقات الدفع — عند المتاجر والدفع الإلكتروني.',
            'icon' => 'https://cdn.simpleicons.org/pci/1F487C',
            'type' => 'img',
        ],
        [
            'name' => 'GDPR مبادئ',
            'desc' => 'حماية الخصوصية وحقوق أصحاب البيانات الشخصية.',
            'icon' => 'https://cdn.simpleicons.org/europeanunion/003399',
            'type' => 'img',
        ],
        [
            'name' => 'Zero Trust',
            'desc' => 'لا ثقة افتراضية — تحقق مستمر من الهوية والجهاز والسياق.',
            'icon' => 'fas fa-shield-alt',
            'type' => 'fa',
            'accent' => '#6366f1',
        ],
    ];
@endphp

<section class="section-padding security-standards-section" style="background: var(--clr-bg-secondary);">
    <div class="security-standards-section__bg" aria-hidden="true">
        <div class="security-standards-section__circuit"></div>
    </div>
    <div class="container position-relative">
        <div class="section-header animate-on-scroll">
            <span class="section-badge">معايير ومراجع</span>
            <h2>معايير نلتزم بها</h2>
            <p>أطر عالمية نُطبّقها في التدقيق، إعداد الخوادم، وتأمين التطبيقات المستضافة</p>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach ($standards as $i => $standard)
                <div class="col-sm-6 col-lg-4">
                    <article class="security-standard-card animate-on-scroll animate-delay-{{ ($i % 3) + 1 }}"
                        @if (! empty($standard['accent'])) style="--standard-accent: {{ $standard['accent'] }}" @endif>
                        <div class="security-standard-card__icon" aria-hidden="true">
                            @if (($standard['type'] ?? '') === 'img')
                                <img src="{{ $standard['icon'] }}"
                                    alt=""
                                    class="security-standard-card__img"
                                    width="36"
                                    height="36"
                                    loading="lazy"
                                    decoding="async"
                                    onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                                <span class="security-standard-card__fallback" hidden>
                                    <i class="fas fa-certificate"></i>
                                </span>
                            @else
                                <i class="{{ $standard['icon'] }}"></i>
                            @endif
                        </div>
                        <h3 class="security-standard-card__name">{{ $standard['name'] }}</h3>
                        <p class="security-standard-card__desc">{{ $standard['desc'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
