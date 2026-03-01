@extends('admin.layouts.master')

@section('page-title')
    إنشاء جدولة جديدة
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إنشاء جدولة جديدة</h5>
            </div>
            <div>
                <a href="{{ route('admin.backup-schedules.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('admin.backup-schedules.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الجدولة <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="backup_type" class="form-label">نوع النسخ <span class="text-danger">*</span></label>
                                    <select class="form-select @error('backup_type') is-invalid @enderror" id="backup_type" name="backup_type" required>
                                        @foreach($backupTypes as $key => $label)
                                            <option value="{{ $key }}" {{ old('backup_type', 'full') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('backup_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="frequency" class="form-label">التكرار <span class="text-danger">*</span></label>
                                    <select class="form-select @error('frequency') is-invalid @enderror" id="frequency" name="frequency" required>
                                        @foreach($frequencies as $key => $label)
                                            <option value="{{ $key }}" {{ old('frequency', 'daily') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="time" class="form-label">الوقت <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control @error('time') is-invalid @enderror" id="time" name="time" value="{{ old('time', '02:00') }}" required>
                                    @error('time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3" id="day_of_month_field" style="display: none;">
                                    <label for="day_of_month" class="form-label">يوم الشهر</label>
                                    <input type="number" class="form-control" id="day_of_month" name="day_of_month" value="{{ old('day_of_month', 1) }}" min="1" max="31">
                                </div>
                            </div>

                            <div class="mb-3" id="days_of_week_field" style="display: none;">
                                <label class="form-label">أيام الأسبوع</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach([0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'] as $day => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="days_of_week[]" value="{{ $day }}" id="day_{{ $day }}" {{ in_array($day, old('days_of_week', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="day_{{ $day }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="storage_config_id" class="form-label">مكان التخزين <span class="text-danger">*</span></label>
                                <select class="form-select @error('storage_config_id') is-invalid @enderror" id="storage_config_id" name="storage_config_id" required>
                                    <option value="">-- اختر مكان التخزين --</option>
                                    @foreach($storageConfigs as $config)
                                        <option value="{{ $config->id }}" {{ old('storage_config_id') == $config->id ? 'selected' : '' }}>
                                            {{ $config->name }} ({{ $config->driver }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('storage_config_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">أنواع الضغط <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 flex-wrap">
                                    @foreach($compressionTypes as $key => $label)
                                        <div class="form-check">
                                            <input class="form-check-input @error('compression_types') is-invalid @enderror" type="checkbox" name="compression_types[]" value="{{ $key }}" id="comp_{{ $key }}" {{ in_array($key, old('compression_types', ['zip'])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="comp_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('compression_types')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="retention_days" class="form-label">أيام الاحتفاظ <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('retention_days') is-invalid @enderror" id="retention_days" name="retention_days" value="{{ old('retention_days', 30) }}" min="1" max="365" required>
                                @error('retention_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ
                                </button>
                                <a href="{{ route('admin.backup-schedules.index') }}" class="btn btn-secondary">
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const frequencySelect = document.getElementById('frequency');
    const daysOfWeekField = document.getElementById('days_of_week_field');
    const dayOfMonthField = document.getElementById('day_of_month_field');

    function toggleFields() {
        if (frequencySelect.value === 'weekly') {
            daysOfWeekField.style.display = 'block';
            dayOfMonthField.style.display = 'none';
        } else if (frequencySelect.value === 'monthly') {
            daysOfWeekField.style.display = 'none';
            dayOfMonthField.style.display = 'block';
        } else {
            daysOfWeekField.style.display = 'none';
            dayOfMonthField.style.display = 'none';
        }
    }

    frequencySelect.addEventListener('change', toggleFields);
    toggleFields();
});
</script>
@endpush
@stop

