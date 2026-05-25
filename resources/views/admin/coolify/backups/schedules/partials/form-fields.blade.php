@php
    $schedule = $schedule ?? null;
    $isEdit = $schedule !== null;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم الجدولة</label>
        <input type="text" name="name" class="form-control" required maxlength="255"
            value="{{ old('name', $schedule->name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">المشروع</label>
        <select name="project_uuid" id="projectUuid" class="form-select" required>
            @foreach($projects as $p)
                @php $puuid = $p['uuid'] ?? ''; @endphp
                <option value="{{ $puuid }}" data-name="{{ $p['name'] ?? '' }}"
                    {{ old('project_uuid', $schedule->project_uuid ?? '') === $puuid ? 'selected' : '' }}>
                    {{ $p['name'] ?? $puuid }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="project_name" id="projectName" value="{{ old('project_name', $schedule->project_name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">التكرار</label>
        <select name="frequency" class="form-select" required>
            @foreach($frequencies as $key => $label)
                <option value="{{ $key }}" {{ old('frequency', $schedule->frequency ?? 'daily') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label d-block">الحالة</label>
        <label class="form-check mt-2">
            <input type="checkbox" name="enabled" value="1" class="form-check-input"
                {{ old('enabled', $schedule->enabled ?? true) ? 'checked' : '' }}> مفعّل
        </label>
    </div>
    <div class="col-12">
        <label class="form-label">موارد اللقطة</label>
        <div class="d-flex flex-wrap gap-3">
            <label class="form-check">
                <input type="checkbox" name="include_databases" value="1" class="form-check-input"
                    {{ old('include_databases', $schedule->options['include_databases'] ?? true) ? 'checked' : '' }}> قواعد البيانات
            </label>
            <label class="form-check">
                <input type="checkbox" name="include_applications" value="1" class="form-check-input"
                    {{ old('include_applications', $schedule->options['include_applications'] ?? true) ? 'checked' : '' }}> التطبيقات
            </label>
            <label class="form-check">
                <input type="checkbox" name="include_services" value="1" class="form-check-input"
                    {{ old('include_services', $schedule->options['include_services'] ?? true) ? 'checked' : '' }}> الخدمات
            </label>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('projectUuid')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('projectName').value = opt?.dataset?.name || '';
});
</script>
@endpush

