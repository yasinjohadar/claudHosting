@extends('admin.layouts.master')

@section('page-title')
تسجيل خدمة لعميل
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="domain-page-hero">
            <div class="d-md-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <nav class="domain-page-hero__breadcrumb mb-2">
                        <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                        <span class="text-muted mx-1">/</span>
                        <a href="{{ route('admin.customer-services.index') }}">خدمات العملاء</a>
                        <span class="text-muted mx-1">/</span>
                        <span>تسجيل</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تسجيل خدمة لعميل</h1>
                    <p class="text-muted small mb-0">ربط خدمة من الكتالوج بحساب عميل — الأسعار والتواريخ والحالة.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('admin.customer-services.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-briefcase"></i></span>
                            <h2 class="domain-panel__title">بيانات الخدمة</h2>
                        </div>
                        <div class="domain-panel__body">
                            @include('admin.customer-services.partials.form', [
                                'customers' => $customers,
                                'catalogServices' => $catalogServices,
                                'selectedCustomerId' => $selectedCustomerId,
                                'selectedOfferedServiceId' => $selectedOfferedServiceId,
                            ])
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="domain-panel h-100">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">معلومات</h2>
                        </div>
                        <div class="domain-panel__body">
                            <p class="text-muted small mb-3">بعد الحفظ يمكنك إنشاء فاتورة مرتبطة من صفحة التفاصيل.</p>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fe fe-file-text me-1"></i>
                                اختر الخدمة من الكتالوج لتعبئة السعر والعملة تلقائياً.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="domain-form-actions">
                <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('offered_service_id');
    if (!select) return;
    select.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (!opt || !opt.value) return;
        const name = document.getElementById('service_name');
        const price = document.getElementById('service_price');
        const due = document.getElementById('amount_due');
        const currency = document.getElementById('service_currency');
        const duration = document.getElementById('execution_duration');
        const days = document.getElementById('execution_days');
        if (name && !name.value) name.value = opt.dataset.name || '';
        if (price && !price.value) price.value = opt.dataset.price || '';
        if (due && !due.value) due.value = opt.dataset.price || '';
        if (currency) currency.value = opt.dataset.currency || 'SAR';
        if (duration && !duration.value) duration.value = opt.dataset.duration || '';
        if (days && !days.value && opt.dataset.days) days.value = opt.dataset.days;
    });
    if (select.value) select.dispatchEvent(new Event('change'));
});
</script>
@endpush
