@extends('admin.layouts.master')

@section('page-title', 'تقرير المنتجات')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 text-gray-800">
                    <i class="fas fa-boxes"></i> تقرير المنتجات
                </h1>
                <a href="{{ route('admin.reports.export.products', request()->query()) }}" class="btn btn-success">
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
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="ابحث عن اسم المنتج"
                        value="{{ $filters['search'] ?? '' }}"
                    >
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="type" class="form-control">
                        <option value="">-- اختر النوع --</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">-- اختر الحالة --</option>
                        <option value="Active" {{ ($filters['status'] ?? '') === 'Active' ? 'selected' : '' }}>نشط</option>
                        <option value="Inactive" {{ ($filters['status'] ?? '') === 'Inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <a href="{{ route('admin.reports.products') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>معرف WHMCS</th>
                            <th>الاسم</th>
                            <th>النوع</th>
                            <th>نوع الدفع</th>
                            <th>الكمية</th>
                            <th>الحالة</th>
                            <th>متكرر</th>
                            <th>تم الإنشاء في</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->whmcs_id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->type }}</td>
                                <td>{{ $product->paytype }}</td>
                                <td>{{ $product->qty }}</td>
                                <td>
                                    <span class="badge badge-{{ $product->status === 'Active' ? 'success' : 'danger' }}">
                                        {{ $product->status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $product->recurring ? 'info' : 'secondary' }}">
                                        {{ $product->recurring ? 'نعم' : 'لا' }}
                                    </span>
                                </td>
                                <td>{{ $product->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا توجد منتجات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $products->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
