@php
    $category = $item['category'] ?? 'service';
    $iconClass = match ($category) {
        'database' => 'catalog-flow-icon--database',
        'application' => 'catalog-flow-icon--application',
        default => 'catalog-flow-icon--service',
    };
    $categoryLabel = config('coolify_catalog.categories')[$category] ?? $category;
@endphp
<div class="catalog-flow-hero">
    <a href="{{ $backUrl }}" class="catalog-flow-back">
        <i class="fe fe-arrow-right"></i> {{ $backLabel ?? 'العودة' }}
    </a>
    <div class="d-flex flex-wrap align-items-start gap-3 position-relative" style="z-index:1">
        <div class="catalog-flow-icon {{ $iconClass }}">
            <i class="fe {{ $item['icon'] ?? 'fe-box' }}"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h1 class="catalog-flow-title mb-0">{{ $item['name_ar'] }}</h1>
                <span class="catalog-flow-badge bg-primary-transparent text-primary">{{ $categoryLabel }}</span>
                @if(($item['featured'] ?? false))
                <span class="catalog-flow-badge bg-warning-transparent text-warning">مميز</span>
                @endif
            </div>
            @if(!empty($item['description_ar']))
            <p class="catalog-flow-desc mb-0">{{ $item['description_ar'] }}</p>
            @endif
        </div>
    </div>
</div>
