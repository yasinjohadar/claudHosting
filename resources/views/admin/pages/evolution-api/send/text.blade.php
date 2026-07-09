@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'إرسال نص';
    $evoTitle = 'إرسال رسالة';
    $evoSubtitle = 'إرسال رسالة نصية عبر Evolution API';
    $evoBreadcrumb = 'إرسال';
@endphp

@section('evo-content')
@include('admin.pages.evolution-api.partials.send-nav', ['instanceName' => $instanceName])

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-chat-1-line me-2 text-success"></i>رسالة نصية</div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.evolution-api.send.text.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">المستلم</label>
                        <input type="text" name="to" class="form-control" required placeholder="905050580036 أو xxx@g.us" value="{{ old('to', request('to')) }}">
                        <div class="form-text">رقم دولي أو JID مجموعة</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">النص</label>
                        <textarea name="text" class="form-control" rows="5" required placeholder="اكتب رسالتك...">{{ old('text') }}</textarea>
                    </div>
                    <button class="btn btn-success"><i class="ri-send-plane-line me-1"></i> إرسال الآن</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
