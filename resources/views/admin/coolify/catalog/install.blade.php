@extends('admin.layouts.master')
@section('page-title') تثبيت {{ $item['name_ar'] }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="my-4">
            <a href="{{ route('admin.coolify.catalog.show', $slug) }}" class="text-muted small">العودة للتفاصيل</a>
            <h4 class="mt-2">تثبيت: {{ $item['name_ar'] }}</h4>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
                    @foreach([1 => 'المتطلبات', 2 => 'السيرفر والمشروع', 3 => 'التأكيد والإنشاء'] as $n => $label)
                    <span class="badge {{ $step >= $n ? 'bg-primary' : 'bg-light text-muted' }} px-3 py-2">{{ $n }}. {{ $label }}</span>
                    @endforeach
                </div>

                @if($step === 1)
                <div>
                    <h6>تأكد من توفر المتطلبات:</h6>
                    <ul>
                        @foreach($item['requirements'] ?? ['سيرفر Coolify متصل'] as $req)
                        <li>{{ $req }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('admin.coolify.catalog.install', ['slug' => $slug, 'step' => 2]) }}" class="btn btn-primary">التالي</a>
                </div>
                @elseif($step === 2)
                <form method="GET" action="{{ route('admin.coolify.catalog.install', $slug) }}">
                    <input type="hidden" name="step" value="3">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المشروع *</label>
                            <select name="project_uuid" class="form-control" required>
                                @foreach($projects as $p)
                                <option value="{{ $p['uuid'] ?? '' }}">{{ $p['name'] ?? $p['uuid'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">السيرفر *</label>
                            <select name="server_uuid" class="form-control" required>
                                @foreach($servers as $s)
                                <option value="{{ $s['uuid'] ?? '' }}">{{ $s['name'] ?? $s['uuid'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">البيئة *</label>
                            <input type="text" name="environment_name" class="form-control" value="production" required>
                        </div>
                    </div>
                    <a href="{{ route('admin.coolify.catalog.install', ['slug' => $slug, 'step' => 1]) }}" class="btn btn-light">السابق</a>
                    <button type="submit" class="btn btn-primary">التالي</button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.coolify.catalog.install.store', $slug) }}">
                    @csrf
                    <input type="hidden" name="project_uuid" value="{{ request('project_uuid') }}">
                    <input type="hidden" name="server_uuid" value="{{ request('server_uuid') }}">
                    <input type="hidden" name="environment_name" value="{{ request('environment_name', 'production') }}">
                    <div class="alert alert-light border small mb-3">
                        <strong>ملخص:</strong>
                        مشروع <code>{{ request('project_uuid') }}</code> —
                        سيرفر <code>{{ request('server_uuid') }}</code> —
                        بيئة <code>{{ request('environment_name', 'production') }}</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المورد *</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name', $item['coolify_key'] ?? 'resource') }}" placeholder="مثال: {{ $item['coolify_key'] }}-prod">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف (اختياري)</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <a href="{{ route('admin.coolify.catalog.install', array_filter(['slug' => $slug, 'step' => 2, 'project_uuid' => request('project_uuid'), 'server_uuid' => request('server_uuid'), 'environment_name' => request('environment_name')])) }}" class="btn btn-light">السابق</a>
                    <button type="submit" class="btn btn-success"><i class="fe fe-check"></i> إنشاء على Coolify</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
