@extends('admin.layouts.master')
@section('page-title') ووردبريس — {{ $account->username }} @stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">مواقع ووردبريس</h4>
                <p class="text-muted small mb-0" dir="ltr">{{ $account->domain }} · {{ $account->username }}</p>
            </div>
            <div class="d-flex gap-2">
                <form method="post" action="{{ route('admin.whm.accounts.wordpress.scan', $account) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm"><i class="fe fe-search me-1"></i> بحث / تحديث</button>
                </form>
                <a href="{{ route('admin.whm.accounts.show', $account) }}" class="btn btn-light btn-sm">رجوع للحساب</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if(!empty($warnings))
            <div class="alert alert-warning">
                <ul class="mb-0">
                    @foreach($warnings as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الموقع</th>
                                <th>المصدر</th>
                                <th>الإصدار</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sites as $site)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $site->display_name }}</div>
                                        <div class="small text-muted" dir="ltr">{{ $site->public_url ?: ($site->path ?: '—') }}</div>
                                    </td>
                                    <td><span class="badge bg-info-transparent">{{ $site->source_label }}</span></td>
                                    <td dir="ltr">{{ $site->wp_version ?: '—' }}</td>
                                    <td><span class="badge bg-{{ $site->status === 'active' ? 'success' : 'secondary' }}-transparent">{{ $site->status_label }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.whm.accounts.wordpress.show', [$account, $site]) }}" class="btn btn-sm btn-primary">إدارة</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">لا توجد مواقع — اضغط بحث / تحديث</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
