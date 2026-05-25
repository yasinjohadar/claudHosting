@if($overview)
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sysdb-stat-card">
            <div class="sysdb-stat-value text-primary">{{ number_format($overview['table_count'] ?? 0) }}</div>
            <div class="sysdb-stat-label">جدول</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sysdb-stat-card">
            <div class="sysdb-stat-value">{{ $overview['total_size'] ?? '—' }}</div>
            <div class="sysdb-stat-label">الحجم الإجمالي</div>
            <div class="small text-muted mt-1">
                بيانات {{ $overview['data_size'] ?? '—' }} · فهارس {{ $overview['index_size'] ?? '—' }}
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sysdb-stat-card">
            <div class="sysdb-stat-value">{{ number_format($overview['total_rows'] ?? 0) }}</div>
            <div class="sysdb-stat-label">
                صفوف
                @if($overview['rows_approximate'] ?? false)
                    <span class="badge bg-warning-transparent text-warning ms-1">تقريبي</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sysdb-stat-card">
            <div class="sysdb-stat-value" style="font-size:1rem;">{{ strtoupper($overview['driver'] ?? '—') }}</div>
            <div class="sysdb-stat-label">السائق</div>
            <div class="small text-muted mt-1" dir="ltr">{{ $overview['database'] ?? '' }}</div>
        </div>
    </div>
</div>
@endif
