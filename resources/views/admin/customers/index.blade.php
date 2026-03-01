@extends('admin.layouts.master')

@section('page-title')
العملاء
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">قائمة العملاء</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">العملاء</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إضافة عميل جديد
                </a>
                <button type="button" class="btn btn-success" id="syncAllCustomers">
                    <i class="fe fe-refresh-cw"></i> مزامنة الكل
                </button>
            </div>
        </div>
        <!-- End Page Header -->

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fe fe-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fe fe-alert-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Row -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">قائمة العملاء</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customersTable" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>الشركة</th>
                                        <th>رقم الهاتف</th>
                                        <th>الحالة</th>
                                        <th>تاريخ التسجيل</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers as $customer)
                                    <tr>
                                        <td>{{ $customer->id }}</td>
                                        <td>{{ $customer->fullname }}</td>
                                        <td>{{ $customer->email }}</td>
                                        <td>{{ $customer->companyname ?: '-' }}</td>
                                        <td>{{ $customer->phonenumber ?: '-' }}</td>
                                        <td>
                                            @if($customer->status == 'Active')
                                                <span class="badge bg-success-transparent">نشط</span>
                                            @elseif($customer->status == 'Inactive')
                                                <span class="badge bg-warning-transparent">غير نشط</span>
                                            @else
                                                <span class="badge bg-danger-transparent">مغلق</span>
                                            @endif
                                        </td>
                                        <td>{{ $customer->date_created ? $customer->date_created->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill delete-customer" data-id="{{ $customer->id }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا يوجد عملاء</td>
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
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Customer
    document.querySelectorAll('.delete-customer').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('هل أنت متأكد من حذف هذا العميل؟')) {
                var id = this.getAttribute('data-id');
                var form = document.getElementById('delete-form');
                form.action = '{{ url("admin/customers") }}/' + id;
                form.submit();
            }
        });
    });

    // Sync All Customers (بدون jQuery)
    var syncBtn = document.getElementById('syncAllCustomers');
    if (syncBtn) {
        syncBtn.addEventListener('click', function() {
            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fe fe-loader fa-spin"></i> جاري المزامنة...';
            var url = '{{ route("admin.customers.syncAll") }}';
            var token = '{{ csrf_token() }}';
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_token=' + encodeURIComponent(token)
            }).then(function(r) { return r.json(); }).then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-refresh-cw"></i> مزامنة الكل';
                if (data.success) {
                    alert(data.message || 'تم مزامنة العملاء بنجاح');
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء المزامنة');
                }
            }).catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fe fe-refresh-cw"></i> مزامنة الكل';
                alert('حدث خطأ أثناء المزامنة');
            });
        });
    }
});
</script>
@endsection
