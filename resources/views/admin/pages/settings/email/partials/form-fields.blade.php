@php
    $isEdit = isset($emailSetting);
    $providerValue = old('provider', $isEdit ? $emailSetting->provider : '');
    $hostValue = old('mail_host', $isEdit ? $emailSetting->mail_host : '');
    $portValue = old('mail_port', $isEdit ? $emailSetting->mail_port : 587);
    $encryptionValue = old('mail_encryption', $isEdit ? $emailSetting->mail_encryption : 'tls');
    $usernameValue = old('mail_username', $isEdit ? $emailSetting->mail_username : '');
    $fromAddressValue = old('mail_from_address', $isEdit ? $emailSetting->mail_from_address : '');
    $fromNameValue = old('mail_from_name', $isEdit ? $emailSetting->mail_from_name : config('app.name'));
@endphp

<div class="card custom-card group-show-members-card mb-4">
    <div class="card-header border-0 pb-0">
        <h6 class="group-show-members-card__title mb-1">اختر المزود</h6>
        <p class="fs-12 text-muted mb-0">Gmail وOutlook يملآن الإعدادات تلقائياً.</p>
    </div>
    <div class="card-body pt-3">
        <label class="form-label fw-semibold">مزود البريد <span class="text-danger">*</span></label>
        <select name="provider" id="provider" class="form-select @error('provider') is-invalid @enderror" required>
            <option value="">— اختر المزود —</option>
            @foreach($providers as $key => $provider)
                <option value="{{ $key }}" {{ $providerValue == $key ? 'selected' : '' }}>{{ $provider['name'] }}</option>
            @endforeach
        </select>
        @error('provider')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="card custom-card group-show-members-card mb-4">
    <div class="card-header border-0 pb-0">
        <h6 class="group-show-members-card__title mb-1">إعدادات SMTP</h6>
    </div>
    <div class="card-body pt-3">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">SMTP Host <span class="text-danger">*</span></label>
                <input type="text" name="mail_host" id="mail_host" class="form-control @error('mail_host') is-invalid @enderror"
                       value="{{ $hostValue }}" placeholder="smtp.gmail.com" required>
                @error('mail_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Port <span class="text-danger">*</span></label>
                <input type="number" name="mail_port" id="mail_port" class="form-control @error('mail_port') is-invalid @enderror"
                       value="{{ $portValue }}" required>
                @error('mail_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">التشفير <span class="text-danger">*</span></label>
                <select name="mail_encryption" id="mail_encryption" class="form-select @error('mail_encryption') is-invalid @enderror" required>
                    <option value="tls" {{ $encryptionValue == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ $encryptionValue == 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ $encryptionValue == 'none' ? 'selected' : '' }}>بدون تشفير</option>
                </select>
                @error('mail_encryption')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
                <label class="form-label fw-semibold">اسم المستخدم / البريد <span class="text-danger">*</span></label>
                <input type="text" name="mail_username" id="mail_username" class="form-control @error('mail_username') is-invalid @enderror"
                       value="{{ $usernameValue }}" required>
                @error('mail_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">
                    كلمة المرور @if(!$isEdit)<span class="text-danger">*</span>@endif
                </label>
                <div class="input-group">
                    <input type="password" name="mail_password" id="mail_password"
                           class="form-control @error('mail_password') is-invalid @enderror"
                           placeholder="{{ $isEdit ? 'اتركه فارغاً للاحتفاظ بالحالية' : '••••••••' }}"
                           {{ $isEdit ? '' : 'required' }}>
                    <button class="btn btn-outline-secondary" type="button" onclick="toggleSmtpPassword()">
                        <i class="fe fe-eye" id="smtpPasswordToggleIcon"></i>
                    </button>
                </div>
                @error('mail_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <p class="admin-group-form-hint mb-0 mt-2">
                    <i class="fe fe-info me-1"></i>
                    لـ Gmail استخدم <strong>App Password</strong> وليس كلمة مرور الحساب.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card custom-card group-show-members-card">
    <div class="card-header border-0 pb-0">
        <h6 class="group-show-members-card__title mb-1">البريد المرسل</h6>
    </div>
    <div class="card-body pt-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">البريد المرسل <span class="text-danger">*</span></label>
                <input type="email" name="mail_from_address" id="mail_from_address"
                       class="form-control @error('mail_from_address') is-invalid @enderror"
                       value="{{ $fromAddressValue }}" required>
                @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">اسم المرسل <span class="text-danger">*</span></label>
                <input type="text" name="mail_from_name" id="mail_from_name"
                       class="form-control @error('mail_from_name') is-invalid @enderror"
                       value="{{ $fromNameValue }}" required>
                @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="admin-email-settings-page__form-actions mt-4 pt-3 border-top d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" id="btnTestConnection" onclick="testFormConnection(this)">
                <i class="fe fe-wifi me-1"></i>اختبار الاتصال
            </button>
            <button type="button" class="btn btn-outline-info" onclick="openFormSendTestModal()">
                <i class="fe fe-send me-1"></i>إرسال بريد اختبار
            </button>
            <button type="submit" class="btn btn-primary ms-md-auto">
                <i class="fe fe-save me-1"></i>{{ $isEdit ? 'حفظ التغييرات' : 'حفظ الإعدادات' }}
            </button>
        </div>
    </div>
</div>

@if($isEdit)
    <input type="hidden" id="email_setting_id" value="{{ $emailSetting->id }}">
@endif
