@extends('client.layouts.master')

@section('page-title')
سداد فاتورة {{ $invoice->invoice_number }}
@stop

@section('css')
@include('client.partials.portal-ui-styles')
@endsection

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="client-portal-hero d-md-flex align-items-center justify-content-between flex-wrap gap-3 my-4">
            <div>
                <h4 class="mb-1 fw-semibold">سداد فاتورة {{ $invoice->invoice_number }}</h4>
                <nav>
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('client.invoices.index') }}">الفواتير</a></li>
                        <li class="breadcrumb-item active">سداد</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('client.invoices.show', $invoice) }}" class="btn btn-light btn-sm rounded-pill">
                <i class="fe fe-arrow-right me-1"></i>العودة للفاتورة
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">تعليمات التحويل البنكي</div></div>
                    <div class="card-body">
                        @if($bank['account_name'] || $bank['iban'] || $bank['bank_name'])
                            <ul class="list-unstyled mb-3">
                                @if($bank['bank_name'])
                                    <li class="mb-2"><span class="text-muted">البنك:</span> <strong>{{ $bank['bank_name'] }}</strong></li>
                                @endif
                                @if($bank['account_name'])
                                    <li class="mb-2"><span class="text-muted">اسم الحساب:</span> <strong>{{ $bank['account_name'] }}</strong></li>
                                @endif
                                @if($bank['iban'])
                                    <li class="mb-2"><span class="text-muted">IBAN:</span> <strong dir="ltr">{{ $bank['iban'] }}</strong></li>
                                @endif
                            </ul>
                        @else
                            <p class="text-muted">يرجى التواصل مع الإدارة للحصول على بيانات التحويل البنكي.</p>
                        @endif
                        <p class="small text-muted mb-0">{{ $bank['instructions'] }}</p>
                        <hr>
                        <p class="mb-1"><span class="text-muted">المبلغ المتبقي:</span></p>
                        <h4 class="text-danger mb-0">{{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">إبلاغ عن الدفع</div></div>
                    <div class="card-body">
                        <form action="{{ route('client.invoices.pay.store', $invoice) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">المبلغ المحوّل <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                        value="{{ old('amount', $invoice->balance) }}" step="0.01" min="0.01" max="{{ $invoice->balance }}" required>
                                    <span class="input-group-text">ر.س</span>
                                </div>
                                @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات (رقم العملية أو تفاصيل إضافية)</label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">إثبات التحويل (اختياري)</label>
                                <input type="file" name="proof" class="form-control @error('proof') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">PDF أو صورة، بحد أقصى 5MB</small>
                                @error('proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-send me-1"></i>إرسال إبلاغ الدفع
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
