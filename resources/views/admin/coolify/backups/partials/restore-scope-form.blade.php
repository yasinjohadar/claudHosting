<form method="POST" action="{{ route('admin.coolify.backups.snapshots.restore', $snapshot->uuid) }}" id="restoreScopeForm">
    @csrf
    <div class="mb-3">
        <label class="form-label">نطاق الاستعادة</label>
        <select name="restore_scope" class="form-select" id="restoreScope">
            <option value="all">استعادة كل موارد اللقطة</option>
            @if($snapshot->project_uuid)
            <option value="project">استعادة مشروع {{ $snapshot->project_name }}</option>
            @endif
            <option value="selected">موارد محددة فقط</option>
        </select>
    </div>
    <div class="mb-3 d-none" id="restoreItemsWrap">
        <label class="form-label">اختر الموارد</label>
        @foreach($snapshot->items->where('status', 'completed') as $item)
            <label class="form-check d-block">
                <input type="checkbox" name="item_ids[]" value="{{ $item->id }}" class="form-check-input restore-item-check" checked>
                {{ $item->resource_name }} ({{ $item->resource_type }} — {{ $item->strategy }})
            </label>
        @endforeach
    </div>
    <div class="mb-3">
        <label class="form-check"><input type="checkbox" name="stop_before_restore" value="1" class="form-check-input" checked> إيقاف الحاويات قبل استعادة volumes</label>
    </div>
    <div class="mb-3">
        <label class="form-check"><input type="checkbox" name="redeploy" value="1" class="form-check-input"> إعادة تشغيل التطبيقات بعد الاستعادة</label>
    </div>
    <div class="alert alert-danger border-0 small d-none" id="restoreFormError"></div>
    <button type="submit" class="btn btn-warning" id="restoreSubmitBtn">
        <span class="restore-btn-label">بدء الاستعادة</span>
        <span class="restore-btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span> جاري البدء…</span>
    </button>
</form>
