@php
    $unpaidInvoicesCount = $invoicesByStatusData[1] ?? 0;
    $openTicketsHint = ($urgentTickets?->count() ?? 0) > 0
        ? (($urgentTickets->count()).' عاجلة')
        : 'دعم فني';

    $kpiCards = [
        [
            'url' => route('admin.customers.index'),
            'theme' => 'purple',
            'icon' => 'ri-team-line',
            'label' => 'إجمالي العملاء',
            'value' => (int) ($stats['total_customers'] ?? 0),
            'sub' => number_format($stats['total_users'] ?? 0).' مستخدم في النظام',
        ],
        [
            'url' => route('admin.invoices.index'),
            'theme' => 'green',
            'icon' => 'ri-file-text-line',
            'label' => 'الفواتير',
            'value' => (int) ($stats['total_invoices'] ?? 0),
            'sub' => 'غير مدفوعة: '.number_format($unpaidInvoicesCount).' · إيراد الشهر '.number_format($stats['revenue_monthly'] ?? 0, 0),
        ],
        [
            'url' => route('admin.coolify.overview'),
            'theme' => 'blue',
            'icon' => 'ri-stack-line',
            'label' => 'تطبيقات Coolify',
            'value' => (int) ($coolifyStats['applications'] ?? 0),
            'sub' => number_format($coolifyStats['projects'] ?? 0).' مشروع · '.number_format($coolifyStats['servers'] ?? 0).' سيرفر',
        ],
        [
            'url' => route('admin.tickets.index'),
            'theme' => 'orange',
            'icon' => 'ri-message-3-line',
            'label' => 'التذاكر',
            'value' => (int) ($stats['total_tickets'] ?? 0),
            'sub' => $openTicketsHint,
        ],
    ];
@endphp
<div class="row g-3 mb-4" id="adminKpiGrid">
    @foreach ($kpiCards as $index => $card)
        <div class="col-xl-3 col-lg-6 col-md-6">
            <a href="{{ $card['url'] }}" class="dashboard-stat-link" style="--card-delay: {{ $index * 0.1 }}s">
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
                            <span class="stat-value" data-kpi-count="{{ $card['value'] }}">0</span>
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

@push('scripts')
<script>
(function() {
    const grid = document.getElementById('adminKpiGrid');
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
