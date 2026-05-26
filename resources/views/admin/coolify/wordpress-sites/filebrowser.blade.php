@php
    $layout = ($panel ?? 'admin') === 'client' ? 'client.layouts.master' : 'admin.layouts.master';
@endphp
@extends($layout)
@section('page-title') FileBrowser — {{ $site->display_name }} @stop
@push('styles')
<style>
    .filebrowser-embed-page { display: flex; flex-direction: column; min-height: calc(100vh - 120px); }
    .filebrowser-embed-toolbar { flex-shrink: 0; }
    .filebrowser-embed-frame-wrap { flex: 1; min-height: 0; }
    .filebrowser-embed-frame {
        width: 100%;
        height: calc(100vh - 180px);
        min-height: 480px;
        border: 1px solid var(--default-border, #dee2e6);
        border-radius: 0.375rem;
        background: #1a1a1a;
    }
</style>
@endpush
@section('content')
<div class="main-content app-content">
    <div class="container-fluid filebrowser-embed-page">
        <div class="filebrowser-embed-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm"><i class="fe fe-arrow-right"></i> العودة للموقع</a>
                <span class="ms-2 fw-bold">{{ $site->display_name }}</span>
                <span class="text-muted small">— FileBrowser</span>
            </div>
            <div class="d-flex gap-2">
                @if(!empty($externalUrl))
                <a href="{{ $externalUrl }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm" dir="ltr">
                    <i class="fe fe-external-link"></i> فتح في تاب جديد
                </a>
                @endif
                @if(($panel ?? 'admin') === 'admin')
                <form method="post" action="{{ route('admin.coolify.wordpress-sites.filebrowser.rotate-credentials', $site->uuid) }}" class="d-inline"
                    onsubmit="return confirm('إعادة تعيين كلمة مرور FileBrowser؟ سيتم قطع الجلسات الحالية.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">إعادة تعيين بيانات الدخول</button>
                </form>
                @endif
            </div>
        </div>
        <div class="alert alert-info py-2 small mb-3">
            يُدار الدخول تلقائياً من اللوحة — لا حاجة لإدخال كلمة المرور من سجلات Coolify.
        </div>
        <div class="filebrowser-embed-frame-wrap">
            <iframe
                class="filebrowser-embed-frame"
                src="{{ $proxyUrl }}"
                title="FileBrowser — {{ $site->display_name }}"
                allow="clipboard-read; clipboard-write"
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        </div>
    </div>
</div>
@endsection
