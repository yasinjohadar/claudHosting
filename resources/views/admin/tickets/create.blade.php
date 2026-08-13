@extends('admin.layouts.master')

@section('page-title')
إنشاء تذكرة
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
                        <a href="{{ route('admin.tickets.index') }}">التذاكر</a>
                        <span class="text-muted mx-1">/</span>
                        <span>إنشاء</span>
                    </nav>
                    <h1 class="domain-page-hero__title">إنشاء تذكرة جديدة</h1>
                    <p class="text-muted small mb-0">فتح تذكرة دعم محلية وربطها بالعميل المختار.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> العودة للقائمة
                    </a>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.tickets.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-headphones"></i></span>
                            <h2 class="domain-panel__title">بيانات التذكرة</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="customer_id" class="domain-form-label">العميل <span class="text-danger">*</span></label>
                                    <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                        <option value="">— اختر العميل —</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id', $customer_id ?? null) == $customer->id)>
                                                {{ $customer->fullname ?? trim($customer->firstname.' '.$customer->lastname) }} ({{ $customer->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="deptid" class="domain-form-label">القسم <span class="text-danger">*</span></label>
                                    <select class="form-select @error('deptid') is-invalid @enderror" id="deptid" name="deptid" required>
                                        <option value="">— اختر القسم —</option>
                                        <option value="1" data-department="المبيعات" @selected(old('deptid') == '1')>المبيعات</option>
                                        <option value="2" data-department="الدعم الفني" @selected(old('deptid') == '2')>الدعم الفني</option>
                                        <option value="3" data-department="الفوترة" @selected(old('deptid') == '3')>الفوترة</option>
                                        <option value="4" data-department="أخرى" @selected(old('deptid') == '4')>أخرى</option>
                                    </select>
                                    <input type="hidden" name="department" id="department" value="{{ old('department', 'الدعم الفني') }}">
                                    @error('deptid')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="priority" class="domain-form-label">الأولوية <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        <option value="">— اختر الأولوية —</option>
                                        <option value="Low" @selected(old('priority') === 'Low')>منخفضة</option>
                                        <option value="Medium" @selected(old('priority', 'Medium') === 'Medium')>متوسطة</option>
                                        <option value="High" @selected(old('priority') === 'High')>عالية</option>
                                        <option value="Urgent" @selected(old('priority') === 'Urgent')>عاجلة</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="urgency" class="domain-form-label">الاستعجال <span class="text-danger">*</span></label>
                                    <select class="form-select @error('urgency') is-invalid @enderror" id="urgency" name="urgency" required>
                                        <option value="">— اختر —</option>
                                        <option value="Low" @selected(old('urgency') === 'Low')>منخفض</option>
                                        <option value="Medium" @selected(old('urgency', 'Medium') === 'Medium')>متوسط</option>
                                        <option value="High" @selected(old('urgency') === 'High')>مرتفع</option>
                                        <option value="Urgent" @selected(old('urgency') === 'Urgent')>عاجل</option>
                                    </select>
                                    @error('urgency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="subject" class="domain-form-label">الموضوع <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject"
                                           value="{{ old('subject') }}" required maxlength="255">
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="message" class="domain-form-label">الرسالة <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="domain-panel mb-3">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                            <h2 class="domain-panel__title">معلومات إضافية</h2>
                        </div>
                        <div class="domain-panel__body">
                            <p class="text-muted small mb-3">سيتم إنشاء التذكرة في نظام الدعم المحلي وربطها بالعميل المختار.</p>
                            <div class="alert alert-info py-2 small mb-0">
                                <i class="fe fe-info me-1"></i>
                                الحقول المميزة بـ <span class="text-danger">*</span> إلزامية قبل الحفظ.
                            </div>
                        </div>
                    </div>

                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-grid"></i></span>
                            <h2 class="domain-panel__title">الأقسام</h2>
                        </div>
                        <div class="domain-panel__body p-0">
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">المبيعات</div>
                                <div class="domain-info-row__value text-muted small">منتجات وخدمات جديدة</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الدعم الفني</div>
                                <div class="domain-info-row__value text-muted small">مشاكل تقنية وخدمات حالية</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الفوترة</div>
                                <div class="domain-info-row__value text-muted small">فواتير ومدفوعات</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">أخرى</div>
                                <div class="domain-info-row__value text-muted small">استفسارات عامة</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="domain-form-actions">
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ التذكرة
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dept = document.getElementById('deptid');
    var departmentInput = document.getElementById('department');
    var priority = document.getElementById('priority');
    var urgency = document.getElementById('urgency');

    function syncDepartment() {
        if (!dept || !departmentInput) return;
        var opt = dept.options[dept.selectedIndex];
        if (opt && opt.dataset.department) {
            departmentInput.value = opt.dataset.department;
        }
    }

    dept?.addEventListener('change', syncDepartment);
    syncDepartment();

    priority?.addEventListener('change', function () {
        if (urgency && !urgency.value && priority.value) {
            urgency.value = priority.value;
        }
    });
});
</script>
@endpush
