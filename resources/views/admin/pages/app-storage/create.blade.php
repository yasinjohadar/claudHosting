@extends('admin.layouts.master')

@section('page-title')
    إضافة مكان تخزين
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">إضافة مكان تخزين</h5>
            </div>
            <div>
                <a href="{{ route('admin.storage.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-right me-1"></i> رجوع
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>حدث خطأ:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form action="{{ route('admin.storage.store') }}" method="POST" id="storage-form">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الإعداد <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="driver" class="form-label">نوع التخزين <span class="text-danger">*</span></label>
                                <select class="form-select @error('driver') is-invalid @enderror" id="driver" name="driver" required>
                                    <option value="">اختر نوع التخزين</option>
                                    @if(isset($drivers) && is_array($drivers))
                                        @foreach($drivers as $key => $label)
                                            <option value="{{ $key }}" {{ old('driver') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('driver')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="config-fields">
                                <!-- سيتم ملؤها ديناميكياً حسب نوع التخزين -->
                            </div>
                            @error('config')
                                <div class="alert alert-danger mt-2">
                                    <i class="fas fa-exclamation-circle me-2"></i>{{ $message }}
                                </div>
                            @enderror

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">الأولوية</label>
                                    <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', 0) }}" min="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cdn_url" class="form-label">رابط CDN (اختياري)</label>
                                    <input type="url" class="form-control" id="cdn_url" name="cdn_url" value="{{ old('cdn_url') }}" placeholder="https://cdn.example.com">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">أنواع الملفات المدعومة (اختياري)</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="image" id="file_type_image">
                                            <label class="form-check-label" for="file_type_image">صور</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="document" id="file_type_document">
                                            <label class="form-check-label" for="file_type_document">وثائق</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="file_types[]" value="video" id="file_type_video">
                                            <label class="form-check-label" for="file_type_video">فيديو</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">نشط</label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="redundancy" name="redundancy" value="1" {{ old('redundancy') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="redundancy">تفعيل التخزين المتعدد (Redundancy)</label>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6>إعدادات التسعير (اختياري)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">تكلفة التخزين لكل GB</label>
                                            <input type="number" step="0.01" class="form-control" name="pricing_config[storage_cost_per_gb]" value="{{ old('pricing_config.storage_cost_per_gb') }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">تكلفة الرفع لكل GB</label>
                                            <input type="number" step="0.01" class="form-control" name="pricing_config[upload_cost_per_gb]" value="{{ old('pricing_config.upload_cost_per_gb') }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">تكلفة التحميل لكل GB</label>
                                            <input type="number" step="0.01" class="form-control" name="pricing_config[download_cost_per_gb]" value="{{ old('pricing_config.download_cost_per_gb') }}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">الميزانية الشهرية</label>
                                            <input type="number" step="0.01" class="form-control" name="monthly_budget" value="{{ old('monthly_budget') }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">حد تنبيه التكلفة</label>
                                            <input type="number" step="0.01" class="form-control" name="cost_alert_threshold" value="{{ old('cost_alert_threshold') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="button" id="test-connection-btn" class="btn btn-warning">
                                    <i class="fas fa-vial me-1"></i> اختبار الاتصال
                                </button>
                                <div id="test-connection-result" class="mt-2" style="display: none;"></div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ
                                </button>
                                <a href="{{ route('admin.storage.index') }}" class="btn btn-secondary">
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
    const driverSelect = document.getElementById('driver');
    const configFields = document.getElementById('config-fields');

    // التحقق من وجود العناصر
    if (!driverSelect || !configFields) {
        console.error('Driver select or config fields not found');
        return;
    }

    const configTemplates = {
        'local': '<div class="mb-3"><label class="form-label">المسار (اختياري)</label><input type="text" class="form-control" name="config[path]" value="public"></div>',
        's3': '<div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>' +
              '<div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>' +
              '<div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>' +
              '<div class="mb-3"><label class="form-label">Region</label><input type="text" class="form-control" name="config[region]" value="us-east-1"></div>' +
              '<div class="mb-3"><label class="form-label">Endpoint (لـ S3-compatible)</label><input type="text" class="form-control" name="config[endpoint]" placeholder="https://s3.region.amazonaws.com"></div>' +
              '<div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[use_path_style]" value="1" id="use_path_style"><label class="form-check-label" for="use_path_style">Use Path Style Endpoint</label></div></div>',
        'digitalocean': '<div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>' +
                       '<div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>' +
                       '<div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>' +
                       '<div class="mb-3"><label class="form-label">Region</label><select class="form-select" name="config[region]"><option value="nyc3">NYC3</option><option value="nyc1">NYC1</option><option value="sfo3">SFO3</option><option value="sgp1">SGP1</option><option value="sfo2">SFO2</option><option value="ams3">AMS3</option></select></div>',
        'wasabi': '<div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>' +
                 '<div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>' +
                 '<div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>' +
                 '<div class="mb-3"><label class="form-label">Region</label><select class="form-select" name="config[region]"><option value="us-east-1">US East 1</option><option value="us-west-1">US West 1</option><option value="eu-central-1">EU Central 1</option><option value="ap-northeast-1">AP Northeast 1</option></select></div>',
        'backblaze': '<div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>' +
                    '<div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>' +
                    '<div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>' +
                    '<div class="mb-3"><label class="form-label">Region</label><input type="text" class="form-control" name="config[region]" value="us-west-000" placeholder="us-west-000"></div>',
        'cloudflare_r2': '<div class="mb-3"><label class="form-label">Account ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[account_id]" required placeholder="Account ID من Cloudflare"></div>' +
                        '<div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>' +
                        '<div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>' +
                        '<div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>',
        'google_drive': '<div class="mb-3"><label class="form-label">Client ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[client_id]" required></div>' +
                      '<div class="mb-3"><label class="form-label">Client Secret <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[client_secret]" required></div>' +
                      '<div class="mb-3"><label class="form-label">Refresh Token <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[refresh_token]" required></div>' +
                      '<div class="mb-3"><label class="form-label">Folder ID (اختياري)</label><input type="text" class="form-control" name="config[folder_id]" placeholder="ID المجلد في Google Drive"></div>',
        'bunny': '<div class="mb-3"><label class="form-label">Storage Zone Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[storage_zone]" required placeholder="اسم Storage Zone من Bunny"></div>' +
                '<div class="mb-3"><label class="form-label">API Key (FTP Password) <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[api_key]" required placeholder="API Key أو FTP Password"></div>' +
                '<div class="mb-3"><label class="form-label">Region</label><select class="form-select" name="config[region]"><option value="de">DE (Germany)</option><option value="uk">UK (United Kingdom)</option><option value="ny">NY (New York)</option><option value="la">LA (Los Angeles)</option><option value="sg">SG (Singapore)</option><option value="syd">SYD (Sydney)</option><option value="br">BR (Brazil)</option><option value="jh">JH (Johannesburg)</option></select></div>' +
                '<div class="mb-3"><label class="form-label">Pull Zone URL (اختياري)</label><input type="text" class="form-control" name="config[pull_zone]" placeholder="https://your-pull-zone.b-cdn.net"></div>',
        'dropbox': '<div class="mb-3"><label class="form-label">Access Token <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_token]" required></div>',
        'ftp': '<div class="mb-3"><label class="form-label">Protocol</label><select class="form-select" name="config[protocol]"><option value="ftp">FTP</option><option value="sftp">SFTP</option></select></div>' +
              '<div class="mb-3"><label class="form-label">Host <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[host]" required></div>' +
              '<div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[username]" required></div>' +
              '<div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[password]" required></div>' +
              '<div class="mb-3"><label class="form-label">Port</label><input type="number" class="form-control" name="config[port]" value="21" id="ftp_port"></div>' +
              '<div class="mb-3"><label class="form-label">Root Path</label><input type="text" class="form-control" name="config[root]" value="/"></div>' +
              '<div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[use_tls]" value="1" id="use_tls"><label class="form-check-label" for="use_tls">Use TLS</label></div></div>' +
              '<div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[passive]" value="1" id="passive" checked><label class="form-check-label" for="passive">Passive Mode</label></div></div>',
        'sftp': '<div class="mb-3"><label class="form-label">Host <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[host]" required></div>' +
               '<div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[username]" required></div>' +
               '<div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[password]" required></div>' +
               '<div class="mb-3"><label class="form-label">Port</label><input type="number" class="form-control" name="config[port]" value="22"></div>' +
               '<div class="mb-3"><label class="form-label">Root Path</label><input type="text" class="form-control" name="config[root]" value="/"></div>',
        'azure': '<div class="mb-3"><label class="form-label">Account Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[account_name]" required></div>' +
                '<div class="mb-3"><label class="form-label">Account Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[account_key]" required></div>' +
                '<div class="mb-3"><label class="form-label">Container <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[container]" required></div>',
    };

    function updateConfigFields() {
        const driver = driverSelect.value;
        
        // إضافة animation
        configFields.style.opacity = '0.5';
        configFields.style.transition = 'opacity 0.2s';
        
        setTimeout(() => {
            if (driver && configTemplates[driver]) {
                configFields.innerHTML = configTemplates[driver];
            } else if (!driver) {
                configFields.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>يرجى اختيار نوع التخزين لعرض الحقول المطلوبة</div>';
            } else {
                configFields.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>نوع التخزين المحدد غير مدعوم</div>';
            }
            
            // إعادة opacity
            configFields.style.opacity = '1';
        }, 100);
    }

    // إضافة event listener
    driverSelect.addEventListener('change', updateConfigFields);

    // تشغيل عند التحميل إذا كان هناك قيمة قديمة
    if (driverSelect.value) {
        updateConfigFields();
    } else {
        configFields.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>يرجى اختيار نوع التخزين لعرض الحقول المطلوبة</div>';
    }

    // زر اختبار الاتصال
    const testConnectionBtn = document.getElementById('test-connection-btn');
    const testConnectionResult = document.getElementById('test-connection-result');
    
    if (testConnectionBtn && testConnectionResult) {
        testConnectionBtn.addEventListener('click', function() {
            const driver = driverSelect.value;
            if (!driver) {
                testConnectionResult.innerHTML = 
                    '<div class="alert alert-warning alert-dismissible fade show" role="alert">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    'يرجى اختيار نوع التخزين أولاً' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                testConnectionResult.style.display = 'block';
                return;
            }

            // جمع بيانات الإعدادات
            const configData = {};
            const configInputs = configFields.querySelectorAll('input, select, textarea');
            configInputs.forEach(input => {
                if (input.name && input.name.startsWith('config[')) {
                    const key = input.name.replace('config[', '').replace(']', '');
                    if (input.type === 'checkbox') {
                        configData[key] = input.checked ? input.value : '';
                    } else if (input.type === 'password' && input.value) {
                        configData[key] = input.value;
                    } else if (input.type !== 'password') {
                        configData[key] = input.value || '';
                    }
                }
            });

            // تعطيل الزر أثناء الاختبار
            testConnectionBtn.disabled = true;
            const originalText = testConnectionBtn.innerHTML;
            testConnectionBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاختبار...';
            testConnectionResult.style.display = 'none';

            // إرسال طلب AJAX
            const testUrl = @json(route('admin.storage.test-connection'));
            fetch(testUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    driver: driver,
                    config: configData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = data.message || 'الاتصال ناجح';
                    testConnectionResult.innerHTML = 
                        '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        '<i class="fas fa-check-circle me-2"></i>' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                } else {
                    const message = data.message || 'فشل الاتصال';
                    testConnectionResult.innerHTML = 
                        '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                        '<i class="fas fa-times-circle me-2"></i>' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
                }
                testConnectionResult.style.display = 'block';
            })
            .catch(error => {
                const errorMsg = error.message || 'حدث خطأ غير معروف';
                testConnectionResult.innerHTML = 
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<i class="fas fa-times-circle me-2"></i>' +
                    'حدث خطأ: ' + errorMsg +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>';
                testConnectionResult.style.display = 'block';
            })
            .finally(() => {
                testConnectionBtn.disabled = false;
                testConnectionBtn.innerHTML = originalText;
            });
        });
    }
});
</script>
@endpush
@stop

