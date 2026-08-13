@extends('client.layouts.master')

@section('page-title')
التذاكر
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <nav class="client-portal-breadcrumb mb-2">
                    <a href="{{ route('client.dashboard') }}">الرئيسية</a>
                    <span class="text-muted mx-1">/</span>
                    <span>التذاكر</span>
                </nav>
                <h4 class="mb-1">تذاكر الدعم</h4>
                <p class="text-muted small mb-0">افتح طلب دعم وتابع تقدمه — نفس التذاكر تظهر لدى الإدارة.</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if($hasCustomerProfile)
                    <span class="client-stat-pill text-primary"><i class="fe fe-headphones"></i>{{ $stats['total'] }} تذكرة</span>
                    <span class="client-stat-pill"><i class="fe fe-alert-circle text-warning"></i>{{ $stats['open'] }} مفتوحة</span>
                    <span class="client-stat-pill"><i class="fe fe-message-circle text-info"></i>{{ $stats['waiting'] }} بانتظارك</span>
                    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fe fe-plus me-1"></i> تذكرة جديدة
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        @if(!$hasCustomerProfile)
            <div class="client-portal-alert client-portal-alert--warn mb-3">
                <span class="client-portal-alert__icon"><i class="fe fe-alert-triangle"></i></span>
                <div>
                    <strong>الحساب غير مربوط بعد</strong>
                    <p class="mb-0 small">تواصل مع الدعم لربط حسابك بملف العميل حتى تتمكن من فتح التذاكر ومتابعتها.</p>
                </div>
            </div>
        @else
            <div class="client-services-shell mb-3">
                <div class="client-services-panel-head">
                    <h2 class="client-services-panel-head__title">
                        <i class="fe fe-filter"></i> بحث وتصفية
                    </h2>
                </div>
                <div class="p-3">
                    <form method="GET" action="{{ route('client.tickets.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">بحث</label>
                            <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="رقم أو موضوع">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">الحالة</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">الكل</option>
                                <option value="Open" @selected(request('status') === 'Open')>مفتوحة</option>
                                <option value="Answered" @selected(request('status') === 'Answered')>تم الرد</option>
                                <option value="Customer-Reply" @selected(request('status') === 'Customer-Reply')>بانتظار الدعم</option>
                                <option value="In Progress" @selected(request('status') === 'In Progress')>قيد المعالجة</option>
                                <option value="Closed" @selected(request('status') === 'Closed')>مغلقة</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                            <a href="{{ route('client.tickets.index') }}" class="btn btn-light btn-sm">مسح</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="client-services-shell">
                <div class="client-services-panel-head">
                    <h2 class="client-services-panel-head__title">
                        <i class="fe fe-list"></i> قائمة التذاكر
                    </h2>
                    <span class="client-services-panel-head__meta">{{ $tickets->total() }} تذكرة</span>
                </div>

                @if($tickets->isEmpty())
                    @include('client.partials.services-empty', [
                        'icon' => 'fe-headphones',
                        'message' => 'لا توجد تذاكر بعد. افتح تذكرة جديدة للبدء.',
                    ])
                    <div class="text-center pb-4">
                        <a href="{{ route('client.tickets.create') }}" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fe fe-plus me-1"></i> إنشاء تذكرة
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table client-services-table mb-0">
                            <thead>
                                <tr>
                                    <th>الرقم</th>
                                    <th>الموضوع</th>
                                    <th>القسم</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>التقدم</th>
                                    <th>التاريخ</th>
                                    <th class="text-end">إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr>
                                        <td dir="ltr"><strong>{{ $ticket->tid }}</strong></td>
                                        <td>{{ \Illuminate\Support\Str::limit($ticket->subject, 42) }}</td>
                                        <td class="text-muted">{{ $ticket->department ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $ticket->priority_color }}-transparent">{{ $ticket->priority_name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $ticket->status_color }}-transparent">{{ $ticket->status_name }}</span>
                                        </td>
                                        <td style="min-width:120px;">
                                            <div class="client-ticket-progress-mini" title="{{ $ticket->progress_percent }}%">
                                                <div class="client-ticket-progress-mini__bar" style="width: {{ $ticket->progress_percent }}%;"></div>
                                            </div>
                                            <span class="small text-muted">{{ $ticket->progress_percent }}%</span>
                                        </td>
                                        <td class="text-muted">{{ $ticket->date?->format('Y-m-d') ?? '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('client.tickets.show', $ticket) }}" class="btn btn-sm btn-primary-light rounded-pill">
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($tickets->hasPages())
                        <div class="client-portal-pagination p-3">{{ $tickets->links() }}</div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
