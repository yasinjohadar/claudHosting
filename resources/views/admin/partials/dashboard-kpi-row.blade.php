@php
    $unpaidInvoicesCount = $invoicesByStatusData[1] ?? 0;
    $openTicketsHint = ($urgentTickets?->count() ?? 0) > 0
        ? (($urgentTickets->count()).' عاجلة')
        : 'دعم فني';
@endphp
<div class="admin-kpi-grid mb-4" id="adminKpiGrid">
    <a href="{{ route('admin.customers.index') }}" class="admin-kpi-link" style="--kpi-i: 0">
        <div class="admin-kpi-card admin-kpi-card--purple">
            <span class="admin-kpi-card__shine" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--1" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--2" aria-hidden="true"></span>
            <div class="admin-kpi-card-inner">
                <div class="admin-kpi-card__body">
                    <div class="admin-kpi-label">إجمالي العملاء</div>
                    <div class="admin-kpi-value" data-kpi-count="{{ (int) ($stats['total_customers'] ?? 0) }}">0</div>
                    <div class="admin-kpi-sub">{{ number_format($stats['total_users'] ?? 0) }} مستخدم في النظام</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-users"></i></div>
            </div>
            <span class="admin-kpi-card__cta" aria-hidden="true"><i class="fe fe-arrow-left"></i> عرض</span>
        </div>
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="admin-kpi-link" style="--kpi-i: 1">
        <div class="admin-kpi-card admin-kpi-card--green">
            <span class="admin-kpi-card__shine" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--1" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--2" aria-hidden="true"></span>
            <div class="admin-kpi-card-inner">
                <div class="admin-kpi-card__body">
                    <div class="admin-kpi-label">الفواتير</div>
                    <div class="admin-kpi-value" data-kpi-count="{{ (int) ($stats['total_invoices'] ?? 0) }}">0</div>
                    <div class="admin-kpi-sub">غير مدفوعة: {{ number_format($unpaidInvoicesCount) }} · إيراد الشهر {{ number_format($stats['revenue_monthly'] ?? 0, 0) }}</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-file-text"></i></div>
            </div>
            <span class="admin-kpi-card__cta" aria-hidden="true"><i class="fe fe-arrow-left"></i> عرض</span>
        </div>
    </a>
    <a href="{{ route('admin.coolify.overview') }}" class="admin-kpi-link" style="--kpi-i: 2">
        <div class="admin-kpi-card admin-kpi-card--blue">
            <span class="admin-kpi-card__shine" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--1" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--2" aria-hidden="true"></span>
            <div class="admin-kpi-card-inner">
                <div class="admin-kpi-card__body">
                    <div class="admin-kpi-label">تطبيقات Coolify</div>
                    <div class="admin-kpi-value" data-kpi-count="{{ (int) ($coolifyStats['applications'] ?? 0) }}">0</div>
                    <div class="admin-kpi-sub">{{ number_format($coolifyStats['projects'] ?? 0) }} مشروع · {{ number_format($coolifyStats['servers'] ?? 0) }} سيرفر</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-layers"></i></div>
            </div>
            <span class="admin-kpi-card__cta" aria-hidden="true"><i class="fe fe-arrow-left"></i> عرض</span>
        </div>
    </a>
    <a href="{{ route('admin.tickets.index') }}" class="admin-kpi-link" style="--kpi-i: 3">
        <div class="admin-kpi-card admin-kpi-card--orange">
            <span class="admin-kpi-card__shine" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--1" aria-hidden="true"></span>
            <span class="admin-kpi-card__orb admin-kpi-card__orb--2" aria-hidden="true"></span>
            <div class="admin-kpi-card-inner">
                <div class="admin-kpi-card__body">
                    <div class="admin-kpi-label">التذاكر</div>
                    <div class="admin-kpi-value" data-kpi-count="{{ (int) ($stats['total_tickets'] ?? 0) }}">0</div>
                    <div class="admin-kpi-sub">{{ $openTicketsHint }}</div>
                </div>
                <div class="admin-kpi-icon"><i class="fe fe-message-circle"></i></div>
            </div>
            <span class="admin-kpi-card__cta" aria-hidden="true"><i class="fe fe-arrow-left"></i> عرض</span>
        </div>
    </a>
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
