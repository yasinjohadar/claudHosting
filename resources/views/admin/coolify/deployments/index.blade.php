@extends('admin.layouts.master')
@section('page-title') النشرات @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">النشرات</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card mb-4">
            <div class="card-header"><div class="card-title">نشر تطبيق</div></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.deployments.deploy') }}" class="row g-2" onsubmit="return confirm('بدء النشر؟');">
                    @csrf
                    <div class="col-md-6">
                        <select name="uuid" class="form-control" required>
                            <option value="">— اختر تطبيقاً —</option>
                            @foreach($applications as $a)
                            <option value="{{ $a['uuid'] ?? '' }}">{{ $a['name'] ?? $a['uuid'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-check"><input type="checkbox" name="force" value="1" class="form-check-input"> إجبار</label></div>
                    <div class="col-md-2"><input type="text" name="tag" class="form-control" placeholder="tag"></div>
                    <div class="col-md-2"><input type="text" name="pr" class="form-control" placeholder="PR"></div>
                    <div class="col-md-12"><button class="btn btn-primary">نشر</button></div>
                </form>
            </div>
        </div>
        <form method="GET" class="card custom-card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">فلترة حسب التطبيق</label>
                    <select name="application_uuid" class="form-control">
                        <option value="">— الكل —</option>
                        @foreach($applications as $a)
                        <option value="{{ $a['uuid'] ?? '' }}" @selected(($filterApp ?? '') === ($a['uuid'] ?? ''))>{{ $a['name'] ?? $a['uuid'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <input type="text" name="status" class="form-control" value="{{ $statusFilter ?? '' }}" placeholder="failed, finished...">
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">تطبيق</button></div>
                <div class="col-md-2"><a href="{{ route('admin.coolify.deployments.index') }}" class="btn btn-light w-100">إعادة تعيين</a></div>
            </div>
        </form>
        @if($error)<div class="alert alert-danger">{{ $error }}</div>@endif
        <div class="card custom-card">
            <table class="table table-hover mb-0">
                <thead><tr><th>UUID</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse($deployments as $d)
                    @php $uuid = $d['uuid'] ?? $d['deployment_uuid'] ?? ''; @endphp
                    <tr>
                        <td><code class="small">{{ $uuid }}</code></td>
                        <td>@include('admin.coolify.partials.status-badges', ['item' => $d])</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.coolify.deployments.show', $uuid) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            <form action="{{ route('admin.coolify.deployments.cancel', $uuid) }}" method="POST" class="d-inline" onsubmit="return confirm('إلغاء النشر؟');">@csrf<button class="btn btn-sm btn-warning">إلغاء</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">لا توجد نشرات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
