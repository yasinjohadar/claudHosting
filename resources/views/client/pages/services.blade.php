@extends('client.layouts.master')

@section('page-title')
الخدمات
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
@php
    $domainCount = $domains->count();
    $projectCount = count($projects);
    $hostingCount = $hosting->count();
    $totalServices = $domainCount + $projectCount + $hostingCount;
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">خدماتي</h4>
                <p class="text-muted small mb-0">كل الخدمات المرتبطة بحسابك في مكان واحد.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <span class="client-stat-pill text-primary">
                    <i class="fe fe-layers"></i>{{ $totalServices }} خدمة
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-globe text-primary"></i>{{ $domainCount }} نطاق
                </span>
                <span class="client-stat-pill">
                    <i class="fe fe-server text-warning"></i>{{ $hostingCount }} استضافة
                </span>
            </div>
        </div>

        <div class="card custom-card client-services-card">
            <div class="card-header client-services-card__header">
                <ul class="nav nav-tabs client-services-tabs card-header-tabs flex-nowrap overflow-auto" id="servicesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-domains-btn" data-bs-toggle="tab" data-bs-target="#pane-domains"
                            type="button" role="tab" aria-controls="pane-domains" aria-selected="true" data-hash="domains">
                            <i class="fe fe-globe me-1"></i>النطاقات
                            <span class="badge bg-primary-transparent ms-1">{{ $domainCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-projects-btn" data-bs-toggle="tab" data-bs-target="#pane-projects"
                            type="button" role="tab" aria-controls="pane-projects" aria-selected="false" data-hash="projects">
                            <i class="fe fe-layers me-1"></i>Coolify
                            <span class="badge bg-secondary-transparent ms-1">{{ $projectCount }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-hosting-btn" data-bs-toggle="tab" data-bs-target="#pane-hosting"
                            type="button" role="tab" aria-controls="pane-hosting" aria-selected="false" data-hash="hosting">
                            <i class="fe fe-server me-1"></i>cPanel
                            <span class="badge bg-warning-transparent ms-1">{{ $hostingCount }}</span>
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="servicesTabContent">
                    {{-- النطاقات --}}
                    <div class="tab-pane fade show active" id="pane-domains" role="tabpanel" aria-labelledby="tab-domains-btn" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover client-services-table mb-0">
                                <thead>
                                    <tr>
                                        <th>النطاق</th>
                                        <th>المصادر</th>
                                        <th>الانتهاء</th>
                                        <th class="text-end">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($domains as $row)
                                        <tr>
                                            <td class="fw-semibold" dir="ltr">{{ $row['display_name'] ?? $row['name'] }}</td>
                                            <td>
                                                @foreach($row['sources'] ?? [] as $src)
                                                    <span class="badge {{ $src['badge'] ?? 'bg-secondary-transparent' }} me-1">{{ $src['label'] }}</span>
                                                @endforeach
                                            </td>
                                            <td class="text-muted">{{ $row['expires_formatted'] ?? '—' }}</td>
                                            <td class="text-end">
                                                <span class="badge {{ $row['status_badge'] ?? 'bg-secondary-transparent' }}">{{ $row['status_label'] ?? '—' }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="client-empty-state">لا توجد نطاقات مرتبطة بحسابك.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Coolify --}}
                    <div class="tab-pane fade" id="pane-projects" role="tabpanel" aria-labelledby="tab-projects-btn" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover client-services-table mb-0">
                                <thead>
                                    <tr>
                                        <th>المشروع</th>
                                        <th>المعرّف</th>
                                        <th class="text-end">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($projects as $proj)
                                        <tr>
                                            <td class="fw-semibold">{{ $proj['name'] }}</td>
                                            <td><span class="client-uuid-chip" dir="ltr" title="{{ $proj['uuid'] }}">{{ $proj['uuid'] }}</span></td>
                                            <td class="text-end"><span class="badge bg-success-transparent">مرتبط</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="client-empty-state">لا توجد مشاريع Coolify مرتبطة بحسابك.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- cPanel --}}
                    <div class="tab-pane fade" id="pane-hosting" role="tabpanel" aria-labelledby="tab-hosting-btn" tabindex="0">
                        <div class="table-responsive">
                            <table class="table table-hover client-services-table mb-0">
                                <thead>
                                    <tr>
                                        <th>النطاق</th>
                                        <th>المستخدم</th>
                                        <th>الباقة</th>
                                <th>البريد</th>
                                <th>نهاية الاشتراك</th>
                                <th class="text-end">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($hosting as $acc)
                                        <tr>
                                            <td dir="ltr">
                                                @if($url = $acc->site_url)
                                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="fw-semibold text-decoration-none">{{ $acc->domain }}</a>
                                                @else
                                                    <span class="fw-semibold">{{ $acc->domain ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td dir="ltr"><code class="text-muted">{{ $acc->username }}</code></td>
                                            <td>{{ $acc->package ?: '—' }}</td>
                                            <td class="text-muted small" dir="ltr">{{ $acc->display_email ?? '—' }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">
                                                    {{ $acc->status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="client-empty-state">لا توجد حسابات استضافة مرتبطة بحسابك.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabEl = document.getElementById('servicesTabs');
    if (!tabEl || typeof bootstrap === 'undefined') return;

    var hashToTarget = {
        domains: '#pane-domains',
        projects: '#pane-projects',
        hosting: '#pane-hosting'
    };

    function showTabByHash(hash) {
        var key = (hash || '').replace('#', '');
        var target = hashToTarget[key];
        if (!target) return;
        var btn = tabEl.querySelector('[data-bs-target="' + target + '"]');
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    tabEl.addEventListener('shown.bs.tab', function (e) {
        var hash = e.target.getAttribute('data-hash');
        if (hash) {
            history.replaceState(null, '', window.location.pathname + '#' + hash);
        }
    });

    showTabByHash(window.location.hash);
    window.addEventListener('hashchange', function () {
        showTabByHash(window.location.hash);
    });
});
</script>
@endpush
