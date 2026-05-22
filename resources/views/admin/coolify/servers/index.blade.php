@extends('admin.layouts.master')
@section('page-title') سيرفرات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div><h4 class="mb-0">السيرفرات</h4></div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coolify.servers.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة سيرفر</a>
                <a href="{{ route('admin.coolify.hetzner.create') }}" class="btn btn-outline-primary">Hetzner</a>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>الاسم</th><th>IP</th><th>المنفذ</th><th>المستخدم</th><th>UUID</th><th></th></tr></thead>
                        <tbody>
                        @forelse($servers as $s)
                            @php $uuid = $s['uuid'] ?? $s['id'] ?? ''; @endphp
                            <tr>
                                <td>{{ $s['name'] ?? '—' }}</td>
                                <td>{{ $s['ip'] ?? '—' }}</td>
                                <td>{{ $s['port'] ?? 22 }}</td>
                                <td>{{ $s['user'] ?? 'root' }}</td>
                                <td><code class="small">{{ $uuid }}</code></td>
                                <td><a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد سيرفرات</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
