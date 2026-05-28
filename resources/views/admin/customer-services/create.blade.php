@extends('admin.layouts.master')

@section('page-title')
تسجيل خدمة لعميل
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تسجيل خدمة لعميل</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.customer-services.index') }}">خدمات العملاء</a></li>
                        <li class="breadcrumb-item active">إضافة</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light">العودة</a>
            </div>
        </div>

        <form action="{{ route('admin.customer-services.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header"><div class="card-title">بيانات الخدمة</div></div>
                        <div class="card-body">
                            @include('admin.customer-services.partials.form', [
                                'customers' => $customers,
                                'catalogServices' => $catalogServices,
                                'selectedCustomerId' => $selectedCustomerId,
                                'selectedOfferedServiceId' => $selectedOfferedServiceId,
                            ])
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('admin.customer-services.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="card-body">
                            <p class="text-muted small">بعد الحفظ يمكنك إنشاء فاتورة مرتبطة من صفحة التفاصيل.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
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
@endsection
