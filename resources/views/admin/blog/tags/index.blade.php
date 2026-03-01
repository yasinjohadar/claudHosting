@extends('admin.layouts.master')

@section('page-title')
الوسوم
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">وسوم المدونة</h4>
                <p class="mb-0 text-muted">إدارة وسوم المقالات</p>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.blog.tags.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    إضافة وسم جديد
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card custom-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label mb-1">البحث</label>
                        <input type="text" name="search" class="form-control" placeholder="ابحث بالاسم..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">الترتيب</label>
                        <select name="sort" class="form-select">
                            <option value="">الترتيب الافتراضي</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>الأكثر استخداماً</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search me-1"></i> بحث
                            </button>
                            <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary" title="إعادة تعيين">
                                <i class="bi bi-arrow-clockwise me-1"></i> إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">قائمة الوسوم ({{ $tags->total() }})</div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>الاسم</th>
                                <th>عدد المقالات</th>
                                <th>اللون</th>
                                <th width="120">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tags as $tag)
                            <tr>
                                <td>{{ $loop->iteration + ($tags->currentPage() - 1) * $tags->perPage() }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $tag->color ?? '#6c757d' }}">
                                        #{{ $tag->name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-transparent">
                                        {{ $tag->posts_count }} مقال
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {{ $tag->color ?? '#6c757d' }}">
                                        {{ $tag->color ?? 'غير محدد' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.blog.tags.edit', $tag->id) }}"
                                           class="btn btn-sm btn-primary-light">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.blog.tags.destroy', $tag->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('هل أنت متأكد؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger-light">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-3">لا توجد وسوم</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($tags->hasPages())
            <div class="card-footer">
                {{ $tags->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
