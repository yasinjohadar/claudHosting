@extends('admin.layouts.master')

@section('title', 'إنشاء تذكرة جديدة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">إنشاء تذكرة جديدة</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.tickets.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="userid">العميل <span class="text-danger">*</span></label>
                            <select class="form-control @error('userid') is-invalid @enderror" id="userid" name="userid" required>
                                <option value="">-- اختر العميل --</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('userid', $customer_id) == $customer->id ? 'selected' : '' }}>
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
                                        <option value="1" {{ old('deptid') == '1' ? 'selected' : '' }}>المبيعات</option>
                                        <option value="2" {{ old('deptid') == '2' ? 'selected' : '' }}>الدعم الفني</option>
                                        <option value="3" {{ old('deptid') == '3' ? 'selected' : '' }}>الفوترة</option>
                                        <option value="4" {{ old('deptid') == '4' ? 'selected' : '' }}>آخر</option>
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
                                        <option value="Low" {{ old('priority') == 'Low' ? 'selected' : '' }}>منخفضة</option>
                                        <option value="Medium" {{ old('priority') == 'Medium' ? 'selected' : '' }}>متوسطة</option>
                                        <option value="High" {{ old('priority') == 'High' ? 'selected' : '' }}>عالية</option>
                                    </select>
                                    @error('priority')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">الموضوع <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                            @error('subject')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="message">الرسالة <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
                            @error('message')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">حفظ التذكرة</button>
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-default">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">معلومات إضافية</h3>
                </div>
                <div class="card-body">
                    <p>سيتم إنشاء التذكرة في نظام WHMCS وفي قاعدة البيانات المحلية.</p>
                    <p>يمكنك مزامنة بيانات التذكرة لاحقاً من WHMCS.</p>
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> ملاحظة</h5>
                        الحقول المطلوبة يجب ملؤها قبل حفظ التذكرة.
                    </div>
                </div>
            </div>
            
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">معلومات الأقسام</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <strong>المبيعات:</strong> للاستفسارات حول المنتجات والخدمات الجديدة
                        </li>
                        <li class="list-group-item">
                            <strong>الدعم الفني:</strong> للمشاكل التقنية والاستفسارات حول الخدمات الحالية
                        </li>
                        <li class="list-group-item">
                            <strong>الفوترة:</strong> للاستفسارات حول الفواتير والمدفوعات
                        </li>
                        <li class="list-group-item">
                            <strong>آخر:</strong> للاستفسارات الأخرى التي لا تنتمي للأقسام المذكورة
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection