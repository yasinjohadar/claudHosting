@extends('admin.layouts.master')
@section('page-title') مركز نسخ Coolify @stop
@push('styles')
@include('admin.coolify.partials.overview-styles')
@include('admin.coolify.backups.partials.hub-styles')
@endpush
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="backup-hub-hero mb-4">
            <div class="d-md-flex align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1 fw-bold">مركز نسخ واستعادة Coolify</h4>
                    <p class="text-muted mb-2 mb-md-0 small">
                        نسخ قواعد البيانات، لقطات المشاريع الكاملة، الجداول الدورية، والاستعادة — من مكان واحد
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($configured ?? false)
                            <span class="backup-hub-pill backup-hub-pill--ok">
                                <i class="fe fe-check-circle"></i> API مضبوط
                            </span>
                        @else
                            <span class="backup-hub-pill backup-hub-pill--warn">
                                <i class="fe fe-alert-circle"></i> API غير مضبوط
                            </span>
                        @endif
                        @if(($readiness['ready_with_db'] ?? false))
                            <span class="backup-hub-pill backup-hub-pill--ok">
                                <i class="fe fe-database"></i> تخزين اللقطات جاهز
                            </span>
                        @elseif(($readiness['ready'] ?? false))
                            <span class="backup-hub-pill">
                                <i class="fe fe-hard-drive"></i> S3 لوحة التحكم فقط
                            </span>
                        @else
                            <span class="backup-hub-pill backup-hub-pill--warn">
                                <i class="fe fe-settings"></i> أكمل إعدادات النسخ
                            </span>
                        @endif
                        @if(($hubStats['snapshots_running'] ?? 0) > 0)
                            <span class="backup-hub-pill">
                                <span class="coolify-pulse" style="width:7px;height:7px;border-radius:50%;background:#0ea5e9;display:inline-block;"></span>
                                {{ $hubStats['snapshots_running'] }} لقطة قيد التنفيذ
                            </span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-zap"></i> لقطة سريعة
                    </a>
                    <a href="{{ route('admin.coolify.settings.index', ['tab' => 'backups']) }}" class="btn btn-light btn-sm">
                        <i class="fe fe-settings"></i> إعدادات النسخ
                    </a>
                </div>
            </div>
        </div>

        @include('admin.coolify.partials.alerts')
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'hub'])

        @if(empty($configured ?? true))
            <div class="alert alert-warning border-0 shadow-sm">
                <strong><i class="fe fe-info me-1"></i> اتصال Coolify مطلوب</strong>
                <p class="small mb-2 mt-2">لنسخ قواعد البيانات عبر API تحتاج ضبط الاتصال أولاً. لقطات المشاريع تعتمد أيضاً على S3 وSSH.</p>
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-sm btn-warning">فتح إعدادات Coolify</a>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                @include('admin.coolify.backups.partials.hub-action-card', [
                    'accent' => 'info',
                    'icon' => 'fe fe-database',
                    'title' => 'قواعد البيانات',
                    'desc' => 'جدولة ونسخ عبر Coolify API مع سجل التنفيذات والاستعادة.',
                    'tags' => array_values(array_filter(['Coolify API', 'Cron', $configured ? null : 'يتطلب API'])),
                    'actions' => [
                        ['href' => route('admin.coolify.backups.index', ['tab' => 'databases']), 'label' => 'فتح القسم', 'class' => 'btn-primary', 'icon' => 'fe fe-arrow-left'],
                    ],
                ])
            </div>
            <div class="col-xl-3 col-md-6">
                @include('admin.coolify.backups.partials.hub-action-card', [
                    'accent' => 'primary',
                    'featured' => true,
                    'icon' => 'fe fe-layers',
                    'title' => 'لقطات المشاريع',
                    'desc' => 'نسخ مشروع كامل: DB على S3 (Coolify) + volumes على S3 (لوحة التحكم) — بدون تخزين دائم على السيرفر.',
                    'stat' => $hubStats['snapshots_total'] ?? 0,
                    'statLabel' => 'لقطة مسجّلة',
                    'tags' => ['S3', 'Volumes', 'معالج'],
                    'actions' => [
                        ['href' => route('admin.coolify.backups.projects.wizard'), 'label' => 'معالج جديد', 'class' => 'btn-primary', 'icon' => 'fe fe-plus-circle'],
                        ['href' => route('admin.coolify.backups.projects.index'), 'label' => 'لوحة المشاريع', 'class' => 'btn-outline-primary', 'icon' => 'fe fe-grid'],
                    ],
                ])
            </div>
            <div class="col-xl-3 col-md-6">
                @include('admin.coolify.backups.partials.hub-action-card', [
                    'accent' => 'success',
                    'icon' => 'fe fe-calendar',
                    'title' => 'جداول اللقطات',
                    'desc' => 'جدولة snapshots دورية تلقائية لكل مشروع مع تاريخ التشغيل القادم.',
                    'stat' => $hubStats['schedules_enabled'] ?? 0,
                    'statLabel' => 'جدول مفعّل من '.($hubStats['schedules_total'] ?? 0),
                    'tags' => ['تلقائي', 'Cron'],
                    'actions' => [
                        ['href' => route('admin.coolify.backups.schedules.index'), 'label' => 'إدارة الجداول', 'class' => 'btn-success', 'icon' => 'fe fe-list'],
                        ['href' => route('admin.coolify.backups.schedules.create'), 'label' => 'جدول جديد', 'class' => 'btn-outline-success', 'icon' => 'fe fe-plus'],
                    ],
                ])
            </div>
            <div class="col-xl-3 col-md-6">
                @include('admin.coolify.backups.partials.hub-action-card', [
                    'accent' => 'secondary',
                    'icon' => 'fe fe-activity',
                    'title' => 'سجل اللقطات',
                    'desc' => 'مراقبة الحالة، التقدّم، والاستعادة الانتقائية لكل مورد.',
                    'stat' => ($hubStats['snapshots_failed'] ?? 0) > 0 ? $hubStats['snapshots_failed'] : ($hubStats['snapshots_total'] ?? 0),
                    'statLabel' => ($hubStats['snapshots_failed'] ?? 0) > 0 ? 'تحتاج مراجعة' : 'إجمالي في السجل',
                    'tags' => ['استعادة', 'سجل'],
                    'actions' => [
                        ['href' => route('admin.coolify.backups.snapshots.index'), 'label' => 'فتح السجل', 'class' => 'btn-outline-secondary', 'icon' => 'fe fe-book-open'],
                    ],
                ])
            </div>
        </div>

        <div class="backup-hub-flow mb-4">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">مسار العمل المقترح</h6>
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <div class="backup-hub-flow-step">
                    <span class="backup-hub-flow-num">1</span>
                    <span>ضبط S3 وSSH في الإعدادات</span>
                </div>
                <i class="fe fe-chevron-left backup-hub-flow-arrow" aria-hidden="true"></i>
                <div class="backup-hub-flow-step">
                    <span class="backup-hub-flow-num">2</span>
                    <span>لقطة مشروع أو جدول دوري</span>
                </div>
                <i class="fe fe-chevron-left backup-hub-flow-arrow" aria-hidden="true"></i>
                <div class="backup-hub-flow-step">
                    <span class="backup-hub-flow-num">3</span>
                    <span>متابعة السجل والاستعادة</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('admin.coolify.backups.projects.wizard') }}" class="coolify-widget-link">
                    <div class="coolify-widget coolify-accent-primary">
                        <div class="coolify-widget-accent"></div>
                        <div class="coolify-widget-body coolify-panel-widget">
                            <div class="coolify-widget-top">
                                <div>
                                    <p class="coolify-widget-label">بدء سريع</p>
                                    <p class="coolify-widget-desc">معالج لقطة مشروع خطوة بخطوة</p>
                                </div>
                                <div class="coolify-widget-icon"><i class="fe fe-zap"></i></div>
                            </div>
                            <div class="coolify-widget-foot">
                                <span>ابدأ الآن</span>
                                <i class="fe fe-arrow-left"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('admin.coolify.backups.snapshots.index') }}" class="coolify-widget-link">
                    <div class="coolify-widget coolify-accent-warning">
                        <div class="coolify-widget-accent"></div>
                        <div class="coolify-widget-body coolify-panel-widget">
                            <div class="coolify-widget-top">
                                <div>
                                    <p class="coolify-widget-label">آخر النشاط</p>
                                    <p class="coolify-widget-desc">
                                        @if(($hubStats['snapshots_running'] ?? 0) > 0)
                                            {{ $hubStats['snapshots_running'] }} عملية قيد التنفيذ الآن
                                        @else
                                            لا توجد عمليات جارية
                                        @endif
                                    </p>
                                </div>
                                <div class="coolify-widget-icon"><i class="fe fe-clock"></i></div>
                            </div>
                            <div class="coolify-widget-foot">
                                <span>عرض السجل</span>
                                <i class="fe fe-arrow-left"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 col-md-12">
                <a href="{{ route('admin.coolify.settings.index', ['tab' => 'backups']) }}" class="coolify-widget-link">
                    <div class="coolify-widget coolify-accent-info">
                        <div class="coolify-widget-accent"></div>
                        <div class="coolify-widget-body coolify-panel-widget">
                            <div class="coolify-widget-top">
                                <div>
                                    <p class="coolify-widget-label">جاهزية التخزين</p>
                                    <p class="coolify-widget-desc">
                                        @if($readiness['ready_with_db'] ?? false)
                                            App Storage + Coolify S3
                                        @elseif($readiness['ready'] ?? false)
                                            App Storage فقط — أضف UUID S3 في Coolify
                                        @else
                                            أكمل إعدادات النسخ واللقطات
                                        @endif
                                    </p>
                                </div>
                                <div class="coolify-widget-icon"><i class="fe fe-hard-drive"></i></div>
                            </div>
                            <div class="coolify-widget-foot">
                                <span>الإعدادات</span>
                                <i class="fe fe-arrow-left"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
