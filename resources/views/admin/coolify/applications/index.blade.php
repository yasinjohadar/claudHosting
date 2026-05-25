@extends('admin.layouts.master')
@section('page-title') تطبيقات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>التطبيقات</h4>
            <a href="{{ route('admin.coolify.applications.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة تطبيق</a>
        </div>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>الاسم</th><th>الحالة</th><th>UUID</th><th></th></tr></thead>
                    <tbody>
                    @forelse($applications as $a)
                        @php $uuid = $a['uuid'] ?? ''; @endphp
                        <tr>
                            <td>{{ $a['name'] ?? '—' }}</td>
                            <td>@include('admin.coolify.partials.status-badges', ['item' => $a])</td>
                            <td><code class="small">{{ $uuid }}</code></td>
                            <td><a href="{{ route('admin.coolify.applications.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">لا توجد تطبيقات</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

