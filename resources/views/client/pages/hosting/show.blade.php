@extends('client.layouts.master')

@section('page-title')
حساب الاستضافة — {{ $account->domain }}
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
                    <span>{{ $account->domain }}</span>
                </nav>
                <h4 class="mb-1" dir="ltr">
                    @if($url = $account->site_url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="client-services-link">{{ $account->domain }}</a>
                    @else
                        {{ $account->domain }}
                    @endif
                </h4>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <code class="text-muted" dir="ltr">{{ $account->username }}</code>
                    <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $account->status_label }}</span>
                    @if($account->display_email)
                        <span class="text-muted small" dir="ltr">{{ $account->display_email }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($account->status === 'active')
                    <a href="{{ route('client.hosting.cpanel', $account) }}" target="_blank" rel="noopener"
                        class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fe fe-external-link me-1"></i> فتح cPanel
                    </a>
                    <a href="{{ route('client.hosting.wordpress.index', $account) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fab fa-wordpress me-1"></i> ووردبريس
                    </a>
                @endif
                <a href="{{ route('client.services') }}#hosting" class="btn btn-light btn-sm rounded-pill">رجوع</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        @if($account->status === 'suspended')
            <div class="alert alert-warning py-2">
                <i class="fe fe-alert-triangle me-1"></i>
                الحساب معلّق حاليًا — البيانات معروضة للقراءة فقط. تواصل مع الدعم لإعادة التفعيل.
            </div>
        @endif

        <div class="card custom-card">
            <div class="card-header border-bottom-0 pb-0">
                <ul class="nav nav-tabs wp-inner-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#client-whm-tab-overview" type="button">نظرة عامة</button>
                    </li>
                    @if($configured)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#client-whm-tab-mail" type="button">البريد</button>
                    </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#client-whm-tab-resources" type="button">موارد</button>
                    </li>
                </ul>
            </div>
            <div class="card-body pt-3">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="client-whm-tab-overview" role="tabpanel">
                        @unless($configured)
                            <div class="alert alert-warning py-2 px-3 small">
                                خدمة الاستضافة غير متصلة حاليًا — بعض البيانات قد لا تظهر. تواصل مع الدعم.
                            </div>
                        @endunless
                        @include('admin.whm.accounts.partials.subscription-panel', [
                            'account' => $account,
                            'invoices' => $invoices,
                            'invoiceRoute' => 'client.invoices.show',
                            'allInvoicesRoute' => 'client.invoices.index',
                            'showTitle' => false,
                        ])
                    </div>

                    @if($configured)
                    <div class="tab-pane fade" id="client-whm-tab-mail" role="tabpanel">
                        @include('admin.whm.accounts.partials.email-deliverability-tab', [
                            'account' => $account,
                            'url' => route('client.hosting.email-deliverability', $account),
                            'embedded' => true,
                            'showTitle' => false,
                            'canWriteDns' => true,
                            'dnsPreviewUrl' => route('client.hosting.mail-dns.preview', $account),
                            'dnsApplyUrl' => route('client.hosting.mail-dns.apply', $account),
                        ])
                    </div>
                    @endif

                    <div class="tab-pane fade" id="client-whm-tab-resources" role="tabpanel">
                        @include('admin.whm.accounts.partials.account-summary', [
                            'account' => $account,
                            'summary' => $summary,
                            'summarySyncedAt' => $summarySyncedAt,
                            'configured' => $configured,
                            'sslBadge' => $sslBadge,
                            'canRefresh' => false,
                            'showTitle' => false,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.whm.accounts.partials.whm-panel-scripts')
@endpush
