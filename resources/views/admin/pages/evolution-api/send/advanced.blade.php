@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'إرسال ' . $type;
    $evoTitle = 'إرسال ' . $type;
    $evoSubtitle = 'Payload JSON حسب Evolution API';
    $evoBreadcrumb = 'إرسال';
@endphp

@section('evo-content')
@include('admin.pages.evolution-api.partials.send-nav', ['instanceName' => $instanceName])

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card custom-card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-code-box-line me-2"></i>{{ $type }}</div>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small">حقل <code>number</code> يُضاف تلقائياً. راجع <a href="https://doc.evolution-api.com/" target="_blank" rel="noopener">التوثيق</a>.</p>
                <form method="POST" action="{{ route('admin.evolution-api.send.advanced.store', $type) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">المستلم</label>
                        <input type="text" name="to" class="form-control" required value="{{ old('to') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payload JSON</label>
                        <textarea name="payload" class="form-control font-monospace" rows="12" required>{{ old('payload', '{}') }}</textarea>
                    </div>
                    <button class="btn btn-success"><i class="ri-send-plane-line me-1"></i> إرسال</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
