@php $embedded = $embedded ?? false; @endphp
@if(!$embedded)
<div class="card custom-card mt-3" id="whm-credentials-card" data-account-id="{{ $account->id }}">
    <div class="card-header"><div class="card-title mb-0">تعديل الحساب في WHM</div></div>
    <div class="card-body">
@endif
<div id="whm-credentials-card" data-account-id="{{ $account->id }}" @if($embedded) class="whm-credentials-embedded" @endif>
    @if($embedded)
        <p class="text-muted small mb-3">التغييرات تُطبَّق مباشرة على السيرفر عبر WHM API.</p>
    @else
        <p class="text-muted small">التغييرات تُطبَّق مباشرة على السيرفر عبر WHM API.</p>
    @endif

    <div class="whm-section">
        <div class="whm-section-title">بريد التواصل</div>
        <div class="input-group">
            <span class="input-group-text"><i class="fe fe-mail"></i></span>
            <input type="email" class="form-control" id="whm-email-input" dir="ltr"
                value="{{ $account->display_email ?? '' }}" placeholder="user@example.com">
            <button type="button" class="btn btn-primary" id="whm-email-save"
                data-url="{{ route('admin.whm.accounts.update-email', $account) }}">
                <span class="whm-btn-label">حفظ</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </div>

    <div class="whm-section">
        <div class="whm-section-title">كلمة مرور cPanel</div>
        <div class="row g-2">
            <div class="col-md-6">
                <input type="password" class="form-control" id="whm-password" placeholder="كلمة مرور جديدة" autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <input type="password" class="form-control" id="whm-password-confirm" placeholder="تأكيد كلمة المرور" autocomplete="new-password">
            </div>
        </div>
        <button type="button" class="btn btn-warning btn-sm mt-2" id="whm-password-save"
            data-url="{{ route('admin.whm.accounts.update-password', $account) }}">
            <span class="whm-btn-label">تغيير كلمة المرور</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>
    </div>

    <div class="whm-section">
        <div class="whm-section-title">اسم المستخدم</div>
        <p class="small text-muted mb-2">الحالي: <code dir="ltr">{{ $account->username }}</code></p>
        <div class="alert alert-warning small py-2 mb-2">
            إعادة التسمية قد تؤثر على قواعد البيانات والمسارات. استخدمها بحذر.
        </div>
        <div class="input-group">
            <input type="text" class="form-control" id="whm-new-username" dir="ltr" maxlength="16"
                pattern="[a-zA-Z][a-zA-Z0-9]{0,15}" placeholder="اسم المستخدم الجديد">
            <button type="button" class="btn btn-outline-danger" id="whm-rename-save"
                data-url="{{ route('admin.whm.accounts.rename-user', $account) }}">
                <span class="whm-btn-label">إعادة تسمية</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </div>
</div>
@if(!$embedded)
    </div>
</div>
@endif
