@extends('admin.layouts.master')

@section('page-title')
التذاكر
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
                        <span>التذاكر</span>
                    </nav>
                    <h1 class="domain-page-hero__title">قائمة التذاكر</h1>
                    <p class="text-muted small mb-0">متابعة طلبات الدعم — البحث، التصفية، والإغلاق.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> إنشاء تذكرة
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="domain-kpi-grid mb-3">
            <div class="domain-kpi domain-kpi--primary">
                <span class="domain-kpi__icon"><i class="fe fe-headphones"></i></span>
                <div>
                    <div class="domain-kpi__label">إجمالي التذاكر</div>
                    <div class="domain-kpi__value">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--warning">
                <span class="domain-kpi__icon"><i class="fe fe-alert-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">مفتوحة</div>
                    <div class="domain-kpi__value">{{ $stats['open'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--info">
                <span class="domain-kpi__icon"><i class="fe fe-message-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">بانتظار المتابعة</div>
                    <div class="domain-kpi__value">{{ $stats['awaiting'] ?? 0 }}</div>
                </div>
            </div>
            <div class="domain-kpi domain-kpi--success">
                <span class="domain-kpi__icon"><i class="fe fe-check-circle"></i></span>
                <div>
                    <div class="domain-kpi__label">مغلقة</div>
                    <div class="domain-kpi__value">{{ $stats['closed'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="domain-panel domain-search-panel mb-3">
            <div class="domain-panel__head">
                <span class="domain-panel__head-icon"><i class="fe fe-filter"></i></span>
                <h2 class="domain-panel__title">بحث وتصفية</h2>
            </div>
            <div class="domain-panel__body py-2">
                <form method="GET" action="{{ route('admin.tickets.index') }}" class="domain-filter-row">
                    <div class="domain-filter-field domain-filter-field--search">
                        <label class="domain-filter-field__label" for="ticket-search">بحث</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fe fe-search"></i></span>
                            <input type="search" id="ticket-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="رقم، موضوع، عميل، بريد" autocomplete="off">
                        </div>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Open" @selected(request('status') === 'Open')>مفتوحة</option>
                            <option value="Answered" @selected(request('status') === 'Answered')>تم الرد</option>
                            <option value="Customer-Reply" @selected(request('status') === 'Customer-Reply')>رد العميل</option>
                            <option value="In Progress" @selected(request('status') === 'In Progress')>قيد المعالجة</option>
                            <option value="Closed" @selected(request('status') === 'Closed')>مغلقة</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">الأولوية</label>
                        <select name="priority" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="Low" @selected(request('priority') === 'Low')>منخفضة</option>
                            <option value="Medium" @selected(request('priority') === 'Medium')>متوسطة</option>
                            <option value="High" @selected(request('priority') === 'High')>عالية</option>
                            <option value="Urgent" @selected(request('priority') === 'Urgent')>عاجلة</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--compact">
                        <label class="domain-filter-field__label">القسم</label>
                        <select name="department" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            <option value="المبيعات" @selected(request('department') === 'المبيعات')>المبيعات</option>
                            <option value="الدعم الفني" @selected(request('department') === 'الدعم الفني')>الدعم الفني</option>
                            <option value="الفوترة" @selected(request('department') === 'الفوترة')>الفوترة</option>
                            <option value="أخرى" @selected(request('department') === 'أخرى')>أخرى</option>
                        </select>
                    </div>
                    <div class="domain-filter-field domain-filter-field--actions">
                        <label class="domain-filter-field__label d-none d-xl-block">&nbsp;</label>
                        <div class="domain-filter-inline-actions">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <a href="{{ route('admin.tickets.index') }}" class="btn btn-light btn-sm">مسح</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="domain-dns-panel mb-4">
            <div class="domain-dns-panel__head">
                <h2 class="domain-dns-panel__title">
                    <i class="fe fe-list text-primary"></i> قائمة التذاكر
                </h2>
                <span class="domain-dns-count">{{ $tickets->total() }} تذكرة</span>
            </div>
            <div class="table-responsive">
                <table class="domain-dns-table domain-list-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم التذكرة</th>
                            <th>العميل</th>
                            <th>الموضوع</th>
                            <th>القسم</th>
                            <th>الأولوية</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th class="domain-list-table__action text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
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
                            <tr>
                                <td>{{ $ticket->id }}</td>
                                <td dir="ltr"><strong>{{ $ticket->tid ?? $ticket->id }}</strong></td>
                                <td>
                                    @if($ticket->customer)
                                        {{ $ticket->customer->fullname ?? $ticket->customer->full_name }}
                                    @else
                                        {{ $ticket->name ?? '—' }}
                                    @endif
                                </td>
                                <td>{{ Str::limit($ticket->subject, 40) }}</td>
                                <td>{{ $ticket->department ?? '—' }}</td>
                                <td>
                                    <span class="domain-status-badge domain-status-badge--{{ $priorityClass }}">
                                        {{ $ticket->priority_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                                        {{ $ticket->status_name }}
                                    </span>
                                </td>
                                <td>{{ $ticket->date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="domain-list-table__action text-center">
                                    <div class="d-inline-flex gap-1 flex-wrap justify-content-center">
                                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="domain-action-btn" title="عرض">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="domain-action-btn" title="تعديل">
                                            <i class="fe fe-edit"></i>
                                        </a>
                                        @if($ticket->status !== 'Closed')
                                            <button type="button" class="domain-action-btn js-ticket-close" title="إغلاق"
                                                data-url="{{ route('admin.tickets.close', $ticket->id) }}">
                                                <i class="fe fe-x"></i>
                                            </button>
                                        @else
                                            <button type="button" class="domain-action-btn js-ticket-reopen" title="إعادة فتح"
                                                data-url="{{ route('admin.tickets.reopen', $ticket->id) }}">
                                                <i class="fe fe-refresh-cw"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="domain-action-btn domain-action-btn--danger js-ticket-delete" title="حذف"
                                            data-url="{{ route('admin.tickets.destroy', $ticket->id) }}">
                                            <i class="fe fe-trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">لا توجد تذاكر</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tickets->hasPages())
                <div class="p-3 border-top">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>
</div>

<form id="ticket-delete-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
<form id="ticket-status-form" action="" method="POST" class="d-none">
    @csrf
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteForm = document.getElementById('ticket-delete-form');
    var statusForm = document.getElementById('ticket-status-form');

    document.querySelectorAll('.js-ticket-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('هل أنت متأكد من حذف هذه التذكرة؟')) return;
            deleteForm.action = btn.dataset.url;
            deleteForm.submit();
        });
    });

    document.querySelectorAll('.js-ticket-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('هل أنت متأكد من إغلاق هذه التذكرة؟')) return;
            statusForm.action = btn.dataset.url;
            statusForm.submit();
        });
    });

    document.querySelectorAll('.js-ticket-reopen').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('هل أنت متأكد من إعادة فتح هذه التذكرة؟')) return;
            statusForm.action = btn.dataset.url;
            statusForm.submit();
        });
    });
});
</script>
@endpush
