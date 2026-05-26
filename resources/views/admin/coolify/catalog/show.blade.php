@extends('admin.layouts.master')
@section('page-title') {{ $item['name_ar'] }} @stop
@section('content')
@include('admin.coolify.catalog.partials.flow-styles')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.partials.alerts')

        @include('admin.coolify.catalog.partials.hero', [
            'item' => $item,
            'backUrl' => route('admin.coolify.catalog.index'),
            'backLabel' => 'العودة للكتالوج',
        ])

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="catalog-panel mb-4">
                    <div class="catalog-panel__head">
                        <div class="catalog-panel__head-icon"><i class="fe fe-list"></i></div>
                        <div>
                            <div class="fw-semibold">خطوات التثبيت</div>
                            <div class="text-muted small">ما سيحدث عند إنشاء المورد على Coolify</div>
                        </div>
                    </div>
                    <div class="catalog-panel__body">
                        <ol class="catalog-steps-list">
                            @forelse($item['install_steps'] ?? [] as $installStep)
                            <li>{{ $installStep }}</li>
                            @empty
                            <li>اختر المشروع والسيرفر ثم أكّد الإنشاء.</li>
                            @endforelse
                        </ol>
                    </div>
                </div>

                @if(!empty($item['requirements']))
                <div class="catalog-panel">
                    <div class="catalog-panel__head">
                        <div class="catalog-panel__head-icon"><i class="fe fe-shield"></i></div>
                        <div>
                            <div class="fw-semibold">المتطلبات</div>
                            <div class="text-muted small">تأكد من توفرها قبل البدء</div>
                        </div>
                    </div>
                    <div class="catalog-panel__body">
                        <ul class="catalog-checklist">
                            @foreach($item['requirements'] as $req)
                            <li>
                                <span class="catalog-checklist__icon"><i class="fe fe-check"></i></span>
                                <span>{{ $req }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="catalog-sidebar-card">
                    <div class="catalog-sidebar-meta">
                        <strong>المعرّف</strong><br>
                        <code class="small">{{ $item['coolify_key'] ?? '—' }}</code>
                    </div>
                    <div class="catalog-sidebar-meta">
                        <strong>التصنيف</strong><br>
                        {{ config('coolify_catalog.categories')[$item['category']] ?? $item['category'] }}
                    </div>
                    @if(($item['category'] ?? '') === 'service')
                    <div class="catalog-sidebar-meta">
                        <strong>التوفر</strong><br>
                        @if($item['available_on_coolify'] ?? false)
                        <span class="badge bg-success-transparent text-success mt-1">متاح على Coolify</span>
                        @else
                        <span class="badge bg-secondary-transparent text-secondary mt-1">غير متوفر — نفّذ مزامنة الكتالوج</span>
                        @endif
                    </div>
                    @endif

                    <div class="mt-4 d-grid gap-2">
                        @if(!empty($item['docs_url']))
                        <a href="{{ $item['docs_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                            <i class="fe fe-book-open"></i> توثيق Coolify
                        </a>
                        @endif

                        @if(($item['install_mode'] ?? '') === 'link' && !empty($item['custom_install_url']))
                        <a href="{{ $item['custom_install_url'] }}" target="_blank" rel="noopener" class="btn btn-primary">
                            <i class="fe fe-external-link"></i> فتح الرابط
                        </a>
                        @elseif($canInstall ?? false)
                        @if(($slug ?? '') === 'svc-wordpress')
                        <a href="{{ route('admin.coolify.wordpress-sites.create') }}" class="btn btn-success">
                            <i class="fe fe-globe"></i> إنشاء موقع WordPress كامل
                        </a>
                        <a href="{{ route('admin.coolify.catalog.install', $slug) }}" class="btn btn-outline-primary">
                            <i class="fe fe-download"></i> تثبيت خدمة فقط
                        </a>
                        @else
                        <a href="{{ route('admin.coolify.catalog.install', $slug) }}" class="btn btn-primary btn-lg catalog-btn-next">
                            <i class="fe fe-download me-1"></i> ابدأ التثبيت
                        </a>
                        @endif
                        @else
                        <p class="text-muted small text-center mb-0 py-2">
                            <i class="fe fe-info"></i> هذا المورد للعرض والتوثيق فقط.
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
