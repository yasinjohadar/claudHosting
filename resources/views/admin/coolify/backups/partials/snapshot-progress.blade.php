<div class="card custom-card mb-3" id="snapshotProgressCard">
    <div class="card-body">
        <h6>تقدم اللقطة</h6>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar" id="snapshotProgressBar" style="width: 0%"></div>
        </div>
        <div id="snapshotProgressText" class="small text-muted">جاري التحديث...</div>
        <p class="small text-muted mb-0 mt-2">
            تتطلّب اللقطة عامل طابور: <code>php artisan queue:work --queue=coolify-backups</code>.
            نسخ قاعدة البيانات عبر Coolify قد يستغرق عدة دقائق لكل DB.
        </p>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const statusUrl = @json(route('admin.coolify.backups.snapshots.status', ['uuid' => $snapshotUuid]));
    let pollTimer = null;

    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                const stats = d.stats || {};
                const total = stats.total || 1;
                const done = (stats.completed || 0) + (stats.failed || 0);
                const pct = Math.round((done / total) * 100);
                const bar = document.getElementById('snapshotProgressBar');
                const text = document.getElementById('snapshotProgressText');
                if (bar) bar.style.width = pct + '%';
                if (text) {
                    text.textContent =
                        'مكتمل: ' + (stats.completed||0) + ' | فاشل: ' + (stats.failed||0)
                        + ' | معلّق/جاري: ' + (stats.running||0);
                }
                const snapStatus = d.snapshot?.status || '';
                const stillActive = ['pending', 'running'].includes(snapStatus)
                    || (stats.running || 0) > 0;
                if (stillActive) {
                    pollTimer = setTimeout(poll, 3000);
                } else if (snapStatus === 'completed' || snapStatus === 'partial' || snapStatus === 'failed') {
                    setTimeout(() => window.location.reload(), 1500);
                }
            })
            .catch(() => { pollTimer = setTimeout(poll, 5000); });
    }
    poll();
})();
</script>
@endpush
