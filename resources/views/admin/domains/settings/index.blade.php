@extends('admin.layouts.master')
@section('page-title') إعدادات فوترة النطاقات @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">فوترة النطاقات</h4>
                <p class="text-muted mb-0 small">يُستخدم المبلغ الافتراضي عند ربط نطاق بعميل إن لم يُوجد سعر في WHMCS</p>
            </div>
            <a href="{{ route('admin.domains.index') }}" class="btn btn-light btn-sm">مركز النطاقات</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card custom-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.domains.settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">مبلغ النطاق الافتراضي (ر.س)</label>
                            <input type="number" name="renewal_amount" class="form-control" min="0" step="0.01"
                                value="{{ old('renewal_amount', $billing['renewal_amount'] ?? 0) }}">
                            <small class="text-muted">يُستبدل تلقائياً بـ recurringamount من WHMCS إن وُجد للنطاق</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">أيام استحقاق الفاتورة</label>
                            <input type="number" name="invoice_due_days" class="form-control" min="1" max="90"
                                value="{{ old('invoice_due_days', $billing['invoice_due_days'] ?? 7) }}">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
