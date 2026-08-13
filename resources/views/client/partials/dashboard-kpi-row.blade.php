@php
    $cards = [
        [
            'href' => route('client.services').'#domains',
            'color' => 'purple',
            'icon' => 'fe fe-globe',
            'label' => 'النطاقات',
            'value' => (int) ($summary['domains'] ?? 0),
            'sub' => 'نطاق مرتبط',
            'i' => 0,
        ],
        [
            'href' => ! empty($summary['first_coolify_project_uuid'])
                ? route('client.coolify.projects.show', $summary['first_coolify_project_uuid'])
                : route('client.services'),
            'color' => 'blue',
            'icon' => 'fe fe-layers',
            'label' => 'Coolify',
            'value' => (int) ($summary['projects'] ?? 0),
            'sub' => 'مشروع نشط',
            'i' => 1,
        ],
        [
            'href' => ! empty($summary['first_wordpress_site_uuid'])
                ? route('client.wordpress-sites.show', $summary['first_wordpress_site_uuid'])
                : route('client.services'),
            'color' => 'teal',
            'icon' => 'fe fe-layout',
            'label' => 'WordPress',
            'value' => (int) ($summary['wordpress_sites'] ?? 0),
            'sub' => 'موقع مُدار',
            'i' => 2,
        ],
        [
            'href' => route('client.services').'#hosting',
            'color' => 'orange',
            'icon' => 'fe fe-server',
            'label' => 'الاستضافة',
            'value' => (int) ($summary['hosting'] ?? 0),
            'sub' => 'حساب cPanel',
            'i' => 3,
        ],
        [
            'href' => route('client.invoices.index'),
            'color' => 'green',
            'icon' => 'fe fe-file-text',
            'label' => 'الفواتير',
            'value' => null,
            'sub' => 'عرض فواتيري والدفع',
            'i' => 4,
        ],
    ];
@endphp

<div class="client-kpi-grid" id="clientKpiGrid">
    @foreach($cards as $card)
    <a href="{{ $card['href'] }}" class="admin-kpi-link" style="--kpi-i: {{ $card['i'] }}">
        <div class="admin-kpi-card admin-kpi-card--{{ $card['color'] }}">
            <span class="admin-kpi-card__shine" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--1" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--2" aria-hidden="true"></span>
            <div class="admin-kpi-card-inner">
                <div class="admin-kpi-card__body">
                    <div class="admin-kpi-label">{{ $card['label'] }}</div>
                    @if($card['value'] !== null)
                    <div class="admin-kpi-value" data-kpi-count="{{ $card['value'] }}">0</div>
                    @else
                    <div class="admin-kpi-value" style="font-size:1.15rem">فواتيري</div>
                    @endif
                    <div class="admin-kpi-sub">{{ $card['sub'] }}</div>
                </div>
                <div class="admin-kpi-icon"><i class="{{ $card['icon'] }}"></i></div>
            </div>
            <span class="admin-kpi-card__cta" aria-hidden="true"><i class="fe fe-arrow-left"></i> عرض</span>
        </div>
    </a>
    @endforeach
</div>

<div class="client-quick-links mb-4">
    <a href="{{ route('client.services') }}" class="client-quick-link">
        <span class="client-quick-link__icon"><i class="fe fe-grid"></i></span>
        <span>
            <p class="client-quick-link__title">كل خدماتي</p>
            <p class="client-quick-link__sub">نطاقات، استضافة، WordPress، ومشاريع Coolify في مكان واحد.</p>
        </span>
    </a>
    <a href="{{ route('client.payments.index') }}" class="client-quick-link">
        <span class="client-quick-link__icon" style="background:rgba(20,184,166,0.12);color:#0d9488"><i class="fe fe-credit-card"></i></span>
        <span>
            <p class="client-quick-link__title">سجل المدفوعات</p>
            <p class="client-quick-link__sub">متابعة الدفعات والمعاملات السابقة.</p>
        </span>
    </a>
    <a href="{{ route('client.wordpress-sites.index') }}" class="client-quick-link">
        <span class="client-quick-link__icon" style="background:rgba(14,165,233,0.12);color:#0284c7"><i class="fe fe-globe"></i></span>
        <span>
            <p class="client-quick-link__title">إدارة WordPress</p>
            <p class="client-quick-link__sub">فتح لوحة المواقع والتحكم السريع.</p>
        </span>
    </a>
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
            entry.target.classList.add('is-visible');
            entry.target.querySelectorAll('[data-kpi-count]').forEach(animateCount);
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.35 });

    grid.querySelectorAll('.admin-kpi-link').forEach(function(link) {
        observer.observe(link);
    });
})();
</script>
@endpush
