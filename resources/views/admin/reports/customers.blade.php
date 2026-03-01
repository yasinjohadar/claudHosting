@extends('admin.layouts.master')

@section('title', 'تقرير العملاء')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تقرير العملاء</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الفترة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.customers', ['period' => 'day']) }}" class="dropdown-item {{ request('period', 'month') == 'day' ? 'active' : '' }}">يومي</a>
                                <a href="{{ route('admin.reports.customers', ['period' => 'week']) }}" class="dropdown-item {{ request('period', 'month') == 'week' ? 'active' : '' }}">أسبوعي</a>
                                <a href="{{ route('admin.reports.customers', ['period' => 'month']) }}" class="dropdown-item {{ request('period', 'month') == 'month' ? 'active' : '' }}">شهري</a>
                                <a href="{{ route('admin.reports.customers', ['period' => 'year']) }}" class="dropdown-item {{ request('period', 'month') == 'year' ? 'active' : '' }}">سنوي</a>
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
                                <span class="info-box-icon"><i class="fas fa-user-plus"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العملاء الجدد</span>
                                    <span class="info-box-number">{{ number_format($currentCustomers) }}</span>
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
                                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">إجمالي العملاء</span>
                                    <span class="info-box-number">{{ number_format($totalCustomers) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العملاء النشطين</span>
                                    <span class="info-box-number">{{ number_format($activeCustomers) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-user-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">العملاء غير النشطين</span>
                                    <span class="info-box-number">{{ number_format($inactiveCustomers) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">مخطط العملاء الجدد</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="customersChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">توزيع العملاء</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="customerDistributionChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">العملاء حسب البلد</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>البلد</th>
                                                    <th>عدد العملاء</th>
                                                    <th>النسبة المئوية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customersByCountry as $index => $country)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $country->country }}</td>
                                                    <td>{{ number_format($country->count) }}</td>
                                                    <td>{{ number_format(($country->count / $totalCustomers) * 100, 2) }}%</td>
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
                                    <h3 class="card-title">العملاء حسب المدينة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>المدينة</th>
                                                    <th>عدد العملاء</th>
                                                    <th>النسبة المئوية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customersByCity as $index => $city)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $city->city }}</td>
                                                    <td>{{ number_format($city->count) }}</td>
                                                    <td>{{ number_format(($city->count / $totalCustomers) * 100, 2) }}%</td>
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
        // مخطط العملاء الجدد
        var customersChartCanvas = $('#customersChart').get(0).getContext('2d');
        var customersChartData = {
            labels: [
                @foreach($chartData as $data)
                    '{{ $data['label'] }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'العملاء الجدد',
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
        
        var customersChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            datasetFill: false
        };
        
        new Chart(customersChartCanvas, {
            type: 'bar',
            data: customersChartData,
            options: customersChartOptions
        });
        
        // مخطط توزيع العملاء
        var customerDistributionChartCanvas = $('#customerDistributionChart').get(0).getContext('2d');
        var customerDistributionChartData = {
            labels: ['النشطين', 'غير النشطين'],
            datasets: [
                {
                    data: [{{ $activeCustomers }}, {{ $inactiveCustomers }}],
                    backgroundColor: ['#00a65a', '#f56954']
                }
            ]
        };
        
        var customerDistributionChartOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        new Chart(customerDistributionChartCanvas, {
            type: 'doughnut',
            data: customerDistributionChartData,
            options: customerDistributionChartOptions
        });
    });
</script>
@endpush