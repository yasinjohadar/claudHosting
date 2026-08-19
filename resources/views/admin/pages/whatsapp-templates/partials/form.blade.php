{{--
    Shared create/edit form.

    @param  \App\Models\WhatsAppMessageTemplate|null $template
    @param  array $variableGroups  from WhatsAppTemplateVariables::grouped()
    @param  array $categories

    Deliberately a plain textarea, not the TinyMCE editor the mail templates use: WhatsApp
    messages are plain text, so a rich editor would let the admin apply formatting that is
    silently flattened on the way out.
--}}
@php
    $isEdit = $template !== null;
    $isProtected = $isEdit && $template->isProtected();
    $body = old('body', $template->body ?? '');
@endphp

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card custom-card group-show-members-card mb-3">
            <div class="card-header border-0 pb-0">
                <h6 class="group-show-members-card__title mb-1">بيانات القالب</h6>
            </div>
            <div class="card-body pt-3">
                @if($isProtected)
                    <div class="alert alert-info border-0 py-2 small">
                        <i class="fe fe-shield me-1"></i>
                        <strong>قالب نظام.</strong> النظام يستدعيه بالمعرّف
                        <code dir="ltr">{{ $template->slug }}</code> في إرسال تلقائي، فالمعرّف غير قابل للتعديل
                        والقالب غير قابل للحذف. النص والحالة قابلان للتعديل بحرية.
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">اسم القالب <span class="text-danger">*</span></label>
                        <input type="text" name="name" maxlength="255" required
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $template->name ?? '') }}"
                            placeholder="مثال: ترحيب بحساب استضافة جديد">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">التصنيف <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach($categories as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('category', $template->category ?? 'general') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">وصف مختصر</label>
                        <input type="text" name="description" maxlength="500"
                            class="form-control @error('description') is-invalid @enderror"
                            value="{{ old('description', $template->description ?? '') }}"
                            placeholder="متى يُستخدم هذا القالب؟">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label fw-semibold">المعرّف (slug)</label>
                        <input type="text" name="slug" maxlength="120" dir="ltr"
                            class="form-control @error('slug') is-invalid @enderror"
                            value="{{ old('slug', $template->slug ?? '') }}"
                            placeholder="welcome_new_account"
                            {{ $isProtected ? 'readonly' : '' }}>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="admin-group-form-hint mb-0 mt-2">يُترك فارغاً ليُولَّد من الاسم. يُستخدم للاستدعاء البرمجي.</p>
                    </div>

                    <div class="col-md-5 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                name="is_active" value="1"
                                {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">القالب مفعّل</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card group-show-members-card mb-3">
            <div class="card-header border-0 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="group-show-members-card__title mb-1">نص الرسالة</h6>
                <span class="small text-muted">
                    <span id="wa-tpl-count">{{ mb_strlen($body) }}</span> / 4096
                </span>
            </div>
            <div class="card-body pt-3">
                <textarea name="body" id="wa-tpl-body" rows="10" maxlength="4096" required dir="auto"
                    class="form-control @error('body') is-invalid @enderror"
                    placeholder="مرحباً {customer_name}، ...">{{ $body }}</textarea>
                @error('body')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <p class="admin-group-form-hint mb-0 mt-2">
                    تنسيق واتساب مدعوم: <code>*عريض*</code> و <code>_مائل_</code> و <code>~مشطوب~</code>.
                    اضغط أي متغير على اليسار لإدراجه عند مؤشر الكتابة.
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fe fe-save me-1"></i> {{ $isEdit ? 'حفظ التعديلات' : 'إنشاء القالب' }}
            </button>
            <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-light">إلغاء</a>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card custom-card group-show-members-card mb-3">
            <div class="card-header border-0 pb-0">
                <h6 class="group-show-members-card__title mb-1">المتغيرات المتاحة</h6>
            </div>
            <div class="card-body pt-3">
                @foreach($variableGroups as $groupKey => $group)
                    <div class="mb-3">
                        <span class="d-block small fw-bold text-muted mb-2">
                            <i class="{{ $group['icon'] }} me-1"></i>{{ $group['label'] }}
                        </span>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($group['variables'] as $variable)
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary wa-tpl-insert"
                                    data-variable="{{ $variable['key'] }}"
                                    title="{{ $variable['label'] }} — مثال: {{ $variable['sample'] }}">
                                    {{ '{'.$variable['key'].'}' }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p class="admin-group-form-hint mb-0">
                    أي متغير غير موجود في هذه القائمة يُرفض عند الحفظ — حتى لا تُرسل رسالة ناقصة لعميل.
                </p>
            </div>
        </div>

        <div class="card custom-card group-show-members-card">
            <div class="card-header border-0 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="group-show-members-card__title mb-1">معاينة بالقيم النموذجية</h6>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="wa-tpl-refresh">
                    <i class="fe fe-refresh-cw me-1"></i> تحديث
                </button>
            </div>
            <div class="card-body pt-3">
                <div id="wa-tpl-warning" class="alert alert-warning border-0 py-2 small d-none"></div>
                <div class="wa-tpl-preview" id="wa-tpl-preview" dir="auto"></div>

                <hr class="my-3">

                <label class="form-label fw-semibold small">إرسال تجريبي</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="wa-tpl-test-to" class="form-control" dir="ltr" placeholder="+905519665883">
                    <button type="button" class="btn btn-outline-success" id="wa-tpl-test-send">
                        <i class="fe fe-send me-1"></i> إرسال
                    </button>
                </div>
                <div id="wa-tpl-test-result" class="small mt-2"></div>
                <p class="admin-group-form-hint mb-0 mt-2">يُرسل النص بالقيم النموذجية — جرّبه على رقمك قبل أي عميل.</p>
            </div>
        </div>
    </div>
</div>
