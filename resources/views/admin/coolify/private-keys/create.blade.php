@extends('admin.layouts.master')
@section('page-title') إضافة مفتاح SSH @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">إضافة مفتاح SSH</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card"><div class="card-body">
            <form method="POST" action="{{ route('admin.coolify.private-keys.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">الاسم *</label><input type="text" name="name" class="form-control" required value="{{ old('name') }}"></div>
                <div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control">{{ old('description') }}</textarea></div>
                <div class="mb-3"><label class="form-label">المفتاح الخاص *</label><textarea name="private_key" class="form-control" rows="10" required dir="ltr">{{ old('private_key') }}</textarea></div>
                <button type="submit" class="btn btn-primary">حفظ</button>
                <a href="{{ route('admin.coolify.private-keys.index') }}" class="btn btn-light">إلغاء</a>
            </form>
        </div></div>
    </div>
</div>
@endsection
