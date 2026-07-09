@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'ربط Instance';
    $evoTitle = 'ربط QR — ' . $instance->instance_name;
    $evoSubtitle = 'امسح الرمز من تطبيق واتساب على الهاتف';
    $evoBreadcrumb = 'QR';
@endphp

@section('evo-content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card custom-card border-0 shadow-sm text-center">
            <div class="card-header bg-transparent">
                <div class="card-title mb-0"><i class="ri-qr-code-line me-2"></i>{{ $instance->instance_name }}</div>
            </div>
            <div class="card-body p-4">
                <p class="mb-3">الحالة:
                    <span id="conn-state" class="badge bg-{{ $instance->isConnected() ? 'success' : 'secondary' }}-transparent text-{{ $instance->isConnected() ? 'success' : 'secondary' }} fs-13">
                        {{ $instance->connection_status }}
                    </span>
                </p>

                <div id="qr-wrap" class="mb-4 p-3 bg-light rounded d-inline-block">
                    @if($instance->qr_code)
                        <img src="{{ str_starts_with($instance->qr_code, 'data:') ? $instance->qr_code : 'data:image/png;base64,'.$instance->qr_code }}" alt="QR" class="rounded" style="max-width:260px">
                    @else
                        <div class="text-muted py-5 px-4"><i class="ri-qr-code-line fs-48 d-block mb-2 opacity-50"></i>اضغط «جلب QR»</div>
                    @endif
                </div>

                <div id="qr-status-msg" class="d-none mb-3"></div>

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" id="fetch-qr" class="btn btn-success"><i class="ri-refresh-line me-1"></i> جلب QR</button>
                    <button type="button" id="check-status" class="btn btn-outline-success"><i class="ri-link me-1"></i> تحقق من الاتصال</button>
                    <a href="{{ route('admin.evolution-api.instances.index') }}" class="btn btn-light border">رجوع</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('evo-scripts')
<script>
(function () {
    const qrUrl = @json(route('admin.evolution-api.instances.qr', $instance->instance_name));
    const statusUrl = @json(route('admin.evolution-api.instances.status', $instance->instance_name));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msgEl = document.getElementById('qr-status-msg');

    document.getElementById('fetch-qr').addEventListener('click', async () => {
        const res = await fetch(qrUrl, { headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.qr) {
            const src = data.qr.startsWith('data:') ? data.qr : 'data:image/png;base64,' + data.qr;
            document.getElementById('qr-wrap').innerHTML = '<img src="' + src + '" alt="QR" class="rounded" style="max-width:260px">';
            window.evoShowInlineAlert(msgEl, 'تم جلب QR — امسحه من واتساب', 'success');
        } else {
            window.evoShowInlineAlert(msgEl, data.message || 'لم يُرجَع QR', 'danger');
        }
    });

    document.getElementById('check-status').addEventListener('click', async () => {
        const res = await fetch(statusUrl, { headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        const data = await res.json();
        const badge = document.getElementById('conn-state');
        const open = data.state === 'open';
        badge.textContent = data.state || 'unknown';
        badge.className = 'badge fs-13 bg-' + (open ? 'success' : 'secondary') + '-transparent text-' + (open ? 'success' : 'secondary');
        window.evoShowInlineAlert(msgEl, open ? 'متصل بنجاح!' : ('الحالة: ' + (data.state || 'غير متصل')), open ? 'success' : 'warning');
    });
})();
</script>
@endsection
