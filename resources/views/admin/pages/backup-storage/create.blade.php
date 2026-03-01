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
                <a href="{{ route('admin.backup-storage.index') }}" class="btn btn-secondary btn-sm">
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
                        <form action="{{ route('admin.backup-storage.store') }}" method="POST" id="storage-form">
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
                                    @foreach($drivers as $key => $label)
                                        <option value="{{ $key }}" {{ old('driver') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('driver')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="config-fields">
                                <!-- سيتم ملؤها ديناميكياً حسب نوع التخزين -->
                            </div>

                            <!-- Test Connection Result -->
                            <div id="test-connection-result" class="mt-3" style="display: none;"></div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">الأولوية</label>
                                    <input type="number" class="form-control" id="priority" name="priority" value="{{ old('priority', 0) }}" min="0">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="max_backups" class="form-label">الحد الأقصى للنسخ</label>
                                    <input type="number" class="form-control" id="max_backups" name="max_backups" value="{{ old('max_backups') }}" min="1">
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

                            <div class="d-flex gap-2">
                                <button type="button" id="test-connection-btn" class="btn btn-info">
                                    <i class="fas fa-plug me-1"></i> اختبار الاتصال
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> حفظ
                                </button>
                                <a href="{{ route('admin.backup-storage.index') }}" class="btn btn-secondary">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var driverSelect = document.getElementById('driver');
    var configFields = document.getElementById('config-fields');

    console.log('Script loaded, driverSelect:', driverSelect, 'configFields:', configFields);

    if (!driverSelect || !configFields) {
        console.error('Elements not found: driver or config-fields');
        return;
    }

    var oldConfig = @json(old('config', []));

    const configTemplates = {
        'local': '<div class="mb-3"><label class="form-label">المسار (اختياري)</label><input type="text" class="form-control" name="config[path]" value="backups"></div>',
        's3': `
            <div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>
            <div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>
            <div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>
            <div class="mb-3"><label class="form-label">Region <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[region]" value="us-east-1" required placeholder="مثال: us-east-1, eu-west-1"></div>
            <div class="mb-3"><label class="form-label">Endpoint (لـ S3-compatible، اختياري)</label><input type="text" class="form-control" name="config[endpoint]" placeholder="https://s3.region.amazonaws.com"></div>
            <div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[use_path_style]" value="1" id="use_path_style"><label class="form-check-label" for="use_path_style">Use Path Style Endpoint</label></div></div>
        `,
        'digitalocean': `
            <div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>
            <div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>
            <div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>
            <div class="mb-3"><label class="form-label">Region</label><select class="form-select" name="config[region]"><option value="nyc3">NYC3</option><option value="nyc1">NYC1</option><option value="sfo3">SFO3</option><option value="sgp1">SGP1</option><option value="sfo2">SFO2</option><option value="ams3">AMS3</option></select></div>
        `,
        'wasabi': `
            <div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>
            <div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>
            <div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>
            <div class="mb-3"><label class="form-label">Region</label><select class="form-select" name="config[region]"><option value="us-east-1">US East 1</option><option value="us-west-1">US West 1</option><option value="eu-central-1">EU Central 1</option><option value="ap-northeast-1">AP Northeast 1</option></select></div>
        `,
        'backblaze': `
            <div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>
            <div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>
            <div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>
            <div class="mb-3"><label class="form-label">Region</label><input type="text" class="form-control" name="config[region]" value="us-west-000" placeholder="us-west-000"></div>
        `,
        'cloudflare_r2': `
            <div class="mb-3"><label class="form-label">Account ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[account_id]" required placeholder="Account ID من Cloudflare"></div>
            <div class="mb-3"><label class="form-label">Access Key ID <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[access_key_id]" required></div>
            <div class="mb-3"><label class="form-label">Secret Access Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[secret_access_key]" required></div>
            <div class="mb-3"><label class="form-label">Bucket <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[bucket]" required></div>
        `,
        'ftp': `
            <div class="mb-3"><label class="form-label">Protocol</label><select class="form-select" name="config[protocol]"><option value="ftp">FTP</option><option value="sftp">SFTP</option></select></div>
            <div class="mb-3"><label class="form-label">Host <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[host]" required></div>
            <div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[username]" required></div>
            <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[password]" required></div>
            <div class="mb-3"><label class="form-label">Port</label><input type="number" class="form-control" name="config[port]" value="21" id="ftp_port"></div>
            <div class="mb-3"><label class="form-label">Root Path</label><input type="text" class="form-control" name="config[root]" value="/backups"></div>
            <div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[use_tls]" value="1" id="use_tls"><label class="form-check-label" for="use_tls">Use TLS</label></div></div>
            <div class="mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="config[passive]" value="1" id="passive" checked><label class="form-check-label" for="passive">Passive Mode</label></div></div>
        `,
        'sftp': `
            <div class="mb-3"><label class="form-label">Host <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[host]" required></div>
            <div class="mb-3"><label class="form-label">Username <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[username]" required></div>
            <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[password]" required></div>
            <div class="mb-3"><label class="form-label">Port</label><input type="number" class="form-control" name="config[port]" value="22"></div>
            <div class="mb-3"><label class="form-label">Root Path</label><input type="text" class="form-control" name="config[root]" value="/backups"></div>
        `,
        'azure': `
            <div class="mb-3"><label class="form-label">Account Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[account_name]" required></div>
            <div class="mb-3"><label class="form-label">Account Key <span class="text-danger">*</span></label><input type="password" class="form-control" name="config[account_key]" required></div>
            <div class="mb-3"><label class="form-label">Container <span class="text-danger">*</span></label><input type="text" class="form-control" name="config[container]" required></div>
        `,
    };

    // وظيفة لإعادة ملء الحقول من القيم القديمة
    function fillOldValues() {
        const inputs = configFields.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const name = input.name;
            if (name && name.startsWith('config[')) {
                const key = name.replace('config[', '').replace(']', '');
                if (oldConfig[key] !== undefined && oldConfig[key] !== null && oldConfig[key] !== '') {
                    if (input.type === 'checkbox') {
                        input.checked = oldConfig[key] == 1 || oldConfig[key] === true;
                    } else if (input.type !== 'password') {
                        input.value = oldConfig[key];
                    }
                }
            }
        });
    }

    function updateConfigFields() {
        const driver = driverSelect.value;
        
        if (driver && configTemplates[driver]) {
            configFields.innerHTML = configTemplates[driver];
            // إعادة ملء القيم القديمة بعد إنشاء الحقول
            setTimeout(fillOldValues, 50);
        } else if (!driver) {
            configFields.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>يرجى اختيار نوع التخزين لعرض الحقول المطلوبة</div>';
        } else {
            configFields.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>نوع التخزين المحدد غير مدعوم</div>';
        }
    }

    // إضافة event listener
    driverSelect.addEventListener('change', updateConfigFields);

    // تشغيل عند التحميل إذا كان هناك قيمة قديمة
    if (driverSelect.value) {
        updateConfigFields();
    } else {
        // عرض رسالة توضيحية عند التحميل الأول
        configFields.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>يرجى اختيار نوع التخزين لعرض الحقول المطلوبة</div>';
    }

    // اختبار الاتصال
    const testBtn = document.getElementById('test-connection-btn');
    const testResultDiv = document.getElementById('test-connection-result');
    
    testBtn.addEventListener('click', function() {
        const driver = driverSelect.value;
        if (!driver) {
            testResultDiv.innerHTML = `
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    يرجى اختيار نوع التخزين أولاً
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            testResultDiv.style.display = 'block';
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
                } else {
                    configData[key] = input.value || '';
                }
            }
        });

        // تعطيل الزر أثناء الاختبار
        testBtn.disabled = true;
        const originalText = testBtn.innerHTML;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاختبار...';
        testResultDiv.style.display = 'none';

        // إرسال طلب AJAX
        fetch('{{ route("admin.backup-storage.test-connection") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                driver: driver,
                config: configData
            })
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 100));
                });
            }
            return response.json();
        })
        .then(function(data) {
            testBtn.disabled = false;
            testBtn.innerHTML = originalText;
            
            if (data.success) {
                testResultDiv.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>✓ نجح الاختبار!</strong><br>
                        ${data.message || 'الاتصال ناجح'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            } else {
                var errorMessage = data.message || 'فشل الاختبار';
                // تقسيم الرسالة إلى أسطر إذا كانت طويلة
                if (errorMessage.includes('\n')) {
                    errorMessage = errorMessage.split('\n').join('<br>');
                }
                // تنظيف الرسالة من أحرف خاصة قد تسبب مشاكل
                errorMessage = errorMessage.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                testResultDiv.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-times-circle me-2"></i>
                        <strong>✗ فشل الاختبار!</strong><br>
                        ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
            }
            testResultDiv.style.display = 'block';
        })
        .catch(function(error) {
            testBtn.disabled = false;
            testBtn.innerHTML = originalText;
            var errorMsg = error.message || 'حدث خطأ غير متوقع';
            // تنظيف رسالة الخطأ
            errorMsg = errorMsg.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            testResultDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>خطأ!</strong><br>
                    حدث خطأ أثناء الاختبار: ${errorMsg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            testResultDiv.style.display = 'block';
        });
    });

    // تشغيل عند التحميل
    console.log('Running updateConfigFields, current driver value:', driverSelect.value);
    updateConfigFields();
});
</script>
@stop

