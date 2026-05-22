@extends('admin.layouts.master')

@section('page-title')
عميل: {{ $client->name }}
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0">{{ $client->name }}</h4>
                <p class="text-muted small mb-0" dir="ltr">{{ $client->email }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-light btn-sm">رجوع</a>
                @if(auth()->user()?->isAdminPanelUser() && ! $client->isAdminPanelUser())
                    <button type="button"
                        class="btn btn-warning btn-sm js-impersonate-client"
                        data-url="{{ route('admin.users.impersonation-token', $client) }}"
                        data-name="{{ $client->name }}">
                        <i class="fe fe-log-in me-1"></i> رابط دخول كعميل
                    </button>
                @endif
                <a href="{{ route('users.edit', $client->id) }}" class="btn btn-outline-primary btn-sm">تعديل المستخدم</a>
                <a href="{{ route('users.show', $client->id) }}" class="btn btn-outline-secondary btn-sm">ملف المستخدم</a>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-primary">{{ $client->whm_accounts_count }}</div>
                        <div class="text-muted small">حساب cPanel</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-success">{{ $clientDomains->count() }}</div>
                        <div class="text-muted small">نطاق</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card h-100">
                    <div class="card-body text-center">
                        <div class="fs-2 fw-bold text-secondary">{{ count($clientProjects) }}</div>
                        <div class="text-muted small">مشروع Coolify</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">حسابات الاستضافة (WHM)</span>
                <a href="{{ route('admin.whm.accounts.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-primary">عرض في WHM</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المستخدم</th>
                            <th>النطاق</th>
                            <th>الباقة</th>
                            <th>الحالة</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($client->whmAccounts as $acc)
                            <tr>
                                <td><code dir="ltr">{{ $acc->username }}</code></td>
                                <td dir="ltr">{{ $acc->domain }}</td>
                                <td>{{ $acc->package ?: '—' }}</td>
                                <td><span class="badge bg-{{ $acc->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $acc->status_label }}</span></td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('admin.whm.accounts.show', $acc) }}" class="btn btn-sm btn-primary-light">عرض</a>
                                    @if($configured && $acc->status !== 'terminated')
                                        <a href="{{ route('admin.whm.accounts.cpanel', $acc) }}" target="_blank" rel="noopener" class="btn btn-sm btn-warning-transparent">cPanel</a>
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

        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">النطاقات</span>
                <a href="{{ route('admin.domains.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-outline-primary">مركز النطاقات</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>النطاق</th>
                            <th>المصادر</th>
                            <th>الحالة</th>
                            <th>الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientDomains as $row)
                            <tr class="{{ ($row['expiring_soon'] ?? false) ? 'table-warning' : '' }}">
                                <td dir="ltr"><strong>{{ $row['display_name'] }}</strong></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($row['sources'] ?? [] as $src)
                                            <span class="badge {{ $src['badge'] }} small">{{ $src['label'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                                <td class="small">{{ $row['expires_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">لا نطاقات — <a href="{{ route('admin.domains.index') }}">اربط من مركز النطاقات</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">فريق Coolify</span>
                @if($coolifyConfigured ?? false)
                    <a href="{{ route('admin.coolify.teams.index') }}" class="btn btn-sm btn-outline-secondary">إدارة الفرق</a>
                @endif
            </div>
            <div class="card-body">
                @if($coolifyTeamLink ?? null)
                    <p class="mb-2">
                        <strong>{{ $coolifyTeamLink->team_name ?: 'فريق #'.$coolifyTeamLink->coolify_team_id }}</strong>
                        <span class="text-muted">(معرّف {{ $coolifyTeamLink->coolify_team_id }})</span>
                    </p>
                    <p class="mb-2">
                        توكن API:
                        @if($coolifyTeamLink->hasApiToken())
                            <span class="badge bg-success-transparent">مضبوط</span>
                        @else
                            <span class="badge bg-warning-transparent">مطلوب للتحقق من المشاريع</span>
                        @endif
                    </p>
                    <a href="{{ route('admin.coolify.teams.show', $coolifyTeamLink->coolify_team_id) }}" class="btn btn-sm btn-primary-light">تفاصيل الفريق</a>
                @else
                    <p class="text-muted mb-2">لم يُربط فريق Coolify بهذا العميل بعد.</p>
                    @if($coolifyConfigured ?? false)
                        <a href="{{ route('admin.coolify.teams.index') }}" class="btn btn-sm btn-primary">ربط فريق</a>
                    @else
                        <span class="small text-muted">اضبط إعدادات Coolify أولاً.</span>
                    @endif
                @endif
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">مشاريع Coolify</span>
                <a href="{{ route('admin.coolify.projects.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-outline-secondary">قائمة المشاريع</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المشروع</th>
                            <th>UUID</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientProjects as $proj)
                            <tr>
                                <td>{{ $proj['name'] }}</td>
                                <td><code class="small" dir="ltr">{{ $proj['uuid'] }}</code></td>
                                <td class="text-center">
                                    <a href="{{ route('admin.coolify.projects.show', $proj['uuid']) }}" class="btn btn-sm btn-primary-light">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">لا مشاريع — <a href="{{ route('admin.coolify.projects.index') }}">اربط مشروعاً</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.impersonate-client-modal')
@endsection
