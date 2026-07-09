@extends('admin.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/admin-email-settings.css') }}?v={{ @filemtime(public_path('assets/css/admin-email-settings.css')) ?: '1' }}">
@endpush

@section('page-title')
    إعدادات البريد الإلكتروني (SMTP)
@stop

@section('content')
    <div class="main-content app-content admin-email-settings-page">
        <div class="container-fluid">

            @include('admin.components.alerts')

            <div class="my-4 page-header-breadcrumb">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
                        <li class="breadcrumb-item active">إعدادات البريد الإلكتروني</li>
                    </ol>
                </nav>
            </div>

            <div class="group-show-hero dashboard-fade-in mb-4">
                <div class="row align-items-start g-3">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-start gap-3">
                            <span class="admin-group-form-page__icon">
                                <i class="fe fe-mail"></i>
                            </span>
                            <div class="min-w-0">
                                <span class="group-show-hero__eyebrow">
                                    <i class="fe fe-settings me-1"></i>إعدادات النظام
                                </span>
                                <h2 class="group-show-hero__title mb-2">إعدادات البريد الإلكتروني (SMTP)</h2>
                                <p class="group-show-hero__desc mb-0">إدارة خوادم الإرسال، اختبار الاتصال، وإرسال بريد تجريبي عند الحاجة.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="group-show-actions">
                            <a href="{{ route('admin.settings.email.create') }}" class="group-show-action group-show-action--primary">
                                <span class="group-show-action__icon"><i class="fe fe-plus"></i></span>
                                <span class="group-show-action__text">إضافة إعدادات جديدة</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @if(!$activeSettings)
                <div class="alert alert-warning border-0 dashboard-fade-in mb-4" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i>
                    لا توجد إعدادات بريد نشطة. أضف إعدادات وفعّلها لتمكين إرسال البريد من النظام.
                </div>
            @else
                @php
                    $lastTestLabel = $activeSettings->last_tested_at
                        ? $activeSettings->last_tested_at->diffForHumans()
                        : 'لم يُختبر';
                    $lastTestType = $activeSettings->test_results['type'] ?? null;
                    $lastTestStatus = $activeSettings->test_results['status'] ?? null;
                    $kpiCards = [
                        ['variant' => 'green', 'icon' => 'fe-server', 'label' => 'المزود النشط', 'value' => $providers[$activeSettings->provider]['name'] ?? 'مخصص', 'sub' => strtoupper($activeSettings->mail_encryption).' · '.$activeSettings->mail_host, 'countup' => false],
                        ['variant' => 'blue', 'icon' => 'fe-at-sign', 'label' => 'البريد المرسل', 'value' => $activeSettings->mail_from_address, 'sub' => $activeSettings->mail_from_name, 'countup' => false],
                        ['variant' => 'cyan', 'icon' => 'fe-shield', 'label' => 'المنفذ والتشفير', 'value' => (string) $activeSettings->mail_port, 'sub' => strtoupper($activeSettings->mail_encryption), 'countup' => false],
                        ['variant' => 'orange', 'icon' => 'fe-activity', 'label' => 'آخر اختبار', 'value' => $lastTestLabel, 'sub' => $lastTestType === 'connection' ? 'اختبار اتصال' : ($lastTestType === 'send' ? 'إرسال بريد' : '—'), 'countup' => false],
                    ];
                @endphp

                <div class="row g-3 dashboard-fade-in mb-4">
                    @foreach ($kpiCards as $index => $card)
                        <div class="col-xl-3 col-lg-6 col-md-6 dashboard-stagger-item" style="--stagger-delay: {{ $index * 70 }}ms">
                            <div class="card admin-stats-card admin-stats-card--{{ $card['variant'] }}">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="admin-stats-card__icon-wrap">
                                        <i class="fe {{ $card['icon'] }} admin-stats-card__icon"></i>
                                    </div>
                                    <div class="admin-stats-card__content flex-fill min-w-0">
                                        <p class="admin-stats-card__label mb-1">{{ $card['label'] }}</p>
                                        <h3 class="admin-stats-card__value admin-stats-card__value--text mb-1 text-truncate" title="{{ $card['value'] }}">{{ $card['value'] }}</h3>
                                        <p class="admin-stats-card__sub mb-0 text-truncate">{{ $card['sub'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4 dashboard-fade-in">
                    <button type="button" class="btn btn-outline-primary rounded-pill"
                            onclick="testSavedConnection({{ $activeSettings->id }}, this)">
                        <i class="fe fe-wifi me-1"></i>اختبار اتصال الإعداد النشط
                    </button>
                    <button type="button" class="btn btn-outline-info rounded-pill"
                            onclick="openSendTestModal({{ $activeSettings->id }}, @js($activeSettings->mail_from_address))">
                        <i class="fe fe-send me-1"></i>إرسال بريد اختبار
                    </button>
                    <a href="{{ route('admin.settings.email.edit', $activeSettings->id) }}" class="btn btn-light rounded-pill">
                        <i class="fe fe-edit-2 me-1"></i>تعديل الإعداد النشط
                    </a>
                </div>
            @endif

            <div class="card custom-card group-show-members-card dashboard-fade-in">
                <div class="card-header border-0 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="group-show-members-card__title mb-0">
                        جميع الإعدادات المحفوظة
                        <span class="group-show-members-card__count">{{ $settings->count() }}</span>
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @if($settings->isEmpty())
                        <div class="group-show-empty py-5">
                            <i class="fe fe-inbox group-show-empty__icon" style="width:56px;height:56px;font-size:1.35rem;"></i>
                            <p class="group-show-empty__title">لا توجد إعدادات بريد</p>
                            <p class="group-show-empty__desc mb-3">أضف إعدادات SMTP للبدء في إرسال البريد من النظام.</p>
                            <a href="{{ route('admin.settings.email.create') }}" class="btn btn-primary btn-sm rounded-pill">
                                <i class="fe fe-plus me-1"></i>إضافة إعدادات
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap dashboard-table admin-users-table mb-0">
                                <thead>
                                    <tr>
                                        <th>المزود</th>
                                        <th>SMTP Host</th>
                                        <th>Port</th>
                                        <th>البريد</th>
                                        <th>التشفير</th>
                                        <th>الحالة</th>
                                        <th>آخر اختبار</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($settings as $setting)
                                        @php
                                            $testType = $setting->test_results['type'] ?? null;
                                            $testStatus = $setting->test_results['status'] ?? null;
                                        @endphp
                                        <tr class="admin-users-table__row {{ $setting->is_active ? 'admin-email-settings-page__row--active' : '' }}"
                                            id="email-setting-row-{{ $setting->id }}">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($setting->provider === 'gmail')
                                                        <i class="fe fe-mail text-danger"></i>
                                                    @elseif($setting->provider === 'outlook')
                                                        <i class="fe fe-mail text-info"></i>
                                                    @else
                                                        <i class="fe fe-server text-primary"></i>
                                                    @endif
                                                    <span class="fw-semibold">{{ $providers[$setting->provider]['name'] ?? 'مخصص' }}</span>
                                                </div>
                                            </td>
                                            <td><code class="fs-12">{{ $setting->mail_host }}</code></td>
                                            <td><span class="group-show-chip group-show-chip--sm">{{ $setting->mail_port }}</span></td>
                                            <td>{{ $setting->mail_from_address }}</td>
                                            <td><span class="group-show-chip group-show-chip--sm text-info">{{ strtoupper($setting->mail_encryption) }}</span></td>
                                            <td>
                                                @if($setting->is_active)
                                                    <span class="group-show-chip group-show-chip--sm text-success">نشط</span>
                                                @else
                                                    <span class="group-show-chip group-show-chip--sm text-muted">غير نشط</span>
                                                @endif
                                            </td>
                                            <td id="email-setting-test-{{ $setting->id }}">
                                                @if($testStatus)
                                                    @if($testStatus === 'success')
                                                        <span class="group-show-chip group-show-chip--sm text-success">
                                                            <i class="fe fe-check me-1"></i>
                                                            {{ $testType === 'connection' ? 'اتصال ناجح' : 'إرسال ناجح' }}
                                                        </span>
                                                    @else
                                                        <span class="group-show-chip group-show-chip--sm text-danger">
                                                            <i class="fe fe-x me-1"></i>
                                                            {{ $testType === 'connection' ? 'اتصال فاشل' : 'إرسال فاشل' }}
                                                        </span>
                                                    @endif
                                                    @if($setting->last_tested_at)
                                                        <br><small class="text-muted">{{ $setting->last_tested_at->diffForHumans() }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted fs-12">لم يُختبر</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                            title="اختبار الاتصال"
                                                            onclick="testSavedConnection({{ $setting->id }}, this)">
                                                        <i class="fe fe-wifi"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                            title="إرسال بريد اختبار"
                                                            onclick="openSendTestModal({{ $setting->id }}, @js($setting->mail_from_address))">
                                                        <i class="fe fe-send"></i>
                                                    </button>
                                                    @if(!$setting->is_active)
                                                        <form action="{{ route('admin.settings.email.activate', $setting->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill"
                                                                    title="تفعيل"
                                                                    onclick="return confirm('تفعيل هذه الإعدادات؟')">
                                                                <i class="fe fe-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('admin.settings.email.edit', $setting->id) }}"
                                                       class="btn btn-sm btn-outline-secondary rounded-pill" title="تعديل">
                                                        <i class="fe fe-edit-2"></i>
                                                    </a>
                                                    @if(!$setting->is_active)
                                                        <form action="{{ route('admin.settings.email.destroy', $setting->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"
                                                                    title="حذف"
                                                                    onclick="return confirm('حذف هذه الإعدادات؟')">
                                                                <i class="fe fe-trash-2"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="sendTestEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title">إرسال بريد اختبار</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="admin-group-form-hint mb-3">سيتم إرسال رسالة فعلية إلى العنوان المحدد للتأكد من الإرسال الكامل.</p>
                    <label class="form-label fw-semibold">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="sendTestEmailInput" placeholder="test@example.com" required>
                    <input type="hidden" id="sendTestSettingId">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="sendTestEmailBtn" onclick="submitSendTestEmail()">
                        <i class="fe fe-send me-1"></i>إرسال
                    </button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    @include('admin.pages.settings.email.partials.scripts')
@endsection
