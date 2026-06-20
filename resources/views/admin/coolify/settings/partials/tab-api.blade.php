<div class="coolify-settings-fields">
    <p class="text-muted small">اتصال Coolify API — تُحفظ في قاعدة البيانات (مجموعة <code>coolify</code>) دون تعديل <code>.env</code>.</p>
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
</div>
