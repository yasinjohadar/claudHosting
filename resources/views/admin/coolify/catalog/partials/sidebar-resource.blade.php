@php
    $categoryLabel = config('coolify_catalog.categories')[$item['category'] ?? ''] ?? ($item['category'] ?? '—');
@endphp
<div class="catalog-sidebar-card">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="catalog-panel__head-icon">
            <i class="fe {{ $item['icon'] ?? 'fe-box' }}"></i>
        </div>
        <div>
            <div class="fw-bold small">{{ $item['name_ar'] }}</div>
            <div class="text-muted" style="font-size:0.72rem">{{ $categoryLabel }}</div>
        </div>
    </div>
    <div class="catalog-sidebar-meta">
        <strong>المعرّف</strong><br>
        <code class="small">{{ $item['coolify_key'] ?? '—' }}</code>
    </div>
    @if(($item['category'] ?? '') === 'service')
    <div class="catalog-sidebar-meta">
        <strong>الحالة</strong><br>
        @if($item['available_on_coolify'] ?? false)
        <span class="badge bg-success-transparent text-success mt-1">متاح على Coolify</span>
        @else
        <span class="badge bg-secondary-transparent text-secondary mt-1">غير متوفر — نفّذ مزامنة</span>
        @endif
    </div>
    @endif
    @if(!empty($item['docs_url']))
    <a href="{{ $item['docs_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100 mt-3">
        <i class="fe fe-book-open"></i> التوثيق الرسمي
    </a>
    @endif
</div>
