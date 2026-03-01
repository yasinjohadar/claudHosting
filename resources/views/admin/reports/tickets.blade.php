@extends('admin.layouts.master')

@section('title', 'تقرير التذاكر')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تقرير التذاكر</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الفترة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.tickets', ['period' => 'day', 'status' => request('status', 'all'), 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'day' ? 'active' : '' }}">يومي</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => 'week', 'status' => request('status', 'all'), 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'week' ? 'active' : '' }}">أسبوعي</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => 'month', 'status' => request('status', 'all'), 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'month' ? 'active' : '' }}">شهري</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => 'year', 'status' => request('status', 'all'), 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('period', 'month') == 'year' ? 'active' : '' }}">سنوي</a>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">الحالة</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => 'all', 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('status', 'all') == 'all' ? 'active' : '' }}">الكل</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => 'Open', 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('status', 'all') == 'Open' ? 'active' : '' }}">مفتوحة</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => 'Answered', 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('status', 'all') == 'Answered' ? 'active' : '' }}">مجابة</a>
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => 'Closed', 'department' => request('department', 'all')]) }}" class="dropdown-item {{ request('status', 'all') == 'Closed' ? 'active' : '' }}">مغلقة</a>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                <span class="sr-only">القسم</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" role="menu">
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => request('status', 'all'), 'department' => 'all']) }}" class="dropdown-item {{ request('department', 'all') == 'all' ? 'active' : '' }}">الكل</a>
                                @foreach(Ticket::select('department')->distinct()->whereNotNull('department')->get() as $dept)
                                <a href="{{ route('admin.reports.tickets', ['period' => request('period', 'month'), 'status' => request('status', 'all'), 'department' => $dept->department]) }}" class="dropdown-item {{ request('department', 'all') == $dept->department ? 'active' : '' }}">{{ $dept->department }}</a>
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
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">عدد التذاكر</span>
                                    <span class="info-box-number">{{ number_format($currentCount) }}</span>
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
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">التذاكر المغلقة</span>
                                    <span class="info-box-number">{{ number_format($ticketsByStatus->where('status', 'Closed')->first()->count ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-comment-dots"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">التذاكر المجابة</span>
                                    <span class="info-box-number">{{ number_format($ticketsByStatus->where('status', 'Answered')->first()->count ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">التذاكر المفتوحة</span>
                                    <span class="info-box-number">{{ number_format($ticketsByStatus->where('status', 'Open')->first()->count ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">مخطط التذاكر</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="ticketsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">التذاكر حسب الحالة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="chart">
                                        <canvas id="ticketStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">التذاكر حسب القسم</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>القسم</th>
                                                    <th>عدد التذاكر</th>
                                                    <th>النسبة المئوية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ticketsByDepartment as $index => $department)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $department->department }}</td>
                                                    <td>{{ number_format($department->count) }}</td>
                                                    <td>{{ number_format(($department->count / $ticketsByDepartment->sum('count')) * 100, 2) }}%</td>
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
                                    <h3 class="card-title">التذاكر حسب الأولوية</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>الأولوية</th>
                                                    <th>عدد التذاكر</th>
                                                    <th>النسبة المئوية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ticketsByPriority as $index => $priority)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        @if($priority->priority == 'High')
                                                            <span class="badge badge-danger">عالية</span>
                                                        @elseif($priority->priority == 'Medium')
                                                            <span class="badge badge-warning">متوسطة</span>
                                                        @elseif($priority->priority == 'Low')
                                                            <span class="badge badge-info">منخفضة</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $priority->priority }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format($priority->count) }}</td>
                                                    <td>{{ number_format(($priority->count / $ticketsByPriority->sum('count')) * 100, 2) }}%</td>
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
                                    <h3 class="card-title">التذاكر المفتوحة منذ فترة طويلة</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>رقم التذكرة</th>
                                                    <th>العميل</th>
                                                    <th>الموضوع</th>
                                                    <th>القسم</th>
                                                    <th>الأولوية</th>
                                                    <th>تاريخ الإنشاء</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($oldOpenTickets as $ticket)
                                                <tr>
                                                    <td><a href="{{ route('admin.tickets.show', $ticket->id) }}">{{ $ticket->ticket_number }}</a></td>
                                                    <td>{{ $ticket->customer->first_name }} {{ $ticket->customer->last_name }}</td>
                                                    <td>{{ Str::limit($ticket->subject, 50) }}</td>
                                                    <td>{{ $ticket->department }}</td>
                                                    <td>
                                                        @if($ticket->priority == 'High')
                                                            <span class="badge badge-danger">عالية</span>
                                                        @elseif($ticket->priority == 'Medium')
                                                            <span class="badge badge-warning">متوسطة</span>
                                                        @elseif($ticket->priority == 'Low')
                                                            <span class="badge badge-info">منخفضة</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $ticket->priority }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
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
        // مخطط التذاكر
        var ticketsChartCanvas = $('#ticketsChart').get(0).getContext('2d');
        var ticketsChartData = {
            labels: [
                @foreach($chartData as $data)
                    '{{ $data['label'] }}',
                @endforeach
            ],
            datasets: [
                {
                    label: 'التذاكر',
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
        
        var ticketsChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            datasetFill: false
        };
        
        new Chart(ticketsChartCanvas, {
            type: 'bar',
            data: ticketsChartData,
            options: ticketsChartOptions
        });
        
        // مخطط التذاكر حسب الحالة
        var ticketStatusChartCanvas = $('#ticketStatusChart').get(0).getContext('2d');
        var ticketStatusChartData = {
            labels: [
                @foreach($ticketsByStatus as $status)
                    '{{ $status->status }}',
                @endforeach
            ],
            datasets: [
                {
                    data: [
                        @foreach($ticketsByStatus as $status)
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
        
        var ticketStatusChartOptions = {
            maintainAspectRatio: false,
            responsive: true
        };
        
        new Chart(ticketStatusChartCanvas, {
            type: 'doughnut',
            data: ticketStatusChartData,
            options: ticketStatusChartOptions
        });
    });
</script>
@endpush