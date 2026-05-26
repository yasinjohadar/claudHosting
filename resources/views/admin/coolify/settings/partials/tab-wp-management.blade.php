<div class="tab-pane fade" id="tabWpMgmt" role="tabpanel">
    <p class="small text-muted">إعدادات WP-CLI وDocker لمواقع WordPress. إدارة المواقع تتطلب SSH (تبويب SSH).</p>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <label class="form-check mb-0">
                <input type="checkbox" name="wordpress_filebrowser_enabled" value="1" class="form-check-input"
                    {{ old('wordpress_filebrowser_enabled', $form['wordpress_filebrowser_enabled'] ?? true) ? 'checked' : '' }}>
                إرفاق <strong>FileBrowser</strong> تلقائياً مع كل موقع WordPress جديد (نفس volume ملفات الموقع)
            </label>
            <p class="small text-muted mb-0 mt-1">المواقع القديمة المنشأة قبل التفعيل لا تُحدَّث تلقائياً.</p>
        </div>
        <div class="col-md-4">
            <label class="form-label">بادئة نطاق FileBrowser</label>
            <input type="text" name="wordpress_filebrowser_subdomain_prefix" class="form-control" dir="ltr"
                value="{{ old('wordpress_filebrowser_subdomain_prefix', $form['wordpress_filebrowser_subdomain_prefix'] ?? 'files') }}"
                placeholder="files">
            <div class="form-text">مثال: <code dir="ltr">https://files.my-shop.example.com</code></div>
        </div>
        <div class="col-md-4">
            <label class="form-label">وسم صورة WordPress (مواقع جديدة)</label>
            <input type="text" name="wordpress_docker_tag" class="form-control" dir="ltr"
                value="{{ old('wordpress_docker_tag', $form['wordpress_docker_tag'] ?? 'latest') }}"
                placeholder="latest أو 6.7-php8.2-apache">
        </div>
        <div class="col-md-4">
            <label class="form-label">طابور إدارة WP</label>
            <input type="text" name="wordpress_management_queue" class="form-control" dir="ltr"
                value="{{ old('wordpress_management_queue', $form['wordpress_management_queue'] ?? 'coolify-provision') }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <label class="form-check mb-2">
                <input type="checkbox" name="wordpress_redis_enabled" value="1" class="form-check-input"
                    {{ old('wordpress_redis_enabled', $form['wordpress_redis_enabled'] ?? false) ? 'checked' : '' }}>
                تفعيل Redis (متغيرات بيئة)
            </label>
        </div>
        <div class="col-md-6">
            <label class="form-label">Redis Host</label>
            <input type="text" name="wordpress_redis_host" class="form-control" dir="ltr"
                value="{{ old('wordpress_redis_host', $form['wordpress_redis_host'] ?? '') }}" placeholder="redis أو IP">
        </div>
        <div class="col-md-6">
            <label class="form-label">Redis Port</label>
            <input type="number" name="wordpress_redis_port" class="form-control"
                value="{{ old('wordpress_redis_port', $form['wordpress_redis_port'] ?? 6379) }}">
        </div>
    </div>
    <p class="small text-muted mb-0">ثبّت إضافة Redis Object Cache يدوياً بعد تفعيل المتغيرات.</p>
</div>
