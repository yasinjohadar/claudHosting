@extends('portal.layouts.master')
@section('page-title') الرئيسية
@section('content')
<h4 class="mb-4">مرحباً، {{ $user->name }}</h4>
<p class="text-muted">هذه لوحة خدماتك — كل ما تم ربطه بحسابك في النظام.</p>
<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('portal.domains.index') }}" class="card text-decoration-none h-100 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="display-6 text-primary">{{ $summary['domains'] }}</div>
                <div class="text-muted">نطاق</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('portal.projects.index') }}" class="card text-decoration-none h-100 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="display-6 text-secondary">{{ $summary['projects'] }}</div>
                <div class="text-muted">مشروع Coolify</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('portal.hosting.index') }}" class="card text-decoration-none h-100 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="display-6 text-warning">{{ $summary['hosting'] }}</div>
                <div class="text-muted">حساب استضافة</div>
            </div>
        </a>
    </div>
</div>
@endsection
