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
            </div>
        </div>
        <!-- End Page Header -->

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

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
                                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-icon btn-sm btn-warning-transparent rounded-pill" title="تعديل">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <button type="button"
                                                    class="btn btn-icon btn-sm btn-success-transparent rounded-pill duplicate-product"
                                                    title="نسخ"
                                                    data-url="{{ route('admin.products.duplicate', $product->id) }}">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-icon btn-sm btn-danger-transparent rounded-pill delete-product"
                                                    title="حذف"
                                                    data-url="{{ route('admin.products.destroy', $product->id) }}">
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

<form id="delete-form" action="" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>
<form id="duplicate-form" action="" method="POST" class="d-none">
    @csrf
</form>
@endsection

@section('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function submitHiddenForm(formId, url) {
        const form = document.getElementById(formId);
        if (!form || !url) return;
        form.setAttribute('action', url);
        form.submit();
    }

    document.addEventListener('click', function (e) {
        const dupBtn = e.target.closest('.duplicate-product');
        if (dupBtn) {
            e.preventDefault();
            e.stopPropagation();
            const url = dupBtn.getAttribute('data-url');
            if (!url) return;
            if (!confirm('نسخ هذا المنتج مع كل بياناته (التسعير، الميزات، WHM)؟')) return;
            submitHiddenForm('duplicate-form', url);
            return;
        }

        const delBtn = e.target.closest('.delete-product');
        if (delBtn) {
            e.preventDefault();
            e.stopPropagation();
            const url = delBtn.getAttribute('data-url');
            if (!url) return;
            if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return;
            submitHiddenForm('delete-form', url);
        }
    });

    $(document).ready(function () {
        if (!$.fn.DataTable) return;
        $('#productsTable').DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json' },
            responsive: true,
            lengthChange: true,
            autoWidth: false,
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, searchable: false, targets: -1 }],
        });
    });
})();
</script>
@endsection
