<div class="tab-pane fade" id="tabBackups" role="tabpanel">
    <div class="mb-3">
        <label class="form-label">طابور Queue للنسخ والاستعادة</label>
        <input type="text" name="backup_queue" class="form-control @error('backup_queue') is-invalid @enderror"
            value="{{ old('backup_queue', $form['backup_queue'] ?? 'coolify-backups') }}"
            placeholder="coolify-backups" dir="ltr">
        @error('backup_queue')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">شغّل العامل: <code>php artisan queue:work --queue={{ old('backup_queue', $form['backup_queue'] ?? 'coolify-backups') }}</code></div>
    </div>
    <hr>
    <h6 class="text-muted">تخزين اللقطات — S3 فقط</h6>
    <p class="small text-muted">لا يُحفظ أي نسخ دائم على سيرفرات Coolify. يُنشأ أرشيف مؤقت في <code>/tmp</code> ثم يُرفع إلى S3 ويُحذف فوراً.</p>
    @if(!($snapshotStorageReady ?? false))
        <div class="alert alert-warning py-2 small">اختر سجل تخزين S3 نشط من <a href="{{ route('admin.storage.index') }}">ربط الأقراص</a>.</div>
    @endif
    <div class="mb-3">
        <label class="form-label">سجل التخزين (App Storage) *</label>
        <select name="snapshot_storage_config_id" class="form-select @error('snapshot_storage_config_id') is-invalid @enderror">
            <option value="">— اختر S3 / R2 / Wasabi —</option>
            @foreach($storageConfigs ?? [] as $sc)
                <option value="{{ $sc->id }}" {{ (int) old('snapshot_storage_config_id', $form['snapshot_storage_config_id'] ?? 0) === $sc->id ? 'selected' : '' }}>
                    {{ $sc->name }} ({{ $sc->driver }})
                </option>
            @endforeach
        </select>
        @error('snapshot_storage_config_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label class="form-label">بادئة المسار داخل الـ bucket</label>
        <input type="text" name="s3_prefix" class="form-control" value="{{ old('s3_prefix', $form['s3_prefix'] ?? 'coolify-snapshots') }}" dir="ltr">
    </div>
    <div class="mb-3">
        <label class="form-label">UUID تخزين S3 في Coolify (لنسخ قواعد البيانات في اللقطة)</label>
        <div class="input-group mb-2">
            <input type="text" name="coolify_s3_storage_uuid" id="coolifyS3Uuid" class="form-control @error('coolify_s3_storage_uuid') is-invalid @enderror"
                value="{{ old('coolify_s3_storage_uuid', $form['coolify_s3_storage_uuid'] ?? '') }}" dir="ltr"
                placeholder="من Coolify → Storages → انسخ UUID">
            @if($connected ?? false)
            <button type="button" class="btn btn-outline-primary" id="btnDiscoverS3">جلب من Coolify</button>
            @endif
        </div>
        @error('coolify_s3_storage_uuid')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">
            <strong>اختياري</strong> لنسخ DB عبر Coolify API. Coolify 4 لا يوفّر قائمة S3 في API —
            افتح <a href="{{ rtrim($form['api_url'] ?? '', '/') }}/storages" target="_blank" rel="noopener">Storages في Coolify</a>
            وانسخ UUID، أو أنشئ جدولة نسخ DB مع S3 ثم «جلب من Coolify».
            بدون UUID: لقطات التطبيقات/volumes تعمل؛ DB تُحفظ كـ manifest على S3.
        </div>
        <div id="discoverS3Result" class="small mt-2"></div>
        @if(!empty($coolifyS3Storages))
            <div class="form-text mt-2">من API:</div>
            <ul class="small mb-0">
                @foreach($coolifyS3Storages as $s3)
                    <li>
                        <a href="#" class="s3-pick" data-uuid="{{ $s3['uuid'] ?? '' }}">
                            <code>{{ $s3['uuid'] ?? '' }}</code></a>
                        — {{ $s3['name'] ?? $s3['bucket'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
