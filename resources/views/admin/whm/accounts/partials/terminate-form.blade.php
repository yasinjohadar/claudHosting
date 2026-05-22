@if(($configured ?? false) && $account->status !== 'terminated')
@php $embedded = $embedded ?? false; @endphp
<div class="whm-danger-zone">
    <div class="d-flex align-items-start gap-2 mb-2">
        <i class="fe fe-alert-triangle text-danger mt-1"></i>
        <div>
            <div class="fw-semibold text-danger">منطقة الخطر</div>
            <p class="small text-muted mb-0">حذف نهائي من WHM — لا يمكن التراجع.</p>
        </div>
    </div>
    <form method="post" action="{{ route('admin.whm.accounts.destroy', $account) }}"
        onsubmit="return confirm('حذف نهائي من WHM؟') && prompt('اكتب {{ $account->username }} للتأكيد') === '{{ $account->username }}';">
        @csrf
        @method('DELETE')
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="keep_dns" value="1" id="keepDns">
            <label class="form-check-label small" for="keepDns">الإبقاء على سجلات DNS</label>
        </div>
        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
            <i class="fe fe-trash-2 me-1"></i>حذف الحساب من WHM
        </button>
    </form>
</div>
@endif
