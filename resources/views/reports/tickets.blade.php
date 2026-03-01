@extends('admin.layouts.master')

@section('page-title', 'تقرير التذاكر')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 text-gray-800">
                    <i class="fas fa-ticket-alt"></i> تقرير التذاكر
                </h1>
                <a href="{{ route('admin.reports.export.tickets', request()->query()) }}" class="btn btn-success">
                    <i class="fas fa-download"></i> تصدير إلى Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="m-0 font-weight-bold">الفلاتر</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">من التاريخ:</label>
                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>
                <div class="form-group mr-2 mb-2">
                    <label class="mr-2">إلى التاريخ:</label>
                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                    >
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">-- اختر الحالة --</option>
                        <option value="Open" {{ ($filters['status'] ?? '') === 'Open' ? 'selected' : '' }}>مفتوح</option>
                        <option value="In Progress" {{ ($filters['status'] ?? '') === 'In Progress' ? 'selected' : '' }}>قيد الإجراء</option>
                        <option value="Closed" {{ ($filters['status'] ?? '') === 'Closed' ? 'selected' : '' }}>مغلق</option>
                        <option value="On Hold" {{ ($filters['status'] ?? '') === 'On Hold' ? 'selected' : '' }}>معلق</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="priority" class="form-control">
                        <option value="">-- الأولوية --</option>
                        <option value="Low" {{ ($filters['priority'] ?? '') === 'Low' ? 'selected' : '' }}>منخفضة</option>
                        <option value="Medium" {{ ($filters['priority'] ?? '') === 'Medium' ? 'selected' : '' }}>متوسطة</option>
                        <option value="High" {{ ($filters['priority'] ?? '') === 'High' ? 'selected' : '' }}>عالية</option>
                        <option value="Urgent" {{ ($filters['priority'] ?? '') === 'Urgent' ? 'selected' : '' }}>عاجلة</option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="department" class="form-control">
                        <option value="">-- القسم --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}" {{ ($filters['department'] ?? '') === $dept ? 'selected' : '' }}>
                                {{ $dept }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <a href="{{ route('admin.reports.tickets') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>معرف WHMCS</th>
                            <th>الموضوع</th>
                            <th>العميل</th>
                            <th>الأولوية</th>
                            <th>القسم</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>آخر رد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->whmcs_id }}</td>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->customer->fullname ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $ticket->priority === 'High' || $ticket->priority === 'Urgent' ? 'danger' :
                                        ($ticket->priority === 'Medium' ? 'warning' : 'info')
                                    }}">
                                        {{ $ticket->priority }}
                                    </span>
                                </td>
                                <td>{{ $ticket->department }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $ticket->status === 'Open' ? 'info' :
                                        ($ticket->status === 'Closed' ? 'success' : 'warning')
                                    }}">
                                        {{ $ticket->status }}
                                    </span>
                                </td>
                                <td>{{ $ticket->date?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $ticket->lastreply?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا توجد تذاكر</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
