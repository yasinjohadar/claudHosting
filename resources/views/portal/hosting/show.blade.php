@extends('portal.layouts.master')
@section('page-title') استضافة: {{ $account->domain }}
@section('content')
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">{{ $account->domain }}</h4>
        <p class="text-muted small mb-0"><code dir="ltr">{{ $account->username }}</code></p>
    </div>
    <div class="d-flex gap-2">
        @if($account->status !== 'terminated')
            <a href="{{ route('portal.hosting.cpanel', $account) }}" class="btn btn-warning btn-sm" target="_blank" rel="noopener">فتح cPanel</a>
        @endif
        <a href="{{ route('portal.hosting.index') }}" class="btn btn-light btn-sm">رجوع</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p><strong>الحالة:</strong>
                    <span class="badge bg-{{ $account->status === 'active' ? 'success' : 'warning' }}-transparent">{{ $account->status_label }}</span>
                </p>
                <p><strong>الباقة:</strong> {{ $account->package ?: '—' }}</p>
                <p><strong>البريد:</strong> <span dir="ltr">{{ $account->display_email ?? '—' }}</span></p>
                @if($url = $account->site_url)
                    <p><strong>الموقع:</strong> <a href="{{ $url }}" target="_blank" rel="noopener" dir="ltr">{{ $url }}</a></p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent"><strong>الموارد</strong></div>
            <div class="card-body small">
                @if(!empty($summary))
                    <p class="mb-1">القرص: <span dir="ltr">{{ $summary['diskused'] ?? '—' }} / {{ $summary['disklimit'] ?? '—' }}</span></p>
                    <p class="mb-1">IP: <span dir="ltr">{{ $summary['ip'] ?? '—' }}</span></p>
                    @if(!empty($sslBadge))
                        <p class="mb-0">SSL: <span class="badge {{ $sslBadge['badge'] }}">{{ $sslBadge['label'] }}</span></p>
                    @endif
                @else
                    <p class="text-muted mb-0">تفاصيل الموارد غير متوفرة حالياً.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
