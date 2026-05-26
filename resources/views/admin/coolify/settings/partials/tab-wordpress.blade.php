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
    <div class="card border mb-3">
        <div class="card-body py-3">
            <h6 class="card-title mb-2"><i class="fe fe-globe text-primary"></i> أنواع النطاق في المعالج</h6>
            <label class="form-check mb-2">
                <input type="hidden" name="wordpress_custom_domain_enabled" value="0">
                <input type="checkbox" name="wordpress_custom_domain_enabled" value="1" class="form-check-input"
                    {{ old('wordpress_custom_domain_enabled', $form['wordpress_custom_domain_enabled'] ?? true) ? 'checked' : '' }}>
                السماح بإنشاء مواقع على <strong>دومين مستقل</strong> (مثل <code dir="ltr">example.com</code>)
            </label>
            <p class="form-text mb-0">
                عند التفعيل يظهر خيار «دومين مستقل» في معالج الإنشاء مع FileBrowser على
                <code dir="ltr">files.{الدومين}</code>.
                تعطيله لا يؤثر على مواقع النطاق الفرعي الحالية (<code>slug.{{ $form['wordpress_base_domain'] ?: '…' }}</code>).
            </p>
        </div>
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
    <div class="card border mb-3">
        <div class="card-body py-3">
            <h6 class="card-title mb-2"><i class="fe fe-folder text-info"></i> FileBrowser (مدير ملفات الويب)</h6>
            <div class="mb-3">
                <label class="form-check mb-0">
                    <input type="checkbox" name="wordpress_filebrowser_enabled" value="1" class="form-check-input"
                        {{ old('wordpress_filebrowser_enabled', $form['wordpress_filebrowser_enabled'] ?? true) ? 'checked' : '' }}>
                    إرفاق <strong>FileBrowser</strong> تلقائياً مع كل موقع WordPress جديد
                </label>
                <p class="form-text mb-0 mt-1">يُضاف كحاوية ثالثة (مع wordpress و mariadb) وتشارك ملفات الموقع. المواقع القديمة: زر «إرفاق FileBrowser» من صفحة الموقع.</p>
            </div>
            <div class="mb-3">
                <label class="form-label">بادئة نطاق FileBrowser</label>
                <input type="text" name="wordpress_filebrowser_subdomain_prefix" class="form-control" dir="ltr"
                    value="{{ old('wordpress_filebrowser_subdomain_prefix', $form['wordpress_filebrowser_subdomain_prefix'] ?? 'files') }}"
                    placeholder="files">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">طريقة فتح FileBrowser</label>
                    @php $fbOpen = old('wordpress_filebrowser_open_mode', $form['wordpress_filebrowser_open_mode'] ?? 'embed'); @endphp
                    <select name="wordpress_filebrowser_open_mode" class="form-select">
                        <option value="embed" {{ $fbOpen === 'embed' ? 'selected' : '' }}>صفحة مدمجة داخل اللوحة (افتراضي)</option>
                        <option value="new_tab" {{ $fbOpen === 'new_tab' ? 'selected' : '' }}>تاب جديد (الرابط المباشر)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم مستخدم FileBrowser الافتراضي</label>
                    <input type="text" name="wordpress_filebrowser_admin_username" class="form-control" dir="ltr"
                        value="{{ old('wordpress_filebrowser_admin_username', $form['wordpress_filebrowser_admin_username'] ?? 'admin') }}"
                        pattern="[a-z0-9_-]+" maxlength="32">
                </div>
            </div>
            <div class="row g-3 mb-0">
                <div class="col-md-6">
                    <label class="form-label">شكل النطاق الفرعي</label>
                    <select name="wordpress_filebrowser_subdomain_style" class="form-select">
                        @php $fbStyle = old('wordpress_filebrowser_subdomain_style', $form['wordpress_filebrowser_subdomain_style'] ?? 'flat'); @endphp
                        <option value="flat" {{ $fbStyle === 'flat' ? 'selected' : '' }}>مستوى واحد (موصى به + Cloudflare مجاني)</option>
                        <option value="nested" {{ $fbStyle === 'nested' ? 'selected' : '' }}>مستويان: files.slug.domain</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">طول كلمة مرور FileBrowser</label>
                    <input type="number" name="wordpress_filebrowser_password_length" class="form-control" min="12" max="64"
                        value="{{ old('wordpress_filebrowser_password_length', $form['wordpress_filebrowser_password_length'] ?? 20) }}">
                </div>
                <div class="col-12">
                    <div class="form-text">
                        <strong>flat:</strong> <code dir="ltr">https://my-shop-files.{{ $form['wordpress_base_domain'] ?: 'claudsoft.com' }}</code> — شهادة Cloudflare المجانية تعمل.<br>
                        <strong>nested:</strong> <code dir="ltr">https://files.my-shop.{{ $form['wordpress_base_domain'] ?: 'claudsoft.com' }}</code> — قد يعطي <code>ERR_SSL_VERSION_OR_CIPHER_MISMATCH</code> بدون شهادة متقدمة.
                    </div>
                </div>
            </div>
        </div>
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
