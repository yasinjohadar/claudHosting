@php
    $isEdit = isset($mailTemplate);
    $variables = old('available_variables', isset($mailTemplate) ? implode(', ', $mailTemplate->available_variables ?? []) : '');
    $variableList = array_values(array_filter(array_map('trim', explode(',', $variables))));
    if (empty($variableList) && $isEdit) {
        $variableList = $mailTemplate->available_variables ?? [];
    }
    if (empty($variableList)) {
        $variableList = ['user_name', 'email', 'action_url'];
    }
@endphp

<div class="card custom-card group-show-members-card mb-4 dashboard-fade-in">
    <div class="card-header border-0 pb-0">
        <h6 class="group-show-members-card__title mb-1">البيانات الأساسية</h6>
    </div>
    <div class="card-body pt-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold required">المفتاح</label>
                <input type="text" name="key" class="form-control @error('key') is-invalid @enderror"
                       value="{{ old('key', $mailTemplate->key ?? '') }}"
                       {{ $isEdit ? 'readonly' : '' }}
                       placeholder="payment.received" required>
                @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold required">الاسم</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $mailTemplate->name ?? '') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold required">موضوع البريد</label>
                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                       value="{{ old('subject', $mailTemplate->subject ?? '') }}" required>
                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="admin-group-form-hint mb-0 mt-2">يمكنك استخدام المتغيرات مثل @{{user_name}} و @{{action_url}}</p>
            </div>
        </div>
    </div>
</div>

<div class="card custom-card group-show-members-card mb-4 dashboard-fade-in">
    <div class="card-header border-0 pb-0">
        <h6 class="group-show-members-card__title mb-1">المتغيرات والمحتوى</h6>
    </div>
    <div class="card-body pt-3">
        <div class="card bg-light mb-3 border-0 admin-email-templates-page__variables-card">
            <div class="card-body">
                <h6 class="mb-3"><i class="fe fe-code me-2"></i>المتغيرات المتاحة</h6>
                <div class="d-flex flex-wrap gap-2 mb-3" id="variableButtons">
                    @foreach($variableList as $variable)
                        <button type="button" class="btn btn-sm btn-outline-primary insert-variable" data-variable="{{ $variable }}">
                            {{ '{' . '{' . $variable . '}' . '}' }}
                        </button>
                    @endforeach
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">إضافة متغيرات (مفصولة بفاصلة)</label>
                    <input type="text" name="available_variables" class="form-control"
                           value="{{ $variables }}"
                           placeholder="user_name, email, action_url">
                </div>
                <p class="admin-group-form-hint mb-0 mt-2">اضغط على متغير لإدراجه في الموضوع أو محتوى HTML</p>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold required">محتوى HTML</label>
            <textarea name="body_html" id="body_html" class="form-control @error('body_html') is-invalid @enderror" rows="12">{{ old('body_html', $mailTemplate->body_html ?? '') }}</textarea>
            @error('body_html')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">محتوى نصي (اختياري)</label>
            <textarea name="body_text" rows="4" class="form-control @error('body_text') is-invalid @enderror">{{ old('body_text', $mailTemplate->body_text ?? '') }}</textarea>
            @error('body_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $mailTemplate->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_active">تفعيل القالب</label>
        </div>
    </div>
</div>

<div class="admin-email-templates-page__form-actions d-flex flex-wrap gap-2 justify-content-md-end mb-4">
    <a href="{{ route('admin.mail-templates.index') }}" class="btn btn-light rounded-pill">
        <i class="fe fe-x me-1"></i>إلغاء
    </a>
    <button type="submit" class="btn btn-primary rounded-pill">
        <i class="fe fe-save me-1"></i>{{ $isEdit ? 'حفظ التعديلات' : 'حفظ القالب' }}
    </button>
</div>
