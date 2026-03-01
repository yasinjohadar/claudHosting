@extends('admin.layouts.master')

@section('title', 'تقرير الفواتير')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تقرير الفواتير</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الفترة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.invoices', ['period' => 'day', 'status' => request('status', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'day' ? 'active' : '' }}">يومي</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => 'week', 'status' => request('status', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'week' ? 'active' : '' }}">أسبوعي</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => 'month', 'status' => request('status', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'month' ? 'active' : '' }}">شهري</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => 'year', 'status' => request('status', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'year' ? 'active' : '' }}">سنوي</a>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الحالة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.invoices', ['period' => request('period', 'month'), 'status' => 'all']) }}" class="dropdown-item {{ request('status', 'all') == 'all' ? 'active' : '' }}">الكل</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => request('period', 'month'), 'status' => 'Paid']) }}" class="dropdown-item {{ request('status', 'all') == 'Paid' ? 'active' : '' }}">مدفوعة</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => request('period', 'month'), 'status' => 'Unpaid']) }}" class="dropdown-item {{ request('status', 'all') == 'Unpaid' ? 'active' : '' }}">غير مدفوعة</a>
                                <a href="{{ route('admin.reports.invoices', ['period' => request('period', 'month'), 'status' => 'Cancelled']) }}" class="dropdown-item {{ request('status', 'all') == 'Cancelled' ? 'active' : '' }}">ملغية</a>
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
                                <span class="info-box-icon"><i class="fas fa-file-invoice"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">عدد الفواتير</span>
                                    <span class="info-box-number">{{ number_format($currentCount) }}</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 70%"></div>
                                    </div>
                                    <span class="progress-description">
                                        @if($countChangePercentage > 0)
                                            <span class="text-success">{{ $countChangePercentage }}% زيادة</span>
                                        @else
                                            <span class="text-danger">{{ abs($countChangePercentage) }}% نقصان</span>
                                        @endif
                                        عن الفترة السابقة
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">إجمالي المبالغ</span>
                                    <span class="info-box-number">{{ number_format($currentTotal, 2) }} <small>ريال سعودي</small></span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: 70%"></div>
                                    </div>
                                    <span class="progress-description">
                                        @if($totalChangePercentage > 0)
                                            <span class="text-success">{{ $totalChangePercentage }}% زيادة</span>
                                        @else
                                            <span class="text-danger">{{ abs($totalChangePercentage) }}% نقصان</span>
                                        @endif
                                        عن الفترة السابقة
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-calculator"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">متوسط المبلغ</span>
                                    <span class="info-box-number">{{ number_format($currentCount > 0 ? $currentTotal / $currentCount : 0, 2) }} <small>ريال سعودي</small></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">نسبة التغيير</span>
                                    <span class="info-box-number">
                                        @if($totalChangePercentage > 0)
                                            <span class="text-success">+{{ $totalChangePercentage }}%</span>
                                        @else
                                            <span class="text-danger">{{ $totalChangePercentage }}%</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">مخطط الفواتير</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="invoicesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">الفواتير حسب الحالة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="invoiceStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">الفواتير حسب طريقة الدفع</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>طريقة الدفع</th>
                                                    <th>عدد الفواتير</th>
                                                    <th>المبلغ الإجمالي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoicesByPaymentMethod as $index => $method)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $method->payment_method }}</td>
                                                    <td>{{ number_format($method->count) }}</td>
                                                    <td>{{ number_format($method->total, 2) }} ريال سعودي</td>
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
                                    <h3 class="card-title">الفواتير غير المدفوعة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>رقم الفاتورة</th>
                                                    <th>العميل</th>
                                                    <th>المبلغ</th>
                                                    <th>تاريخ الاستحقاق</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($unpaidInvoices as $invoice)
                                                <tr>
                                                    <td><a href="{{ route('admin.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                                    <td>{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}</td>
                                                    <td>{{ number_format($invoice->total, 2) }}</td>
                                                    <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
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
        // مخطط الفواتير
        var invoicesChartCanvas = $('#invoicesChart').get(0).getContext('2d');
        var invoicesChartData = {
            labels: [
                @foreach($chartData as $data)
                    '{{ $data['label'] }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'الفواتير',
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
        
        var invoicesChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            datasetFill: false
        };
        
        new Chart(invoicesChartCanvas, {
            type: 'bar',
            data: invoicesChartData,
            options: invoicesChartOptions
        });
        
        // مخطط الفواتير حسب الحالة
        var invoiceStatusChartCanvas = $('#invoiceStatusChart').get(0).getContext('2d');
        var invoiceStatusChartData = {
            labels: [
                @foreach($invoicesByStatus as $status)
                    '{{ $status->status }}',
                @endforeach
            ],
            datasets: [
                {
                    data: [
                        @foreach($invoicesByStatus as $status)
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
        
        var invoiceStatusChartOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        new Chart(invoiceStatusChartCanvas, {
            type: 'doughnut',
            data: invoiceStatusChartData,
            options: invoiceStatusChartOptions
        });
    });
</script>
@endpush