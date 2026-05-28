@extends('admin.layouts.master')

@section('page-title')
إنشاء تذكرة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إنشاء تذكرة جديدة</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">التذاكر</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إنشاء</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-light">
                    <i class="fe fe-arrow-right"></i> العودة للقائمة
                </a>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.tickets.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">بيانات التذكرة</div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="customer_id" class="form-label">العميل <span class="text-danger">*</span></label>
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
                                    <label for="deptid" class="form-label">القسم <span class="text-danger">*</span></label>
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
                                    <label for="priority" class="form-label">الأولوية <span class="text-danger">*</span></label>
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
                                    <label for="urgency" class="form-label">الاستعجال <span class="text-danger">*</span></label>
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
                                    <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject"
                                           value="{{ old('subject') }}" required maxlength="255">
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> حفظ التذكرة
                            </button>
                            <a href="{{ route('admin.tickets.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">معلومات إضافية</div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">سيتم إنشاء التذكرة في نظام الدعم المحلي وربطها بالعميل المختار.</p>
                            <div class="alert alert-info mb-0">
                                <i class="fe fe-info me-1"></i>
                                الحقول المميزة بـ <span class="text-danger">*</span> إلزامية قبل الحفظ.
                            </div>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">الأقسام</div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>المبيعات:</strong> منتجات وخدمات جديدة</li>
                                <li class="list-group-item"><strong>الدعم الفني:</strong> مشاكل تقنية وخدمات حالية</li>
                                <li class="list-group-item"><strong>الفوترة:</strong> فواتير ومدفوعات</li>
                                <li class="list-group-item"><strong>أخرى:</strong> استفسارات عامة</li>
                            </ul>
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
@endsection
