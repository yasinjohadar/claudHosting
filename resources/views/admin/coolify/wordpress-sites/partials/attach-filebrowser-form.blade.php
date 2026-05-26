@if(!empty($filebrowserMissing) && $site->service_uuid && in_array($site->status, ['running', 'failed'], true))
<form method="POST" action="{{ route('admin.coolify.wordpress-sites.attach-filebrowser', $site->uuid) }}" class="d-inline"
    onsubmit="return confirm('سيتم تحديث compose وإعادة نشر الخدمة لإضافة FileBrowser. قد يستغرق 3–8 دقائق وستبقى الصفحة قيد التحميل. متابعة؟');">
    @csrf
    <button type="submit" class="btn btn-info btn-sm" id="btnAttachFilebrowser">
        <i class="fe fe-folder"></i> إرفاق FileBrowser
    </button>
</form>
@endif
