@extends('client.layouts.master')

@section('page-title')
تذكرة جديدة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <a href="{{ route('client.tickets.index') }}">التذاكر</a>
                    <span class="text-muted mx-1">/</span>
                    <span>إنشاء</span>
                </nav>
                <h4 class="mb-1">فتح تذكرة دعم</h4>
                <p class="text-muted small mb-0">سيتم إرسال طلبك مباشرة إلى لوحة الإدارة للمتابعة.</p>
            </div>
            <a href="{{ route('client.tickets.index') }}" class="btn btn-light btn-sm rounded-pill px-3">
                <i class="fe fe-arrow-right me-1"></i> العودة
            </a>
        </div>

        @if(!$hasCustomerProfile)
            <div class="client-portal-alert client-portal-alert--warn mb-3">
                <span class="client-portal-alert__icon"><i class="fe fe-alert-triangle"></i></span>
                <div>
                    <strong>الحساب غير مربوط بعد</strong>
                    <p class="mb-0 small">لا يمكن فتح تذكرة قبل ربط حسابك بملف العميل.</p>
                </div>
            </div>
        @else
            @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('client.tickets.store') }}" method="POST" class="client-ticket-form" id="client-ticket-create-form">
                @csrf
                <div class="row g-3">
                    <div class="col-xl-8">
                        <div class="client-profile-show__card">
                            <div class="client-profile-show__card-head">
                                <span class="client-profile-show__card-icon client-profile-show__card-icon--blue">
                                    <i class="fe fe-edit-2"></i>
                                </span>
                                <div>
                                    <h3>بيانات التذكرة</h3>
                                    <p>املأ التفاصيل بدقة لتسريع الرد</p>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="deptid" class="form-label">القسم <span class="text-danger">*</span></label>
                                    <select name="deptid" id="deptid" class="form-select @error('deptid') is-invalid @enderror" required>
                                        <option value="">— اختر القسم —</option>
                                        <option value="1" @selected(old('deptid') == '1')>المبيعات</option>
                                        <option value="2" @selected(old('deptid', '2') == '2')>الدعم الفني</option>
                                        <option value="3" @selected(old('deptid') == '3')>الفوترة</option>
                                        <option value="4" @selected(old('deptid') == '4')>أخرى</option>
                                    </select>
                                    @error('deptid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="priority" class="form-label">الأولوية <span class="text-danger">*</span></label>
                                    <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                        <option value="Low" @selected(old('priority') === 'Low')>منخفضة</option>
                                        <option value="Medium" @selected(old('priority', 'Medium') === 'Medium')>متوسطة</option>
                                        <option value="High" @selected(old('priority') === 'High')>عالية</option>
                                        <option value="Urgent" @selected(old('priority') === 'Urgent')>عاجلة</option>
                                    </select>
                                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                                    <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror"
                                        value="{{ old('subject') }}" required maxlength="255">
                                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                    <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror" rows="10">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="client-profile-show__card h-100">
                            <div class="client-profile-show__card-head">
                                <span class="client-profile-show__card-icon client-profile-show__card-icon--violet">
                                    <i class="fe fe-info"></i>
                                </span>
                                <div>
                                    <h3>معلومات</h3>
                                    <p>كيف تعمل التذاكر؟</p>
                                </div>
                            </div>
                            <ul class="client-ticket-hints">
                                <li>التذكرة تُنشأ محليًا وتظهر فورًا لدى الإدارة.</li>
                                <li>يمكنك متابعة مراحل التقدم من صفحة التذكرة.</li>
                                <li>الملاحظات الداخلية للإدارة لا تظهر لك.</li>
                            </ul>
                            <div class="client-portal-alert mt-3 mb-0">
                                <span class="client-portal-alert__icon"><i class="fe fe-check-circle"></i></span>
                                <div class="small mb-0">الحقول المميزة بـ <span class="text-danger">*</span> إلزامية.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="client-profile-show__footer-cta mt-3">
                    <div>
                        <h4>جاهز للإرسال؟</h4>
                        <p class="text-muted small mb-0">سيصلك التحديث عند رد فريق الدعم.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('client.tickets.index') }}" class="btn btn-light btn-sm rounded-pill px-4">إلغاء</a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fe fe-send me-1"></i> إرسال التذكرة
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@include('admin.partials.tinymce-editor', [
    'tinymceSelector' => '#message',
    'tinymceHeight' => 360,
    'tinymceFormId' => 'client-ticket-create-form',
    'tinymceRequiredMessage' => 'يرجى كتابة رسالة التذكرة',
])
@endpush
