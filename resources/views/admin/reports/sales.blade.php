@extends('admin.layouts.master')

@section('page-title')
تقرير المبيعات
@stop

@section('content')
<!-- Start::app-content -->
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">تقرير المبيعات</h4>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
                        <li class="breadcrumb-item active" aria-current="page">تقرير المبيعات</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fe fe-calendar me-1"></i>
                        @if($period == 'day') يومي
                        @elseif($period == 'week') أسبوعي
                        @elseif($period == 'year') سنوي
                        @else شهري
                        @endif
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item {{ $period == 'day' ? 'active' : '' }}" href="{{ route('admin.reports.sales', ['period' => 'day']) }}">يومي</a></li>
                        <li><a class="dropdown-item {{ $period == 'week' ? 'active' : '' }}" href="{{ route('admin.reports.sales', ['period' => 'week']) }}">أسبوعي</a></li>
                        <li><a class="dropdown-item {{ $period == 'month' ? 'active' : '' }}" href="{{ route('admin.reports.sales', ['period' => 'month']) }}">شهري</a></li>
                        <li><a class="dropdown-item {{ $period == 'year' ? 'active' : '' }}" href="{{ route('admin.reports.sales', ['period' => 'year']) }}">سنوي</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted fs-12">إجمالي المبيعات</span>
                                <h4 class="fw-semibold mt-1 mb-0">{{ number_format($currentTotal, 2) }}</h4>
                                <span class="text-muted fs-11">ريال سعودي</span>
                            </div>
                            <div class="avatar avatar-md bg-success-transparent">
                                <i class="fe fe-dollar-sign fs-18 text-success"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            @if($changePercentage >= 0)
                                <span class="badge bg-success-transparent text-success me-2">
                                    <i class="fe fe-arrow-up"></i> {{ $changePercentage }}%
                                </span>
                                <span class="text-muted fs-11">زيادة عن الفترة السابقة</span>
                            @else
                                <span class="badge bg-danger-transparent text-danger me-2">
                                    <i class="fe fe-arrow-down"></i> {{ abs($changePercentage) }}%
                                </span>
                                <span class="text-muted fs-11">نقصان عن الفترة السابقة</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted fs-12">الفترة السابقة</span>
                                <h4 class="fw-semibold mt-1 mb-0">{{ number_format($previousTotal, 2) }}</h4>
                                <span class="text-muted fs-11">ريال سعودي</span>
                            </div>
                            <div class="avatar avatar-md bg-info-transparent">
                                <i class="fe fe-trending-up fs-18 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted fs-12">متوسط المبيعات</span>
                                <h4 class="fw-semibold mt-1 mb-0">{{ number_format($currentTotal / max(1, $invoiceStatuses->sum('count')), 2) }}</h4>
                                <span class="text-muted fs-11">ريال سعودي</span>
                            </div>
                            <div class="avatar avatar-md bg-warning-transparent">
                                <i class="fe fe-bar-chart-2 fs-18 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <span class="text-muted fs-12">نسبة التغيير</span>
                                <h4 class="fw-semibold mt-1 mb-0 {{ $changePercentage >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $changePercentage >= 0 ? '+' : '' }}{{ $changePercentage }}%
                                </h4>
                            </div>
                            <div class="avatar avatar-md {{ $changePercentage >= 0 ? 'bg-success-transparent' : 'bg-danger-transparent' }}">
                                <i class="fe fe-percent fs-18 {{ $changePercentage >= 0 ? 'text-success' : 'text-danger' }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Stats Cards -->

        <!-- Charts Row -->
        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">مخطط المبيعات</div>
                    </div>
                    <div class="card-body">
                        <div id="salesChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">الفواتير حسب الحالة</div>
                    </div>
                    <div class="card-body">
                        <div id="invoiceStatusChart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Charts Row -->

        <!-- Tables Row -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">المنتجات الأكثر مبيعاً</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>اسم المنتج</th>
                                        <th>المجموعة</th>
                                        <th>عدد المبيعات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topProducts as $index => $product)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.products.show', $product->id) }}" class="fw-semibold">
                                                {{ $product->name }}
                                            </a>
                                        </td>
                                        <td><span class="badge bg-primary-transparent">{{ $product->product_group ?? '-' }}</span></td>
                                        <td><span class="badge bg-success">{{ $product->customers_count }}</span></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">أفضل العملاء</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>اسم العميل</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>إجمالي الإنفاق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topCustomers as $index => $customer)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ route('admin.customers.show', $customer->id) }}" class="fw-semibold">
                                                {{ $customer->firstname }} {{ $customer->lastname }}
                                            </a>
                                        </td>
                                        <td>{{ $customer->email }}</td>
                                        <td><span class="text-success fw-semibold">{{ number_format($customer->total_spent, 2) }} ر.س</span></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">لا توجد بيانات</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Tables Row -->
    </div>
</div>
<!-- End::app-content -->
@endsection

@section('scripts')
<!-- Apex Charts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    var salesOptions = {
        series: [{
            name: 'المبيعات',
            data: [@foreach($chartData as $data){{ $data['value'] }},@endforeach]
        }],
        chart: {
            type: 'area',
            height: 300,
            fontFamily: 'inherit',
            toolbar: {
                show: false
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: [@foreach($chartData as $data)'{{ $data['label'] }}',@endforeach],
            labels: {
                style: {
                    fontFamily: 'inherit'
                }
            }
        },
        yaxis: {
            labels: {
                formatter: function(val) {
                    return val.toFixed(0) + ' ر.س';
                },
                style: {
                    fontFamily: 'inherit'
                }
            }
        },
        colors: ['#6366f1'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 90, 100]
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val.toFixed(2) + ' ر.س';
                }
            }
        }
    };

    var salesChart = new ApexCharts(document.querySelector("#salesChart"), salesOptions);
    salesChart.render();

    // Invoice Status Chart
    var statusLabels = [@foreach($invoiceStatuses as $status)'{{ $status->status }}',@endforeach];
    var statusData = [@foreach($invoiceStatuses as $status){{ $status->count }},@endforeach];

    if (statusLabels.length === 0) {
        statusLabels = ['لا توجد بيانات'];
        statusData = [1];
    }

    var statusOptions = {
        series: statusData,
        chart: {
            type: 'donut',
            height: 300,
            fontFamily: 'inherit'
        },
        labels: statusLabels,
        colors: ['#10b981', '#f59e0b', '#ef4444', '#6366f1', '#8b5cf6', '#06b6d4'],
        legend: {
            position: 'bottom',
            fontFamily: 'inherit'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'الإجمالي',
                            fontFamily: 'inherit'
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var statusChart = new ApexCharts(document.querySelector("#invoiceStatusChart"), statusOptions);
    statusChart.render();
});
</script>
@endsection
