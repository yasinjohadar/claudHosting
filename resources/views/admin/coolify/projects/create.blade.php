@extends('admin.layouts.master')
@section('page-title') إضافة مشروع @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة مشروع</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.projects.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
                <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control">{{ old('description') }}</textarea></div>
                <div class="mb-3">
                    <label class="form-label">إنشاء ضمن فريق عميل (اختياري)</label>
                    <select name="user_id" class="form-select">
                        <option value="">— توكن اللوحة الرئيسي —</option>
                        @foreach($clientUsers ?? [] as $u)
                            <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">يظهر العملاء الذين لديهم فريق Coolify مربوط مع توكن API.</div>
                </div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.projects.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
