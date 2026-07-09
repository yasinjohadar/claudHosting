<div class="my-4 page-header-breadcrumb">
    <nav>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.evolution-api.settings.index') }}">Evolution API</a></li>
            @if(!empty($breadcrumb))
                <li class="breadcrumb-item active">{{ $breadcrumb }}</li>
            @endif
        </ol>
    </nav>
</div>

<div class="group-show-hero dashboard-fade-in mb-4">
    <div class="row align-items-start g-3">
        <div class="col-lg-8">
            <span class="group-show-hero__eyebrow">
                <i class="ri-whatsapp-line me-1"></i>
                Evolution API
            </span>
            <h2 class="group-show-hero__title mb-2">{{ $title ?? 'Evolution API' }}</h2>
            <p class="group-show-hero__desc mb-0">{{ $subtitle ?? 'ربط المنصة مع Evolution API للواتساب' }}</p>
        </div>
        @if(!empty($headerActions))
            <div class="col-lg-4">
                <div class="group-show-actions group-show-actions--single">
                    {!! $headerActions !!}
                </div>
            </div>
        @endif
    </div>
</div>
