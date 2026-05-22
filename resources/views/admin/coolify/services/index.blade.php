@extends('admin.layouts.master')
@section('page-title') خدمات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>الخدمات</h4>
            <a href="{{ route('admin.coolify.services.create') }}" class="btn btn-primary"><i class="fe fe-plus"></i> إضافة</a>
        </div>
        @include('admin.coolify.partials.alerts')
        @if(!empty($error))<div class="alert alert-danger">{{ $error }}</div>@endif
        <div class="card custom-card">
            <table class="table table-hover mb-0">
                <thead><tr><th>الاسم</th><th>النوع</th><th>UUID</th><th>إجراءات</th></tr></thead>
                <tbody>
                @forelse($services as $s)
                    @php $uuid = $s['uuid'] ?? ''; @endphp
                    <tr>
                        <td>{{ $s['name'] ?? '—' }}</td>
                        <td>{{ $s['type'] ?? '—' }}</td>
                        <td><code class="small">{{ $uuid }}</code></td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.coolify.services.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            <a href="{{ route('admin.coolify.services.logs', $uuid) }}" class="btn btn-sm btn-outline-secondary">سجلات</a>
                            <form action="{{ route('admin.coolify.services.start', $uuid) }}" method="POST" class="d-inline">@csrf<input type="hidden" name="_return" value="1"><button type="submit" class="btn btn-sm btn-outline-success" title="تشغيل">▶</button></form>
                            <form action="{{ route('admin.coolify.services.stop', $uuid) }}" method="POST" class="d-inline">@csrf<input type="hidden" name="_return" value="1"><button type="submit" class="btn btn-sm btn-outline-warning" title="إيقاف">■</button></form>
                            <form action="{{ route('admin.coolify.services.restart', $uuid) }}" method="POST" class="d-inline">@csrf<input type="hidden" name="_return" value="1"><button type="submit" class="btn btn-sm btn-outline-info" title="إعادة تشغيل">↻</button></form>
                            <form action="{{ route('admin.coolify.services.redeploy', $uuid) }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة نشر compose (pull + up)؟');">@csrf<input type="hidden" name="_return" value="1"><button type="submit" class="btn btn-sm btn-outline-primary" title="إعادة نشر">Deploy</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">لا توجد خدمات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
