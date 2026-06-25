@php
    $usersCount = (int) ($wpInfoData['users_count'] ?? count($wpInfoData['users'] ?? []));
@endphp
<div class="wp-users-panel">
    <div class="alert alert-light border small mb-3 py-2">
        <strong>إدارة المستخدمين عبر WordPress REST API</strong> — باستخدام جلسة CyberPanel AutoLogin (مستخدم <code dir="ltr">cyberpanel</code>).
    </div>

    <div id="wpPassResult" class="alert alert-success py-2 small d-none mb-3" role="status"></div>
    <div id="wpUserCreateResult" class="alert alert-success py-2 small d-none mb-3" role="status"></div>

    <div class="d-flex gap-2 flex-wrap mb-3 align-items-center">
        <button type="button" class="btn btn-outline-primary btn-sm cp-wp-action" data-action="refresh_info" id="wpBtnRefreshUsers" @disabled(!($wpExec ?? false))>
            <i class="fe fe-refresh-cw"></i> تحديث القائمة
        </button>
        <span class="small text-muted" id="wpUsersCountLabel">@if($usersCount > 0)({{ $usersCount }} مستخدم)@endif</span>
    </div>

    <div id="wpUsersTable" class="wp-pt-table-wrap small mb-4">
        <p class="text-muted mb-0 py-3 text-center">
            @if($wpExec ?? false)
                اضغط «تحديث القائمة» لجلب المستخدمين
            @else
                أكمل إعدادات CyberPanel أولاً
            @endif
        </p>
    </div>

    <div class="cp-wp-detail-card mb-0">
        <div class="cp-wp-detail-card__head">
            <i class="fe fe-user-plus text-primary"></i> مستخدم جديد
        </div>
        <div class="cp-wp-detail-card__body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">اسم المستخدم</label>
                    <input type="text" id="cpWpNewLogin" class="form-control form-control-sm" dir="ltr" placeholder="username" @disabled(!($wpExec ?? false))>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">البريد الإلكتروني</label>
                    <input type="email" id="cpWpNewEmail" class="form-control form-control-sm" dir="ltr" @disabled(!($wpExec ?? false))>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">الدور</label>
                    <select id="cpWpNewRole" class="form-select form-select-sm" @disabled(!($wpExec ?? false))>
                        <option value="subscriber">مشترك</option>
                        <option value="contributor">مساهم</option>
                        <option value="author">كاتب</option>
                        <option value="editor">محرر</option>
                        <option value="administrator">مدير</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">كلمة المرور</label>
                    <input type="text" id="cpWpNewPass" class="form-control form-control-sm" dir="ltr" placeholder="توليد تلقائي" @disabled(!($wpExec ?? false))>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="cpWpBtnCreateUser" @disabled(!($wpExec ?? false))>
                        <i class="fe fe-plus me-1"></i> إنشاء
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cpWpPasswordModal" tabindex="-1" aria-labelledby="cpWpPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cpWpPasswordModalLabel">تغيير كلمة المرور</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cpWpPassModalLogin" value="">
                    <div id="cpWpPassModalError" class="alert alert-danger py-2 small d-none mb-2" role="alert"></div>
                    <p class="small text-muted mb-2">المستخدم: <code id="cpWpPassModalLoginLabel" dir="ltr">—</code></p>
                    <label class="form-label small" for="cpWpPassInput">كلمة المرور الجديدة</label>
                    <div class="input-group mb-2">
                        <input type="password" id="cpWpPassInput" class="form-control font-monospace" dir="ltr" autocomplete="new-password" placeholder="أدخل أو ولّد كلمة مرور">
                        <button type="button" class="btn btn-outline-secondary" id="cpWpPassToggleVis" title="إظهار/إخفاء"><i class="fe fe-eye"></i></button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="cpWpPassGenerate"><i class="fe fe-refresh-cw me-1"></i> توليد قوي</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cpWpPassCopy"><i class="fe fe-copy me-1"></i> نسخ</button>
                        <span id="cpWpPassCopyFeedback" class="small text-success align-self-center d-none">تم النسخ</span>
                    </div>
                    <p class="small text-muted mb-0">اترك الحقل فارغاً لتوليد كلمة مرور تلقائياً.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-warning" id="cpWpPassApply" @disabled(!($wpExec ?? false))>تطبيق على WordPress</button>
                </div>
            </div>
        </div>
    </div>
</div>
