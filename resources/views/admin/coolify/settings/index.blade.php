@extends('admin.layouts.master')
@section('page-title') إعدادات Coolify @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h4 class="mb-0">إعدادات اتصال Coolify</h4>
                <nav><ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.coolify.overview') }}">Coolify</a></li>
                    <li class="breadcrumb-item active">الإعدادات</li>
                </ol></nav>
            </div>
        </div>
        @include('admin.coolify.partials.alerts')

        @if($connected ?? false)
        <div class="card custom-card mb-3 border-success">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span><i class="fe fe-check-circle text-success"></i> <strong>اتصال API:</strong> مضبوط من هذه الصفحة</span>
                    <span>
                        @if($readiness['app_storage'] ?? false)<i class="fe fe-check text-success"></i>@else<i class="fe fe-x text-danger"></i>@endif
                        <strong>تخزين اللقطات (S3):</strong>
                        {{ ($readiness['app_storage'] ?? false) ? 'مضبوط' : 'مطلوب — ربط الأقراص' }}
                    </span>
                    <span>
                        @if($readiness['coolify_s3'] ?? false)<i class="fe fe-check text-success"></i>@else<i class="fe fe-x text-warning"></i>@endif
                        <strong>S3 في Coolify (للـ DB):</strong>
                        {{ ($readiness['coolify_s3'] ?? false) ? 'مضبوط' : 'مطلوب أو يُكتشف من نسخ DB' }}
                    </span>
                </div>
                @if(!empty($synced))
                    <p class="small text-success mb-0 mt-2">تم ضبط تلقائياً من Coolify: {{ implode('، ', $synced) }}</p>
                @endif
                @if($readiness['ready'] ?? false)
                    <p class="small text-success mb-0 mt-2">يمكنك إنشاء لقطات <strong>التطبيقات/volumes</strong> الآن. لنسخ <strong>قواعد البيانات</strong> في اللقطة أدخل UUID S3 في Coolify أدناه.</p>
                @else
                    <p class="small text-muted mb-0 mt-2">إدارة المشاريع تعمل باتصال API. لـ <strong>لقطات المشاريع</strong> اختر سجل S3 من «ربط الأقراص» أدناه.</p>
                @endif
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-xl-8">
                <div class="card custom-card mb-4">
                    <div class="card-header"><div class="card-title">بيانات الاتصال</div></div>
                    <div class="card-body">
                        <p class="text-muted small">تُحفظ الإعدادات في قاعدة البيانات (مجموعة <code>coolify</code>) ولا حاجة لتعديل ملف <code>.env</code>.</p>
                        <form action="{{ route('admin.coolify.settings.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">عنوان Coolify API *</label>
                                <input type="url" name="api_url" class="form-control @error('api_url') is-invalid @enderror"
                                    value="{{ old('api_url', $form['api_url'] ?? '') }}"
                                    placeholder="https://coolify.example.com" required dir="ltr">
                                @error('api_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">بدون <code>/api/v1</code> — يُضاف تلقائياً.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رمز API (Bearer Token)</label>
                                <input type="password" name="api_token" class="form-control @error('api_token') is-invalid @enderror"
                                    placeholder="{{ ($form['has_token'] ?? false) ? '••••••••  (اتركه فارغاً للإبقاء على الرمز الحالي)' : 'الصق التوكن من Coolify → Keys & Tokens' }}"
                                    autocomplete="new-password" dir="ltr">
                                @error('api_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if($form['has_token'] ?? false)
                                    <div class="form-text text-success"><i class="fe fe-check"></i> يوجد رمز محفوظ ومشفّر.</div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label">مهلة الطلب (ثوانٍ)</label>
                                <input type="number" name="timeout" class="form-control" min="5" max="120"
                                    value="{{ old('timeout', $form['timeout'] ?? 30) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">طابور Queue للنسخ والاستعادة</label>
                                <input type="text" name="backup_queue" class="form-control @error('backup_queue') is-invalid @enderror"
                                    value="{{ old('backup_queue', $form['backup_queue'] ?? 'coolify-backups') }}"
                                    placeholder="coolify-backups" dir="ltr">
                                @error('backup_queue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">شغّل العامل: <code>php artisan queue:work --queue={{ old('backup_queue', $form['backup_queue'] ?? 'coolify-backups') }}</code></div>
                            </div>
                            <hr>
                            <h6 class="text-muted">تخزين اللقطات — S3 فقط</h6>
                            <p class="small text-muted">لا يُحفظ أي نسخ دائم على سيرفرات Coolify. يُنشأ أرشيف مؤقت في <code>/tmp</code> ثم يُرفع إلى S3 ويُحذف فوراً.</p>
                            @if(!($snapshotStorageReady ?? false))
                                <div class="alert alert-warning py-2 small">اختر سجل تخزين S3 نشط من <a href="{{ route('admin.storage.index') }}">ربط الأقراص</a>.</div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">سجل التخزين (App Storage) *</label>
                                <select name="snapshot_storage_config_id" class="form-select @error('snapshot_storage_config_id') is-invalid @enderror">
                                    <option value="">— اختر S3 / R2 / Wasabi —</option>
                                    @foreach($storageConfigs ?? [] as $sc)
                                        <option value="{{ $sc->id }}" {{ (int) old('snapshot_storage_config_id', $form['snapshot_storage_config_id'] ?? 0) === $sc->id ? 'selected' : '' }}>
                                            {{ $sc->name }} ({{ $sc->driver }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('snapshot_storage_config_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">بادئة المسار داخل الـ bucket</label>
                                <input type="text" name="s3_prefix" class="form-control" value="{{ old('s3_prefix', $form['s3_prefix'] ?? 'coolify-snapshots') }}" dir="ltr">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">UUID تخزين S3 في Coolify (لنسخ قواعد البيانات في اللقطة)</label>
                                <div class="input-group mb-2">
                                    <input type="text" name="coolify_s3_storage_uuid" id="coolifyS3Uuid" class="form-control @error('coolify_s3_storage_uuid') is-invalid @enderror"
                                        value="{{ old('coolify_s3_storage_uuid', $form['coolify_s3_storage_uuid'] ?? '') }}" dir="ltr"
                                        placeholder="من Coolify → Storages → انسخ UUID">
                                    @if($connected ?? false)
                                    <button type="button" class="btn btn-outline-primary" id="btnDiscoverS3">جلب من Coolify</button>
                                    @endif
                                </div>
                                @error('coolify_s3_storage_uuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <strong>اختياري</strong> لنسخ DB عبر Coolify API. Coolify 4 لا يوفّر قائمة S3 في API —
                                    افتح <a href="{{ rtrim($form['api_url'] ?? '', '/') }}/storages" target="_blank" rel="noopener">Storages في Coolify</a>
                                    وانسخ UUID، أو أنشئ جدولة نسخ DB مع S3 ثم «جلب من Coolify».
                                    بدون UUID: لقطات التطبيقات/volumes تعمل؛ DB تُحفظ كـ manifest على S3.
                                </div>
                                <div id="discoverS3Result" class="small mt-2"></div>
                                @if(!empty($coolifyS3Storages))
                                    <div class="form-text mt-2">من API:</div>
                                    <ul class="small mb-0">
                                        @foreach($coolifyS3Storages as $s3)
                                            <li>
                                                <a href="#" class="s3-pick" data-uuid="{{ $s3['uuid'] ?? '' }}">
                                                    <code>{{ $s3['uuid'] ?? '' }}</code></a>
                                                — {{ $s3['name'] ?? $s3['bucket'] ?? '' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <hr>
                            <h6 class="text-muted">مواقع WordPress</h6>
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
                            <hr>
                            <h6 class="text-muted">Cloudflare — حماية DDoS وتسريع</h6>
                            <div class="mb-3">
                                <label class="form-check">
                                    <input type="checkbox" name="wordpress_cloudflare_enabled" value="1" class="form-check-input"
                                        {{ old('wordpress_cloudflare_enabled', $form['wordpress_cloudflare_enabled'] ?? true) ? 'checked' : '' }}>
                                    تفعيل Cloudflare تلقائياً عند إنشاء موقع WordPress
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Zone ID (Cloudflare)</label>
                                <select name="wordpress_cloudflare_zone_id" class="form-select" dir="ltr">
                                    <option value="">— اختر المنطقة —</option>
                                    @foreach($cloudflareZones ?? [] as $zone)
                                        @if(is_array($zone))
                                        <option value="{{ $zone['id'] ?? '' }}" {{ old('wordpress_cloudflare_zone_id', $form['wordpress_cloudflare_zone_id'] ?? '') === ($zone['id'] ?? '') ? 'selected' : '' }}>
                                            {{ $zone['name'] ?? $zone['id'] }} ({{ $zone['status'] ?? '' }})
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="form-text">منطقة النطاق الأساسي (مثل claudsoft.com). يتطلب إعداد Cloudflare API.</div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">قالب الأمان الافتراضي</label>
                                    <select name="wordpress_security_preset" class="form-select">
                                        @foreach(config('coolify.wordpress_security_presets', []) as $presetKey => $presetLabel)
                                        <option value="{{ $presetKey }}" {{ old('wordpress_security_preset', $form['wordpress_security_preset'] ?? 'basic') === $presetKey ? 'selected' : '' }}>{{ $presetLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">وضع SSL</label>
                                    <select name="wordpress_cloudflare_ssl_mode" class="form-select">
                                        @foreach(['full' => 'Full', 'strict' => 'Strict', 'flexible' => 'Flexible', 'off' => 'Off'] as $sslKey => $sslLabel)
                                        <option value="{{ $sslKey }}" {{ old('wordpress_cloudflare_ssl_mode', $form['wordpress_cloudflare_ssl_mode'] ?? 'full') === $sslKey ? 'selected' : '' }}>{{ $sslLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <label class="form-check mb-2">
                                        <input type="checkbox" name="wordpress_cloudflare_proxied" value="1" class="form-check-input"
                                            {{ old('wordpress_cloudflare_proxied', $form['wordpress_cloudflare_proxied'] ?? true) ? 'checked' : '' }}>
                                        بروكسي Cloudflare (DDoS + إخفاء IP)
                                    </label>
                                </div>
                            </div>
                            <hr>
                            <h6 class="text-muted">إدارة WordPress (WP-CLI / Docker)</h6>
                            <div class="row g-3 mb-3">
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
                            <p class="small text-muted">إدارة المواقع تتطلب SSH. ثبّت إضافة Redis Object Cache يدوياً بعد تفعيل المتغيرات.</p>
                            <hr>
                            <h6 class="text-muted">SSH — إدارة WordPress والنسخ</h6>
                            <div class="alert alert-warning py-2 small mb-3">
                                إذا كان IP السيرفر في Coolify = <code>host.docker.internal</code> (سيرفر محلي)، ضع هنا <strong>IP الحقيقي</strong> للجهاز الذي يشغّل Docker/Coolify (مثال: <code>203.0.113.10</code> أو <code>192.168.1.50</code>) — وليس <code>host.docker.internal</code>.
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">عنوان SSH للسيرفر (IP الحقيقي) *</label>
                                    <input type="text" name="ssh_host_fallback" class="form-control @error('ssh_host_fallback') is-invalid @enderror"
                                        value="{{ old('ssh_host_fallback', $form['ssh_host_fallback'] ?? '') }}"
                                        placeholder="82.x.x.x أو 192.168.x.x" dir="ltr" required>
                                    @error('ssh_host_fallback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">لا تستخدم <code>coolify.claudsoft.com</code> — هذا نطاق الويب فقط. ضع IP الـ VPS الذي يشغّل Docker.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">منفذ SSH</label>
                                    <input type="number" name="ssh_port" class="form-control" min="1" max="65535"
                                        value="{{ old('ssh_port', $form['ssh_port'] ?? 22) }}" dir="ltr">
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">مستخدم SSH</label>
                                    <input type="text" name="ssh_user" class="form-control" value="{{ old('ssh_user', $form['ssh_user'] ?? 'root') }}" dir="ltr">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">مسار ملف المفتاح (.pem) — اختياري</label>
                                    @php $defaultKeyPath = str_replace('\\', '/', storage_path('app/coolify-keys/server.pem')); @endphp
                                    <input type="text" name="ssh_private_key_path" class="form-control @error('ssh_private_key_path') is-invalid @enderror"
                                        value="{{ old('ssh_private_key_path', ($form['ssh_private_key_path'] ?? '') && !str_contains((string)($form['ssh_private_key_path'] ?? ''), 'BEGIN') ? $form['ssh_private_key_path'] : '') }}"
                                        placeholder="{{ is_file(storage_path('app/coolify-keys/server.pem')) ? $defaultKeyPath : 'D:/path/to/server.pem' }}" dir="ltr">
                                    @error('ssh_private_key_path')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text text-danger fw-bold">لا تلصق المفتاح هنا — مسار الملف فقط.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">لصق المفتاح (PEM) — من Coolify «localhost's key»</label>
                                    <textarea name="ssh_private_key" class="form-control" rows="6" dir="ltr" placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----">{{ old('ssh_private_key') }}</textarea>
                                    <div class="form-text">إن وُجد <strong>مسار ملف</strong> أعلاه: اترك هذا الحقل <strong>فارغاً</strong> ثم احفظ. إن فشل C:\temp انسخ المفتاح إلى:<br>
                                    <code>{{ str_replace('\\', '/', storage_path('app/coolify-keys/server.pem')) }}</code></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fe fe-save"></i> حفظ الإعدادات</button>
                            <a href="{{ route('admin.coolify.overview') }}" class="btn btn-light">رجوع</a>
                        </form>
                    </div>
                </div>

                <div class="card custom-card">
                    <div class="card-header"><div class="card-title">اختبار الاتصال</div></div>
                    <div class="card-body">
                        <p class="mb-2"><strong>الاتصال الحالي:</strong>
                            @if($connected)<span class="badge bg-success">متصل</span>
                            @elseif($configured)<span class="badge bg-warning">مضبوط — فشل الاتصال</span>
                            @else<span class="badge bg-secondary">غير مضبوط</span>@endif
                        </p>
                        <button type="button" class="btn btn-outline-primary" id="btnTestCoolify" @if(!$configured) disabled @endif>
                            <i class="fe fe-wifi"></i> اختبار الاتصال
                        </button>
                        <div id="coolifyTestResult" class="mt-3"></div>
                        <hr>
                        <label class="form-label">اختبار SSH (IP السيرفر)</label>
                        <p class="small text-muted mb-2">يختبر المفتاح والمسار <strong>بعد الحفظ</strong> — ليس ما في الحقول قبل الضغط على «حفظ».</p>
                        <div class="input-group">
                            <input type="text" id="sshTestHost" class="form-control" placeholder="203.0.113.10" dir="ltr"
                                value="{{ $form['ssh_host_fallback'] ?? '' }}">
                            <button type="button" class="btn btn-outline-secondary" id="btnTestSsh">اختبار SSH</button>
                        </div>
                        <div id="sshTestResult" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                @if($version && ($version['success'] ?? false))
                <div class="card custom-card mb-3">
                    <div class="card-header"><div class="card-title">إصدار Coolify</div></div>
                    <div class="card-body p-0">
                        <pre class="mb-0 p-3 small" style="direction:ltr;max-height:200px;overflow:auto;">{{ json_encode($version['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('btnTestCoolify')?.addEventListener('click', function() {
    const el = document.getElementById('coolifyTestResult');
    el.innerHTML = '<span class="text-muted">جاري الاختبار...</span>';
    fetch('{{ route('admin.coolify.settings.test') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    }).then(r => r.json()).then(d => {
        el.innerHTML = d.success
            ? '<div class="alert alert-success mb-0">' + d.message + '</div>'
            : '<div class="alert alert-danger mb-0">' + (d.message || 'فشل') + '</div>';
    }).catch(e => { el.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>'; });
});
document.getElementById('btnDiscoverS3')?.addEventListener('click', function() {
    const el = document.getElementById('discoverS3Result');
    const input = document.getElementById('coolifyS3Uuid');
    el.innerHTML = '<span class="text-muted">جاري الجلب...</span>';
    fetch('{{ route('admin.coolify.settings.discover-s3') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    }).then(r => r.json()).then(d => {
        if (d.uuid) input.value = d.uuid;
        let html = (d.found ? '<span class="text-success">' : '<span class="text-warning">') + (d.message || '') + '</span>';
        if (d.coolify_storages_url) {
            html += ' <a href="' + d.coolify_storages_url + '" target="_blank" rel="noopener">فتح Storages في Coolify</a>';
        }
        el.innerHTML = html;
    }).catch(e => { el.innerHTML = '<span class="text-danger">' + e.message + '</span>'; });
});
document.querySelectorAll('.s3-pick').forEach(a => {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('coolifyS3Uuid').value = this.dataset.uuid || '';
    });
});
document.getElementById('btnTestSsh')?.addEventListener('click', function() {
    const host = document.getElementById('sshTestHost')?.value;
    const key = (document.querySelector('textarea[name="ssh_private_key"]')?.value || '').trim();
    const el = document.getElementById('sshTestResult');
    if (!host) { el.innerHTML = '<div class="alert alert-warning mb-0">أدخل IP السيرفر</div>'; return; }
    el.innerHTML = '<span class="text-muted">جاري الاختبار (الإعدادات المحفوظة)...</span>';
    fetch('{{ route('admin.coolify.settings.test-ssh') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({ host: host, ssh_private_key: key })
    }).then(r => r.json()).then(d => {
        const msg = (d.message || 'فشل').replace(/\n/g, '<br>');
        let extra = d.details ? '<br><small class="text-muted">' + d.details + '</small>' : '';
        if (d.diagnostics) {
            extra += '<br><small class="text-muted">تشخيص: ' + JSON.stringify(d.diagnostics) + '</small>';
        }
        el.innerHTML = d.success
            ? '<div class="alert alert-success mb-0">' + msg + extra + '</div>'
            : '<div class="alert alert-danger mb-0" style="white-space:pre-wrap">' + msg + extra + '<br><small>إن غيّرت مسار المفتاح: اضغط «حفظ الإعدادات» ثم أعد الاختبار. أو نفّذ: php artisan coolify:fix-ssh</small></div>';
    }).catch(e => { el.innerHTML = '<div class="alert alert-danger">' + e.message + '</div>'; });
});
</script>
@endpush
@endsection

