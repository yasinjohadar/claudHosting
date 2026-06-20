<div class="card custom-card mb-4 border-warning">
    <div class="card-header bg-warning-transparent">
        <span class="card-title mb-0">إعادة تثبيت النظام</span>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">يُمسح محتوى القرص ويُعاد تثبيت الصورة المختارة. العملية لا يمكن التراجع عنها.</p>
        <div class="mb-3">
            <label class="form-label small">الصور المتاحة</label>
            <select id="vpsReinstallImage" class="form-select form-select-sm" dir="ltr">
                <option value="">— جاري التحميل —</option>
            </select>
            <div id="vpsReinstallImagesError" class="small text-danger mt-1 d-none"></div>
        </div>
        <form method="POST" action="{{ route('admin.infrastructure.servers.reinstall', $server->uuid) }}" onsubmit="return confirm('تأكيد: سيتم مسح السيرفر وإعادة تثبيت النظام. متابعة؟');">
            @csrf
            <input type="hidden" name="image_id" id="vpsReinstallImageId" value="">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirm_reinstall" value="1" id="confirmReinstall" required>
                <label class="form-check-label small" for="confirmReinstall">أفهم أن جميع البيانات على السيرفر قد تُفقد</label>
            </div>
            <button type="submit" class="btn btn-warning btn-sm" id="vpsReinstallBtn" disabled>إعادة تثبيت</button>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function() {
    const select = document.getElementById('vpsReinstallImage');
    const hidden = document.getElementById('vpsReinstallImageId');
    const btn = document.getElementById('vpsReinstallBtn');
    const err = document.getElementById('vpsReinstallImagesError');
    if (!select) return;

    fetch('{{ route('admin.infrastructure.servers.lifecycle.images', $server->uuid) }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(function(data) {
        if (!data.success) {
            select.innerHTML = '<option value="">—</option>';
            if (err) { err.textContent = data.message || 'تعذّر جلب الصور'; err.classList.remove('d-none'); }
            return;
        }
        const images = data.images || [];
        if (!images.length) {
            select.innerHTML = '<option value="">لا توجد صور</option>';
            return;
        }
        select.innerHTML = '<option value="">اختر صورة…</option>' + images.map(function(img) {
            const id = img.id || img.imageId || img.templateName || img.name || '';
            const label = img.name || img.displayName || img.description || id;
            return '<option value="' + id + '">' + label + '</option>';
        }).join('');
    }).catch(function() {
        if (err) { err.textContent = 'خطأ في الاتصال'; err.classList.remove('d-none'); }
    });

    select.addEventListener('change', function() {
        if (hidden) hidden.value = select.value;
        if (btn) btn.disabled = !select.value;
    });
})();
</script>
@endpush
