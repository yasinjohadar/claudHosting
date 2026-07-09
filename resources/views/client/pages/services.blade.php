@extends('client.layouts.master')

@section('page-title')
الخدمات
@stop

@section('content')
@php
    $domainCount = $domains->count();
    $projectCount = count($projects);
    $wordpressCount = $wordpressSites->count();
    $hostingCount = $hosting->count();
    $totalServices = $domainCount + $projectCount + $wordpressCount + $hostingCount;
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="mb-1">خدماتي</h4>
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

        <div class="client-services-shell">
            <div class="client-services-toolbar">
                <ul class="nav client-services-tabs" id="servicesTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active client-services-tab client-services-tab--domains" id="tab-domains-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-domains" type="button" role="tab"
                            aria-controls="pane-domains" aria-selected="true" data-hash="domains">
                            <span class="client-services-tab__icon"><i class="fe fe-globe"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">النطاقات</span>
                                <span class="client-services-tab__count">{{ $domainCount }}</span>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link client-services-tab client-services-tab--coolify" id="tab-projects-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-projects" type="button" role="tab"
                            aria-controls="pane-projects" aria-selected="false" data-hash="projects">
                            <span class="client-services-tab__icon"><i class="fe fe-layers"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">Coolify</span>
                                <span class="client-services-tab__count">{{ $projectCount }}</span>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link client-services-tab client-services-tab--wordpress" id="tab-wordpress-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-wordpress" type="button" role="tab"
                            aria-controls="pane-wordpress" aria-selected="false" data-hash="wordpress">
                            <span class="client-services-tab__icon"><i class="fe fe-layout"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">WordPress</span>
                                <span class="client-services-tab__count">{{ $wordpressCount }}</span>
                            </span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link client-services-tab client-services-tab--hosting" id="tab-hosting-btn"
                            data-bs-toggle="tab" data-bs-target="#pane-hosting" type="button" role="tab"
                            aria-controls="pane-hosting" aria-selected="false" data-hash="hosting">
                            <span class="client-services-tab__icon"><i class="fe fe-server"></i></span>
                            <span class="client-services-tab__text">
                                <span class="client-services-tab__label">cPanel</span>
                                <span class="client-services-tab__count">{{ $hostingCount }}</span>
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content client-services-panels" id="servicesTabContent">
                <div class="tab-pane fade show active" id="pane-domains" role="tabpanel" aria-labelledby="tab-domains-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-globe"></i> النطاقات المرتبطة</h2>
                        <span class="client-services-panel-head__meta">{{ $domainCount }} نطاق</span>
                    </div>
                    @if($domains->isEmpty())
                        @include('client.partials.services-empty', ['icon' => 'fe-globe', 'message' => 'لا توجد نطاقات مرتبطة بحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-4">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">النطاق</div>
                                <div class="client-services-grid__cell">المصادر</div>
                                <div class="client-services-grid__cell">الانتهاء</div>
                                <div class="client-services-grid__cell">الحالة</div>
                            </div>
                            @foreach($domains as $row)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr fw-semibold">{{ $row['display_name'] ?? $row['name'] }}</div>
                                    <div class="client-services-grid__cell">
                                        @foreach($row['sources'] ?? [] as $src)
                                            <span class="badge {{ $src['badge'] ?? 'bg-secondary-transparent' }} me-1">{{ $src['label'] }}</span>
                                        @endforeach
                                    </div>
                                    <div class="client-services-grid__cell text-muted">{{ $row['expires_formatted'] ?? '—' }}</div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge {{ $row['status_badge'] ?? 'bg-secondary-transparent' }}">{{ $row['status_label'] ?? '—' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pane-projects" role="tabpanel" aria-labelledby="tab-projects-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-layers"></i> مشاريع Coolify</h2>
                        <span class="client-services-panel-head__meta">{{ $projectCount }} مشروع</span>
                    </div>
                    @if(empty($projects))
                        @include('client.partials.services-empty', ['icon' => 'fe-layers', 'message' => 'لا توجد مشاريع Coolify مرتبطة بحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-3">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">المشروع</div>
                                <div class="client-services-grid__cell">المعرّف</div>
                                <div class="client-services-grid__cell">الحالة</div>
                            </div>
                            @foreach($projects as $proj)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell fw-semibold">
                                        <a href="{{ route('client.coolify.projects.show', $proj['uuid']) }}" class="client-services-link">
                                            {{ $proj['name'] }}
                                        </a>
                                    </div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        <span class="client-uuid-chip" title="{{ $proj['uuid'] }}">{{ $proj['uuid'] }}</span>
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge bg-success-transparent">مرتبط</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pane-wordpress" role="tabpanel" aria-labelledby="tab-wordpress-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-layout"></i> مواقع WordPress</h2>
                        <span class="client-services-panel-head__meta">{{ $wordpressCount }} موقع</span>
                    </div>
                    @if($wordpressSites->isEmpty())
                        @include('client.partials.services-empty', ['icon' => 'fe-layout', 'message' => 'لا توجد مواقع WordPress مخصصة لحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-4">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">الاسم</div>
                                <div class="client-services-grid__cell">الرابط</div>
                                <div class="client-services-grid__cell">الحالة</div>
                                <div class="client-services-grid__cell">إجراء</div>
                            </div>
                            @foreach($wordpressSites as $site)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell fw-semibold">{{ $site->display_name }}</div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        @if($site->public_url)
                                            <a href="{{ $site->public_url }}" target="_blank" rel="noopener" class="client-services-link small">{{ $site->slug }}</a>
                                        @else
                                            <code class="small">{{ $site->slug }}</code>
                                        @endif
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge bg-{{ $site->status === 'running' ? 'success' : 'secondary' }}-transparent">
                                            {{ \App\Models\CoolifyWordpressSite::STATUSES[$site->status] ?? $site->status }}
                                        </span>
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <a href="{{ route('client.wordpress-sites.show', $site->uuid) }}" class="btn btn-primary btn-sm rounded-pill px-3">إدارة</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pane-hosting" role="tabpanel" aria-labelledby="tab-hosting-btn" tabindex="0">
                    <div class="client-services-panel-head">
                        <h2 class="client-services-panel-head__title"><i class="fe fe-server"></i> حسابات cPanel</h2>
                        <span class="client-services-panel-head__meta">{{ $hostingCount }} حساب</span>
                    </div>
                    @if($hosting->isEmpty())
                        @include('client.partials.services-empty', ['icon' => 'fe-server', 'message' => 'لا توجد حسابات استضافة مرتبطة بحسابك.'])
                    @else
                        <div class="client-services-grid client-services-grid--cols-6">
                            <div class="client-services-grid__row client-services-grid__row--head">
                                <div class="client-services-grid__cell">النطاق</div>
                                <div class="client-services-grid__cell">المستخدم</div>
                                <div class="client-services-grid__cell">الباقة</div>
                                <div class="client-services-grid__cell">البريد</div>
                                <div class="client-services-grid__cell">نهاية الاشتراك</div>
                                <div class="client-services-grid__cell">الحالة</div>
                            </div>
                            @foreach($hosting as $acc)
                                <div class="client-services-grid__row">
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        @if($url = $acc->site_url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="client-services-link fw-semibold">{{ $acc->domain }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $acc->domain ?? '—' }}</span>
                                        @endif
                                    </div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr">
                                        <code class="text-muted">{{ $acc->username }}</code>
                                    </div>
                                    <div class="client-services-grid__cell">{{ $acc->package ?: '—' }}</div>
                                    <div class="client-services-grid__cell client-services-grid__cell--ltr text-muted small">
                                        {{ $acc->display_email ?? '—' }}
                                    </div>
                                    <div class="client-services-grid__cell text-muted small">
                                        {{ $acc->subscription_ends_at?->translatedFormat('j M Y') ?? '—' }}
                                    </div>
                                    <div class="client-services-grid__cell">
                                        <span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">
                                            {{ $acc->status_label }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
        wordpress: '#pane-wordpress',
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
