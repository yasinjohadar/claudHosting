@extends('admin.layouts.master')

@section('page-title')
    رسائل WhatsApp
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">رسائل WhatsApp</h5>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">رسائل WhatsApp</li>
                    </ol>
                </nav>
            </div>
            <div class="my-auto">
                <a href="{{ route('admin.whatsapp-messages.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> إرسال رسالة
                </a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.whatsapp-messages.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">بحث</label>
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="البحث...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الاتجاه</label>
                        <select class="form-select" name="direction">
                            <option value="">الكل</option>
                            <option value="inbound" {{ request('direction') == 'inbound' ? 'selected' : '' }}>واردة</option>
                            <option value="outbound" {{ request('direction') == 'outbound' ? 'selected' : '' }}>صادرة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" name="status">
                            <option value="">الكل</option>
                            <option value="queued" {{ request('status') == 'queued' ? 'selected' : '' }}>في الانتظار</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>مرسل</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>مستلم</option>
                            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>مقروء</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>فشل</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة الرسائل</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاتجاه</th>
                                <th>المستقبل</th>
                                <th>الرسالة</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                                <tr>
                                    <td>{{ $message->id }}</td>
                                    <td>
                                        @if($message->direction === 'inbound')
                                            <span class="badge bg-info">واردة</span>
                                        @else
                                            <span class="badge bg-primary">صادرة</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->contact->wa_id ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($message->body ?? '-', 50) }}</td>
                                    <td>
                                        @if($message->status === 'sent')
                                            <span class="badge bg-success">مرسل</span>
                                        @elseif($message->status === 'delivered')
                                            <span class="badge bg-info">مستلم</span>
                                        @elseif($message->status === 'read')
                                            <span class="badge bg-primary">مقروء</span>
                                        @elseif($message->status === 'failed')
                                            <span class="badge bg-danger">فشل</span>
                                        @elseif($message->status === 'queued')
                                            <span class="badge bg-warning">في الانتظار (Queue)</span>
                                        @else
                                            <span class="badge bg-warning">في الانتظار</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('admin.whatsapp-messages.show', $message) }}" class="btn btn-sm btn-info" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">لا توجد رسائل</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop

