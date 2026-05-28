@extends('admin.layouts.master')

@section('page-title')
كتالوج الخدمات
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">كتالوج الخدمات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الخدمات</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn d-flex gap-2">
                <a href="{{ route('admin.service-types.index') }}" class="btn btn-light">أنواع الخدمات</a>
                <a href="{{ route('admin.offered-services.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إضافة خدمة
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card custom-card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">نوع الخدمة</label>
                        <select name="service_type_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($serviceTypes as $type)
                                <option value="{{ $type->id }}" @selected(request('service_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            <option value="active" @selected(request('status') === 'active')>نشط</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>غير نشط</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">بحث</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="اسم أو وصف">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">تصفية</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header"><div class="card-title">قائمة الخدمات</div></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap w-100" id="offeredServicesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>النوع</th>
                                <th>السعر</th>
                                <th>مدة التنفيذ</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $service)
                            <tr>
                                <td>{{ $service->id }}</td>
                                <td>{{ $service->name }}</td>
                                <td>{{ $service->serviceType?->name ?? '—' }}</td>
                                <td>{{ $service->formatted_price }}</td>
                                <td>{{ $service->execution_duration ?? ($service->execution_days ? $service->execution_days.' يوم' : '—') }}</td>
                                <td>
                                    @if($service->is_active)
                                        <span class="badge bg-success-transparent">نشط</span>
                                    @else
                                        <span class="badge bg-secondary-transparent">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="hstack gap-2">
                                        <a href="{{ route('admin.offered-services.show', $service) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill"><i class="ri-eye-line"></i></a>
                                        <a href="{{ route('admin.offered-services.edit', $service) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill"><i class="ri-edit-line"></i></a>
                                        <form action="{{ route('admin.offered-services.destroy', $service) }}" method="POST" onsubmit="return confirm('حذف هذه الخدمة؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted">لا توجد خدمات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($services->hasPages())
                    <div class="mt-3">{{ $services->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
