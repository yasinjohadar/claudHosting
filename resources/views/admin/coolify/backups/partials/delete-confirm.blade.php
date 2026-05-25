<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="deleteConfirmForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">{{ $title ?? 'تأكيد الحذف' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ $message ?? 'هل أنت متأكد؟' }}</p>
                    @if(!empty($showDeleteS3))
                    <label class="form-check">
                        <input type="checkbox" name="delete_s3" value="1" class="form-check-input">
                        حذف الملفات من S3 أيضاً
                    </label>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const form = document.getElementById('deleteConfirmForm');
        if (btn && form && btn.dataset.action) {
            form.action = btn.dataset.action;
        }
    });
});
</script>

