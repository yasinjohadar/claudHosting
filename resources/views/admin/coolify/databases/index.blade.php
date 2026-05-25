@extends('admin.layouts.master')
@section('page-title') قواعد بيانات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>قواعد البيانات</h4>
            <a href="{{ route('admin.coolify.databases.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>النوع</th><th>UUID</th><th></th></tr></thead>
                <tbody>
                @forelse($databases as $db)
                    @php $uuid = $db['uuid'] ?? ''; @endphp
                    <tr>
                        <td>{{ $db['name'] ?? '—' }}</td>
                        <td>{{ $db['type'] ?? $db['database_type'] ?? '—' }}</td>
                        <td><code class="small">{{ $uuid }}</code></td>
                        <td><a href="{{ route('admin.coolify.databases.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">لا توجد قواعد بيانات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

