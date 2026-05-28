@extends('admin.layouts.master')

@section('page-title')
تعديل التذكرة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تعديل التذكرة</h4>
                <p class="mb-0 text-muted">{{ $ticket->subject }} — {{ $ticket->tid }}</p>
                <nav class="mt-1">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">التذاكر</a></li>
                        <li class="breadcrumb-item active" aria-current="page">تعديل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn d-flex flex-wrap gap-2">
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-info-light">
                    <i class="fe fe-eye"></i> عرض
                </a>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-light">
                    <i class="fe fe-arrow-right"></i> القائمة
                </a>
            </div>
        </div>

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

        <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">بيانات التذكرة</div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="deptid" class="form-label">القسم <span class="text-danger">*</span></label>
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
                                    <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
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
                                    <label for="priority" class="form-label">الأولوية <span class="text-danger">*</span></label>
                                    <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        @foreach (['Low', 'Medium', 'High', 'Urgent'] as $p)
                                            <option value="{{ $p }}" @selected(old('priority', $ticket->priority) === $p)>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="urgency" class="form-label">الاستعجال <span class="text-danger">*</span></label>
                                    <select class="form-select @error('urgency') is-invalid @enderror" id="urgency" name="urgency" required>
                                        @foreach (['Low', 'Medium', 'High', 'Urgent'] as $u)
                                            <option value="{{ $u }}" @selected(old('urgency', $ticket->urgency ?? $ticket->priority) === $u)>{{ $u }}</option>
                                        @endforeach
                                    </select>
                                    @error('urgency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject"
                                           value="{{ old('subject', $ticket->subject) }}" required maxlength="255">
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> حفظ التعديلات
                            </button>
                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">معلومات التذكرة</div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>رقم التذكرة:</strong> {{ $ticket->tid }}</p>
                            <p class="mb-2"><strong>العميل:</strong>
                                @if($ticket->customer)
                                    {{ $ticket->customer->fullname }}
                                @else
                                    {{ $ticket->name }}
                                @endif
                            </p>
                            <p class="mb-2"><strong>تاريخ الإنشاء:</strong> {{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}</p>
                            <p class="mb-0"><strong>رقم النظام:</strong> #{{ $ticket->id }}</p>
                        </div>
                    </div>

                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">نص التذكرة الأصلي</div>
                        </div>
                        <div class="card-body">
                            <div class="bg-light rounded p-3 small" style="max-height: 200px; overflow-y: auto;">
                                {!! nl2br(e($ticket->message)) !!}
                            </div>
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
document.getElementById('deptid')?.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    var input = document.getElementById('department');
    if (input && opt?.dataset.department) {
        input.value = opt.dataset.department;
    }
});
</script>
@endsection
