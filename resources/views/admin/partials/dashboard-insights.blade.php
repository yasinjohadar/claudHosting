<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="admin-insight-card card border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fe fe-trending-up me-1 text-primary"></i> الإيرادات الشهرية</span>
                <span class="small text-muted">{{ now()->year }}</span>
            </div>
            <div class="card-body">
                <div id="adminRevenueChart" style="min-height:280px"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-insight-card card border-0 shadow-sm mb-3">
            <div class="card-header">
                <span class="fw-semibold"><i class="fe fe-file-text me-1 text-success"></i> آخر الفواتير</span>
            </div>
            <div class="card-body p-0">
                @forelse($latestInvoices ?? [] as $invoice)
                    <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="admin-feed-item d-block text-decoration-none text-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold small">#{{ $invoice->id }}</div>
                                <div class="text-muted small">{{ $invoice->date?->format('Y-m-d') ?? '—' }}</div>
                            </div>
                            <span class="badge bg-{{ $invoice->status === 'Paid' ? 'success' : 'warning' }}-transparent text-{{ $invoice->status === 'Paid' ? 'success' : 'warning' }}">
                                {{ $invoice->status }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="p-3 text-muted small">لا توجد فواتير</div>
                @endforelse
            </div>
        </div>
        <div class="admin-insight-card card border-0 shadow-sm">
            <div class="card-header">
                <span class="fw-semibold"><i class="fe fe-alert-circle me-1 text-danger"></i> تذاكر عاجلة</span>
            </div>
            <div class="card-body p-0">
                @forelse($urgentTickets ?? [] as $ticket)
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="admin-feed-item d-block text-decoration-none text-body">
                        <div class="fw-semibold small">{{ Str::limit($ticket->subject ?? 'تذكرة', 40) }}</div>
                        <div class="text-muted small">{{ $ticket->priority }} · {{ $ticket->date?->format('Y-m-d') ?? '—' }}</div>
                    </a>
                @empty
                    <div class="p-3 text-muted small">لا تذاكر عاجلة</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const el = document.querySelector('#adminRevenueChart');
    if (!el || typeof ApexCharts === 'undefined') return;

    const labels = @json($monthlyRevenueLabels ?? []);
    const data = @json($monthlyRevenueData ?? []);

    new ApexCharts(el, {
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Cairo, sans-serif' },
        series: [{ name: 'الإيرادات', data: data }],
        xaxis: { categories: labels },
        stroke: { curve: 'smooth', width: 2 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
        },
        colors: ['rgb(var(--primary-rgb, 132, 90, 223))'],
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(0,0,0,0.06)' },
        tooltip: { y: { formatter: v => v.toLocaleString('ar') } }
    }).render();
})();
</script>
@endpush
