@extends('admin.layouts.master')

@section('page-title')
    تفاصيل الرسالة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">تفاصيل الرسالة</h5>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp-messages.index') }}">رسائل WhatsApp</a></li>
                        <li class="breadcrumb-item active">تفاصيل الرسالة</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">معلومات الرسالة</h5>
                        @if(in_array($message->status, ['queued', 'failed']))
                            <form action="{{ route('admin.whatsapp-messages.retry', $message) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('هل تريد إعادة إرسال هذه الرسالة؟')">
                                    <i class="ri-refresh-line me-1"></i>إعادة المحاولة
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">ID</th>
                                <td>{{ $message->id }}</td>
                            </tr>
                            <tr>
                                <th>الاتجاه</th>
                                <td>
                                    @if($message->direction === 'inbound')
                                        <span class="badge bg-info">واردة</span>
                                    @else
                                        <span class="badge bg-primary">صادرة</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>المستقبل</th>
                                <td>{{ $message->contact->wa_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Meta Message ID</th>
                                <td>{{ $message->meta_message_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>النوع</th>
                                <td>{{ $message->type }}</td>
                            </tr>
                            <tr>
                                <th>الحالة</th>
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
                                        <span class="badge bg-warning">في الانتظار (في الـ Queue)</span>
                                    @else
                                        <span class="badge bg-warning">في الانتظار</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>الرسالة</th>
                                <td>{{ $message->body ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>تاريخ الإنشاء</th>
                                <td>{{ $message->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            @if($message->error)
                                <tr>
                                    <th>خطأ</th>
                                    <td>
                                        <pre class="bg-danger text-white p-2 rounded">{{ json_encode($message->error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop




