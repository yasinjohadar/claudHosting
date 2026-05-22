@extends('admin.layouts.master')
@section('page-title') تعديل {{ $site->display_name }} @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">تعديل الموقع</h4>
        @include('admin.coolify.partials.alerts')
        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.coolify.wordpress-sites.update', $uuid) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">اسم الموقع *</label>
                        <input type="text" name="display_name" class="form-control" required value="{{ old('display_name', $site->display_name) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المعرّف الفرعي *</label>
                        <div class="input-group">
                            <input type="text" name="slug" id="editSlug" class="form-control" dir="ltr" required
                                pattern="[a-z0-9]([a-z0-9\-]*[a-z0-9])?"
                                value="{{ old('slug', $site->slug) }}">
                            <span class="input-group-text">.{{ $baseDomain }}</span>
                        </div>
                        <div class="form-text">سيُحدَّث النطاق على Coolify عند تغيير المعرّف الفرعي.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وصف</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $site->description) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                    <a href="{{ route('admin.coolify.wordpress-sites.show', $uuid) }}" class="btn btn-light">إلغاء</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
