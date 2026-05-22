<div class="card custom-card mb-3" id="snapshotProgressCard">
    <div class="card-body">
        <h6>تقدم اللقطة</h6>
        <div class="progress mb-2" style="height: 8px;">
            <div class="progress-bar" id="snapshotProgressBar" style="width: 0%"></div>
        </div>
        <div id="snapshotProgressText" class="small text-muted">جاري التحديث...</div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const uuid = @json($snapshotUuid);
    const statusUrl = @json(route('admin.coolify.backups.snapshots.status', ['uuid' => $snapshotUuid]));
    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                const stats = d.stats || {};
                const total = stats.total || 1;
                const done = (stats.completed || 0) + (stats.failed || 0);
                const pct = Math.round((done / total) * 100);
                document.getElementById('snapshotProgressBar').style.width = pct + '%';
                document.getElementById('snapshotProgressText').textContent =
                    'مكتمل: ' + (stats.completed||0) + ' | فاشل: ' + (stats.failed||0) + ' | جاري: ' + (stats.running||0);
                if ((d.snapshot?.status === 'pending' || d.snapshot?.status === 'running') && stats.running > 0) {
                    setTimeout(poll, 3000);
                }
            });
    }
    poll();
})();
</script>
@endpush
