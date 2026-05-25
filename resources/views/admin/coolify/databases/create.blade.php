@extends('admin.layouts.master')
@section('page-title') إضافة قاعدة بيانات @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة قاعدة بيانات</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.databases.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">المحرك *</label>
                    <select name="type" class="form-control" required>
                        @foreach($types as $k => $label)
                        <option value="{{ $k }}" @selected(old('type', $type) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">المشروع *</label>
                        <select name="project_uuid" class="form-control" required>@foreach($projects as $p)<option value="{{ $p['uuid'] ?? '' }}">{{ $p['name'] ?? '' }}</option>@endforeach</select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">السيرفر *</label>
                        <select name="server_uuid" class="form-control" required>@foreach($servers as $s)<option value="{{ $s['uuid'] ?? '' }}">{{ $s['name'] ?? '' }}</option>@endforeach</select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">البيئة *</label><input type="text" name="environment_name" class="form-control" value="{{ old('environment_name', 'production') }}" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
                </div>
                <button type="submit" class="btn btn-primary">إنشاء</button>
                <a href="{{ route('admin.coolify.databases.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection

