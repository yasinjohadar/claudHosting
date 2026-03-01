@extends('admin.layouts.master')

@section('title', 'تفاصيل التذكرة: ' . $ticket->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <!-- معلومات التذكرة الأساسية -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">معلومات التذكرة</h3>
                </div>
                <div class="card-body">
                    <p><strong>رقم التذكرة:</strong> {{ $ticket->tid }}</p>
                    <p><strong>الموضوع:</strong> {{ $ticket->title }}</p>
                    <p><strong>القسم:</strong> {{ $ticket->department }}</p>
                    <p><strong>الأولوية:</strong>
                        @if ($ticket->urgency == 'High')
                            <span class="badge badge-danger">عالية</span>
                        @elseif ($ticket->urgency == 'Medium')
                            <span class="badge badge-warning">متوسطة</span>
                        @else
                            <span class="badge badge-info">منخفضة</span>
                        @endif
                    </p>
                    <p><strong>الحالة:</strong>
                        @if ($ticket->status == 'Open')
                            <span class="badge badge-success">مفتوحة</span>
                        @elseif ($ticket->status == 'Answered')
                            <span class="badge badge-info">تم الرد عليها</span>
                        @elseif ($ticket->status == 'Customer-Reply')
                            <span class="badge badge-primary">رد العميل</span>
                        @elseif ($ticket->status == 'Closed')
                            <span class="badge badge-secondary">مغلقة</span>
                        @else
                            <span class="badge badge-info">{{ $ticket->status }}</span>
                        @endif
                    </p>
                    <p><strong>تاريخ الإنشاء:</strong> {{ $ticket->date }}</p>
                    <p><strong>آخر تحديث:</strong> {{ $ticket->lastreply }}</p>
                    <p><strong>معرف WHMCS:</strong> {{ $ticket->whmcs_id }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- أزرار التحكم -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">التحكم بالتذكرة</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        @if ($ticket->status != 'Closed')
                            <form action="{{ route('admin.tickets.close', $ticket->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من رغبتك في إغلاق هذه التذكرة؟')">
                                    <i class="fas fa-times"></i> إغلاق
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.tickets.reopen', $ticket->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> إعادة فتح
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('admin.tickets.sync', $ticket->id) }}" method="POST" style="display: inline-block;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-sync"></i> مزامنة من WHMCS
                            </button>
                        </form>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- معلومات العميل -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">معلومات العميل</h3>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> {{ $ticket->customer->firstname }} {{ $ticket->customer->lastname }}</p>
                    <p><strong>البريد الإلكتروني:</strong> {{ $ticket->customer->email }}</p>
                    <p><strong>الشركة:</strong> {{ $ticket->customer->companyname ?? '-' }}</p>
                    <p><strong>رقم الهاتف:</strong> {{ $ticket->customer->phonenumber ?? '-' }}</p>
                    <div class="btn-group mt-2">
                        <a href="{{ route('admin.customers.show', $ticket->customer->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-user"></i> عرض العميل
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- محتوى التذكرة -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">محتوى التذكرة</h3>
                </div>
                <div class="card-body">
                    <div class="bg-light p-3 rounded">
                        <p>{{ $ticket->message }}</p>
                    </div>
                </div>
            </div>
            
            <!-- الردود -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الردود</h3>
                    @if ($ticket->status != 'Closed')
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addReplyModal">
                                <i class="fas fa-reply"></i> إضافة رد
                            </button>
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#addNoteModal">
                                <i class="fas fa-sticky-note"></i> إضافة ملاحظة
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @forelse ($ticket->replies as $reply)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $reply->name }}</strong>
                                        @if ($reply->admin)
                                            <span class="badge badge-primary">مدير</span>
                                        @else
                                            <span class="badge badge-info">عميل</span>
                                        @endif
                                    </div>
                                    <small>{{ $reply->date }}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>{{ $reply->message }}</p>
                            </div>
                        </div>
                    @empty
                        <p>لا توجد ردود على هذه التذكرة.</p>
                    @endforelse
                </div>
            </div>
            
            <!-- الملاحظات -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الملاحظات</h3>
                </div>
                <div class="card-body">
                    @forelse ($ticket->notes as $note)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>{{ $note->admin->name }}</strong>
                                        <span class="badge badge-primary">مدير</span>
                                    </div>
                                    <small>{{ $note->date }}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>{{ $note->message }}</p>
                            </div>
                        </div>
                    @empty
                        <p>لا توجد ملاحظات على هذه التذكرة.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة رد -->
<div class="modal fade" id="addReplyModal" tabindex="-1" role="dialog" aria-labelledby="addReplyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReplyModalLabel">إضافة رد جديد</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.tickets.add-reply', $ticket->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="message">الرد <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required></textarea>
                        @error('message')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة الرد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal إضافة ملاحظة -->
<div class="modal fade" id="addNoteModal" tabindex="-1" role="dialog" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">إضافة ملاحظة جديدة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.tickets.add-note', $ticket->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="note">الملاحظة <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror" id="note" name="message" rows="5" required></textarea>
                        @error('message')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة الملاحظة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection