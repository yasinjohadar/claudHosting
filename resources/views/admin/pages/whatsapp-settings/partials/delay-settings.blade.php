{{-- إعدادات الفواصل الزمنية — Evolution API --}}
<div class="card border mb-4" id="delay-settings">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="ri-time-line me-2"></i>إعدادات الفواصل الزمنية
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-3">
            <i class="ri-alert-line me-2"></i>
            <strong>مهم:</strong> الفواصل الزمنية تساعد في تجنب الحظر من WhatsApp. تُطبَّق على الإرسال الجماعي، دعوات الانضمام، رسائل Evolution المباشرة، ومقارنة المجموعات.
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">الفاصل بين الرسائل (بالثواني)</label>
                <input type="number"
                       class="form-control"
                       name="delay_between_messages"
                       id="delay_between_messages"
                       value="{{ old('delay_between_messages', $settings['delay_between_messages'] ?? 3) }}"
                       min="1"
                       max="60"
                       placeholder="3">
                <small class="text-muted">الفاصل الزمني بين كل رسالة وأخرى <span class="text-success">(مُطبَّق)</span></small>
                @error('delay_between_messages')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">الفاصل بين عمليات الإرسال الجماعي (بالثواني)</label>
                <input type="number"
                       class="form-control"
                       name="delay_between_broadcasts"
                       id="delay_between_broadcasts"
                       value="{{ old('delay_between_broadcasts', $settings['delay_between_broadcasts'] ?? 5) }}"
                       min="1"
                       max="60"
                       placeholder="5">
                <small class="text-muted">الفاصل بين كل عملية إرسال جماعي <span class="text-muted">(محفوظ — غير مُطبَّق بعد)</span></small>
                @error('delay_between_broadcasts')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">الحد الأقصى للرسائل في الدقيقة</label>
                <input type="number"
                       class="form-control"
                       name="max_messages_per_minute"
                       id="max_messages_per_minute"
                       value="{{ old('max_messages_per_minute', $settings['max_messages_per_minute'] ?? 20) }}"
                       min="1"
                       max="100"
                       placeholder="20">
                <small class="text-muted">الحد الأقصى لعدد الرسائل في الدقيقة <span class="text-muted">(محفوظ — غير مُطبَّق بعد)</span></small>
                @error('max_messages_per_minute')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">تفعيل الفواصل العشوائية</label>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox"
                           name="random_delay_enabled"
                           id="random_delay_enabled"
                           value="1"
                           {{ ($settings['random_delay_enabled'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="random_delay_enabled">
                        تفعيل الفواصل العشوائية لتجنب الأنماط الثابتة <span class="text-success">(مُطبَّق)</span>
                    </label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">الحد الأدنى للفاصل العشوائي (بالثواني)</label>
                <input type="number"
                       class="form-control"
                       name="min_delay"
                       id="min_delay"
                       value="{{ old('min_delay', $settings['min_delay'] ?? 2) }}"
                       min="1"
                       max="10"
                       placeholder="2">
                <small class="text-muted">يُضاف عشوائياً فوق الفاصل الأساسي</small>
                @error('min_delay')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">الحد الأقصى للفاصل العشوائي (بالثواني)</label>
                <input type="number"
                       class="form-control"
                       name="max_delay"
                       id="max_delay"
                       value="{{ old('max_delay', $settings['max_delay'] ?? 5) }}"
                       min="1"
                       max="10"
                       placeholder="5">
                <small class="text-muted">يُضاف عشوائياً فوق الفاصل الأساسي</small>
                @error('max_delay')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
