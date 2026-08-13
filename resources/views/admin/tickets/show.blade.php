@extends('admin.layouts.master')

@section('page-title')
تفاصيل التذكرة
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
    $priorityClass = match ($ticket->priority) {
        'High', 'Urgent', 'Critical', 'Emergency' => 'expired',
        'Medium' => 'warning',
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
                        <span dir="ltr">{{ $ticket->tid }}</span>
                    </nav>
                    <h1 class="domain-page-hero__title">{{ $ticket->subject }}</h1>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $ticket->status_name }}</span>
                        <span class="domain-status-badge domain-status-badge--{{ $priorityClass }}">{{ $ticket->priority_name }}</span>
                        <span class="text-muted small">
                            {{ $ticket->department ?? '—' }}
                            · {{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-light btn-sm">
                        <i class="fe fe-arrow-right me-1"></i> القائمة
                    </a>
                    <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-warning btn-sm">
                        <i class="fe fe-edit me-1"></i> تعديل
                    </a>
                    @if ($ticket->status !== 'Closed')
                        <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('إغلاق هذه التذكرة؟');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fe fe-x me-1"></i> إغلاق
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.tickets.reopen', $ticket->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fe fe-refresh-cw me-1"></i> إعادة فتح
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="domain-kpi-grid domain-kpi-grid--3 mb-3">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-hash"></i></span>
                <div>
                    <div class="domain-kpi__label">رقم التذكرة</div>
                    <div class="domain-kpi__value" dir="ltr" style="font-size:1.15rem;">{{ $ticket->tid }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-message-square"></i></span>
                <div>
                    <div class="domain-kpi__label">الردود</div>
                    <div class="domain-kpi__value">{{ $ticket->replies->count() }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-clock"></i></span>
                <div>
                    <div class="domain-kpi__label">آخر رد</div>
                    <div class="domain-kpi__value" style="font-size:1rem;">{{ $ticket->lastreply?->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head">
                        <span class="domain-panel__head-icon"><i class="fe fe-info"></i></span>
                        <h2 class="domain-panel__title">معلومات التذكرة</h2>
                    </div>
                    <div class="domain-panel__body p-0">
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">القسم</div>
                            <div class="domain-info-row__value">{{ $ticket->department ?? '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الأولوية</div>
                            <div class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $priorityClass }}">{{ $ticket->priority_name }}</span>
                            </div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">الحالة</div>
                            <div class="domain-info-row__value">
                                <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">{{ $ticket->status_name }}</span>
                            </div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">تاريخ الإنشاء</div>
                            <div class="domain-info-row__value">{{ $ticket->date?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                        <div class="domain-info-row">
                            <div class="domain-info-row__label">آخر رد</div>
                            <div class="domain-info-row__value">{{ $ticket->lastreply?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="domain-panel h-100">
                    <div class="domain-panel__head domain-panel__head--split">
                        <div class="domain-panel__head-main">
                            <span class="domain-panel__head-icon"><i class="fe fe-user"></i></span>
                            <h2 class="domain-panel__title">العميل</h2>
                        </div>
                        @if($ticket->customer?->user_id)
                            <a href="{{ route('admin.customers.show', $ticket->customer->user_id) }}" class="btn btn-sm btn-primary-light">
                                <i class="fe fe-external-link me-1"></i> ملف العميل
                            </a>
                        @endif
                    </div>
                    <div class="domain-panel__body p-0">
                        @if($ticket->customer)
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الاسم</div>
                                <div class="domain-info-row__value">{{ $ticket->customer->fullname ?? $ticket->customer->full_name }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">البريد</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $ticket->customer->email }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الهاتف</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $ticket->customer->phonenumber ?: '—' }}</div>
                            </div>
                        @else
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">الاسم</div>
                                <div class="domain-info-row__value">{{ $ticket->name ?? '—' }}</div>
                            </div>
                            <div class="domain-info-row">
                                <div class="domain-info-row__label">البريد</div>
                                <div class="domain-info-row__value" dir="ltr">{{ $ticket->email ?? '—' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="domain-panel mb-4">
            <div class="domain-panel__head domain-panel__head--split">
                <div class="domain-panel__head-main">
                    <span class="domain-panel__head-icon"><i class="fe fe-message-square"></i></span>
                    <h2 class="domain-panel__title">محتوى التذكرة</h2>
                </div>
                @if ($ticket->status !== 'Closed')
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addReplyModal">
                            <i class="fe fe-message-square me-1"></i> إضافة رد
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                            <i class="fe fe-file-text me-1"></i> ملاحظة داخلية
                        </button>
                    </div>
                @endif
            </div>
            <div class="domain-panel__body">
                <div class="p-3 rounded mb-4 ticket-rich-content" style="background: var(--domain-soft-bg, #f6f8fb);">{!! \App\Support\Html::safe($ticket->message) !!}</div>

                <h3 class="h6 fw-semibold mb-3">الردود ({{ $ticket->replies->count() }})</h3>
                @forelse ($ticket->replies as $reply)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                            <div>
                                <strong>{{ $reply->name }}</strong>
                                @if ($reply->admin)
                                    <span class="domain-mini-badge domain-mini-badge--yes ms-1">فريق الدعم</span>
                                @else
                                    <span class="domain-mini-badge domain-mini-badge--no ms-1">عميل</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ $reply->date?->format('Y-m-d H:i') }}</small>
                        </div>
                        <div class="text-muted ticket-rich-content">{!! \App\Support\Html::safe($reply->message) !!}</div>
                    </div>
                @empty
                    <p class="text-muted mb-0">لا توجد ردود بعد.</p>
                @endforelse

                @if($ticket->notes->count() > 0)
                    <hr>
                    <h3 class="h6 fw-semibold mb-3">ملاحظات داخلية</h3>
                    @foreach ($ticket->notes as $note)
                        <div class="border rounded p-3 mb-3" style="border-color: #f0c36d !important; background: rgba(240, 195, 109, 0.12);">
                            <div class="d-flex justify-content-between mb-2 flex-wrap gap-2">
                                <strong>{{ $note->admin_name }}</strong>
                                <small class="text-muted">{{ $note->date?->format('Y-m-d H:i') }}</small>
                            </div>
                            <div style="white-space: pre-wrap;">{{ $note->note }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@if ($ticket->status !== 'Closed')
<div class="modal fade" id="addReplyModal" tabindex="-1" aria-labelledby="addReplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReplyModalLabel">إضافة رد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">ملاحظة داخلية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
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
