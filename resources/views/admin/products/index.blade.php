@extends('admin.layouts.master')

@section('page-title')
المنتجات
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">قائمة المنتجات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">المنتجات</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto pageheader-btn">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus"></i> إضافة منتج جديد
                </a>
                <button type="button" class="btn btn-success" id="syncAllProducts">
                    <i class="fe fe-refresh-cw"></i> مزامنة مع WHMCS
                </button>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Row -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">قائمة المنتجات</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="productsTable" class="table table-bordered text-nowrap w-100">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>المجموعة</th>
                                        <th>النوع</th>
                                        <th>السعر</th>
                                        <th>الحالة</th>
                                        <th>عدد المبيعات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                    <tr>
                                        <td>{{ $product->id }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->product_group ?: '-' }}</td>
                                        <td>
                                            @switch($product->type)
                                                @case('hostingaccount')
                                                    <span class="badge bg-primary-transparent">استضافة</span>
                                                    @break
                                                @case('reselleraccount')
                                                    <span class="badge bg-info-transparent">ريسيلر</span>
                                                    @break
                                                @case('server')
                                                    <span class="badge bg-warning-transparent">خادم</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary-transparent">{{ $product->type }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($product->pricing)
                                                @php
                                                    $pricing = is_string($product->pricing) ? json_decode($product->pricing, true) : $product->pricing;
                                                    $price = $pricing['monthly'] ?? $pricing['msetupfee'] ?? 0;
                                                @endphp
                                                {{ number_format($price, 2) }} ر.س
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->status == 'Active')
                                                <span class="badge bg-success-transparent">نشط</span>
                                            @else
                                                <span class="badge bg-danger-transparent">غير نشط</span>
                                            @endif
                                        </td>
                                        <td>{{ $product->sales_count ?? 0 }}</td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-icon btn-sm btn-info-transparent rounded-pill">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <button type="button" class="btn btn-icon btn-sm btn-danger-transparent rounded-pill delete-product" data-id="{{ $product->id }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد منتجات</td>
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
    $(document).ready(function() {
        // DataTable
        if ($.fn.DataTable) {
            $('#productsTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json"
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "order": [[0, 'desc']]
            });
        }
        
        // Delete Product
        $('.delete-product').click(function() {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                var id = $(this).data('id');
                var form = $('#delete-form');
                form.attr('action', '/admin/products/' + id);
                form.submit();
            }
        });

        // Sync All Products
        $('#syncAllProducts').click(function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fe fe-loader fa-spin"></i> جاري المزامنة...');
            
            $.ajax({
                url: '{{ route("admin.products.syncAll") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert('تم مزامنة المنتجات بنجاح');
                    location.reload();
                },
                error: function() {
                    alert('حدث خطأ أثناء المزامنة');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fe fe-refresh-cw"></i> مزامنة مع WHMCS');
                }
            });
        });
    });
</script>
@endsection
