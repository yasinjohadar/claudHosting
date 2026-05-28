<div class="card custom-card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">المفتاح</label>
                <input type="text" name="key" class="form-control" value="{{ old('key', $mailTemplate->key ?? '') }}" {{ isset($mailTemplate) ? 'readonly' : '' }} required>
            </div>
            <div class="col-md-6">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $mailTemplate->name ?? '') }}" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">العنوان Subject</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject', $mailTemplate->subject ?? '') }}" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">محتوى HTML</label>
                <textarea name="body_html" rows="8" class="form-control" required>{{ old('body_html', $mailTemplate->body_html ?? '') }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">محتوى نصي (اختياري)</label>
                <textarea name="body_text" rows="4" class="form-control">{{ old('body_text', $mailTemplate->body_text ?? '') }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">المتغيرات (comma separated)</label>
                <input type="text" name="available_variables" class="form-control" value="{{ old('available_variables', isset($mailTemplate) ? implode(',', $mailTemplate->available_variables ?? []) : '') }}" placeholder="user_name,email,phone">
            </div>
            <div class="col-md-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $mailTemplate->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">مفعل</label>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mt-3">
    <button type="submit" class="btn btn-primary">حفظ</button>
    <a href="{{ route('admin.mail-templates.index') }}" class="btn btn-light">رجوع</a>
</div>
