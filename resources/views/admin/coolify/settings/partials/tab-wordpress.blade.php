<div class="tab-pane fade" id="tabWordpress" role="tabpanel">
    <p class="small text-muted">لإنشاء مواقع بروابط فرعية تلقائية (<code>mysite.{{ $form['wordpress_base_domain'] ?: 'sites.example.com' }}</code>) يجب ضبط <strong>Wildcard DNS</strong> (*.{base_domain}) على السيرفر/النطاق.</p>
    @if(!($wordpressReadiness['ready'] ?? false))
        <div class="alert alert-warning py-2 small">لتفعيل معالج WordPress: اضبط النطاق الأساسي والسيرفر الافتراضي.</div>
    @endif
    <div class="mb-3">
        <label class="form-label">النطاق الأساسي *</label>
        <input type="text" name="wordpress_base_domain" class="form-control" dir="ltr"
            value="{{ old('wordpress_base_domain', $form['wordpress_base_domain'] ?? '') }}"
            placeholder="sites.example.com">
    </div>
    <div class="mb-3">
        <label class="form-label">السيرفر الافتراضي *</label>
        <select name="wordpress_default_server_uuid" class="form-select">
            <option value="">— اختر —</option>
            @foreach($wordpressServers ?? [] as $srv)
                <option value="{{ $srv['uuid'] ?? '' }}" {{ old('wordpress_default_server_uuid', $form['wordpress_default_server_uuid'] ?? '') === ($srv['uuid'] ?? '') ? 'selected' : '' }}>
                    {{ $srv['name'] ?? $srv['uuid'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">مشروع مشترك (اختياري)</label>
        <select name="wordpress_shared_project_uuid" class="form-select">
            <option value="">— بدون —</option>
            @foreach($wordpressProjects ?? [] as $prj)
                <option value="{{ $prj['uuid'] ?? '' }}" {{ old('wordpress_shared_project_uuid', $form['wordpress_shared_project_uuid'] ?? '') === ($prj['uuid'] ?? '') ? 'selected' : '' }}>
                    {{ $prj['name'] ?? $prj['uuid'] }}
                </option>
            @endforeach
        </select>
        <div class="form-text">يُستخدم عند اختيار «مشروع مشترك» في معالج إنشاء الموقع.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">نوع خدمة WordPress</label>
        <select name="wordpress_service_type" class="form-select">
            @foreach(config('coolify.wordpress_service_types', []) as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" {{ old('wordpress_service_type', $form['wordpress_service_type'] ?? 'wordpress-with-mariadb') === $typeKey ? 'selected' : '' }}>
                    {{ $typeLabel }}
                </option>
            @endforeach
        </select>
        <div class="form-text">يُستخدم عند إنشاء مواقع WordPress من المعالج. Coolify لا يقبل النوع القديم <code>wordpress</code>.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">UUID وجهة السيرفر (destination) — اختياري</label>
        <input type="text" name="wordpress_default_destination_uuid" class="form-control" dir="ltr"
            value="{{ old('wordpress_default_destination_uuid', $form['wordpress_default_destination_uuid'] ?? '') }}"
            placeholder="مطلوب إن كان السيرفر له أكثر من destination">
        <div class="form-text">من Coolify → Server → Destinations. إن تُرك فارغاً يُستخدم أول وجهة تلقائياً عند وجود واحدة فقط.</div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">البيئة الافتراضية</label>
            <input type="text" name="wordpress_default_environment" class="form-control"
                value="{{ old('wordpress_default_environment', $form['wordpress_default_environment'] ?? 'production') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">طابور الإنشاء</label>
            <input type="text" name="wordpress_provision_queue" class="form-control" dir="ltr"
                value="{{ old('wordpress_provision_queue', $form['wordpress_provision_queue'] ?? 'coolify-provision') }}">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check">
            <input type="checkbox" name="wordpress_instant_deploy" value="1" class="form-check-input"
                {{ old('wordpress_instant_deploy', $form['wordpress_instant_deploy'] ?? true) ? 'checked' : '' }}>
            نشر فوري (instant_deploy)
        </label>
    </div>
</div>
