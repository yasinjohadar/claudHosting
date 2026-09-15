@php
    $cards = [
        [
            'href' => route('client.services').'#domains',
            'theme' => 'purple',
            'icon' => 'ri-global-line',
            'label' => 'النطاقات',
            'value' => (int) ($summary['domains'] ?? 0),
            'sub' => 'نطاق مرتبط',
        ],
        [
            'href' => ! empty($summary['first_coolify_project_uuid'])
                ? route('client.coolify.projects.show', $summary['first_coolify_project_uuid'])
                : route('client.services'),
            'theme' => 'blue',
            'icon' => 'ri-cloud-line',
            'label' => 'Coolify',
            'value' => (int) ($summary['projects'] ?? 0),
            'sub' => 'مشروع نشط',
        ],
        [
            'href' => ! empty($summary['first_wordpress_site_uuid'])
                ? route('client.wordpress-sites.show', $summary['first_wordpress_site_uuid'])
                : route('client.services'),
            'theme' => 'teal',
            'icon' => 'ri-wordpress-line',
            'label' => 'WordPress',
            'value' => (int) ($summary['wordpress_sites'] ?? 0),
            'sub' => 'موقع مُدار',
        ],
        [
            'href' => route('client.services').'#hosting',
            'theme' => 'orange',
            'icon' => 'ri-server-line',
            'label' => 'الاستضافة',
            'value' => (int) ($summary['hosting'] ?? 0),
            'sub' => 'حساب cPanel',
        ],
        [
            'href' => route('client.invoices.index'),
            'theme' => 'green',
            'icon' => 'ri-file-text-line',
            'label' => 'الفواتير',
            'value' => null,
            'sub' => 'عرض فواتيري والدفع',
        ],
    ];

    $quickLinks = [
        ['href' => route('client.services'), 'theme' => 'blue', 'icon' => 'ri-grid-line', 'title' => 'كل خدماتي', 'desc' => 'نطاقات، استضافة، WordPress، ومشاريع Coolify.'],
        ['href' => route('client.payments.index'), 'theme' => 'teal', 'icon' => 'ri-bank-card-line', 'title' => 'سجل المدفوعات', 'desc' => 'متابعة الدفعات والمعاملات السابقة.'],
        ['href' => route('client.wordpress-sites.index'), 'theme' => 'cyan', 'icon' => 'ri-wordpress-line', 'title' => 'إدارة WordPress', 'desc' => 'فتح لوحة المواقع والتحكم السريع.'],
    ];
@endphp

<div class="row g-3 mb-4" id="clientKpiGrid">
    @foreach($cards as $index => $card)
        <div class="col-xl col-lg-4 col-md-6">
            <a href="{{ $card['href'] }}" class="dashboard-stat-link" style="--card-delay: {{ $index * 0.1 }}s">
                <div class="dashboard-stat-card dashboard-stat-{{ $card['theme'] }}">
                    <div class="stat-card-shine"></div>
                    <div class="stat-card-mesh"></div>
                    <div class="stat-card-bubble stat-card-bubble-1"></div>
                    <div class="stat-card-bubble stat-card-bubble-2"></div>
                    <div class="stat-card-bubble stat-card-bubble-3"></div>
                    <div class="stat-card-glow"></div>
                    <div class="stat-card-body">
                        <div class="stat-card-content">
                            <span class="stat-label">{{ $card['label'] }}</span>
                            @if($card['value'] !== null)
                                <span class="stat-value" data-kpi-count="{{ $card['value'] }}">0</span>
                            @else
                                <span class="stat-value" style="font-size:1.15rem">فواتيري</span>
                            @endif
                            <span class="stat-subtext">{{ $card['sub'] }}</span>
                        </div>
                        <div class="stat-icon-wrap">
                            <span class="stat-icon-ring"></span>
                            <span class="stat-icon-circle">
                                <i class="{{ $card['icon'] }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="shortcuts-section mb-4">
    <div class="shortcuts-section-header">
        <span class="shortcuts-section-icon"><i class="ri-flashlight-line"></i></span>
        <h5 class="dashboard-section-title mb-0">اختصارات سريعة</h5>
    </div>
    <div class="row g-3 shortcuts-grid">
        @foreach($quickLinks as $i => $link)
            <div class="col-xl-4 col-md-4 col-sm-6 col-12">
                <a href="{{ $link['href'] }}" class="shortcut-card shortcut-theme-{{ $link['theme'] }}" style="--shortcut-delay: {{ $i * 0.05 }}s">
                    <span class="shortcut-shine"></span>
                    <span class="shortcut-accent"></span>
                    <span class="shortcut-icon-wrap">
                        <span class="shortcut-icon-ring"></span>
                        <span class="shortcut-icon">
                            <i class="{{ $link['icon'] }}"></i>
                        </span>
                    </span>
                    <span class="shortcut-title">{{ $link['title'] }}</span>
                    <span class="shortcut-desc">{{ $link['desc'] }}</span>
                    <span class="shortcut-arrow"><i class="ri-arrow-left-s-line"></i></span>
                </a>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
(function() {
    const grid = document.getElementById('clientKpiGrid');
    if (!grid) return;

    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function animateCount(el) {
        const target = parseInt(el.dataset.kpiCount || '0', 10);
        if (prefersReduced || target <= 0) {
            el.textContent = target.toLocaleString('ar-EG');
            return;
        }
        const duration = 900;
        const start = performance.now();
        function tick(now) {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('ar-EG');
            if (p < 1) requestAnimationFrame(tick);
            else el.classList.add('stat-value-done');
        }
        requestAnimationFrame(tick);
    }

    const values = grid.querySelectorAll('[data-kpi-count]');
    if (prefersReduced) {
        values.forEach(function(el) {
            el.textContent = parseInt(el.dataset.kpiCount || '0', 10).toLocaleString('ar-EG');
        });
        return;
    }

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            entry.target.querySelectorAll('[data-kpi-count]').forEach(animateCount);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.35 });

    grid.querySelectorAll('.dashboard-stat-link').forEach(function(link) {
        observer.observe(link);
    });
})();
</script>
@endpush
