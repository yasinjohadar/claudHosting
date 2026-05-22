{{-- featureIcons: catalog, packageFeatures: array of {icon, text}, inputPrefix optional --}}
@php
    $featureIcons = $featureIcons ?? config('package_features.icons', []);
    $rows = old('package_features', $packageFeatures ?? []);
    if (! is_array($rows)) {
        $rows = [];
    }
    $inputPrefix = $inputPrefix ?? 'package_features';
@endphp
<div class="package-features-editor" data-prefix="{{ $inputPrefix }}">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <label class="form-label mb-0 fw-semibold">ميزات الباقة</label>
        <button type="button" class="btn btn-sm btn-outline-primary package-feature-add">
            <i class="fe fe-plus"></i> إضافة بند
        </button>
    </div>
    <p class="text-muted small mb-3">كل بند يظهر في الموقع في سطر مستقل مع الأيقونة المختارة.</p>

    <div class="package-features-list d-flex flex-column gap-2">
        @foreach($rows as $i => $row)
            @include('admin.products.partials.package-feature-row', [
                'index' => $i,
                'row' => $row,
                'featureIcons' => $featureIcons,
                'inputPrefix' => $inputPrefix,
            ])
        @endforeach
    </div>

    <template id="package-feature-row-template">
        @include('admin.products.partials.package-feature-row', [
            'index' => '__INDEX__',
            'row' => ['icon' => 'check', 'text' => ''],
            'featureIcons' => $featureIcons,
            'inputPrefix' => $inputPrefix,
        ])
    </template>
</div>

@once
@push('scripts')
<script>
(function () {
    function reindexEditor(editor) {
        const prefix = editor.dataset.prefix || 'package_features';
        editor.querySelectorAll('.package-feature-row').forEach((row, i) => {
            row.dataset.index = i;
            row.querySelectorAll('[data-field]').forEach(el => {
                const field = el.dataset.field;
                el.name = `${prefix}[${i}][${field}]`;
            });
        });
    }

    document.querySelectorAll('.package-features-editor').forEach(editor => {
        const list = editor.querySelector('.package-features-list');
        const tpl = document.getElementById('package-feature-row-template');
        if (!list || !tpl) return;

        editor.querySelector('.package-feature-add')?.addEventListener('click', () => {
            const i = list.querySelectorAll('.package-feature-row').length;
            const html = tpl.innerHTML.replace(/__INDEX__/g, String(i));
            list.insertAdjacentHTML('beforeend', html);
            reindexEditor(editor);
        });

        list.addEventListener('click', e => {
            const btn = e.target.closest('.package-feature-remove');
            if (!btn) return;
            btn.closest('.package-feature-row')?.remove();
            reindexEditor(editor);
        });

        reindexEditor(editor);
    });
})();
</script>
@endpush
@endonce
