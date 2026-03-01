@extends('admin.layouts.master')

@section('page-title', 'لوحة التقارير')

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-4 text-gray-800">
                    <i class="fas fa-chart-line"></i> لوحة التقارير
                </h1>
            </div>
        </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Customers Stats -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary font-weight-bold text-uppercase mb-1">
                        إجمالي العملاء
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                        {{ $stats['customers']['total'] }}
                    </div>
                    <div class="small mt-2">
                        <span class="text-success">نشط: {{ $stats['customers']['active'] }}</span> |
                        <span class="text-danger">غير نشط: {{ $stats['customers']['inactive'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Stats -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success font-weight-bold text-uppercase mb-1">
                        إجمالي الإيرادات
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                        ${{ number_format($stats['invoices']['total_revenue'], 2) }}
                    </div>
                    <div class="small mt-2">
                        <span class="text-success">مدفوع: ${{ number_format($stats['invoices']['paid_revenue'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Stats -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info font-weight-bold text-uppercase mb-1">
                        إجمالي المنتجات
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                        {{ $stats['products']['total'] }}
                    </div>
                    <div class="small mt-2">
                        <span class="text-success">نشط: {{ $stats['products']['active'] }}</span> |
                        <span class="text-warning">مخفي: {{ $stats['products']['hidden'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets Stats -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning font-weight-bold text-uppercase mb-1">
                        إجمالي التذاكر
                    </div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">
                        {{ $stats['tickets']['total'] }}
                    </div>
                    <div class="small mt-2">
                        <span class="text-success">مفتوح: {{ $stats['tickets']['open'] }}</span> |
                        <span class="text-info">مغلق: {{ $stats['tickets']['closed'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Buttons -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">التقارير المتاحة</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.reports.customers') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-users"></i> تقرير العملاء
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.reports.invoices') }}" class="btn btn-success btn-block">
                                <i class="fas fa-file-invoice"></i> تقرير الفواتير
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.reports.products') }}" class="btn btn-info btn-block">
                                <i class="fas fa-boxes"></i> تقرير المنتجات
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('admin.reports.tickets') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-ticket-alt"></i> تقرير التذاكر
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">أفضل العملاء</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>الاسم</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCustomers as $customer)
                                    <tr>
                                        <td>{{ $customer->fullname }}</td>
                                        <td>${{ number_format($customer->total_spent ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">آخر النشاطات</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($recentActivities as $activity)
                            <div class="timeline-item mb-3">
                                <div class="timeline-badge">
                                    @switch($activity['type'])
                                        @case('customer')
                                            <i class="fas fa-user text-primary"></i>
                                            @break
                                        @case('invoice')
                                            <i class="fas fa-file-invoice text-success"></i>
                                            @break
                                        @case('product')
                                            <i class="fas fa-box text-info"></i>
                                            @break
                                        @case('ticket')
                                            <i class="fas fa-ticket-alt text-warning"></i>
                                            @break
                                    @endswitch
                                </div>
                                <div class="timeline-content">
                                    <p class="mb-0">{{ $activity['description'] }}</p>
                                    <small class="text-muted">
                                        {{ $activity['date']?->diffForHumans() ?? 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center">لا توجد نشاطات حديثة</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Status Distribution -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">توزيع حالات الفواتير</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between">
                            <span>مدفوعة</span>
                            <strong class="text-success">{{ $stats['invoices']['paid'] }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span>غير مدفوعة</span>
                            <strong class="text-warning">{{ $stats['invoices']['unpaid'] }}</strong>
                        </div>
                        <div class="list-group-item d-flex justify-content-between">
                            <span>متأخرة</span>
                            <strong class="text-danger">{{ $stats['invoices']['overdue'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Priority Distribution -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">توزيع أولويات التذاكر</h6>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @forelse($stats['tickets']['by_priority'] as $priority => $count)
                            <div class="list-group-item d-flex justify-content-between">
                                <span>{{ $priority }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                        @empty
                            <p class="text-muted text-center">لا توجد بيانات</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
@endsection
