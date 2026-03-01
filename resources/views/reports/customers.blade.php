@extends('admin.layouts.master')

@section('page-title', 'تقرير العملاء')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 text-gray-800">
                    <i class="fas fa-users"></i> تقرير العملاء
                </h1>
                <a href="{{ route('admin.reports.export.customers', request()->query()) }}" class="btn btn-success">
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
            <style>
                /* Force filters inline on desktop, stack on small screens */
                .filters-row .form-row{display:flex;gap:12px;align-items:center;flex-wrap:nowrap}
                .filters-row .form-group{margin-bottom:0}
                .filters-row .col-md-4{flex:0 0 40%}
                .filters-row .col-md-3{flex:0 0 30%}
                .filters-row .col-md-2{flex:0 0 15%;display:flex;align-items:center}
                @media (max-width: 767.98px){
                    .filters-row .form-row{flex-direction:column;gap:10px}
                    .filters-row .col-md-4,
                    .filters-row .col-md-3,
                    .filters-row .col-md-2{flex:initial;width:100%}
                }
            </style>

            <form method="GET" class="form filters-row">
                <div class="form-row">
                    <div class="form-group col-md-4 mb-2">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="ابحث عن اسم أو بريد إلكتروني"
                            value="{{ $filters['search'] ?? '' }}"
                        >
                    </div>

                    <div class="form-group col-md-3 mb-2">
                        <select name="status" class="form-control">
                            <option value="">-- اختر الحالة --</option>
                            <option value="Active" {{ ($filters['status'] ?? '') === 'Active' ? 'selected' : '' }}>نشط</option>
                            <option value="Inactive" {{ ($filters['status'] ?? '') === 'Inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3 mb-2">
                        <select name="country" class="form-control">
                            <option value="">-- اختر الدولة --</option>
                            @foreach($countries as $country)
                                <option value="{{ $country }}" {{ ($filters['country'] ?? '') === $country ? 'selected' : '' }}>
                                    {{ $country }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-2 mb-2 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search"></i> بحث
                        </button>
                        <a href="{{ route('admin.reports.customers') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>معرف WHMCS</th>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الشركة</th>
                            <th>رقم الهاتف</th>
                            <th>الدولة</th>
                            <th>الحالة</th>
                            <th>تم الإنشاء في</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $customer->whmcs_id }}</td>
                                <td>{{ $customer->fullname }}</td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->companyname }}</td>
                                <td>{{ $customer->phonenumber }}</td>
                                <td>{{ $customer->country }}</td>
                                <td>
                                    <span class="badge badge-{{ $customer->status === 'Active' ? 'success' : 'danger' }}">
                                        {{ $customer->status }}
                                    </span>
                                </td>
                                <td>{{ $customer->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">لا توجد عملاء</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $customers->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
