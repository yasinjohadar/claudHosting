@extends('admin.layouts.master')

@section('title', 'تعديل التذكرة: ' . $ticket->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تعديل التذكرة: {{ $ticket->title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> عرض
                        </a>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.tickets.update', $ticket->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="userid">العميل <span class="text-danger">*</span></label>
                            <select class="form-control @error('userid') is-invalid @enderror" id="userid" name="userid" required>
                                <option value="">-- اختر العميل --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('userid', $ticket->userid) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->firstname }} {{ $customer->lastname }} ({{ $customer->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('userid')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="deptid">القسم <span class="text-danger">*</span></label>
                                    <select class="form-control @error('deptid') is-invalid @enderror" id="deptid" name="deptid" required>
                                        <option value="">-- اختر القسم --</option>
                                        <option value="1" {{ old('deptid', $ticket->deptid) == '1' ? 'selected' : '' }}>المبيعات</option>
                                        <option value="2" {{ old('deptid', $ticket->deptid) == '2' ? 'selected' : '' }}>الدعم الفني</option>
                                        <option value="3" {{ old('deptid', $ticket->deptid) == '3' ? 'selected' : '' }}>الفوترة</option>
                                        <option value="4" {{ old('deptid', $ticket->deptid) == '4' ? 'selected' : '' }}>آخر</option>
                                    </select>
                                    @error('deptid')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">الأولوية <span class="text-danger">*</span></label>
                                    <select class="form-control @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                        <option value="">-- اختر الأولوية --</option>
                                        <option value="Low" {{ old('priority', $ticket->urgency) == 'Low' ? 'selected' : '' }}>منخفضة</option>
                                        <option value="Medium" {{ old('priority', $ticket->urgency) == 'Medium' ? 'selected' : '' }}>متوسطة</option>
                                        <option value="High" {{ old('priority', $ticket->urgency) == 'High' ? 'selected' : '' }}>عالية</option>
                                    </select>
                                    @error('priority')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">الموضوع <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject', $ticket->title) }}" required>
                            @error('subject')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">تحديث التذكرة</button>
                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-default">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">معلومات التذكرة</h3>
                </div>
                <div class="card-body">
                    <p><strong>رقم التذكرة:</strong> {{ $ticket->tid }}</p>
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
            
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">ملاحظات</h3>
                </div>
                <div class="card-body">
                    <p>سيتم تحديث بيانات التذكرة في نظام WHMCS وفي قاعدة البيانات المحلية.</p>
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> تحذير</h5>
                        الحقول المطلوبة يجب ملؤها قبل تحديث التذكرة.
                    </div>
                </div>
            </div>
            
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">محتوى التذكرة</h3>
                </div>
                <div class="card-body">
                    <div class="bg-light p-3 rounded">
                        <p>{{ $ticket->message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection