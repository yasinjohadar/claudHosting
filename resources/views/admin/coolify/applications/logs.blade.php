@extends('admin.layouts.master')
@section('page-title') سجلات التطبيق @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <h4 class="my-4">سجلات التطبيق <code>{{ $uuid }}</code></h4>
        <a href="{{ route('admin.coolify.applications.show', $uuid) }}" class="btn btn-light mb-3">رجوع</a>
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">السجلات (تحديث تلقائي)</div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefreshLogs">تحديث الآن</button>
            </div>
            <div class="card-body p-0">
                <pre id="logsOutput" class="mb-0 p-3 small" style="min-height:400px;max-height:70vh;overflow:auto;direction:ltr;text-align:left;">جاري التحميل...</pre>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const url = @json(route('admin.coolify.applications.logs.fetch', $uuid));
    const el = document.getElementById('logsOutput');
    function render(data) {
        if (typeof data === 'string') { el.textContent = data; return; }
        el.textContent = JSON.stringify(data, null, 2);
    }
    function load() {
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(d => {
                if (d.success) render(d.data);
                else el.textContent = d.message || 'فشل جلب السجلات';
            })
            .catch(e => { el.textContent = e.message; });
    }
    document.getElementById('btnRefreshLogs')?.addEventListener('click', load);
    load();
    setInterval(load, 5000);
})();
</script>
@endpush
@endsection

