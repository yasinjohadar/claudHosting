@extends('admin.layouts.master')

@section('page-title')
تفاصيل التذكرة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">

        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">{{ $ticket->subject }}</h4>
                <p class="mb-0 text-muted">رقم التذكرة: {{ $ticket->tid }}</p>
                <nav class="mt-1">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">التذاكر</a></li>
                        <li class="breadcrumb-item active" aria-current="page">تفاصيل</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn d-flex flex-wrap gap-2">
                <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-warning">
                    <i class="fe fe-edit"></i> تعديل
                </a>
                @if ($ticket->status !== 'Closed')
                    <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST" class="d-inline" onsubmit="return confirm('إغلاق هذه التذكرة؟');">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fe fe-x"></i> إغلاق
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.tickets.reopen', $ticket->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="fe fe-refresh-cw"></i> إعادة فتح
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-light">
                    <i class="fe fe-arrow-right"></i> القائمة
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">معلومات التذكرة</div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>القسم:</strong> {{ $ticket->department ?? $ticket->department_name }}</p>
                        <p class="mb-2"><strong>الأولوية:</strong>
                            <span class="badge bg-{{ $ticket->priority_color }}-transparent">{{ $ticket->priority_name }}</span>
                        </p>
                        <p class="mb-2"><strong>الحالة:</strong>
                            <span class="badge bg-{{ $ticket->status_color }}-transparent">{{ $ticket->status_name }}</span>
                        </p>
                        <p class="mb-2"><strong>تاريخ الإنشاء:</strong> {{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}</p>
                        <p class="mb-0"><strong>آخر رد:</strong> {{ $ticket->lastreply?->format('Y-m-d H:i') ?? '—' }}</p>
                    </div>
                </div>

                @if($ticket->customer)
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">العميل</div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>الاسم:</strong> {{ $ticket->customer->fullname }}</p>
                        <p class="mb-2"><strong>البريد:</strong> {{ $ticket->customer->email }}</p>
                        <p class="mb-3"><strong>الهاتف:</strong> {{ $ticket->customer->phonenumber ?? '—' }}</p>
                        <a href="{{ route('admin.customers.show', $ticket->customer->id) }}" class="btn btn-sm btn-info-light w-100">
                            <i class="fe fe-user"></i> ملف العميل
                        </a>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="card-title mb-0">محتوى التذكرة</div>
                        @if ($ticket->status !== 'Closed')
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addReplyModal">
                                    <i class="fe fe-message-square"></i> إضافة رد
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                    <i class="fe fe-file-text"></i> ملاحظة داخلية
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="bg-light rounded p-3 mb-4">
                            {!! nl2br(e($ticket->message)) !!}
                        </div>

                        <h6 class="fw-semibold mb-3">الردود ({{ $ticket->replies->count() }})</h6>
                        @forelse ($ticket->replies as $reply)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ $reply->name }}</strong>
                                        @if ($reply->admin)
                                            <span class="badge bg-primary-transparent ms-1">فريق الدعم</span>
                                        @else
                                            <span class="badge bg-info-transparent ms-1">عميل</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $reply->date?->format('Y-m-d H:i') }}</small>
                                </div>
                                <div class="text-muted">{!! nl2br(e($reply->message)) !!}</div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">لا توجد ردود بعد.</p>
                        @endforelse

                        @if($ticket->notes->count() > 0)
                            <hr>
                            <h6 class="fw-semibold mb-3">ملاحظات داخلية</h6>
                            @foreach ($ticket->notes as $note)
                                <div class="border border-warning rounded p-3 mb-3 bg-warning-transparent">
                                    <div class="d-flex justify-content-between mb-2">
                                        <strong>{{ $note->admin_name }}</strong>
                                        <small class="text-muted">{{ $note->date?->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <div>{!! nl2br(e($note->note)) !!}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($ticket->status !== 'Closed')
<div class="modal fade" id="addReplyModal" tabindex="-1" aria-labelledby="addReplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReplyModalLabel">إضافة رد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.tickets.addReply', $ticket->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label for="reply_message" class="form-label">الرد <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="reply_message" name="message" rows="5" required></textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">ملاحظة داخلية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.tickets.addNote', $ticket->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label for="note_message" class="form-label">الملاحظة <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="note_message" name="message" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
