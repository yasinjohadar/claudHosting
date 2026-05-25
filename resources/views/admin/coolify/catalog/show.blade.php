@extends('admin.layouts.master')
@section('page-title') {{ $item['name_ar'] }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4">
            <a href="{{ route('admin.coolify.catalog.index') }}" class="text-muted small"><i class="fe fe-arrow-right"></i> العودة للكتالوج</a>
            <h4 class="mt-2 mb-1"><i class="fe {{ $item['icon'] ?? 'fe-box' }} text-primary me-2"></i>{{ $item['name_ar'] }}</h4>
            <p class="text-muted">{{ $item['description_ar'] }}</p>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title mb-0">خطوات التثبيت</div></div>
                    <div class="card-body">
                        <ol class="mb-0 ps-3">
                            @foreach($item['install_steps'] ?? [] as $step)
                            <li class="mb-2">{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
                @if(!empty($item['requirements']))
                <div class="card custom-card mt-3">
                    <div class="card-header"><div class="card-title mb-0">المتطلبات</div></div>
                    <div class="card-body">
                        <ul class="mb-0">
                            @foreach($item['requirements'] as $req)
                            <li>{{ $req }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <p class="small text-muted mb-2">المعرّف: <code>{{ $item['coolify_key'] ?? '—' }}</code></p>
                        <p class="small text-muted mb-2">التصنيف: {{ config('coolify_catalog.categories')[$item['category']] ?? $item['category'] }}</p>
                        @if(($item['category'] ?? '') === 'service')
                        <p class="mb-3">
                            @if($item['available_on_coolify'] ?? false)
                            <span class="badge bg-success">متاح على Coolify</span>
                            @else
                            <span class="badge bg-secondary">غير متوفر على نسختك — نفّذ مزامنة الكتالوج</span>
                            @endif
                        </p>
                        @endif
                        @if(!empty($item['docs_url']))
                        <a href="{{ $item['docs_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                            <i class="fe fe-book-open"></i> توثيق Coolify
                        </a>
                        @endif
                        @if(($item['install_mode'] ?? '') === 'link' && !empty($item['custom_install_url']))
                        <a href="{{ $item['custom_install_url'] }}" target="_blank" rel="noopener" class="btn btn-primary w-100">
                            <i class="fe fe-external-link"></i> فتح الرابط
                        </a>
                        @elseif($canInstall ?? false)
                        @if(($slug ?? '') === 'svc-wordpress')
                        <a href="{{ route('admin.coolify.wordpress-sites.create') }}" class="btn btn-success w-100 mb-2">
                            <i class="fe fe-globe"></i> إنشاء موقع WordPress كامل
                        </a>
                        <a href="{{ route('admin.coolify.catalog.install', $slug) }}" class="btn btn-outline-primary w-100">
                            <i class="fe fe-download"></i> تثبيت خدمة فقط
                        </a>
                        @else
                        <a href="{{ route('admin.coolify.catalog.install', $slug) }}" class="btn btn-primary w-100">
                            <i class="fe fe-download"></i> ابدأ التثبيت
                        </a>
                        @endif
                        @else
                        <p class="text-muted small mb-0">هذا المورد للعرض والتوثيق فقط.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

