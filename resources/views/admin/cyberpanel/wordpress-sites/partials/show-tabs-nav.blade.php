@php
    $tabs = [
        ['id' => 'siteTabOverview', 'key' => 'overview', 'icon' => 'fe fe-home', 'label' => 'نظرة عامة', 'hint' => 'ملخص الموقع', 'class' => ''],
        ['id' => 'siteTabWordpress', 'key' => 'wordpress', 'icon' => 'fab fa-wordpress', 'label' => 'إدارة WordPress', 'hint' => 'إضافات وصيانة', 'class' => 'cp-wp-tabs__item--wordpress'],
        ['id' => 'siteTabBackups', 'key' => 'backups', 'icon' => 'fe fe-archive', 'label' => 'النسخ الاحتياطي', 'hint' => 'إنشاء واستعادة', 'class' => 'cp-wp-tabs__item--backup'],
        ['id' => 'siteTabHosting', 'key' => 'hosting', 'icon' => 'fe fe-server', 'label' => 'الاستضافة', 'hint' => 'الباقة والعميل', 'class' => 'cp-wp-tabs__item--hosting'],
        ['id' => 'siteTabCyberPanel', 'key' => 'tools', 'icon' => 'fe fe-tool', 'label' => 'أدوات CyberPanel', 'hint' => 'ملفات ولوحة', 'class' => 'cp-wp-tabs__item--tools'],
    ];
@endphp
<div class="cp-wp-tabs" id="cpWpSiteTabs" role="tablist">
    @foreach($tabs as $i => $tab)
        <button type="button"
            class="cp-wp-tabs__item {{ $tab['class'] }} @if($i === 0) active @endif"
            id="cp-tab-{{ $tab['key'] }}"
            data-bs-toggle="tab"
            data-bs-target="#{{ $tab['id'] }}"
            data-cp-tab="{{ $tab['key'] }}"
            role="tab"
            aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
            <span class="cp-wp-tabs__top">
                <span class="cp-wp-tabs__icon"><i class="{{ $tab['icon'] }}"></i></span>
                <span class="cp-wp-tabs__label">{{ $tab['label'] }}</span>
            </span>
            <span class="cp-wp-tabs__hint">{{ $tab['hint'] }}</span>
        </button>
    @endforeach
</div>
