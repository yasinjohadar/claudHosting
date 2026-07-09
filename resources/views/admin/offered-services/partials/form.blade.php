@php
    $service = $service ?? null;
@endphp

<div class="row g-3">
    <div class="col-12">
        <label class="domain-form-label">نوع الخدمة <span class="text-danger">*</span></label>
        <select name="service_type_id" class="form-select form-select-sm @error('service_type_id') is-invalid @enderror" required>
            <option value="">— اختر النوع —</option>
            @foreach($serviceTypes as $type)
            <option value="{{ $type->id }}" @selected(old('service_type_id', $service?->service_type_id) == $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        @error('service_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($serviceTypes->isEmpty())
        <small class="text-danger">أضف <a href="{{ route('admin.service-types.create') }}">نوع خدمة</a> أولاً.</small>
        @endif
    </div>
    <div class="col-md-8">
        <label class="domain-form-label">اسم الخدمة <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name', $service?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">Slug</label>
        <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $service?->slug) }}" dir="ltr">
    </div>
    <div class="col-12">
        <label class="domain-form-label">الوصف</label>
        <textarea name="description" rows="4" class="form-control form-control-sm">{{ old('description', $service?->description) }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">السعر <span class="text-danger">*</span></label>
        <input type="number" name="price" step="0.01" min="0" class="form-control form-control-sm @error('price') is-invalid @enderror" value="{{ old('price', $service?->price ?? 0) }}" required>
        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">العملة</label>
        <select name="currency" class="form-select form-select-sm">
            <option value="SAR" @selected(old('currency', $service?->currency ?? 'SAR') === 'SAR')>ريال سعودي (SAR)</option>
            <option value="USD" @selected(old('currency', $service?->currency) === 'USD')>دولار (USD)</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="domain-form-label">ترتيب العرض</label>
        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $service?->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-md-6">
        <label class="domain-form-label">مدة التنفيذ (نص)</label>
        <input type="text" name="execution_duration" class="form-control form-control-sm" value="{{ old('execution_duration', $service?->execution_duration) }}" placeholder="مثال: 3–5 أيام عمل">
    </div>
    <div class="col-md-6">
        <label class="domain-form-label">مدة التنفيذ (أيام)</label>
        <input type="number" name="execution_days" class="form-control form-control-sm" value="{{ old('execution_days', $service?->execution_days) }}" min="0" placeholder="اختياري">
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="service_is_active" {{ old('is_active', $service?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="service_is_active">خدمة نشطة في الكتالوج</label>
        </div>
    </div>
</div>
