@extends('admin.layouts.master')
@section('page-title') سجلات الخدمة @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between my-4">
            <h4>سجلات الخدمة <code>{{ $uuid }}</code></h4>
            <a href="{{ route('admin.coolify.services.show', $uuid) }}" class="btn btn-outline-secondary btn-sm">عودة</a>
        </div>
        <div class="card custom-card">
            <div class="card-body">
                <button type="button" class="btn btn-primary btn-sm mb-3" id="fetchLogs">تحديث السجلات</button>
                <div id="logsArea">
                    @if(!empty($logs))
                        @foreach($logs as $name => $block)
                        <h6 class="mt-2">{{ $name }}</h6>
                        <pre class="bg-dark text-light p-3 small" style="max-height:320px;overflow:auto">{{ $block['lines'] ?? '' }}</pre>
                        @endforeach
                    @else
                        <p class="text-muted">اضغط «تحديث السجلات» لجلب سجلات التطبيقات الفرعية عبر Coolify API.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('fetchLogs')?.addEventListener('click', function() {
    fetch('{{ route('admin.coolify.services.logs.fetch', $uuid) }}?lines=200', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (!data.logs || Object.keys(data.logs).length === 0) {
                html = '<p class="text-muted">لا سجلات متاحة.</p>';
            } else {
                for (const [name, block] of Object.entries(data.logs)) {
                    html += '<h6 class="mt-2">'+name+'</h6><pre class="bg-dark text-light p-3 small" style="max-height:320px;overflow:auto">'+(block.lines || '')+'</pre>';
                }
            }
            document.getElementById('logsArea').innerHTML = html;
        });
});
</script>
@endsection

