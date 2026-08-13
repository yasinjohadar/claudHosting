@extends('client.layouts.master')

@section('page-title')
مواقع WordPress
@stop

@section('content')
@php
    $siteCount = $sites->count();
    $runningCount = $sites->where('status', 'running')->count();
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <span>WordPress</span>
                </nav>
                <h4 class="mb-1">مواقع WordPress</h4>
                <p class="text-muted small mb-0">المواقع المخصصة لحسابك — إدارة كاملة مثل لوحة المدير.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($siteCount > 0)
                <span class="client-stat-pill text-primary">
                    <i class="fe fe-layout"></i>{{ $siteCount }} موقع
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-check-circle text-success"></i>{{ $runningCount }} نشط
                </span>
                @endif
                <a href="{{ route('client.services') }}" class="btn btn-light btn-sm rounded-pill">
                    <i class="fe fe-grid me-1"></i>كل الخدمات
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="client-portal-alert client-portal-alert--success mb-3">
            <span class="client-portal-alert__icon"><i class="fe fe-check-circle"></i></span>
            <div>{{ session('success') }}</div>
        </div>
        @endif
        @if(session('error'))
        <div class="client-portal-alert client-portal-alert--danger mb-3">
            <span class="client-portal-alert__icon"><i class="fe fe-x-circle"></i></span>
            <div>{{ session('error') }}</div>
        </div>
        @endif

        <div class="client-services-shell">
            <div class="client-services-panel-head">
                <h2 class="client-services-panel-head__title">
                    <i class="fe fe-globe"></i> قائمة المواقع
                </h2>
                <span class="client-services-panel-head__meta">{{ $siteCount }} موقع</span>
            </div>

            @if($sites->isEmpty())
                @include('client.partials.services-empty', [
                    'icon' => 'fe-layout',
                    'message' => 'لا توجد مواقع WordPress مخصصة لحسابك بعد.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table client-services-table mb-0">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th class="text-end">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sites as $site)
                            <tr>
                                <td class="fw-semibold">{{ $site->display_name }}</td>
                                <td dir="ltr">
                                    @if($site->public_url)
                                    <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="client-services-link small">{{ $site->slug }}</a>
                                    @else
                                    <code class="small">{{ $site->slug }}</code>
                                    @endif
                                </td>
                                <td>
                                    @php $st = $site->status; @endphp
                                    <span class="badge bg-{{ $st === 'running' ? 'success' : ($st === 'failed' ? 'danger' : 'secondary') }}-transparent">
                                        {{ \App\Models\CoolifyWordpressSite::STATUSES[$st] ?? $st }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('client.wordpress-sites.show', $site->uuid) }}" class="btn btn-primary btn-sm rounded-pill px-3">
                                        إدارة
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
