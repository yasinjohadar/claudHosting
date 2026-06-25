@extends('admin.layouts.master')
@section('page-title') {{ $website->domain }} @stop

@push('styles')
    @include('admin.coolify.partials.overview-styles')
    @include('admin.cyberpanel.websites.partials.show-styles')
@endpush

@section('content')
@php
    $wp = $website->wordpressSite;
    $sslMeta = is_array($website->metadata) ? ($website->metadata['ssl'] ?? null) : null;
    $sslActive = is_array($sslMeta) && !empty($sslMeta['success']);
    $wpBadge = $wp ? match($wp->status) {
        'running' => 'running',
        'provisioning' => 'provisioning',
        'failed' => 'failed',
        default => 'none',
    } : 'none';
@endphp
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.cyberpanel.websites.partials.show-header', ['website' => $website])
        @include('admin.coolify.partials.alerts')

        @if(!($supportsCloud ?? true))
            <div class="alert alert-warning border-0 shadow-sm mb-3">
                <strong>CloudAPI:</strong> احفظ كلمة مرور المدير في
                <a href="{{ route('admin.cyberpanel.settings.index') }}">إعدادات CyberPanel</a>
                وفعّل <strong>API Access</strong> للمستخدم admin من لوحة CyberPanel.
            </div>
        @endif

        <h6 class="cp-show-section-title">ملخص الموقع</h6>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => 'primary',
                    'icon' => 'fe fe-layers',
                    'label' => 'الباقة',
                    'desc' => 'خطة الاستضافة الحالية',
                    'highlight' => $website->package ?? '—',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => 'info',
                    'icon' => 'fe fe-code',
                    'label' => 'PHP',
                    'desc' => 'إصدار PHP على السيرفر',
                    'highlight' => $website->php_version ?? '—',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => 'secondary',
                    'icon' => 'fe fe-user',
                    'label' => 'المالك',
                    'desc' => 'حساب CyberPanel',
                    'highlight' => $website->owner ?? '—',
                ])
            </div>
            <div class="col-sm-6 col-xl-3">
                @include('admin.coolify.partials.info-widget', [
                    'accent' => $website->subscription_ends_at && $website->is_subscription_expired ? 'danger' : 'success',
                    'icon' => 'fe fe-calendar',
                    'label' => 'الاشتراك',
                    'desc' => 'تاريخ انتهاء الاشتراك',
                    'highlight' => $website->subscription_ends_at?->format('Y-m-d') ?? '—',
                ])
            </div>
        </div>

        <h6 class="cp-show-section-title">الإدارة</h6>
        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="cp-show-panel">
                    <div class="cp-show-panel__head">
                        <h6 class="cp-show-panel__title">
                            <i class="fe fe-users text-primary"></i> العميل المسؤول
                        </h6>
                    </div>
                    <div class="cp-show-panel__body">
                        <div id="cp-client-cell" class="cp-show-client-display">
                            @include('admin.cyberpanel.websites.partials.client-cell', ['client' => $website->client])
                        </div>
                        @include('admin.partials.asset-client-assign-inline', [
                            'layout' => 'panel',
                            'assignUrl' => route('admin.cyberpanel.websites.assign-client', $website),
                            'payloadKey' => 'domain',
                            'payloadValue' => $website->domain,
                            'clientUsers' => $clientUsers,
                            'selectedUserId' => $website->user_id,
                            'cellSelector' => '#cp-client-cell',
                            'saveButtonLabel' => 'حفظ الربط',
                        ])
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="cp-show-panel">
                    <div class="cp-show-panel__head">
                        <h6 class="cp-show-panel__title">
                            <i class="fe fe-package text-warning"></i> تغيير الباقة
                        </h6>
                    </div>
                    <div class="cp-show-panel__body">
                        <form method="POST" action="{{ route('admin.cyberpanel.websites.change-package', $website) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-8">
                                <label class="form-label small text-muted mb-1">الباقة</label>
                                <select name="package" class="form-select" required>
                                    @forelse($packages as $pkg)
                                        <option value="{{ $pkg }}" @selected($website->package === $pkg)>{{ $pkg }}</option>
                                    @empty
                                        <option value="{{ $website->package }}">{{ $website->package }}</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fe fe-check me-1"></i> تطبيق
                                </button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.cyberpanel.websites.renew', $website) }}" class="d-inline">@csrf
                            <button type="submit" class="cp-show-renew-link border-0 bg-transparent p-0">
                                <i class="fe fe-refresh-cw"></i> تجديد الاشتراك + إنشاء فاتورة
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="cp-show-panel" id="wordpress">
                    <div class="cp-show-panel__head cp-show-panel__head--wp">
                        <h6 class="cp-show-panel__title">
                            <i class="fab fa-wordpress text-info"></i> WordPress
                        </h6>
                        @if($wp)
                            <span class="cp-show-feature__status cp-show-feature__status--{{ $wpBadge }}">
                                @if($wp->status === 'running')<i class="fe fe-check-circle"></i>@endif
                                {{ $wp->status_label }}
                            </span>
                        @endif
                    </div>
                    <div class="cp-show-panel__body">
                        @if($wp)
                            <div class="cp-show-feature">
                                <div class="cp-show-feature__icon cp-show-feature__icon--wp">
                                    <i class="fab fa-wordpress"></i>
                                </div>
                                <div class="cp-show-feature__content">
                                    @if($wp->wp_user)
                                        <div class="small text-muted mb-1">مستخدم المدير: <strong dir="ltr">{{ $wp->wp_user }}</strong></div>
                                    @endif
                                    <div class="cp-show-feature__actions">
                                        @if($wp->status === 'running')
                                            <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $wp) }}" target="_blank" rel="noopener" class="cp-show-btn-wp">
                                                <i class="fab fa-wordpress"></i> لوحة WP — دخول تلقائي
                                            </a>
                                            <a href="{{ route('admin.cyberpanel.wordpress-sites.show', $wp) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fe fe-sliders me-1"></i> لوحة الإدارة الكاملة
                                            </a>
                                            @if($wp->public_url)
                                                <a href="{{ $wp->public_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fe fe-external-link me-1"></i> الموقع
                                                </a>
                                            @endif
                                        @endif
                                        @if($wp->status === 'provisioning')
                                            <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.refresh-status', $wp) }}" class="d-inline">@csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="fe fe-refresh-cw me-1"></i> تحديث الحالة
                                                </button>
                                            </form>
                                        @endif
                                        @if(in_array($wp->status, ['failed', 'provisioning'], true))
                                            <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.install-wordpress', $wp) }}" class="d-inline" onsubmit="return confirm('إعادة تثبيت WordPress؟');">@csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fe fe-rotate-cw me-1"></i> إعادة التثبيت
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if(!$wp->hasStoredAdminPassword())
                                <div class="cp-show-credentials">
                                    <div class="small fw-semibold mb-2"><i class="fe fe-key me-1 text-primary"></i> بيانات الدخول للوحة</div>
                                    <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.save-credentials', $wp) }}" class="row g-2">
                                        @csrf
                                        <div class="col-md-4">
                                            <input type="text" name="wp_user" class="form-control form-control-sm" placeholder="مستخدم WP" value="{{ $wp->wp_user }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="password" name="wp_password" class="form-control form-control-sm" placeholder="كلمة مرور WP" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">حفظ</button>
                                        </div>
                                    </form>
                                    <p class="cp-show-credentials__hint mb-0">اختياري: للدخول التلقائي يُستخدم CyberPanel API أولاً (مستخدم <code dir="ltr">cyberpanel</code>). احفظ هنا بيانات المستخدم الحقيقي كاحتياطي فقط.</p>
                                </div>
                            @else
                                <div class="small text-success mt-2">
                                    <i class="fe fe-check-circle me-1"></i> بيانات الدخول محفوظة — الدخول التلقائي جاهز
                                </div>
                            @endif
                        @else
                            <p class="text-muted small mb-3">لم يُثبَّت WordPress على هذا الموقع بعد.</p>
                            <form method="POST" action="{{ route('admin.cyberpanel.websites.install-wordpress', $website) }}" class="row g-2 cp-show-install-form">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label">عنوان الموقع</label>
                                    <input type="text" name="title" class="form-control form-control-sm" placeholder="موقعي">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">مستخدم</label>
                                    <input type="text" name="admin_user" class="form-control form-control-sm" value="admin">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">البريد</label>
                                    <input type="email" name="admin_email" class="form-control form-control-sm" value="{{ $website->email }}">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-sm btn-success w-100">
                                        <i class="fab fa-wordpress me-1"></i> تثبيت
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="cp-show-panel">
                    <div class="cp-show-panel__head cp-show-panel__head--ssl">
                        <h6 class="cp-show-panel__title">
                            <i class="fe fe-shield text-success"></i> شهادة SSL
                        </h6>
                        <span class="cp-show-feature__status cp-show-feature__status--{{ $sslActive ? 'ssl-on' : 'ssl-off' }}">
                            {{ $sslActive ? "Let's Encrypt" : 'غير مفعّلة' }}
                        </span>
                    </div>
                    <div class="cp-show-panel__body">
                        <div class="cp-show-feature">
                            <div class="cp-show-feature__icon cp-show-feature__icon--{{ $sslActive ? 'ssl-on' : 'ssl-off' }}">
                                <i class="fe fe-{{ $sslActive ? 'lock' : 'unlock' }}"></i>
                            </div>
                            <div class="cp-show-feature__content">
                                @if($sslActive)
                                    <p class="mb-1 fw-semibold text-success">الشهادة مفعّلة</p>
                                    @if(!empty($sslMeta['issued_at']))
                                        <p class="small text-muted mb-0">آخر إصدار: {{ $sslMeta['issued_at'] }}</p>
                                    @endif
                                @else
                                    <p class="mb-2 text-muted small">الموقع يعمل بدون شهادة SSL مشفّرة. يُنصح بإصدار Let's Encrypt.</p>
                                    @if($website->wordpressSite)
                                        <form method="POST" action="{{ route('admin.cyberpanel.wordpress-sites.issue-ssl', $website->wordpressSite) }}" class="d-inline" onsubmit="return confirm('إصدار شهادة SSL لـ {{ $website->domain }}؟');">@csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fe fe-shield me-1"></i> إصدار شهادة SSL
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($website->status !== 'terminated')
            <div class="cp-show-danger">
                <div class="cp-show-danger__text">
                    <strong>منطقة خطرة</strong> — حذف الموقع من CyberPanel لا يمكن التراجع عنه.
                </div>
                <form method="POST" action="{{ route('admin.cyberpanel.websites.destroy', $website) }}" onsubmit="return confirm('حذف الموقع من CyberPanel؟');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fe fe-trash-2 me-1"></i> حذف من CyberPanel
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@push('scripts')
@include('admin.partials.asset-client-assign-script')
@endpush
@endsection
