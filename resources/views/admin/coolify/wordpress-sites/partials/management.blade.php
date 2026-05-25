@php
    $wpState = $wpManagementState ?? ['ui_ready' => false, 'ssh_ready' => false, 'execute_ready' => false, 'message' => ''];
    $wpUi = $wpState['ui_ready'] ?? false;
    $wpExec = $wpState['execute_ready'] ?? false;
    $wpSsh = $wpState['ssh_ready'] ?? false;
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
    $wpLog = $site->metadata['wp_management_log'] ?? [];
    $wpMcpReady = !empty($site->metadata['wp_mcp_bootstrapped_at']);
    $wpMcpSnippet = $site->metadata['wp_mcp_cursor_snippet'] ?? '';
    $settingsUrl = route('admin.coolify.settings.index');
@endphp
<div class="card custom-card mb-3" id="wpManagementCard">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="card-title mb-0">إدارة WordPress</div>
        @if($wpExec)
        <span class="badge bg-success">SSH + WP-CLI جاهز</span>
        @elseif($wpUi && !$wpSsh)
        <span class="badge bg-warning">يتطلب SSH</span>
        @else
        <span class="badge bg-secondary">{{ $wpState['message'] ?? 'غير متاح' }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(!$wpUi)
        <p class="text-muted small mb-0">{{ $wpState['message'] ?? 'غير متاح' }} — انتظر تشغيل الحاويات أو حدّث الصفحة.</p>
        @else
        @if(!$wpSsh)
        <div class="alert alert-warning py-3 mb-3">
            <strong>خطوة مطلوبة:</strong> لاستخدام التحديث وإعادة تثبيت Core وغيرها، أضف <strong>مفتاح SSH</strong> للسيرفر في
            <a href="{{ $settingsUrl }}" class="alert-link fw-bold">إعدادات Coolify</a>
            (قسم SSH — لصق المفتاح PEM أو مسار الملف على الجهاز الذي يشغّل Laravel).
            <hr class="my-2">
            <span class="small">بعد الحفظ، حدّث هذه الصفحة. الأزرار أدناه ستُفعَّل تلقائياً.</span>
        </div>
        @elseif($wpState['ssh_host_required'] ?? false)
        <div class="alert alert-danger py-3 mb-3">
            <strong>مطلوب: IP السيرفر للـ SSH</strong>
            <p class="small mb-2">المفتاح مضبوط، لكن لم يُحدَّد IP الـ VPS. نطاق <code>coolify.claudsoft.com</code> للويب فقط ولا يقبل SSH.</p>
            <a href="{{ $settingsUrl }}" class="btn btn-sm btn-danger">إعدادات Coolify → عنوان SSH للسيرفر</a>
            <span class="small text-muted d-block mt-2">ضع IP من لوحة الاستضافة (Hetzner/OVH/…)، ثم «اختبار SSH»، ثم «تشخيص الاتصال» هنا.</span>
        </div>
        @endif
        <div id="wpJobAlert" class="alert alert-info py-2 small d-none mb-3"></div>
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wpTabOverview" type="button">نظرة عامة</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabCore" type="button">النواة (تحديث)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabPlugins" type="button">إضافات وقوالب</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabUsers" type="button">المستخدمون</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabMaint" type="button">صيانة</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabDatabase" type="button">قاعدة البيانات</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabCli" type="button">WP-CLI</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabDocker" type="button">Docker</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#wpTabLog" type="button">سجل العمليات</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="wpTabOverview">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm wp-action-btn" id="wpBtnRefresh" @disabled(!$wpExec)>تحديث المعلومات</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action wp-action-btn" data-action="diagnose" @disabled(!($wpState['ssh_ready'] ?? false))>تشخيص الاتصال</button>
                </div>
                <div id="wpOverviewContent" class="small">
                    @if(!empty($wpInfoData))
                    <p><strong>إصدار WordPress:</strong> <code id="wpCoreVersion">{{ $wpInfoData['core_version'] ?? '—' }}</code></p>
                    <p><strong>الحاوية:</strong> <code>{{ $wpInfoData['container']['name'] ?? '—' }}</code></p>
                    <p><strong>آخر فحص:</strong> {{ $wpInfoData['fetched_at'] ?? '—' }}</p>
                    @else
                    <p class="text-muted">@if($wpExec) اضغط «تحديث المعلومات». @else فعّل SSH أولاً. @endif</p>
                    @endif
                </div>
            </div>
            <div class="tab-pane fade" id="wpTabCore">
                <p class="small text-muted mb-2">تحديث WordPress من wordpress.org، أو إعادة تثبيت ملفات النظام مع الإبقاء على <code>wp-content</code>.</p>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="core_update" @disabled(!$wpExec)>تحديث Core + DB</button>
                    <button type="button" class="btn btn-warning btn-sm wp-action-btn wp-action" data-action="core_reinstall" data-confirm="إعادة تثبيت ملفات WordPress الأساسية؟ المحتوى والإضافات تبقى." @disabled(!$wpExec)>إعادة تثبيت ملفات Core</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="core_update_db" @disabled(!$wpExec)>تحديث قاعدة البيانات</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="core_check_update" @disabled(!$wpExec)>فحص التحديثات</button>
                </div>
                <pre id="wpCoreOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:160px;overflow:auto;white-space:pre-wrap;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabPlugins">
                <div class="d-flex gap-2 mb-2 flex-wrap">
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="plugin_update_all" @disabled(!$wpExec)>تحديث كل الإضافات</button>
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="theme_update_all" @disabled(!$wpExec)>تحديث كل القوالب</button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">تثبيت إضافة (slug)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="wpInstallPluginSlug" class="form-control" dir="ltr" placeholder="hello-dolly" @disabled(!$wpExec)>
                            <span class="input-group-text"><input type="checkbox" id="wpInstallPluginActivate" checked @disabled(!$wpExec)> تفعيل</span>
                            <button type="button" class="btn btn-outline-primary" id="wpBtnInstallPlugin" @disabled(!$wpExec)>تثبيت</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">تثبيت قالب</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="wpInstallThemeSlug" class="form-control" dir="ltr" placeholder="twentytwentyfour" @disabled(!$wpExec)>
                            <button type="button" class="btn btn-outline-primary" id="wpBtnInstallTheme" @disabled(!$wpExec)>تثبيت</button>
                        </div>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-6"><h6 class="small">الإضافات</h6><div id="wpPluginsTable" class="table-responsive small"></div></div>
                    <div class="col-md-6"><h6 class="small">القوالب</h6><div id="wpThemesTable" class="table-responsive small"></div></div>
                </div>
            </div>
            <div class="tab-pane fade" id="wpTabUsers">
                <div id="wpUsersTable" class="table-responsive small mb-2"></div>
                <h6 class="small text-muted">إعادة تعيين كلمة المرور</h6>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">اسم المستخدم</label>
                        <input type="text" id="wpResetLogin" class="form-control form-control-sm" dir="ltr" placeholder="admin">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">كلمة مرور (فارغ = توليد)</label>
                        <input type="text" id="wpResetPass" class="form-control form-control-sm" dir="ltr">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-warning btn-sm wp-action-btn" id="wpBtnResetPass" @disabled(!$wpExec)>إعادة تعيين</button>
                    </div>
                </div>
                <div id="wpPassResult" class="alert alert-success py-2 small d-none mb-3"></div>
                <h6 class="small text-muted">مستخدم جديد</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">اسم المستخدم</label>
                        <input type="text" id="wpNewLogin" class="form-control form-control-sm" dir="ltr" @disabled(!$wpExec)>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">البريد</label>
                        <input type="email" id="wpNewEmail" class="form-control form-control-sm" dir="ltr" @disabled(!$wpExec)>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">الدور</label>
                        <select id="wpNewRole" class="form-select form-select-sm" @disabled(!$wpExec)>
                            <option value="subscriber">subscriber</option>
                            <option value="author">author</option>
                            <option value="editor">editor</option>
                            <option value="administrator">administrator</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">كلمة مرور</label>
                        <input type="text" id="wpNewPass" class="form-control form-control-sm" dir="ltr" placeholder="توليد تلقائي" @disabled(!$wpExec)>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-sm wp-action-btn" id="wpBtnCreateUser" @disabled(!$wpExec)>إنشاء</button>
                    </div>
                </div>
                <div id="wpUserCreateResult" class="alert alert-success py-2 small d-none mt-2"></div>
            </div>
            <div class="tab-pane fade" id="wpTabMaint">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-warning btn-sm wp-action-btn wp-action" data-action="maintenance_activate" @disabled(!$wpExec)>وضع الصيانة</button>
                    <button type="button" class="btn btn-outline-success btn-sm wp-action-btn wp-action" data-action="maintenance_deactivate" @disabled(!$wpExec)>إيقاف الصيانة</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="cache_flush" @disabled(!$wpExec)>مسح الكاش</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action-btn wp-action" data-action="rewrite_flush" @disabled(!$wpExec)>إعادة الروابط</button>
                    @if(app(\App\Services\Coolify\CoolifySettingsService::class)->getWordpressRedisEnabled())
                    <button type="button" class="btn btn-outline-info btn-sm wp-action-btn wp-action" data-action="redis_apply_env" @disabled(!$wpExec)>تطبيق Redis (Coolify env)</button>
                    @endif
                    <button type="button" class="btn btn-info btn-sm wp-action-btn wp-action" data-action="bootstrap_mcp" data-confirm="تركيب إضافة MCP Server + حزمة WP-CLI AI على هذا الموقع؟" @disabled(!$wpExec)>تركيب MCP + WP-CLI AI</button>
                </div>
                @if($wpMcpReady)
                <div class="alert alert-success py-2 small mb-2">
                    MCP مُثبَّت — {{ $site->metadata['wp_mcp_bootstrapped_at'] ?? '' }}
                    @if($wpMcpSnippet)
                    <details class="mt-2"><summary class="fw-bold">إعداد Cursor MCP (انسخ إلى Settings → MCP)</summary>
                    <pre class="small mb-0 mt-2" dir="ltr" style="white-space:pre-wrap;max-height:200px;overflow:auto;">{{ $wpMcpSnippet }}</pre>
                    </details>
                    @endif
                </div>
                @else
                <p class="small text-muted mb-2">يثبّت <code>mcp-server</code> على WordPress و<code>mcp-wp/ai-command</code> في WP-CLI، ويُنشئ Application Password لربط Cursor.</p>
                @endif
                <pre id="wpMaintOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:200px;overflow:auto;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabDatabase">
                <p class="small text-muted">عمليات قاعدة البيانات عبر WP-CLI.</p>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-primary btn-sm wp-action wp-action-btn" data-action="db_check" @disabled(!$wpExec)>فحص DB</button>
                    <button type="button" class="btn btn-outline-warning btn-sm wp-action wp-action-btn" data-action="db_repair" @disabled(!$wpExec)>إصلاح DB</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action wp-action-btn" data-action="db_export" @disabled(!$wpExec)>تصدير DB</button>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-5">
                        <label class="form-label small">بحث واستبدال (قديم)</label>
                        <input type="text" id="wpSrOld" class="form-control form-control-sm" dir="ltr" @disabled(!$wpExec)>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small">جديد</label>
                        <input type="text" id="wpSrNew" class="form-control form-control-sm" dir="ltr" @disabled(!$wpExec)>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="wpBtnSearchReplaceDry" @disabled(!$wpExec)>معاينة</button>
                        <button type="button" class="btn btn-danger btn-sm" id="wpBtnSearchReplace" @disabled(!$wpExec)>تنفيذ</button>
                    </div>
                </div>
                <pre id="wpDbOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:200px;overflow:auto;white-space:pre-wrap;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabCli">
                <p class="small text-muted">تشغيل أوامر WP-CLI حرة (مسموح ببادئات آمنة فقط).</p>
                <div id="wpCliQuickChips" class="mb-2"></div>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="wpCliCommand" class="form-control" dir="ltr" placeholder="plugin list --status=active" @disabled(!$wpExec)>
                    <button type="button" class="btn btn-primary" id="wpBtnRunCli" @disabled(!$wpExec)>تشغيل</button>
                </div>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="wpBtnCronList" @disabled(!$wpExec)>قائمة Cron</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm wp-action wp-action-btn" data-action="post_list" @disabled(!$wpExec)>قائمة المنشورات</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="wpBtnTransientDelete" @disabled(!$wpExec)>مسح Transients</button>
                </div>
                <pre id="wpCliOutput" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:280px;overflow:auto;white-space:pre-wrap;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabDocker">
                <p class="small text-muted">إدارة حاويات Docker على السيرفر (compose Coolify).</p>
                <p class="small"><strong>الصورة:</strong> <code id="wpDockerImage">{{ $wpInfoData['container']['image'] ?? '—' }}</code></p>
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <button type="button" class="btn btn-danger btn-sm wp-action wp-action-btn" data-action="docker_compose_stop" data-confirm="إيقاف حاويات الموقع؟" @disabled(!$wpExec)>إيقاف الحاويات</button>
                    <button type="button" class="btn btn-success btn-sm wp-action wp-action-btn" data-action="docker_compose_start" @disabled(!$wpExec)>تشغيل الحاويات</button>
                    <button type="button" class="btn btn-warning btn-sm wp-action wp-action-btn" data-action="docker_compose_restart" data-confirm="إعادة تشغيل الحاويات؟" @disabled(!$wpExec)>إعادة تشغيل</button>
                    <button type="button" class="btn btn-primary btn-sm wp-action-btn wp-action" data-action="docker_compose_pull" data-confirm="سحب الصور وإعادة التشغيل؟" @disabled(!$wpExec)>سحب أحدث صورة</button>
                </div>
                <pre id="wpDockerOutput" class="p-2 bg-light rounded small mt-2 mb-0" dir="ltr" style="max-height:200px;overflow:auto;white-space:pre-wrap;"></pre>
            </div>
            <div class="tab-pane fade" id="wpTabLog">
                <pre id="wpManagementLog" class="p-2 bg-light rounded small mb-0" dir="ltr" style="max-height:240px;overflow:auto;white-space:pre-wrap;">@foreach($wpLog as $entry)[{{ $entry['at'] ?? '' }}] {{ $entry['action'] ?? '' }} ({{ $entry['status'] ?? '' }})
@endforeach</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@if($wpUi)
@include('admin.coolify.wordpress-sites.partials.management-scripts', ['wpExec' => $wpExec, 'uuid' => $uuid])
@endif

