@php
    $links = $cpLinks ?? [];
@endphp
<div class="tab-pane fade" id="siteTabCyberPanel" role="tabpanel">
    <p class="text-muted small mb-4">افتح أدوات CyberPanel المتقدمة في تبويب جديد — مدير الملفات، WP Manager، ولوحة التحكم.</p>
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <a href="{{ $links['file_manager'] ?? '#' }}" target="_blank" rel="noopener" class="cp-tool-card text-decoration-none text-body">
                <span class="cp-tool-card__icon" style="background:rgba(13,110,253,.12);color:#0d6efd"><i class="fe fe-folder"></i></span>
                <strong>مدير الملفات</strong>
                <span class="small text-muted">تصفح ملفات الموقع على السيرفر</span>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ $links['wp_manager'] ?? '#' }}" target="_blank" rel="noopener" class="cp-tool-card text-decoration-none text-body">
                <span class="cp-tool-card__icon" style="background:rgba(33,117,155,.12);color:#21759b"><i class="fab fa-wordpress"></i></span>
                <strong>WP Manager</strong>
                <span class="small text-muted">لوحة CyberPanel لـ WordPress</span>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ $links['websites'] ?? '#' }}" target="_blank" rel="noopener" class="cp-tool-card text-decoration-none text-body">
                <span class="cp-tool-card__icon" style="background:rgba(91,95,207,.12);color:#5b5fcf"><i class="fe fe-globe"></i></span>
                <strong>قائمة المواقع</strong>
                <span class="small text-muted">كل مواقع الاستضافة</span>
            </a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ $links['panel'] ?? route('admin.cyberpanel.panel') }}" target="_blank" rel="noopener" class="cp-tool-card text-decoration-none text-body">
                <span class="cp-tool-card__icon" style="background:rgba(108,117,125,.12);color:#6c757d"><i class="fe fe-server"></i></span>
                <strong>لوحة CyberPanel</strong>
                <span class="small text-muted">الصفحة الرئيسية للوحة</span>
            </a>
        </div>
    </div>
    <div class="alert alert-light border small mt-4 mb-0">
        <strong>ملاحظة:</strong> التحديثات المتقدمة للنواة، المستخدمين، وWP-CLI الحر تتم من WP Manager في CyberPanel مباشرة.
    </div>
</div>
