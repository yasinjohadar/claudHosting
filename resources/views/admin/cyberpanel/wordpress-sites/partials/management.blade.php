@php
    $wpState = $wpManagementState ?? ['ui_ready' => false, 'api_ready' => false, 'execute_ready' => false, 'message' => ''];
    $wpUi = $wpState['ui_ready'] ?? false;
    $wpExec = $wpExec ?? ($wpState['execute_ready'] ?? false);
    $wpInfoData = $wpInfo ?? ($site->metadata['wp_info'] ?? []);
    $wpLog = $site->metadata['cp_wp_management_log'] ?? [];
    $settingsUrl = route('admin.cyberpanel.settings.index');
    $cpLinks = $cpLinks ?? [];
    $autoPolicies = config('cyberpanel_wordpress.auto_update_policies', []);
@endphp
<div id="wpManagementCard" class="site-wp-management">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <p class="text-muted small mb-0">إدارة الإضافات، القوالب، الصيانة عبر CyberPanel CloudAPI.</p>
        @if($wpExec)
            <span class="cp-wp-api-badge cp-wp-api-badge--ready">CyberPanel API جاهز</span>
        @elseif($wpUi)
            <span class="badge bg-warning">API غير جاهز</span>
        @else
            <span class="badge bg-secondary">{{ $wpState['message'] ?? 'غير متاح' }}</span>
        @endif
    </div>

    @if(!$wpUi)
        <p class="text-muted small mb-0">{{ $wpState['message'] ?? 'غير متاح' }}</p>
    @else
        @if(!$wpExec)
        <div class="alert alert-warning py-3 mb-3">
            <strong>خطوة مطلوبة:</strong> أكمل <a href="{{ $settingsUrl }}" class="alert-link">إعدادات CyberPanel</a> وفعّل CloudAPI للمستخدم admin.
        </div>
        @endif

        <div id="cpWpJobAlert" class="alert alert-info py-2 small d-none mb-3"></div>

        @php
            $innerTabs = [
                ['id' => 'wpTabOverview', 'icon' => 'fe fe-grid', 'label' => 'نظرة عامة'],
                ['id' => 'wpTabCore', 'icon' => 'fe fe-package', 'label' => 'النواة'],
                ['id' => 'wpTabPlugins', 'icon' => 'fe fe-layers', 'label' => 'إضافات وقوالب'],
                ['id' => 'wpTabUsers', 'icon' => 'fe fe-users', 'label' => 'المستخدمون'],
                ['id' => 'wpTabMaint', 'icon' => 'fe fe-settings', 'label' => 'صيانة'],
                ['id' => 'wpTabDatabase', 'icon' => 'fe fe-database', 'label' => 'قاعدة البيانات'],
                ['id' => 'wpTabLog', 'icon' => 'fe fe-list', 'label' => 'سجل العمليات'],
            ];
        @endphp
        <div class="cp-wp-inner-tabs" role="tablist">
            @foreach($innerTabs as $i => $t)
                <button type="button"
                    class="cp-wp-inner-tabs__btn @if($i === 0) active @endif"
                    data-bs-toggle="tab"
                    data-bs-target="#{{ $t['id'] }}"
                    role="tab">
                    <i class="{{ $t['icon'] }}"></i> {{ $t['label'] }}
                </button>
            @endforeach
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="wpTabOverview">
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm cp-wp-action" data-action="refresh_info" @disabled(!$wpExec)>
                        <i class="fe fe-refresh-cw me-1"></i> تحديث المعلومات
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm cp-wp-action" data-action="diagnose" @disabled(!($wpState['api_ready'] ?? false))>
                        <i class="fe fe-activity me-1"></i> تشخيص الاتصال
                    </button>
                </div>
                <div id="wpOverviewContent" class="cp-wp-detail-card">
                    <div class="cp-wp-detail-card__body">
                        @if(!empty($wpInfoData))
                            <div class="cp-wp-detail-row">
                                <span class="cp-wp-detail-row__label"><i class="fab fa-wordpress"></i> إصدار WordPress</span>
                                <span class="cp-wp-detail-row__value"><code id="wpCoreVersion" dir="ltr">{{ $wpInfoData['core_version'] ?? '—' }}</code></span>
                            </div>
                            <div class="cp-wp-detail-row">
                                <span class="cp-wp-detail-row__label"><i class="fe fe-layers"></i> الإضافات</span>
                                <span class="cp-wp-detail-row__value">{{ $wpInfoData['plugins_count'] ?? 0 }} — تحديثات: {{ $wpInfoData['plugins_updates_count'] ?? 0 }}</span>
                            </div>
                            <div class="cp-wp-detail-row">
                                <span class="cp-wp-detail-row__label"><i class="fe fe-image"></i> القوالب</span>
                                <span class="cp-wp-detail-row__value">{{ $wpInfoData['themes_count'] ?? 0 }} — تحديثات: {{ $wpInfoData['themes_updates_count'] ?? 0 }}</span>
                            </div>
                            <div class="cp-wp-detail-row">
                                <span class="cp-wp-detail-row__label"><i class="fe fe-clock"></i> آخر فحص</span>
                                <span class="cp-wp-detail-row__value">{{ $wpInfoData['fetched_at'] ?? '—' }}</span>
                            </div>
                        @else
                            <div class="p-4 text-center text-muted">
                                @if($wpExec) اضغط «تحديث المعلومات» لجلب بيانات الموقع. @else أكمل الإعدادات أولاً. @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="wpTabCore">
                <p class="small text-muted mb-3">سياسات التحديث التلقائي عبر CyberPanel. لتحديث النواة يدوياً استخدم WP Manager.</p>
                <form id="cpAutoUpdateForm" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">تحديثات النواة</label>
                        <select name="wp_core" class="form-select form-select-sm" @disabled(!$wpExec)>
                            @foreach($autoPolicies['wp_core'] ?? [] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">الإضافات</label>
                        <select name="plugins" class="form-select form-select-sm" @disabled(!$wpExec)>
                            @foreach($autoPolicies['plugins_themes'] ?? [] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">القوالب</label>
                        <select name="themes" class="form-select form-select-sm" @disabled(!$wpExec)>
                            @foreach($autoPolicies['plugins_themes'] ?? [] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100" @disabled(!$wpExec)>حفظ</button>
                    </div>
                </form>
                <div class="cp-wp-detail-card mb-3">
                    <div class="cp-wp-detail-card__head">
                        <i class="fe fe-refresh-cw text-warning"></i> إعادة تثبيت ملفات النواة
                    </div>
                    <div class="cp-wp-detail-card__body p-3">
                        <p class="small text-muted mb-3">
                            يستبدل ملفات <code>wp-admin</code> و<code>wp-includes</code> وملفات النظام فقط —
                            يُبقي <strong>wp-content</strong> و<strong>wp-config.php</strong> وقاعدة البيانات كما هي.
                            يُجرّب أولاً عبر CyberPanel WP Manager، وإن تعذّر تحديد الموقع يُكمل تلقائياً عبر لوحة WordPress.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button"
                                class="btn btn-warning btn-sm cp-wp-action"
                                data-action="core_reinstall"
                                data-confirm="إعادة تثبيت ملفات WordPress الأساسية؟ سيتم الإبقاء على wp-content وwp-config.php والإضافات."
                                @disabled(!$wpExec)>
                                <i class="fe fe-download me-1"></i> إعادة تثبيت WordPress
                            </button>
                            <a href="{{ $cpLinks['wp_manager'] ?? '#' }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                                <i class="fe fe-external-link me-1"></i> تحديث النواة في CyberPanel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="wpTabPlugins">
                @include('admin.cyberpanel.wordpress-sites.partials.wp-plugins-themes-panel')
            </div>

            <div class="tab-pane fade" id="wpTabUsers">
                @include('admin.cyberpanel.wordpress-sites.partials.wp-users-panel')
            </div>

            <div class="tab-pane fade" id="wpTabMaint">
                <p class="small text-muted mb-3">تبديل إعدادات الصيانة عبر CyberPanel API.</p>
                <div class="cp-wp-toggle-grid">
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="maintenance_activate" @disabled(!$wpExec)>
                        <i class="fe fe-alert-triangle text-warning"></i> تفعيل وضع الصيانة
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="maintenance_deactivate" @disabled(!$wpExec)>
                        <i class="fe fe-check-circle text-success"></i> إيقاف الصيانة
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="debugging_on" @disabled(!$wpExec)>
                        <i class="fe fe-code text-info"></i> تفعيل التصحيح
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="debugging_off" @disabled(!$wpExec)>
                        <i class="fe fe-x-circle text-secondary"></i> إيقاف التصحيح
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="lscache_on" @disabled(!$wpExec)>
                        <i class="fe fe-zap text-success"></i> تفعيل LSCache
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="lscache_off" @disabled(!$wpExec)>
                        <i class="fe fe-zap-off text-secondary"></i> إيقاف LSCache
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="search_index_on" @disabled(!$wpExec)>
                        <i class="fe fe-search text-primary"></i> فهرسة محركات البحث
                    </button>
                    <button type="button" class="cp-wp-toggle-btn cp-wp-action" data-action="search_index_off" @disabled(!$wpExec)>
                        <i class="fe fe-eye-off text-secondary"></i> إيقاف الفهرسة
                    </button>
                </div>
            </div>

            <div class="tab-pane fade" id="wpTabDatabase">
                <p class="text-muted small mb-3">تصدير وإصلاح قاعدة البيانات عبر النسخ الاحتياطي أو WP Manager.</p>
                <button type="button" class="btn btn-primary btn-sm cp-wp-action" data-action="backup_create" @disabled(!$wpExec)>نسخة احتياطية (ملفات + DB)</button>
                <a href="{{ $cpLinks['wp_manager'] ?? '#' }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm ms-2">أدوات DB في CyberPanel</a>
            </div>

            <div class="tab-pane fade" id="wpTabLog">
                @if(empty($wpLog))
                    <p class="text-muted small mb-0">لا توجد عمليات مسجّلة بعد.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>الوقت</th><th>الإجراء</th><th>النتيجة</th><th>الرسالة</th></tr></thead>
                            <tbody>
                            @foreach(array_slice($wpLog, 0, 30) as $entry)
                                <tr>
                                    <td class="text-nowrap small">{{ $entry['at'] ?? '—' }}</td>
                                    <td>{{ $entry['label'] ?? $entry['action'] ?? '—' }}</td>
                                    <td>
                                        @if($entry['success'] ?? false)
                                            <span class="badge bg-success-transparent text-success">نجاح</span>
                                        @else
                                            <span class="badge bg-danger-transparent text-danger">فشل</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $entry['message'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
