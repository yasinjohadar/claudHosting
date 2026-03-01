@extends('admin.layouts.master')

@section('title', 'تقرير المدفوعات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تقرير المدفوعات</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الفترة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.payments', ['period' => 'day', 'method' => request('method', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'day' ? 'active' : '' }}">يومي</a>
                                <a href="{{ route('admin.reports.payments', ['period' => 'week', 'method' => request('method', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'week' ? 'active' : '' }}">أسبوعي</a>
                                <a href="{{ route('admin.reports.payments', ['period' => 'month', 'method' => request('method', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'month' ? 'active' : '' }}">شهري</a>
                                <a href="{{ route('admin.reports.payments', ['period' => 'year', 'method' => request('method', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'year' ? 'active' : '' }}">سنوي</a>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">طريقة الدفع</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.payments', ['period' => request('period', 'month'), 'method' => 'all']) }}" class="dropdown-item {{ request('method', 'all') == 'all' ? 'active' : '' }}">الكل</a>
                                @foreach(Payment::select('method')->distinct()->whereNotNull('method')->get() as $paymentMethod)
                                <a href="{{ route('admin.reports.payments', ['period' => request('period', 'month'), 'method' => $paymentMethod->method]) }}" class="dropdown-item {{ request('method', 'all') == $paymentMethod->method ? 'active' : '' }}">{{ $paymentMethod->method }}</a>
                                @endforeach
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
                                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">عدد المدفوعات</span>
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
                                <span class="info-box-icon"><i class="fas fa-coins"></i></span>
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
                                    <h3 class="card-title">مخطط المدفوعات</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="paymentsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المدفوعات حسب الطريقة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="paymentMethodChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">المدفوعات حسب الطريقة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>طريقة الدفع</th>
                                                    <th>عدد المدفوعات</th>
                                                    <th>المبلغ الإجمالي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($paymentsByMethod as $index => $method)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $method->method }}</td>
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
                                    <h3 class="card-title">المدفوعات حسب الحالة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>الحالة</th>
                                                    <th>عدد المدفوعات</th>
                                                    <th>المبلغ الإجمالي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($paymentsByStatus as $index => $status)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        @if($status->status == 'Completed')
                                                            <span class="badge badge-success">مكتمل</span>
                                                        @elseif($status->status == 'Pending')
                                                            <span class="badge badge-warning">قيد الانتظار</span>
                                                        @elseif($status->status == 'Failed')
                                                            <span class="badge badge-danger">فشل</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $status->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format($status->count) }}</td>
                                                    <td>{{ number_format($status->total, 2) }} ريال سعودي</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">آخر المدفوعات</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>العميل</th>
                                                    <th>الفاتورة</th>
                                                    <th>الطريقة</th>
                                                    <th>المبلغ</th>
                                                    <th>الحالة</th>
                                                    <th>التاريخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentPayments as $payment)
                                                <tr>
                                                    <td>{{ $payment->customer->first_name }} {{ $payment->customer->last_name }}</td>
                                                    <td><a href="{{ route('admin.invoices.show', $payment->invoice->id) }}">{{ $payment->invoice->invoice_number }}</a></td>
                                                    <td>{{ $payment->method }}</td>
                                                    <td>{{ number_format($payment->amount, 2) }} ريال سعودي</td>
                                                    <td>
                                                        @if($payment->status == 'Completed')
                                                            <span class="badge badge-success">مكتمل</span>
                                                        @elseif($payment->status == 'Pending')
                                                            <span class="badge badge-warning">قيد الانتظار</span>
                                                        @elseif($payment->status == 'Failed')
                                                            <span class="badge badge-danger">فشل</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $payment->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
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
        // مخطط المدفوعات
        var paymentsChartCanvas = $('#paymentsChart').get(0).getContext('2d');
        var paymentsChartData = {
            labels: [
                @foreach($chartData as $data)
                    '{{ $data['label'] }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'المدفوعات',
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
        
        var paymentsChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            datasetFill: false
        };
        
        new Chart(paymentsChartCanvas, {
            type: 'bar',
            data: paymentsChartData,
            options: paymentsChartOptions
        });
        
        // مخطط المدفوعات حسب الطريقة
        var paymentMethodChartCanvas = $('#paymentMethodChart').get(0).getContext('2d');
        var paymentMethodChartData = {
            labels: [
                @foreach($paymentsByMethod as $method)
                    '{{ $method->method }}',
                @endforeach
            ],
            datasets: [
                {
                    data: [
                        @foreach($paymentsByMethod as $method)
                            {{ $method->total }},
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
        
        var paymentMethodChartOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        new Chart(paymentMethodChartCanvas, {
            type: 'doughnut',
            data: paymentMethodChartData,
            options: paymentMethodChartOptions
        });
    });
</script>
@endpush