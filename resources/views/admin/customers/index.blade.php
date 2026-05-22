@extends('admin.layouts.master')

@section('page-title')
عملاء الاستضافة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">عملاء الاستضافة</h4>
                <p class="text-muted small mb-0">مستخدمو النظام المسؤولون عن حسابات cPanel — الربط من <a href="{{ route('admin.whm.accounts.index') }}">حسابات WHM</a>.</p>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-user-plus me-1"></i> مستخدم جديد
                </a>
                <a href="{{ route('admin.whm.accounts.index') }}" class="btn btn-outline-primary btn-sm">حسابات WHM</a>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card custom-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">بحث</label>
                        <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="اسم، بريد، هاتف">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">فلتر</label>
                        <select name="has_whm" class="form-select">
                            <option value="">كل المستخدمين</option>
                            <option value="1" @selected(request('has_whm'))>لديه حسابات استضافة فقط</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">بحث</button>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-light">مسح</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>العميل</th>
                            <th>البريد</th>
                            <th class="text-center">حسابات cPanel</th>
                            <th>أحدث الحسابات</th>
                            <th class="text-center">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            <tr>
                                <td class="fw-semibold">{{ $client->name }}</td>
                                <td dir="ltr" class="small">{{ $client->email }}</td>
                                <td class="text-center">
                                    @if($client->whm_accounts_count > 0)
                                        <span class="badge bg-primary-transparent text-primary">{{ $client->whm_accounts_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @forelse($client->whmAccounts as $acc)
                                        <span class="d-inline-block me-2" dir="ltr">{{ $acc->domain }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('admin.customers.show', $client->id) }}" class="btn btn-sm btn-primary-light">ملف العميل</a>
                                    @if(auth()->user()?->isAdminPanelUser() && ! $client->isAdminPanelUser())
                                        <button type="button"
                                            class="btn btn-sm btn-warning-transparent js-impersonate-client"
                                            data-url="{{ route('admin.users.impersonation-token', $client) }}"
                                            data-name="{{ $client->name }}"
                                            title="رابط دخول كعميل">
                                            <i class="fe fe-log-in"></i>
                                        </button>
                                    @endif
                                    @if($client->whm_accounts_count > 0)
                                        <a href="{{ route('admin.whm.accounts.index', ['user_id' => $client->id]) }}" class="btn btn-sm btn-outline-secondary">WHM</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">لا يوجد مستخدمون — أنشئ مستخدماً ثم اربطه من حسابات WHM.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clients->hasPages())
                <div class="card-footer">{{ $clients->links() }}</div>
            @endif
        </div>
    </div>
</div>

@include('admin.partials.impersonate-client-modal')
@endsection
