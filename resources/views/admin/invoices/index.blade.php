@extends('admin.layouts.master')

@section('page-title')
الفواتير
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">قائمة الفواتير</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">الفواتير</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إنشاء فاتورة
                </a>
                <button type="button" class="btn btn-success" id="syncAllInvoices">
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
                        <div class="card-title">قائمة الفواتير</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="invoicesTable" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>رقم الفاتورة</th>
                                        <th>العميل</th>
                                        <th>التاريخ</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->id }}</td>
                                        <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                                        <td>
                                            @if($invoice->customer)
                                                {{ $invoice->customer->fullname }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $invoice->date ? $invoice->date->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $invoice->duedate ? $invoice->duedate->format('Y-m-d') : '-' }}</td>
                                        <td>{{ number_format($invoice->total ?? 0, 2) }} ر.س</td>
                                        <td>
                                            @if($invoice->status == 'Paid')
                                                <span class="badge bg-success-transparent">مدفوعة</span>
                                            @elseif($invoice->status == 'Unpaid')
                                                <span class="badge bg-danger-transparent">غير مدفوعة</span>
                                            @elseif($invoice->status == 'Cancelled')
                                                <span class="badge bg-secondary-transparent">ملغاة</span>
                                            @else
                                                <span class="badge bg-warning-transparent">{{ $invoice->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                @if($invoice->status == 'Unpaid')
                                                <button type="button" class="btn btn-icon btn-sm btn-success-transparent rounded-pill mark-paid" data-id="{{ $invoice->id }}">
                                                    <i class="ri-check-line"></i>
                                                </button>
                                                @endif
                                                <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill delete-invoice" data-id="{{ $invoice->id }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد فواتير</td>
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

<!-- Mark Paid Form -->
<form id="mark-paid-form" action="" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // DataTable
        if ($.fn.DataTable) {
            $('#invoicesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json"
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [[0, 'desc']]
            });
        }
        
        // Delete Invoice
        $('.delete-invoice').click(function() {
            if (confirm('هل أنت متأكد من حذف هذه الفاتورة؟')) {
                var id = $(this).data('id');
                var form = $('#delete-form');
                form.attr('action', '/admin/invoices/' + id);
                form.submit();
            }
        });

        // Mark as Paid
        $('.mark-paid').click(function() {
            if (confirm('هل أنت متأكد من تحديد هذه الفاتورة كمدفوعة؟')) {
                var id = $(this).data('id');
                var form = $('#mark-paid-form');
                form.attr('action', '/admin/invoices/' + id + '/mark-paid');
                form.submit();
            }
        });

        // Sync All Invoices
        $('#syncAllInvoices').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fe fe-loader fa-spin"></i> جاري المزامنة...');
            
            $.ajax({
                url: '{{ route("admin.invoices.syncAll") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert('تم مزامنة الفواتير بنجاح');
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
