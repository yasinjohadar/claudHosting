@extends('admin.layouts.master')

@section('title', 'تقرير المنتجات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تقرير المنتجات</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الفترة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.products', ['period' => 'day']) }}" class="dropdown-item {{ request('period', 'month') == 'day' ? 'active' : '' }}">يومي</a>
                                <a href="{{ route('admin.reports.products', ['period' => 'week']) }}" class="dropdown-item {{ request('period', 'month') == 'week' ? 'active' : '' }}">أسبوعي</a>
                                <a href="{{ route('admin.reports.products', ['period' => 'month']) }}" class="dropdown-item {{ request('period', 'month') == 'month' ? 'active' : '' }}">شهري</a>
                                <a href="{{ route('admin.reports.products', ['period' => 'year']) }}" class="dropdown-item {{ request('period', 'month') == 'year' ? 'active' : '' }}">سنوي</a>
                            </div>
                        </div>
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-tool">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">المنتجات المباعة</span>
                                    <span class="info-box-number">{{ number_format($currentSales) }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 70%"></div>
                                    </div>
                                    <span class="progress-description">
                                        @if($changePercentage > 0)
                                            <span class="text-success">{{ $changePercentage }}% زيادة</span>
                                        @else
                                            <span class="text-danger">{{ abs($changePercentage) }}% نقصان</span>
                                        @endif
                                        عن الفترة السابقة
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-cubes"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">إجمالي المنتجات</span>
                                    <span class="info-box-number">{{ number_format($totalProducts) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">المنتجات النشطة</span>
                                    <span class="info-box-number">{{ number_format($activeProducts) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">المنتجات غير النشطة</span>
                                    <span class="info-box-number">{{ number_format($inactiveProducts) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">مخطط مبيعات المنتجات</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="productsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المنتجات حسب الحالة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="productStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المنتجات الأكثر مبيعاً</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>اسم المنتج</th>
                                                    <th>المجموعة</th>
                                                    <th>عدد المبيعات</th>
                                                    <th>السعر</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topProducts as $index => $product)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $product->name }}</td>
                                                    <td>{{ $product->product_group }}</td>
                                                    <td>{{ $product->customers_count }}</td>
                                                    <td>{{ number_format($product->price, 2) }} ريال سعودي</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المنتجات حسب الفئة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>الفئة</th>
                                                    <th>عدد المنتجات</th>
                                                    <th>النسبة المئوية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($productsByGroup as $index => $group)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $group->product_group }}</td>
                                                    <td>{{ number_format($group->count) }}</td>
                                                    <td>{{ number_format(($group->count / $totalProducts) * 100, 2) }}%</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // مخطط مبيعات المنتجات
        var productsChartCanvas = $('#productsChart').get(0).getContext('2d');
        var productsChartData = {
            labels: [
                @foreach($chartData as $data)
                    '{{ $data['label'] }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'المبيعات',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    pointRadius: false,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: [
                        @foreach($chartData as $data)
                            {{ $data['value'] }},
                        @endforeach
                    ]
                }
            ]
        };
        
        var productsChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            datasetFill: false
        };
        
        new Chart(productsChartCanvas, {
            type: 'bar',
            data: productsChartData,
            options: productsChartOptions
        });
        
        // مخطط المنتجات حسب الحالة
        var productStatusChartCanvas = $('#productStatusChart').get(0).getContext('2d');
        var productStatusChartData = {
            labels: [
                @foreach($productsByStatus as $status)
                    '{{ $status->status }}',
                @endforeach
            ],
            datasets: [
                {
                    data: [
                        @foreach($productsByStatus as $status)
                            {{ $status->count }},
                        @endforeach
                    ],
                    backgroundColor: [
                        '#f56954',
                        '#00a65a',
                        '#f39c12',
                        '#00c0ef',
                        '#3c8dbc',
                        '#d2d6de'
                    ]
                }
            ]
        };
        
        var productStatusChartOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        new Chart(productStatusChartCanvas, {
            type: 'doughnut',
            data: productStatusChartData,
            options: productStatusChartOptions
        });
    });
</script>
@endpush