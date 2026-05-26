@if(!empty($filebrowserMissing) && $site->service_uuid && in_array($site->status, ['running', 'failed'], true))
<form method="POST" action="{{ route('admin.coolify.wordpress-sites.attach-filebrowser', $site->uuid) }}" class="d-inline"
    onsubmit="return confirm('سيتم تحديث compose وإعادة نشر الخدمة لإضافة FileBrowser. قد يستغرق بضع دقائق. متابعة؟');">
    @csrf
    <button type="submit" class="btn btn-info btn-sm">
        <i class="fe fe-folder"></i> إرفاق FileBrowser
    </button>
</form>
@endif
