@extends('admin.layouts.master')
@section('page-title') مفاتيح SSH @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>مفاتيح SSH</h4>
            <a href="{{ route('admin.coolify.private-keys.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>UUID</th><th></th></tr></thead>
                <tbody>
                @forelse($keys as $k)
                    @php $uuid = $k['uuid'] ?? ''; @endphp
                    <tr>
                        <td>{{ $k['name'] ?? '—' }}</td>
                        <td><code class="small">{{ $uuid }}</code></td>
                        <td><a href="{{ route('admin.coolify.private-keys.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">لا توجد مفاتيح</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

