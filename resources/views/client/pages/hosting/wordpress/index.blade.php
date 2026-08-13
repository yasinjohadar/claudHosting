@extends('client.layouts.master')

@section('page-title')
ووردبريس — {{ $account->domain }}
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.services') }}#hosting">الاستضافة</a>
                    <span class="text-muted mx-1">/</span>
                    <span>ووردبريس</span>
                </nav>
                <h4 class="mb-1">مواقع ووردبريس</h4>
                <p class="text-muted small mb-0" dir="ltr">{{ $account->domain }} · {{ $account->username }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="{{ route('client.hosting.wordpress.scan', $account) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fe fe-search me-1"></i> بحث / تحديث
                    </button>
                </form>
                <a href="{{ route('client.services') }}#hosting" class="btn btn-light btn-sm rounded-pill">رجوع</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info py-2">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if(!empty($warnings))
            <div class="alert alert-warning py-2">
                <div class="fw-semibold mb-1">ملاحظات الاكتشاف</div>
                <ul class="mb-0 small pe-3">
                    @foreach($warnings as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="client-services-panel">
            <div class="client-services-panel-head">
                <h2 class="client-services-panel-head__title"><i class="fab fa-wordpress"></i> المواقع المكتشفة</h2>
                <span class="client-services-panel-head__meta">{{ $sites->count() }} موقع</span>
            </div>

            @if($sites->isEmpty())
                @include('client.partials.services-empty', [
                    'icon' => 'fe-layout',
                    'message' => 'لا توجد مواقع ووردبريس مكتشفة. اضغط «بحث / تحديث» للمسح عبر Softaculous و WP Toolkit والمجلدات.',
                ])
            @else
                <div class="client-services-grid client-services-grid--cols-5">
                    <div class="client-services-grid__row client-services-grid__row--head">
                        <div class="client-services-grid__cell">الموقع</div>
                        <div class="client-services-grid__cell">المصدر</div>
                        <div class="client-services-grid__cell">الإصدار</div>
                        <div class="client-services-grid__cell">الحالة</div>
                        <div class="client-services-grid__cell">إجراء</div>
                    </div>
                    @foreach($sites as $site)
                        <div class="client-services-grid__row">
                            <div class="client-services-grid__cell">
                                <div class="fw-semibold">{{ $site->display_name }}</div>
                                @if($site->public_url)
                                    <a href="{{ $site->public_url }}" class="client-services-link small" dir="ltr" target="_blank" rel="noopener">{{ $site->public_url }}</a>
                                @elseif($site->path)
                                    <code class="small text-muted" dir="ltr">{{ $site->path }}</code>
                                @endif
                            </div>
                            <div class="client-services-grid__cell">
                                <span class="badge bg-info-transparent">{{ $site->source_label }}</span>
                            </div>
                            <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                {{ $site->wp_version ?: '—' }}
                            </div>
                            <div class="client-services-grid__cell">
                                <span class="badge bg-{{ $site->status === 'active' ? 'success' : 'secondary' }}-transparent">{{ $site->status_label }}</span>
                            </div>
                            <div class="client-services-grid__cell">
                                <a href="{{ route('client.hosting.wordpress.show', [$account, $site]) }}" class="btn btn-primary btn-sm rounded-pill px-3">إدارة</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
