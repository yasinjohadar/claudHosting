@extends('admin.layouts.master')
@section('page-title') Hetzner @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>سيرفرات Hetzner (عبر Coolify)</h4>
            <a href="{{ route('admin.coolify.hetzner.create') }}" class="btn btn-primary btn-sm">إنشاء سيرفر</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card mb-3">
            <div class="card-header"><span class="card-title">Cloud Tokens المتاحة</span></div>
            <div class="card-body">
                @forelse($cloudTokens as $t)
                    <span class="badge bg-secondary me-1">{{ $t['name'] ?? $t['uuid'] ?? '—' }}</span>
                @empty
                    <span class="text-muted">لا توجد tokens — <a href="{{ route('admin.coolify.cloud-tokens.create') }}">أضف token</a></span>
                @endforelse
            </div>
        </div>
        <div class="card custom-card">
            <div class="card-header"><span class="card-title">السيرفرات في Coolify</span></div>
            <table class="table mb-0">
                <thead><tr><th>الاسم</th><th>UUID</th><th>IP</th><th></th></tr></thead>
                <tbody>
                @forelse($servers as $s)
                    @php $uuid = $s['uuid'] ?? ''; @endphp
                    <tr>
                        <td>{{ $s['name'] ?? '—' }}</td>
                        <td><code class="small">{{ $uuid }}</code></td>
                        <td dir="ltr">{{ $s['ip'] ?? ($s['settings']['ip'] ?? '—') }}</td>
                        <td><a href="{{ route('admin.coolify.servers.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">لا سيرفرات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


