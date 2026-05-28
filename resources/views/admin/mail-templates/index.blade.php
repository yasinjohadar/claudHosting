@extends('admin.layouts.master')

@section('page-title')
قوالب البريد
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h4 class="mb-0">قوالب البريد</h4>
            <a href="{{ route('admin.mail-templates.create') }}" class="btn btn-primary">قالب جديد</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card custom-card">
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>المفتاح</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td><code>{{ $template->key }}</code></td>
                            <td>{!! $template->is_active ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-secondary">معطل</span>' !!}</td>
                            <td>
                                <a href="{{ route('admin.mail-templates.edit', $template) }}" class="btn btn-sm btn-warning">تعديل</a>
                                <form action="{{ route('admin.mail-templates.destroy', $template) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('حذف القالب؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">لا توجد قوالب</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $templates->links() }}</div>
    </div>
</div>
@endsection
