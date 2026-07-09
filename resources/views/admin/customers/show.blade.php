@extends('admin.layouts.master')

@section('page-title')
عميل: {{ $client->name }}
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.customers.index') }}">عملاء الاستضافة</a>
                        <span class="text-muted mx-1">/</span>
                        <span>{{ $client->name }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">{{ $client->name }}</h1>
                    <p class="text-muted small mb-0" dir="ltr">{{ $client->email }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2 customer-actions">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> رجوع
                    </a>
                    <a href="{{ route('users.show', $client->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fe fe-user me-1"></i> ملف المستخدم
                    </a>
                    <a href="{{ route('users.edit', $client->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fe fe-edit me-1"></i> تعديل المستخدم
                    </a>
                    @if(auth()->user()?->isAdminPanelUser() && ! $client->isAdminPanelUser())
                        <button type="button"
                            class="btn btn-warning btn-sm js-impersonate-client"
                            data-url="{{ route('admin.users.impersonation-token', $client) }}"
                            data-name="{{ $client->name }}">
                            <i class="fe fe-log-in me-1"></i> رابط دخول كعميل
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                        <h2 class="domain-panel__title">بيانات العميل</h2>
                    </div>
                    <div class="domain-panel__body p-0">
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">البريد</div>
                            <div class="domain-info-row__value" dir="ltr">{{ $client->email }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الهاتف</div>
                            <div class="domain-info-row__value" dir="ltr">{{ $client->phone ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الشركة</div>
                            <div class="domain-info-row__value">{{ $client->companyname ?: '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">تاريخ التسجيل</div>
                            <div class="domain-info-row__value">{{ $client->created_at?->format('Y-m-d') ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="domain-kpi-grid domain-kpi-grid--3 mb-0 h-100">
                    <div class="domain-kpi domain-kpi--primary">
                        <span class="domain-kpi__icon"><i class="fe fe-server"></i></span>
                        <div>
                            <div class="domain-kpi__label">حساب cPanel</div>
                            <div class="domain-kpi__value">{{ $client->whm_accounts_count }}</div>
                        </div>
                    </div>
                    <div class="domain-kpi domain-kpi--success">
                        <span class="domain-kpi__icon"><i class="fe fe-globe"></i></span>
                        <div>
                            <div class="domain-kpi__label">نطاق</div>
                            <div class="domain-kpi__value">{{ $clientDomains->count() }}</div>
                        </div>
                    </div>
                    <div class="domain-kpi domain-kpi--purple">
                        <span class="domain-kpi__icon"><i class="fe fe-layers"></i></span>
                        <div>
                            <div class="domain-kpi__label">مشروع Coolify</div>
                            <div class="domain-kpi__value">{{ count($clientProjects) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="domain-connection-bar mb-3">
            @if($configured)
                <span class="domain-connection-badge domain-connection-badge--ok"><i class="fe fe-check"></i> WHM متصل</span>
            @else
                <span class="domain-connection-badge domain-connection-badge--warn">WHM غير مضبوط</span>
            @endif
            @if($coolifyConfigured ?? false)
                <span class="domain-connection-badge domain-connection-badge--ok"><i class="fe fe-check"></i> Coolify متصل</span>
            @else
                <span class="domain-connection-badge domain-connection-badge--warn">Coolify غير مضبوط</span>
            @endif
            @if($coolifyTeamLink ?? null)
                <span class="domain-connection-badge domain-connection-badge--info">
                    فريق: {{ $coolifyTeamLink->team_name ?: '#'.$coolifyTeamLink->coolify_team_id }}
                </span>
            @endif
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-server text-primary"></i> حسابات الاستضافة (WHM)
                </h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="domain-dns-count">{{ $client->whmAccounts->count() }} حساب</span>
                    <a href="{{ route('admin.whm.accounts.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-primary-light">عرض في WHM</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>النطاق</th>
                            <th>الباقة</th>
                            <th>الحالة</th>
                            <th class="domain-list-table__action text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($client->whmAccounts as $acc)
                            @php
                                $whmStatusClass = match ($acc->status) {
                                    'active' => 'active',
                                    'suspended' => 'warning',
                                    'terminated' => 'expired',
                                    default => 'info',
                                };
                            @endphp
                            <tr>
                                <td><span class="domain-dns-value">{{ $acc->username }}</span></td>
                                <td dir="ltr">{{ $acc->domain }}</td>
                                <td>{{ $acc->package ?: '—' }}</td>
                                <td>
                                    <span class="domain-status-badge domain-status-badge--{{ $whmStatusClass }}">
                                        {{ $acc->status_label }}
                                    </span>
                                </td>
                                <td class="domain-list-table__action text-center text-nowrap">
                                    <a href="{{ route('admin.whm.accounts.show', $acc) }}" class="domain-action-btn">
                                        <i class="fe fe-eye"></i> عرض
                                    </a>
                                    @if($configured && $acc->status !== 'terminated')
                                        <a href="{{ route('admin.whm.accounts.cpanel', $acc) }}" target="_blank" rel="noopener" class="domain-action-btn domain-action-btn--warning">
                                            <i class="fe fe-external-link"></i> cPanel
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    لا توجد حسابات مرتبطة —
                                    <a href="{{ route('admin.whm.accounts.index') }}">اربط حساباً من قائمة WHM</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="domain-dns-panel">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-globe text-primary"></i> النطاقات
                </h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="domain-dns-count">{{ $clientDomains->count() }} نطاق</span>
                    <a href="{{ route('admin.domains.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-primary-light">مركز النطاقات</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th class="domain-list-table__domain">النطاق</th>
                            <th>المصادر</th>
                            <th>الحالة</th>
                            <th>الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientDomains as $row)
                            @php
                                $domainStatusClass = match ($row['status'] ?? 'active') {
                                    'expired' => 'expired',
                                    'expiring', 'pending' => 'warning',
                                    default => 'active',
                                };
                            @endphp
                            <tr class="{{ ($row['expiring_soon'] ?? false) ? 'domain-list-table__row--warning' : '' }}">
                                <td class="domain-list-table__domain">
                                    <span class="domain-name-link__icon d-inline-flex align-items-center justify-content-center me-1"><i class="fe fe-globe"></i></span>
                                    <strong class="domain-dns-value" dir="ltr">{{ $row['display_name'] }}</strong>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($row['sources'] ?? [] as $src)
                                            <span class="badge {{ $src['badge'] ?? 'bg-secondary-transparent' }} small">{{ $src['label'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <span class="domain-status-badge domain-status-badge--{{ $domainStatusClass }}">{{ $row['status_label'] }}</span>
                                </td>
                                <td class="small {{ ($row['expiring_soon'] ?? false) ? 'domain-expire-soon' : 'text-muted' }}">
                                    {{ $row['expires_formatted'] }}
                                    @if($row['expiring_soon'] ?? false)
                                        <span class="domain-mini-badge domain-mini-badge--warning">قريباً</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    لا نطاقات —
                                    <a href="{{ route('admin.domains.index') }}">اربط من مركز النطاقات</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-users"></i></span>
                            <h2 class="domain-panel__title">فريق Coolify</h2>
                        </div>
                        @if($coolifyConfigured ?? false)
                            <a href="{{ route('admin.coolify.teams.index') }}" class="btn btn-sm btn-light">إدارة الفرق</a>
                        @endif
                    </div>
                    <div class="domain-panel__body">
                        @if($coolifyTeamLink ?? null)
                            <div class="domain-info-row domain-info-row--stack">
                                <div class="domain-info-row__label">الفريق</div>
                                <div class="domain-info-row__value">
                                    <strong>{{ $coolifyTeamLink->team_name ?: 'فريق #'.$coolifyTeamLink->coolify_team_id }}</strong>
                                </div>
                            </div>
                            <div class="domain-info-row domain-info-row--stack">
                                <div class="domain-info-row__label">المعرّف</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $coolifyTeamLink->coolify_team_id }}</div>
                            </div>
                            <div class="domain-info-row domain-info-row--stack">
                                <div class="domain-info-row__label">توكن API</div>
                                <div class="domain-info-row__value">
                                    @if($coolifyTeamLink->hasApiToken())
                                        <span class="domain-status-badge domain-status-badge--active">مضبوط</span>
                                    @else
                                        <span class="domain-status-badge domain-status-badge--warning">مطلوب للتحقق</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('admin.coolify.teams.show', $coolifyTeamLink->coolify_team_id) }}" class="btn btn-sm btn-primary-light mt-2">
                                <i class="fe fe-eye me-1"></i> تفاصيل الفريق
                            </a>
                        @else
                            <p class="text-muted mb-3">لم يُربط فريق Coolify بهذا العميل بعد.</p>
                            @if($coolifyConfigured ?? false)
                                <a href="{{ route('admin.coolify.teams.index') }}" class="btn btn-sm btn-primary">
                                    <i class="fe fe-link me-1"></i> ربط فريق
                                </a>
                            @else
                                <span class="small text-muted">اضبط إعدادات Coolify أولاً.</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="domain-dns-panel mb-0 h-100">
                    <div class="domain-dns-panel__head">
                        <h2 class="domain-dns-panel__title">
                            <i class="fe fe-layers text-primary"></i> مشاريع Coolify
                        </h2>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="domain-dns-count">{{ count($clientProjects) }} مشروع</span>
                            <a href="{{ route('admin.coolify.projects.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-primary-light">قائمة المشاريع</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="domain-dns-table domain-list-table">
                            <thead>
                                <tr>
                                    <th>المشروع</th>
                                    <th>UUID</th>
                                    <th class="domain-list-table__action text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientProjects as $proj)
                                    <tr>
                                        <td><strong>{{ $proj['name'] }}</strong></td>
                                        <td><span class="domain-dns-value">{{ $proj['uuid'] }}</span></td>
                                        <td class="domain-list-table__action text-center">
                                            <a href="{{ route('admin.coolify.projects.show', $proj['uuid']) }}" class="domain-action-btn">
                                                <i class="fe fe-eye"></i> عرض
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            لا مشاريع —
                                            <a href="{{ route('admin.coolify.projects.index') }}">اربط مشروعاً</a>
                                        </td>
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

@include('admin.partials.impersonate-client-modal')
@endsection
