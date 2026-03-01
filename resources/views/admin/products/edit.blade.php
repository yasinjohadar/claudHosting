@extends('admin.layouts.master')

@section('title', 'تعديل المنتج: ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">تعديل المنتج: {{ $product->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> عرض
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">نوع المنتج <span class="text-danger">*</span></label>
                                    <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">-- اختر النوع --</option>
                                        <option value="hostingaccount" {{ old('type', $product->type) == 'hostingaccount' ? 'selected' : '' }}>حساب استضافة</option>
                                        <option value="reselleraccount" {{ old('type', $product->type) == 'reselleraccount' ? 'selected' : '' }}>حساب ريستلر</option>
                                        <option value="server" {{ old('type', $product->type) == 'server' ? 'selected' : '' }}>خادم</option>
                                        <option value="other" {{ old('type', $product->type) == 'other' ? 'selected' : '' }}>آخر</option>
                                    </select>
                                    @error('type')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gid">مجموعة المنتج <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('gid') is-invalid @enderror" id="gid" name="gid" value="{{ old('gid', $product->gid) }}" required>
                                    @error('gid')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">اسم المنتج <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">السعر <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required>
                                        <select class="form-control @error('currency') is-invalid @enderror" name="currency" style="max-width: 100px;">
                                            <option value="USD" {{ old('currency', $product->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="EUR" {{ old('currency', $product->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                                            <option value="GBP" {{ old('currency', $product->currency) == 'GBP' ? 'selected' : '' }}>GBP</option>
                                            <option value="SAR" {{ old('currency', $product->currency) == 'SAR' ? 'selected' : '' }}>SAR</option>
                                            <option value="AED" {{ old('currency', $product->currency) == 'AED' ? 'selected' : '' }}>AED</option>
                                            <option value="EGP" {{ old('currency', $product->currency) == 'EGP' ? 'selected' : '' }}>EGP</option>
                                        </select>
                                    </div>
                                    @error('price')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                    @error('currency')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="setupfee">رسوم الإعداد</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('setupfee') is-invalid @enderror" id="setupfee" name="setupfee" value="{{ old('setupfee', $product->setupfee ?? 0) }}" step="0.01" min="0">
                                        <select class="form-control" name="setupfee_currency" style="max-width: 100px;">
                                            <option value="USD" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                                            <option value="EUR" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                            <option value="GBP" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'GBP' ? 'selected' : '' }}>GBP</option>
                                            <option value="SAR" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'SAR' ? 'selected' : '' }}>SAR</option>
                                            <option value="AED" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'AED' ? 'selected' : '' }}>AED</option>
                                            <option value="EGP" {{ old('setupfee_currency', $product->setupfee_currency ?? 'USD') == 'EGP' ? 'selected' : '' }}>EGP</option>
                                        </select>
                                    </div>
                                    @error('setupfee')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paytype">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select class="form-control @error('paytype') is-invalid @enderror" id="paytype" name="paytype" required>
                                        <option value="">-- اختر طريقة الدفع --</option>
                                        <option value="free" {{ old('paytype', $product->paytype) == 'free' ? 'selected' : '' }}>مجاني</option>
                                        <option value="onetime" {{ old('paytype', $product->paytype) == 'onetime' ? 'selected' : '' }}>لمرة واحدة</option>
                                        <option value="recurring" {{ old('paytype', $product->paytype) == 'recurring' ? 'selected' : '' }}>متكرر</option>
                                    </select>
                                    @error('paytype')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billingcycle">دورة الفوترة <span class="text-danger">*</span></label>
                                    <select class="form-control @error('billingcycle') is-invalid @enderror" id="billingcycle" name="billingcycle" required>
                                        <option value="">-- اختر دورة الفوترة --</option>
                                        <option value="Free" {{ old('billingcycle', $product->billingcycle) == 'Free' ? 'selected' : '' }}>مجاني</option>
                                        <option value="One Time" {{ old('billingcycle', $product->billingcycle) == 'One Time' ? 'selected' : '' }}>لمرة واحدة</option>
                                        <option value="Monthly" {{ old('billingcycle', $product->billingcycle) == 'Monthly' ? 'selected' : '' }}>شهري</option>
                                        <option value="Quarterly" {{ old('billingcycle', $product->billingcycle) == 'Quarterly' ? 'selected' : '' }}>ربع سنوي</option>
                                        <option value="Semi-Annually" {{ old('billingcycle', $product->billingcycle) == 'Semi-Annually' ? 'selected' : '' }}>نصف سنوي</option>
                                        <option value="Annually" {{ old('billingcycle', $product->billingcycle) == 'Annually' ? 'selected' : '' }}>سنوي</option>
                                        <option value="Biennially" {{ old('billingcycle', $product->billingcycle) == 'Biennially' ? 'selected' : '' }}>كل سنتين</option>
                                        <option value="Triennially" {{ old('billingcycle', $product->billingcycle) == 'Triennially' ? 'selected' : '' }}>كل ثلاث سنوات</option>
                                    </select>
                                    @error('billingcycle')
                                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">تحديث المنتج</button>
                        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-default">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">معلومات المنتج</h3>
                </div>
                <div class="card-body">
                    <p><strong>معرف المنتج:</strong> {{ $product->id }}</p>
                    <p><strong>معرف WHMCS:</strong> {{ $product->whmcs_id }}</p>
                    <p><strong>الحالة:</strong>
                        @if ($product->status == 'Active')
                            <span class="badge badge-success">نشط</span>
                        @elseif ($product->status == 'Inactive')
                            <span class="badge badge-secondary">غير نشط</span>
                        @else
                            <span class="badge badge-info">{{ $product->status }}</span>
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">ملاحظات</h3>
                </div>
                <div class="card-body">
                    <p>سيتم تحديث بيانات المنتج في نظام WHMCS وفي قاعدة البيانات المحلية.</p>
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> تحذير</h5>
                        الحقول المطلوبة يجب ملؤها قبل تحديث المنتج.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection