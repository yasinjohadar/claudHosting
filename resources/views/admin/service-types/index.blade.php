@extends('admin.layouts.master')

@section('page-title')
أنواع الخدمات
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">أنواع الخدمات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">أنواع الخدمات</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.service-types.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إضافة نوع
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">قائمة الأنواع</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100" id="serviceTypesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>عدد الخدمات</th>
                                <th>الترتيب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serviceTypes as $type)
                            <tr>
                                <td>{{ $type->id }}</td>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->offered_services_count }}</td>
                                <td>{{ $type->sort_order }}</td>
                                <td>
                                    @if($type->is_active)
                                        <span class="badge bg-success-transparent">نشط</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="hstack gap-2 fs-15">
                                        <a href="{{ route('admin.service-types.edit', $type) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <form action="{{ route('admin.service-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف هذا النوع؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد أنواع خدمات</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($serviceTypes->hasPages())
                    <div class="mt-3">{{ $serviceTypes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    if ($.fn.DataTable && $('#serviceTypesTable tbody tr').length > 0 && $('#serviceTypesTable td[colspan]').length === 0) {
        $('#serviceTypesTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json' },
            responsive: true,
            order: [[3, 'asc']]
        });
    }
});
</script>
@endsection
