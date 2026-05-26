@php
    $accessLinks = $accessLinks ?? [];
    $primaryUrl = $primaryUrl ?? null;
    $resourceName = $resourceName ?? 'المورد';
    $statusRaw = strtolower((string) ($resourceStatus ?? ''));
    $isRunning = $statusRaw === '' || str_contains($statusRaw, 'running') || str_contains($statusRaw, 'healthy');
    $isExited = str_contains($statusRaw, 'exited') || str_contains($statusRaw, 'unhealthy') || str_contains($statusRaw, 'failed');
    $coolifyPanelUrl = $coolifyPanelUrl ?? null;
@endphp
<style>
    .resource-access-card {
        border-radius: 1rem;
        border: 1px solid rgba(var(--primary-rgb, 132, 90, 223), 0.15);
        background: linear-gradient(135deg, rgba(var(--primary-rgb, 132, 90, 223), 0.06), var(--custom-white, #fff));
        padding: 1.25rem 1.35rem;
        margin-bottom: 1.25rem;
    }
    .resource-access-card__title {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .resource-access-link-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.55rem 0;
        border-bottom: 1px dashed var(--default-border, #e9ecef);
    }
    .resource-access-link-row:last-child { border-bottom: none; }
    .resource-access-link-row a {
        word-break: break-all;
        font-size: 0.85rem;
        direction: ltr;
        text-align: left;
    }
    .resource-access-link-label {
        font-weight: 600;
        font-size: 0.82rem;
        color: var(--default-text-color, #333);
    }
</style>
<div class="resource-access-card">
    <div class="resource-access-card__title">
        <i class="fe fe-external-link text-primary"></i>
        روابط الوصول
    </div>

    @if($isExited && !empty($accessLinks))
    <div class="alert alert-warning py-2 small mb-3 mb-0">
        الحالة الحالية قد تمنع الوصول. جرّب <strong>تشغيل</strong> أو <strong>إعادة النشر / التركيب</strong> ثم افتح الرابط.
    </div>
    @endif

    @if($primaryUrl)
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <a href="{{ $primaryUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
            <i class="fe fe-external-link me-1"></i> فتح {{ $resourceName }}
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-copy-url="{{ $primaryUrl }}" onclick="navigator.clipboard.writeText(this.dataset.copyUrl); this.textContent='تم النسخ';">
            <i class="fe fe-copy"></i> نسخ الرابط
        </button>
        @if(!$isRunning)
        <span class="badge bg-secondary-transparent text-secondary">قد لا يعمل الآن</span>
        @else
        <span class="badge bg-success-transparent text-success">جاهز</span>
        @endif
    </div>
    @endif

    @if(count($accessLinks) > 0)
    <div class="small text-muted mb-2">جميع الروابط (مثل تبويب Links في Coolify)</div>
    @foreach($accessLinks as $link)
    <div class="resource-access-link-row">
        <div>
            <span class="resource-access-link-label">{{ $link['label'] }}</span>
            <span class="badge bg-light text-muted ms-1" style="font-size:0.65rem">{{ $link['kind'] ?? 'link' }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ $link['url'] }}" target="_blank" rel="noopener">{{ $link['url'] }}</a>
            <button type="button" class="btn btn-link btn-sm p-0 text-muted" title="نسخ"
                data-copy-url="{{ $link['url'] }}"
                onclick="navigator.clipboard.writeText(this.dataset.copyUrl);">
                <i class="fe fe-copy"></i>
            </button>
        </div>
    </div>
    @endforeach
    @else
    <p class="text-muted small mb-2">
        لم يُعثر على روابط عامة في بيانات Coolify لهذا المورد.
        @if($isExited)
        قد تظهر الروابط بعد إعادة النشر وتشغيل الحاويات.
        @endif
    </p>
    @if($coolifyPanelUrl)
    <a href="{{ $coolifyPanelUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
        <i class="fe fe-monitor"></i> فتح في لوحة Coolify
    </a>
    @endif
    @endif
</div>
