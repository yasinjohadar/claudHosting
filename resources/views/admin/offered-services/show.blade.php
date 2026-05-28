@extends('admin.layouts.master')

@section('page-title')
{{ $service->name }}
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">{{ $service->name }}</h4>
                <p class="mb-0 text-muted">{{ $service->serviceType?->name }}</p>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.offered-services.edit', $service) }}" class="btn btn-warning">تعديل</a>
                <a href="{{ route('admin.offered-services.index') }}" class="btn btn-light">القائمة</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">التفاصيل</div></div>
                    <div class="card-body">
                        @if($service->description)
                            <p class="mb-4">{!! nl2br(e($service->description)) !!}</p>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6"><strong>السعر:</strong> {{ $service->formatted_price }}</div>
                            <div class="col-md-6"><strong>مدة التنفيذ:</strong> {{ $service->execution_duration ?? '—' }}</div>
                            @if($service->execution_days)
                                <div class="col-md-6"><strong>أيام التنفيذ:</strong> {{ $service->execution_days }}</div>
                            @endif
                            <div class="col-md-6"><strong>Slug:</strong> <code>{{ $service->slug }}</code></div>
                            <div class="col-md-6">
                                <strong>الحالة:</strong>
                                @if($service->is_active)
                                    <span class="badge bg-success-transparent">نشط</span>
                                @else
                                    <span class="badge bg-secondary-transparent">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">معلومات النظام</div></div>
                    <div class="card-body">
                        <p class="mb-2"><strong>المعرّف:</strong> #{{ $service->id }}</p>
                        <p class="mb-2"><strong>تاريخ الإنشاء:</strong> {{ $service->created_at?->format('Y-m-d H:i') }}</p>
                        <p class="mb-0"><strong>آخر تحديث:</strong> {{ $service->updated_at?->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
