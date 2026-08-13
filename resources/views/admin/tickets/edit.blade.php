@extends('admin.layouts.master')

@section('page-title')
تعديل التذكرة
@stop

@push('styles')
@include('admin.partials.domain-ui-styles')
@endpush

@section('content')
@php
    $statusClass = match ($ticket->status) {
        'Open' => 'expired',
        'Answered' => 'info',
        'Customer-Reply', 'In Progress' => 'warning',
        'Closed' => 'active',
        default => 'info',
    };
@endphp
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
                        <span>تعديل</span>
                    </nav>
                    <h1 class="domain-page-hero__title">تعديل التذكرة</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $ticket->status_name }}</span>
                        <span class="text-muted small" dir="ltr">{{ $ticket->tid }}</span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-info-light btn-sm">
                        <i class="fe fe-eye me-1"></i> عرض
                    </a>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-xl-8">
                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-edit"></i></span>
                            <h2 class="domain-panel__title">بيانات التذكرة</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="deptid" class="domain-form-label">القسم <span class="text-danger">*</span></label>
                                    <select class="form-select @error('deptid') is-invalid @enderror" id="deptid" name="deptid" required>
                                        <option value="1" data-department="المبيعات" @selected(old('deptid', $ticket->deptid) == 1)>المبيعات</option>
                                        <option value="2" data-department="الدعم الفني" @selected(old('deptid', $ticket->deptid) == 2)>الدعم الفني</option>
                                        <option value="3" data-department="الفوترة" @selected(old('deptid', $ticket->deptid) == 3)>الفوترة</option>
                                        <option value="4" data-department="أخرى" @selected(old('deptid', $ticket->deptid) == 4)>أخرى</option>
                                    </select>
                                    <input type="hidden" name="department" id="department" value="{{ old('department', $ticket->department) }}">
                                    @error('deptid')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="domain-form-label">الحالة <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        @foreach (['Open' => 'مفتوحة', 'Answered' => 'تم الرد', 'Customer-Reply' => 'رد العميل', 'In Progress' => 'قيد المعالجة', 'Closed' => 'مغلقة'] as $val => $label)
                                            <option value="{{ $val }}" @selected(old('status', $ticket->status) === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="priority" class="domain-form-label">الأولوية <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        @foreach (['Low' => 'منخفضة', 'Medium' => 'متوسطة', 'High' => 'عالية', 'Urgent' => 'عاجلة'] as $val => $label)
                                            <option value="{{ $val }}" @selected(old('priority', $ticket->priority) === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="urgency" class="domain-form-label">الاستعجال <span class="text-danger">*</span></label>
                                    <select class="form-select @error('urgency') is-invalid @enderror" id="urgency" name="urgency" required>
                                        @foreach (['Low' => 'منخفض', 'Medium' => 'متوسط', 'High' => 'مرتفع', 'Urgent' => 'عاجل'] as $val => $label)
                                            <option value="{{ $val }}" @selected(old('urgency', $ticket->urgency ?? $ticket->priority) === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('urgency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="subject" class="domain-form-label">الموضوع <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject"
                                           value="{{ old('subject', $ticket->subject) }}" required maxlength="255">
                                    @error('subject')
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
                            <h2 class="domain-panel__title">معلومات التذكرة</h2>
                        </div>
                        <div class="domain-panel__body p-0">
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">رقم التذكرة</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $ticket->tid }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">العميل</div>
                                <div class="domain-info-row__value">
                                    @if($ticket->customer)
                                        {{ $ticket->customer->fullname ?? $ticket->customer->full_name }}
                                    @else
                                        {{ $ticket->name ?? '—' }}
                                    @endif
                                </div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">تاريخ الإنشاء</div>
                                <div class="domain-info-row__value">{{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">رقم النظام</div>
                                <div class="domain-info-row__value">#{{ $ticket->id }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="domain-panel">
                        <div class="domain-panel__head">
                            <span class="domain-panel__head-icon"><i class="fe fe-file-text"></i></span>
                            <h2 class="domain-panel__title">نص التذكرة الأصلي</h2>
                        </div>
                        <div class="domain-panel__body">
                            <div class="small text-muted" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap;">{{ $ticket->message }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="domain-form-actions">
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-light btn-sm px-4">إلغاء</a>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="fe fe-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('deptid')?.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    var input = document.getElementById('department');
    if (input && opt?.dataset.department) {
        input.value = opt.dataset.department;
    }
});
</script>
@endpush
