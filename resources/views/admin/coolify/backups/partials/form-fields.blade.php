@php
    $config = $config ?? [];
    $raw = $config['raw'] ?? $config;
@endphp
<div class="row g-3">
    @if(!empty($showDatabaseSelect))
    <div class="col-md-6">
        <label class="form-label">قاعدة البيانات <span class="text-danger">*</span></label>
        <select name="database_uuid" class="form-select" required>
            <option value="">— اختر —</option>
            @foreach($databases as $db)
                @php $duuid = $db['uuid'] ?? ''; @endphp
                <option value="{{ $duuid }}" {{ old('database_uuid', $databaseUuid ?? request('database_uuid')) === $duuid ? 'selected' : '' }}>
                    {{ $db['name'] ?? $duuid }} ({{ $db['type'] ?? $db['database_type'] ?? '—' }})
                </option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-6">
        <label class="form-label">التكرار <span class="text-danger">*</span></label>
        <select name="frequency" class="form-select" required>
            @foreach($frequencies as $key => $label)
                <option value="{{ $key }}" {{ old('frequency', $raw['frequency'] ?? 'daily') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">أو cron مخصص:</small>
        <input type="text" name="frequency_custom" class="form-control mt-1" placeholder="* * * * *" value="{{ old('frequency_custom') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">قواعد للنسخ (مفصولة بفاصلة)</label>
        <input type="text" name="databases_to_backup" class="form-control" value="{{ old('databases_to_backup', $raw['databases_to_backup'] ?? '') }}" placeholder="postgres, mydb">
    </div>
    <div class="col-md-4">
        <label class="form-label">مهلة (ثوانٍ)</label>
        <input type="number" name="timeout" class="form-control" min="60" max="86400" value="{{ old('timeout', $raw['timeout'] ?? 3600) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">S3 Storage UUID</label>
        <input type="text" name="s3_storage_uuid" class="form-control" value="{{ old('s3_storage_uuid', $raw['s3_storage_uuid'] ?? '') }}" placeholder="مطلوب عند تفعيل S3">
    </div>
    <div class="col-12 d-flex flex-wrap gap-4">
        <label class="form-check">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" class="form-check-input" {{ old('enabled', $raw['enabled'] ?? true) ? 'checked' : '' }}> مفعّل
        </label>
        <label class="form-check">
            <input type="hidden" name="save_s3" value="0">
            <input type="checkbox" name="save_s3" value="1" class="form-check-input" {{ old('save_s3', $raw['save_s3'] ?? false) ? 'checked' : '' }}> حفظ على S3
        </label>
        <label class="form-check">
            <input type="hidden" name="dump_all" value="0">
            <input type="checkbox" name="dump_all" value="1" class="form-check-input" {{ old('dump_all', $raw['dump_all'] ?? false) ? 'checked' : '' }}> نسخ كل القواعد (dump_all)
        </label>
        @if(!empty($showBackupNow))
        <label class="form-check">
            <input type="hidden" name="backup_now" value="0">
            <input type="checkbox" name="backup_now" value="1" class="form-check-input" {{ old('backup_now') ? 'checked' : '' }}> نسخ الآن
        </label>
        @endif
    </div>
    <div class="col-12"><hr class="my-1"><h6 class="text-muted">الاحتفاظ محلياً</h6></div>
    <div class="col-md-4">
        <label class="form-label">عدد النسخ</label>
        <input type="number" name="database_backup_retention_amount_locally" class="form-control" min="0" value="{{ old('database_backup_retention_amount_locally', $raw['database_backup_retention_amount_locally'] ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">أيام الاحتفاظ</label>
        <input type="number" name="database_backup_retention_days_locally" class="form-control" min="0" value="{{ old('database_backup_retention_days_locally', $raw['database_backup_retention_days_locally'] ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">حد التخزين (MB)</label>
        <input type="number" name="database_backup_retention_max_storage_locally" class="form-control" min="0" value="{{ old('database_backup_retention_max_storage_locally', $raw['database_backup_retention_max_storage_locally'] ?? 0) }}">
    </div>
    <div class="col-12"><h6 class="text-muted">الاحتفاظ على S3</h6></div>
    <div class="col-md-4">
        <label class="form-label">عدد النسخ S3</label>
        <input type="number" name="database_backup_retention_amount_s3" class="form-control" min="0" value="{{ old('database_backup_retention_amount_s3', $raw['database_backup_retention_amount_s3'] ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">أيام S3</label>
        <input type="number" name="database_backup_retention_days_s3" class="form-control" min="0" value="{{ old('database_backup_retention_days_s3', $raw['database_backup_retention_days_s3'] ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">حد S3 (MB)</label>
        <input type="number" name="database_backup_retention_max_storage_s3" class="form-control" min="0" value="{{ old('database_backup_retention_max_storage_s3', $raw['database_backup_retention_max_storage_s3'] ?? 0) }}">
    </div>
</div>

