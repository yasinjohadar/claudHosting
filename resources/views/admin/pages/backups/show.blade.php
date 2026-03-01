@extends('admin.layouts.master')

@section('page-title')
    تفاصيل النسخة الاحتياطية
@stop

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">تفاصيل النسخة: {{ $backup->name }}</h5>
            </div>
            <div>
                <a href="{{ route('admin.backups.index') }}" class="btn btn-secondary btn-sm">
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

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">معلومات النسخة</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>الاسم:</strong> {{ $backup->name }}</p>
                        <p><strong>النوع:</strong> {{ \App\Models\Backup::BACKUP_TYPES[$backup->backup_type] }}</p>
                        <p><strong>مكان التخزين:</strong> 
                            @if($backup->storageConfig)
                                {{ $backup->storageConfig->name }} ({{ \App\Models\AppStorageConfig::DRIVERS[$backup->storage_driver] ?? $backup->storage_driver }})
                            @else
                                {{ \App\Models\AppStorageConfig::DRIVERS[$backup->storage_driver] ?? $backup->storage_driver }}
                            @endif
                        </p>
                        <p><strong>الحالة:</strong> 
                            <span id="backup-status-badge" class="badge">
                                @if($backup->status === 'completed')
                                    <span class="bg-success">مكتمل</span>
                                @elseif($backup->status === 'failed')
                                    <span class="bg-danger">فشل</span>
                                @elseif($backup->status === 'running')
                                    <span class="bg-warning">
                                        <i class="fas fa-spinner fa-spin me-1"></i>قيد التنفيذ
                                    </span>
                                @else
                                    <span class="bg-secondary">معلق</span>
                                @endif
                            </span>
                            @if(in_array($backup->status, ['pending', 'running']))
                                <button type="button" id="refresh-status-btn" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="fas fa-sync-alt" id="refresh-icon"></i> تحديث
                                </button>
                            @endif
                            @if(in_array($backup->status, ['pending', 'failed']))
                                <form action="{{ route('admin.backups.run', $backup->id) }}" method="POST" class="d-inline ms-2" id="run-backup-form">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" id="run-backup-btn">
                                        <i class="fas fa-play me-1"></i> تشغيل الآن
                                    </button>
                                </form>
                            @endif
                        </p>
                        @if(in_array($backup->status, ['pending', 'running']))
                            <div id="progress-message" class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                    <div>
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span id="progress-text">جاري معالجة النسخة الاحتياطية...</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <p><strong>الحجم:</strong> <span id="backup-size">{{ $backup->getFileSize() }}</span></p>
                        <p><strong>تاريخ الإنشاء:</strong> {{ $backup->created_at->format('Y-m-d H:i:s') }}</p>
                        <p id="completed-at-section" style="display: {{ $backup->completed_at ? 'block' : 'none' }};">
                            <strong>تاريخ الاكتمال:</strong> <span id="backup-completed-at">{{ $backup->completed_at?->format('Y-m-d H:i:s') }}</span>
                        </p>
                        @if($backup->duration)
                            <p><strong>المدة:</strong> {{ $backup->duration }} ثانية</p>
                        @endif
                        <div id="error-message-section" style="display: {{ $backup->error_message ? 'block' : 'none' }};">
                            <div class="alert alert-danger">
                                <strong>خطأ:</strong> <span id="backup-error-message">{{ $backup->error_message }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="actions-section" style="display: {{ $backup->status === 'completed' ? 'block' : 'none' }};">
                    <div class="card shadow-sm border-0">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">الإجراءات</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.backups.download', $backup->id) }}" class="btn btn-primary">
                                    <i class="fas fa-download me-1"></i> تحميل
                                </a>
                                <form id="restore-form" action="{{ route('admin.backups.restore', $backup->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="confirm" value="1">
                                    <button type="submit" class="btn btn-warning" id="restore-btn">
                                        <i class="fas fa-undo me-1"></i> استعادة
                                    </button>
                                </form>
                            </div>
                            <div id="restore-progress" style="display: none;" class="mt-3">
                                <div class="progress" style="height: 25px;">
                                    <div id="restore-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                                         role="progressbar" style="width: 0%">
                                        <span id="restore-progress-text">0%</span>
                                    </div>
                                </div>
                                <div id="restore-status" class="mt-2 text-muted small"></div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    // فلترة logs: النسخ الاحتياطي vs الاستعادة
                    $backupLogs = $backup->logs->filter(function($log) {
                        $message = strtolower($log->message);
                        return !str_contains($message, 'استعادة') && 
                               !str_contains($message, 'restore') && 
                               !str_contains($message, 'استرجاع');
                    });
                    
                    $restoreLogs = $backup->logs->filter(function($log) {
                        $message = strtolower($log->message);
                        return str_contains($message, 'استعادة') || 
                               str_contains($message, 'restore') || 
                               str_contains($message, 'استرجاع');
                    });
                @endphp

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-list-alt me-2"></i>سجل العمليات
                            @if($backupLogs->count() > 0)
                                <span class="badge bg-secondary ms-2">{{ $backupLogs->count() }}</span>
                            @endif
                        </h6>
                        @if(in_array($backup->status, ['pending', 'running']))
                            <button type="button" id="refresh-logs-btn" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> تحديث السجل
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="logs-container" class="table-responsive">
                            @if($backupLogs->count() > 0)
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>الوقت</th>
                                            <th>المستوى</th>
                                            <th>الرسالة</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logs-tbody">
                                        @foreach($backupLogs as $log)
                                            <tr>
                                                <td>{{ $log->created_at->format('H:i:s') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info') }}">
                                                        {{ \App\Models\BackupLog::LEVELS[$log->level] ?? $log->level }}
                                                    </span>
                                                </td>
                                                <td>{{ $log->message }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted text-center mb-0">لا توجد سجلات بعد</p>
                            @endif
                        </div>
                    </div>
                </div>

                @if($restoreLogs->count() > 0)
                <div class="card shadow-sm border-0 mt-3" style="border-left: 4px solid #ffc107 !important;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #fff9e6;">
                        <h6 class="mb-0">
                            <i class="fas fa-undo me-2 text-warning"></i>تقرير الاستعادة
                            <span class="badge bg-warning ms-2">{{ $restoreLogs->count() }}</span>
                        </h6>
                        @if(in_array($backup->status, ['pending', 'running']))
                            <button type="button" id="refresh-restore-logs-btn" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-sync-alt"></i> تحديث التقرير
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="restore-logs-container" class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الوقت</th>
                                        <th>المستوى</th>
                                        <th>الرسالة</th>
                                    </tr>
                                </thead>
                                <tbody id="restore-logs-tbody">
                                    @foreach($restoreLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('H:i:s') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info') }}">
                                                    {{ \App\Models\BackupLog::LEVELS[$log->level] ?? $log->level }}
                                                </span>
                                            </td>
                                            <td>{{ $log->message }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const backupId = {{ $backup->id }};
    const statusUrl = '{{ route("admin.backups.status", $backup->id) }}';
    let pollingInterval = null;
    let isPolling = false;

    // التحقق من الحالة تلقائياً إذا كانت النسخة قيد المعالجة
    @if(in_array($backup->status, ['pending', 'running']))
        startPolling();
    @endif

    // زر التحديث اليدوي
    const refreshBtn = document.getElementById('refresh-status-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const icon = document.getElementById('refresh-icon');
            if (icon) {
                icon.classList.add('fa-spin');
            }
            checkStatus(true).finally(() => {
                if (icon) {
                    setTimeout(() => {
                        icon.classList.remove('fa-spin');
                    }, 500);
                }
            });
        });
    }

    // زر تشغيل النسخة يدوياً
    const runBackupForm = document.getElementById('run-backup-form');
    if (runBackupForm) {
        runBackupForm.addEventListener('submit', function(e) {
            const btn = document.getElementById('run-backup-btn');
            if (btn) {
                const originalText = btn.innerHTML;
                
                // تعطيل الزر وإظهار حالة التحميل
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري التشغيل...';
                
                // السماح للنموذج بالإرسال العادي (Laravel سيتعامل معه)
                // سيتم إعادة تحميل الصفحة تلقائياً بعد الإرسال
            }
        });
    }

    // زر تحديث السجل
    const refreshLogsBtn = document.getElementById('refresh-logs-btn');
    if (refreshLogsBtn) {
        refreshLogsBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    // زر تحديث تقرير الاستعادة
    const refreshRestoreLogsBtn = document.getElementById('refresh-restore-logs-btn');
    if (refreshRestoreLogsBtn) {
        refreshRestoreLogsBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        
        // التحقق كل 3 ثواني
        pollingInterval = setInterval(function() {
            checkStatus(false);
        }, 3000);
        
        // التحقق فوراً
        checkStatus(false);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        isPolling = false;
    }

    function checkStatus(manual = false) {
        return fetch(statusUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            updateStatus(data);
            
            // إذا اكتملت أو فشلت، توقف عن الـ polling
            if (data.status === 'completed' || data.status === 'failed') {
                stopPolling();
                if (data.status === 'completed') {
                    // إظهار رسالة نجاح قبل إعادة التحميل
                    const progressText = document.getElementById('progress-text');
                    if (progressText) {
                        progressText.textContent = '✓ اكتملت عملية النسخ الاحتياطي بنجاح!';
                    }
                    // إعادة تحميل الصفحة بعد اكتمال النسخة لعرض جميع البيانات
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            }
            return data;
        })
        .catch(error => {
            console.error('Error checking backup status:', error);
            if (manual) {
                alert('حدث خطأ أثناء التحقق من حالة النسخة: ' + error.message);
            }
            throw error;
        });
    }

    function updateStatus(data) {
        // تحديث الحالة
        const statusBadge = document.getElementById('backup-status-badge');
        if (statusBadge) {
            const statusLabels = {
                'pending': '<span class="badge bg-secondary">معلق</span>',
                'running': '<span class="badge bg-warning"><i class="fas fa-spinner fa-spin me-1"></i>قيد التنفيذ</span>',
                'completed': '<span class="badge bg-success">مكتمل</span>',
                'failed': '<span class="badge bg-danger">فشل</span>'
            };
            statusBadge.innerHTML = statusLabels[data.status] || statusLabels['pending'];
        }

        // تحديث الحجم
        if (data.file_size_formatted) {
            const sizeElement = document.getElementById('backup-size');
            if (sizeElement) {
                sizeElement.textContent = data.file_size_formatted;
            }
        }

        // تحديث تاريخ الاكتمال
        if (data.completed_at) {
            const completedAtSection = document.getElementById('completed-at-section');
            const completedAtElement = document.getElementById('backup-completed-at');
            if (completedAtSection) {
                completedAtSection.style.display = 'block';
            }
            if (completedAtElement) {
                completedAtElement.textContent = data.completed_at;
            }
        }

        // تحديث رسالة الخطأ
        if (data.error_message) {
            const errorSection = document.getElementById('error-message-section');
            const errorElement = document.getElementById('backup-error-message');
            if (errorSection) {
                errorSection.style.display = 'block';
            }
            if (errorElement) {
                errorElement.textContent = data.error_message;
            }
        }

        // تحديث رسالة التقدم
        const progressText = document.getElementById('progress-text');
        if (progressText) {
            if (data.status === 'running') {
                const messages = {
                    'database': 'جاري نسخ قاعدة البيانات...',
                    'files': 'جاري نسخ الملفات...',
                    'config': 'جاري نسخ الإعدادات...',
                    'full': 'جاري إنشاء النسخة الكاملة...'
                };
                progressText.textContent = data.latest_log || messages['{{ $backup->backup_type }}'] || 'جاري معالجة النسخة الاحتياطية...';
            } else if (data.status === 'completed') {
                progressText.textContent = 'اكتملت عملية النسخ الاحتياطي بنجاح!';
            } else if (data.status === 'failed') {
                progressText.textContent = 'فشلت عملية النسخ الاحتياطي';
            }
        }

        // إظهار/إخفاء قسم الإجراءات
        const actionsSection = document.getElementById('actions-section');
        if (actionsSection) {
            actionsSection.style.display = data.status === 'completed' ? 'block' : 'none';
        }

        // إخفاء رسالة التقدم عند اكتمال أو فشل
        const progressMessage = document.getElementById('progress-message');
        if (progressMessage && (data.status === 'completed' || data.status === 'failed')) {
            progressMessage.style.display = 'none';
        }

        // تحديث logs إذا كانت موجودة
        if (data.logs && Array.isArray(data.logs)) {
            updateLogs(data.logs);
            updateRestoreLogs(data.logs);
        }
    }

    function updateLogs(logs) {
        const logsTbody = document.getElementById('logs-tbody');
        const logsContainer = document.getElementById('logs-container');
        
        if (!logsTbody || !logsContainer) return;

        if (logs.length === 0) {
            logsContainer.innerHTML = '<p class="text-muted text-center mb-0">لا توجد سجلات بعد</p>';
            return;
        }

        // إنشاء HTML للجدول إذا لم يكن موجوداً
        if (!logsTbody.closest('table')) {
            logsContainer.innerHTML = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>المستوى</th>
                            <th>الرسالة</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody"></tbody>
                </table>
            `;
            const newTbody = document.getElementById('logs-tbody');
            if (!newTbody) return;
        }

        const tbody = document.getElementById('logs-tbody');
        if (!tbody) return;

        // فلترة logs: عرض logs النسخ الاحتياطي فقط (استبعاد logs الاستعادة)
        const backupLogs = logs.filter(log => {
            const message = (log.message || '').toLowerCase();
            return !message.includes('استعادة') && 
                   !message.includes('restore') && 
                   !message.includes('استرجاع');
        });

        // حفظ معرفات الـ logs الموجودة
        const existingLogIds = new Set();
        Array.from(tbody.querySelectorAll('tr')).forEach(row => {
            const logId = row.getAttribute('data-log-id');
            if (logId) {
                existingLogIds.add(parseInt(logId));
            }
        });

        // إضافة logs جديدة فقط (logs النسخ الاحتياطي)
        backupLogs.forEach(log => {
            if (!existingLogIds.has(log.id)) {
                const levelColors = {
                    'error': 'danger',
                    'warning': 'warning',
                    'info': 'info'
                };
                const levelLabels = {
                    'error': 'خطأ',
                    'warning': 'تحذير',
                    'info': 'معلومات'
                };
                const row = document.createElement('tr');
                row.setAttribute('data-log-id', log.id);
                row.innerHTML = `
                    <td>${log.created_at}</td>
                    <td>
                        <span class="badge bg-${levelColors[log.level] || 'info'}">
                            ${levelLabels[log.level] || log.level}
                        </span>
                    </td>
                    <td>${escapeHtml(log.message)}</td>
                `;
                tbody.appendChild(row);
            }
        });

        // التمرير للأسفل لعرض آخر log
        if (backupLogs.length > 0) {
            tbody.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function updateRestoreLogs(logs) {
        // فلترة logs: عرض logs الاستعادة فقط
        const restoreLogs = logs.filter(log => {
            const message = (log.message || '').toLowerCase();
            return message.includes('استعادة') || 
                   message.includes('restore') || 
                   message.includes('استرجاع');
        });

        // إذا لم تكن هناك logs استعادة، لا نفعل شيء (القسم مخفي بالفعل في Blade)
        if (restoreLogs.length === 0) return;

        // التأكد من وجود قسم الاستعادة، إنشاءه إذا لم يكن موجوداً
        let restoreSection = document.querySelector('.card[style*="border-left"]');
        if (!restoreSection) {
            // إنشاء قسم الاستعادة ديناميكياً
            const actionsSection = document.getElementById('actions-section');
            if (actionsSection && actionsSection.parentNode) {
                restoreSection = document.createElement('div');
                restoreSection.className = 'card shadow-sm border-0 mt-3';
                restoreSection.style.borderLeft = '4px solid #ffc107';
                restoreSection.innerHTML = `
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #fff9e6;">
                        <h6 class="mb-0">
                            <i class="fas fa-undo me-2 text-warning"></i>تقرير الاستعادة
                            <span class="badge bg-warning ms-2" id="restore-logs-count">${restoreLogs.length}</span>
                        </h6>
                        @if(in_array($backup->status, ['pending', 'running']))
                            <button type="button" id="refresh-restore-logs-btn" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-sync-alt"></i> تحديث التقرير
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="restore-logs-container" class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الوقت</th>
                                        <th>المستوى</th>
                                        <th>الرسالة</th>
                                    </tr>
                                </thead>
                                <tbody id="restore-logs-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                `;
                actionsSection.parentNode.insertBefore(restoreSection, actionsSection.nextSibling);
            } else {
                return; // لا يمكن إنشاء القسم
            }
        }

        const restoreTbody = document.getElementById('restore-logs-tbody');
        const restoreContainer = document.getElementById('restore-logs-container');
        const restoreLogsCount = document.getElementById('restore-logs-count');
        
        if (!restoreTbody || !restoreContainer) return;

        // تحديث العدد
        if (restoreLogsCount) {
            restoreLogsCount.textContent = restoreLogs.length;
        }

        // حفظ معرفات الـ logs الموجودة
        const existingLogIds = new Set();
        Array.from(restoreTbody.querySelectorAll('tr')).forEach(row => {
            const logId = row.getAttribute('data-log-id');
            if (logId) {
                existingLogIds.add(parseInt(logId));
            }
        });

        // إضافة logs جديدة فقط (logs الاستعادة)
        restoreLogs.forEach(log => {
            if (!existingLogIds.has(log.id)) {
                const levelColors = {
                    'error': 'danger',
                    'warning': 'warning',
                    'info': 'info'
                };
                const levelLabels = {
                    'error': 'خطأ',
                    'warning': 'تحذير',
                    'info': 'معلومات'
                };
                const row = document.createElement('tr');
                row.setAttribute('data-log-id', log.id);
                row.innerHTML = `
                    <td>${log.created_at}</td>
                    <td>
                        <span class="badge bg-${levelColors[log.level] || 'info'}">
                            ${levelLabels[log.level] || log.level}
                        </span>
                    </td>
                    <td>${escapeHtml(log.message)}</td>
                `;
                restoreTbody.appendChild(row);
            }
        });

        // التمرير للأسفل لعرض آخر log
        if (restoreLogs.length > 0 && restoreTbody.lastElementChild) {
            restoreTbody.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // تحويل restore form إلى AJAX
    const restoreForm = document.getElementById('restore-form');
    let restorePollingInterval = null;

    if (restoreForm) {
        restoreForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('هل أنت متأكد من استعادة هذه النسخة؟ سيتم استبدال البيانات الحالية.')) {
                return;
            }
            
            const restoreBtn = document.getElementById('restore-btn');
            const progressDiv = document.getElementById('restore-progress');
            const progressBar = document.getElementById('restore-progress-bar');
            const progressText = document.getElementById('restore-progress-text');
            const statusDiv = document.getElementById('restore-status');
            
            // تعطيل الزر وإظهار progress bar
            if (restoreBtn) {
                restoreBtn.disabled = true;
                restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاستعادة...';
            }
            
            if (progressDiv) {
                progressDiv.style.display = 'block';
            }
            
            // إرسال AJAX request
            fetch(restoreForm.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    confirm: true
                })
            })
            .then(response => {
                // التحقق من حالة HTTP response
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (statusDiv) {
                        statusDiv.textContent = data.message || 'تم بدء عملية الاستعادة...';
                    }
                    // بدء polling للحالة
                    startRestorePolling(backupId);
                } else {
                    throw new Error(data.message || 'فشل بدء عملية الاستعادة');
                }
            })
            .catch(error => {
                console.error('Error restoring backup:', error);
                const errorMessage = error.message || 'حدث خطأ غير معروف أثناء بدء عملية الاستعادة';
                alert('حدث خطأ أثناء بدء عملية الاستعادة:\n' + errorMessage);
                if (restoreBtn) {
                    restoreBtn.disabled = false;
                    restoreBtn.innerHTML = '<i class="fas fa-undo me-1"></i> استعادة';
                }
                if (progressDiv) {
                    progressDiv.style.display = 'none';
                }
                if (statusDiv) {
                    statusDiv.textContent = 'فشلت عملية الاستعادة: ' + errorMessage;
                    statusDiv.classList.remove('text-muted');
                    statusDiv.classList.add('text-danger');
                }
            });
        });
    }

    function startRestorePolling(backupId) {
        // إيقاف أي polling سابق
        if (restorePollingInterval) {
            clearInterval(restorePollingInterval);
        }

        const progressBar = document.getElementById('restore-progress-bar');
        const progressText = document.getElementById('restore-progress-text');
        const statusDiv = document.getElementById('restore-status');
        const restoreBtn = document.getElementById('restore-btn');

        restorePollingInterval = setInterval(() => {
            fetch(`{{ route('admin.backups.status', $backup->id) }}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                // تحديث progress bar بناءً على logs
                updateRestoreProgress(data.logs || [], data.status);
                
                // إذا اكتملت أو فشلت، توقف عن polling
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(restorePollingInterval);
                    restorePollingInterval = null;
                    
                    if (data.status === 'completed') {
                        if (progressBar) {
                            progressBar.style.width = '100%';
                            progressBar.classList.remove('bg-warning');
                            progressBar.classList.add('bg-success');
                        }
                        if (progressText) {
                            progressText.textContent = '100%';
                        }
                        if (statusDiv) {
                            statusDiv.textContent = 'اكتملت عملية الاستعادة بنجاح';
                            statusDiv.classList.remove('text-muted');
                            statusDiv.classList.add('text-success');
                        }
                        if (restoreBtn) {
                            restoreBtn.disabled = false;
                            restoreBtn.innerHTML = '<i class="fas fa-undo me-1"></i> استعادة';
                        }
                        // إعادة تحميل الصفحة بعد ثانيتين
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else if (data.status === 'failed') {
                        if (progressBar) {
                            progressBar.classList.remove('bg-warning');
                            progressBar.classList.add('bg-danger');
                        }
                        if (statusDiv) {
                            statusDiv.classList.remove('text-muted');
                            statusDiv.classList.add('text-danger');
                        }
                        if (restoreBtn) {
                            restoreBtn.disabled = false;
                            restoreBtn.innerHTML = '<i class="fas fa-undo me-1"></i> استعادة';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error polling restore status:', error);
            });
        }, 2000); // كل ثانيتين
    }

    function updateRestoreProgress(logs, status) {
        const progressBar = document.getElementById('restore-progress-bar');
        const progressText = document.getElementById('restore-progress-text');
        const statusDiv = document.getElementById('restore-status');

        // تحديث logs: النسخ الاحتياطي والاستعادة بشكل منفصل
        if (logs && Array.isArray(logs)) {
            updateLogs(logs);
            updateRestoreLogs(logs);
        }

        // حساب التقدم بناءً على عدد الخطوات المكتملة
        const steps = [
            'بدء عملية الاستعادة',
            'إنشاء نسخة احتياطية',
            'تحميل النسخة',
            'فك الضغط',
            'استعادة قاعدة البيانات',
            'اكتملت عملية الاستعادة'
        ];

        let completedSteps = 0;
        const lastLogMessage = logs && logs.length > 0 ? logs[logs.length - 1].message : '';

        // حساب الخطوات المكتملة بناءً على محتوى logs
        if (lastLogMessage.includes('بدء عملية الاستعادة')) completedSteps = 1;
        if (lastLogMessage.includes('نسخة احتياطية') && lastLogMessage.includes('نجاح')) completedSteps = 2;
        if (lastLogMessage.includes('تحميل') || lastLogMessage.includes('تم الحصول') || lastLogMessage.includes('تم تحميل')) completedSteps = 2.5;
        if (lastLogMessage.includes('حفظ') && lastLogMessage.includes('مؤقت')) completedSteps = 3;
        if (lastLogMessage.includes('ضغط') || lastLogMessage.includes('استخراج') || lastLogMessage.includes('فك ضغط')) completedSteps = 4;
        if (lastLogMessage.includes('SQL') || lastLogMessage.includes('ملف')) completedSteps = 4.5;
        if (lastLogMessage.includes('استعادة') && (lastLogMessage.includes('قاعدة البيانات') || lastLogMessage.includes('Database'))) completedSteps = 5;
        if (lastLogMessage.includes('اكتملت') || status === 'completed') completedSteps = 6;

        const progress = Math.min((completedSteps / steps.length) * 100, 100);
        
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        if (progressText) {
            progressText.textContent = Math.round(progress) + '%';
        }
        
        // إظهار آخر رسالة
        if (statusDiv && lastLogMessage) {
            statusDiv.textContent = lastLogMessage;
        }
    }
});
</script>
@endpush
@stop

