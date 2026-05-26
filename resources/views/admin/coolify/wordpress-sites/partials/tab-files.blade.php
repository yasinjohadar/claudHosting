@php
    $filesReady = $wpManagementState['execute_ready'] ?? false;
    $filebrowserUrl = $site->metadata['filebrowser_url'] ?? null;
    $canOpenFilebrowser = $filebrowserUrl && ($site->status ?? '') === 'running';
@endphp
<div class="tab-pane fade" id="siteTabFiles" role="tabpanel">
    <div class="site-files-panel">
        @if(!empty($filebrowserMissing))
        <div class="alert alert-warning py-3 mb-3">
            <strong>FileBrowser غير مثبت</strong> على هذا الموقع — يظهر هنا فقط مدير الملفات عبر SSH.
            @if(empty($isClientPanel) && $site->service_uuid)
            استخدم زر <strong>«إرفاق FileBrowser»</strong> أعلى الصفحة لإضافته وإعادة النشر.
            @endif
        </div>
        @endif
        @if(!$filesReady)
        <div class="alert alert-warning py-3 mb-3">
            <strong>غير متاح:</strong> {{ $wpManagementState['message'] ?? 'اضبط SSH أولاً' }}
        </div>
        @else
        <div class="alert alert-light border small mb-3 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>
                الملفات تُدار عبر <strong>SSH → docker exec / docker cp</strong> داخل حاوية WordPress. المسموح داخل جذر الموقع فقط.
                @if($filebrowserUrl)
                يمكنك أيضاً استخدام <strong>FileBrowser</strong> على نفس ملفات الموقع.
                @endif
            </span>
            @if($filebrowserUrl)
            <a href="{{ $canOpenFilebrowser ? $filebrowserUrl : '#' }}"
                class="btn btn-outline-info btn-sm {{ $canOpenFilebrowser ? '' : 'disabled' }}"
                @if($canOpenFilebrowser) target="_blank" rel="noopener" @endif>
                <i class="fe fe-external-link"></i> فتح FileBrowser
            </a>
            @endif
        </div>
        @if($filebrowserUrl && $canOpenFilebrowser)
        <div class="alert alert-info py-2 small mb-3">
            عند أول فتح لـ FileBrowser عيّن كلمة مرور قوية من واجهة التطبيق.
        </div>
        @endif
        <div id="siteFilesAlert" class="alert py-2 small d-none mb-2"></div>
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <nav id="siteFilesBreadcrumb" class="small flex-grow-1" aria-label="breadcrumb"></nav>
            <button type="button" class="btn btn-outline-primary btn-sm" id="siteFilesRefresh"><i class="fe fe-refresh-cw"></i> تحديث</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="siteFilesMkdir">مجلد جديد</button>
            <label class="btn btn-outline-primary btn-sm mb-0">
                رفع ملف <input type="file" id="siteFilesUploadInput" class="d-none">
            </label>
        </div>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="site-files-table-wrap border rounded">
                    <div id="siteFilesLoading" class="text-center py-5 text-muted d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span> جاري التحميل…
                    </div>
                    <table class="table table-sm table-hover mb-0 site-files-table" id="siteFilesTable">
                        <thead><tr><th>الاسم</th><th>الحجم</th><th>تعديل</th><th></th></tr></thead>
                        <tbody id="siteFilesTableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="border rounded p-2 site-files-editor-wrap">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <span class="small fw-bold" id="siteFilesEditorPath">اختر ملفاً للتحرير</span>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-sm" id="siteFilesSave" disabled>حفظ</button>
                            <a href="#" class="btn btn-outline-secondary btn-sm disabled" id="siteFilesDownload" target="_blank" rel="noopener">تنزيل</a>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="siteFilesDelete" disabled>حذف</button>
                        </div>
                    </div>
                    <div id="siteFilesMonaco" class="site-files-monaco" style="height:420px;border:1px solid var(--default-border);"></div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@if($filesReady)
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.0/min/vs/editor/editor.main.css">
@endpush
@include('admin.coolify.wordpress-sites.partials.files-scripts')
@endif
