@extends('frontend.layouts.master')

@section('page-title')
طلب الباقة: {{ $product->name }} | استضافة كلاودسوفت
@endsection

@section('content')
    <section class="course-detail-hero">
        <div class="container">
            <div class="animate-on-scroll">
                <div class="breadcrumb-custom" style="justify-content: flex-start; margin-bottom: 15px;">
                    <a href="{{ url('/') }}">الرئيسية</a><span>/</span><a href="{{ route('frontend.packages') }}">الباقات</a><span>/</span><a href="{{ route('frontend.package-detail', $product->id) }}">{{ $product->name }}</a><span>/</span><span>طلب الباقة</span>
                </div>
                <h1 class="cd-title">طلب الباقة: {{ $product->name }}</h1>
                <p class="text-secondary">بياناتك مأخوذة من حسابك. اختر دورة الفوترة وأضف ملاحظات إن رغبت.</p>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="glass-panel animate-on-scroll p-4">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="p-3 rounded" style="background: var(--clr-bg-secondary); border: 1px solid rgba(255,255,255,0.1);">
                            <h5 class="mb-2">{{ $product->name }}</h5>
                            <p class="text-muted small mb-0">{{ Str::limit(strip_tags($product->description ?? ''), 100) ?: 'باقة استضافة' }}</p>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <form action="{{ route('frontend.package.order.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">الاسم الكامل</label>
                                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">رقم الهاتف</label>
                                    <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+963 XXX XXX XXX">
                                    @error('phone')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">دورة الفوترة <span class="text-danger">*</span></label>
                                    <select name="billing_cycle" class="form-select" required>
                                        @foreach($availableCycles as $key => $info)
                                            <option value="{{ $key }}" {{ old('billing_cycle', 'monthly') == $key ? 'selected' : '' }}>{{ $info['label'] }} — {{ $info['price'] }} $</option>
                                        @endforeach
                                    </select>
                                    @error('billing_cycle')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">ملاحظات</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات أو متطلبات إضافية">{{ old('notes') }}</textarea>
                                    @error('notes')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn-primary-custom"><i class="fas fa-paper-plane"></i> إرسال الطلب</button>
                                    <a href="{{ route('frontend.package-detail', $product->id) }}" class="btn-outline-custom ms-2">إلغاء</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
