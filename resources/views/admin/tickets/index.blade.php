@extends('admin.layouts.master')

@section('page-title')
التذاكر
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">قائمة التذاكر</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">التذاكر</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إنشاء تذكرة
                </a>
                <button type="button" class="btn btn-success" id="syncAllTickets">
                    <i class="fe fe-refresh-cw"></i> مزامنة من WHMCS
                </button>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Row -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">قائمة التذاكر</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="ticketsTable" class="table table-bordered text-nowrap w-100">
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
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->id }}</td>
                                        <td>{{ $ticket->tid ?? $ticket->id }}</td>
                                        <td>
                                            @if($ticket->customer)
                                                {{ $ticket->customer->fullname }}
                                            @else
                                                {{ $ticket->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($ticket->subject, 40) }}</td>
                                        <td>{{ $ticket->department ?? '-' }}</td>
                                        <td>
                                            @if($ticket->priority == 'High' || $ticket->priority == 'Urgent')
                                                <span class="badge bg-danger-transparent">{{ $ticket->priority }}</span>
                                            @elseif($ticket->priority == 'Medium')
                                                <span class="badge bg-warning-transparent">{{ $ticket->priority }}</span>
                                            @else
                                                <span class="badge bg-info-transparent">{{ $ticket->priority ?? 'عادي' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->status == 'Open')
                                                <span class="badge bg-success-transparent">مفتوحة</span>
                                            @elseif($ticket->status == 'Answered')
                                                <span class="badge bg-info-transparent">تم الرد</span>
                                            @elseif($ticket->status == 'Customer-Reply')
                                                <span class="badge bg-warning-transparent">رد العميل</span>
                                            @elseif($ticket->status == 'Closed')
                                                <span class="badge bg-secondary-transparent">مغلقة</span>
                                            @else
                                                <span class="badge bg-primary-transparent">{{ $ticket->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->date ? $ticket->date->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                @if($ticket->status != 'Closed')
                                                <button type="button" class="btn btn-icon btn-sm btn-secondary-transparent rounded-pill close-ticket" data-id="{{ $ticket->id }}">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                                @else
                                                <button type="button" class="btn btn-icon btn-sm btn-success-transparent rounded-pill reopen-ticket" data-id="{{ $ticket->id }}">
                                                    <i class="ri-refresh-line"></i>
                                                </button>
                                                @endif
                                                <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill delete-ticket" data-id="{{ $ticket->id }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">لا توجد تذاكر</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>
</div>
<!-- End::app-content -->

<!-- Delete Form -->
<form id="delete-form" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Close/Reopen Form -->
<form id="status-form" action="" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // DataTable
        if ($.fn.DataTable) {
            $('#ticketsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json"
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [[0, 'desc']]
            });
        }
        
        // Delete Ticket
        $('.delete-ticket').click(function() {
            if (confirm('هل أنت متأكد من حذف هذه التذكرة؟')) {
                var id = $(this).data('id');
                var form = $('#delete-form');
                form.attr('action', '/admin/tickets/' + id);
                form.submit();
            }
        });

        // Close Ticket
        $('.close-ticket').click(function() {
            if (confirm('هل أنت متأكد من إغلاق هذه التذكرة؟')) {
                var id = $(this).data('id');
                var form = $('#status-form');
                form.attr('action', '/admin/tickets/' + id + '/close');
                form.submit();
            }
        });

        // Reopen Ticket
        $('.reopen-ticket').click(function() {
            if (confirm('هل أنت متأكد من إعادة فتح هذه التذكرة؟')) {
                var id = $(this).data('id');
                var form = $('#status-form');
                form.attr('action', '/admin/tickets/' + id + '/reopen');
                form.submit();
            }
        });

        // Sync All Tickets
        $('#syncAllTickets').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fe fe-loader fa-spin"></i> جاري المزامنة...');
            
            $.ajax({
                url: '{{ route("admin.tickets.syncAll") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert('تم مزامنة التذاكر بنجاح');
                    location.reload();
                },
                error: function() {
                    alert('حدث خطأ أثناء المزامنة');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fe fe-refresh-cw"></i> مزامنة من WHMCS');
                }
            });
        });
    });
</script>
@endsection
