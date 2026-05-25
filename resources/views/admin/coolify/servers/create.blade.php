@extends('admin.layouts.master')
@section('page-title') إضافة سيرفر @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة سيرفر Coolify</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.servers.store') }}">
                    @csrf
                    <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="mb-3"><label class="form-label">IP *</label><input type="text" name="ip" class="form-control" value="{{ old('ip') }}" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">المنفذ</label><input type="number" name="port" class="form-control" value="{{ old('port', 22) }}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">المستخدم</label><input type="text" name="user" class="form-control" value="{{ old('user', 'root') }}"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">مفتاح SSH (UUID)</label>
                        <select name="private_key_uuid" class="form-control">
                            <option value="">— اختياري —</option>
                            @foreach($privateKeys as $k)
                                <option value="{{ $k['uuid'] ?? '' }}">{{ $k['name'] ?? $k['uuid'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                    <a href="{{ route('admin.coolify.servers.index') }}" class="btn btn-light">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

