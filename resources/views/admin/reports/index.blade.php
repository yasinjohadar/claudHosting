@extends('admin.layouts.master')

@section('title', 'التقارير والإحصائيات')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">التقارير والإحصائيات</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير المبيعات</span>
                                    <span class="info-box-number">
                                        {{ number_format(Invoice::where('status', 'Paid')->sum('total'), 2) }}
                                        <small>ريال سعودي</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.sales') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-users"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير العملاء</span>
                                    <span class="info-box-number">
                                        {{ number_format(Customer::count()) }}
                                        <small>عميل</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.customers') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير المنتجات</span>
                                    <span class="info-box-number">
                                        {{ number_format(Product::count()) }}
                                        <small>منتج</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.products') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-file-invoice-dollar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير الفواتير</span>
                                    <span class="info-box-number">
                                        {{ number_format(Invoice::count()) }}
                                        <small>فاتورة</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.invoices') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-ticket-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير التذاكر</span>
                                    <span class="info-box-number">
                                        {{ number_format(Ticket::count()) }}
                                        <small>تذكرة</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.tickets') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">تقرير المدفوعات</span>
                                    <span class="info-box-number">
                                        {{ number_format(Payment::sum('amount'), 2) }}
                                        <small>ريال سعودي</small>
                                    </span>
                                </div>
                                <div class="info-box-footer">
                                    <a href="{{ route('admin.reports.payments') }}" class="small-box-footer">عرض التقرير <i class="fas fa-arrow-circle-left"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">ملخص سريع</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-success">{{ number_format(Invoice::where('status', 'Paid')->count()) }}</span>
                                                <h5 class="description-header">فاتورة مدفوعة</h5>
                                                <span class="description-text">إجمالي الفواتير المدفوعة</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-warning">{{ number_format(Invoice::where('status', 'Unpaid')->count()) }}</span>
                                                <h5 class="description-header">فاتورة غير مدفوعة</h5>
                                                <span class="description-text">إجمالي الفواتير غير المدفوعة</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="description-block border-right">
                                                <span class="description-percentage text-info">{{ number_format(Ticket::where('status', 'Open')->count()) }}</span>
                                                <h5 class="description-header">تذكرة مفتوحة</h5>
                                                <span class="description-text">إجمالي التذاكر المفتوحة</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="description-block">
                                                <span class="description-percentage text-primary">{{ number_format(Customer::whereHas('products', function($query) {
                                                    $query->where('status', 'Active');
                                                })->count()) }}</span>
                                                <h5 class="description-header">عميل نشط</h5>
                                                <span class="description-text">إجمالي العملاء النشطين</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">أحدث الفواتير</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table m-0">
                                            <thead>
                                                <tr>
                                                    <th>رقم الفاتورة</th>
                                                    <th>العميل</th>
                                                    <th>المبلغ</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(Invoice::orderBy('created_at', 'desc')->take(5)->get() as $invoice)
                                                <tr>
                                                    <td><a href="{{ route('admin.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                                    <td>{{ $invoice->customer->first_name }} {{ $invoice->customer->last_name }}</td>
                                                    <td>{{ number_format($invoice->total, 2) }}</td>
                                                    <td>
                                                        @if($invoice->status == 'Paid')
                                                            <span class="badge badge-success">مدفوعة</span>
                                                        @elseif($invoice->status == 'Unpaid')
                                                            <span class="badge badge-warning">غير مدفوعة</span>
                                                        @elseif($invoice->status == 'Cancelled')
                                                            <span class="badge badge-danger">ملغية</span>
                                                        @else
                                                            <span class="badge badge-info">{{ $invoice->status }}</span>
                                                        @endif
                                                    </td>
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
                                    <h3 class="card-title">أحدث التذاكر</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table m-0">
                                            <thead>
                                                <tr>
                                                    <th>رقم التذكرة</th>
                                                    <th>العميل</th>
                                                    <th>الموضوع</th>
                                                    <th>الحالة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(Ticket::orderBy('created_at', 'desc')->take(5)->get() as $ticket)
                                                <tr>
                                                    <td><a href="{{ route('admin.tickets.show', $ticket->id) }}">{{ $ticket->ticket_number }}</a></td>
                                                    <td>{{ $ticket->customer->first_name }} {{ $ticket->customer->last_name }}</td>
                                                    <td>{{ Str::limit($ticket->subject, 30) }}</td>
                                                    <td>
                                                        @if($ticket->status == 'Open')
                                                            <span class="badge badge-warning">مفتوحة</span>
                                                        @elseif($ticket->status == 'Answered')
                                                            <span class="badge badge-info">مجابة</span>
                                                        @elseif($ticket->status == 'Closed')
                                                            <span class="badge badge-success">مغلقة</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $ticket->status }}</span>
                                                        @endif
                                                    </td>
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