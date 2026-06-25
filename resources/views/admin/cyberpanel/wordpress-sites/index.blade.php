@extends('admin.layouts.master')
@section('page-title') WordPress — CyberPanel @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center flex-wrap gap-2 my-4">
            <div>
                <h4 class="mb-1">مواقع WordPress (CyberPanel)</h4>
                <p class="text-muted mb-0 small">لوحة إدارة كاملة — إضافات، قوالب، نسخ احتياطي، وصيانة</p>
            </div>
            <a href="{{ route('admin.cyberpanel.websites.index') }}" class="btn btn-outline-primary btn-sm">المواقع</a>
        </div>
        @include('admin.coolify.partials.alerts')
        @if(!($supportsCloud ?? true))
            <div class="alert alert-warning">أضف <strong>API Token</strong> من <a href="{{ route('admin.cyberpanel.settings.index') }}">إعدادات CyberPanel</a> قبل تثبيت WordPress أو SSL.</div>
        @endif
        @if(!($configured ?? false))
            <div class="alert alert-warning">أكمل إعدادات CyberPanel أولاً من <a href="{{ route('admin.cyberpanel.settings.index') }}">صفحة الإعدادات</a>.</div>
        @endif
        <div class="card custom-card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>النطاق</th>
                            <th>الحالة</th>
                            <th>موقع الاستضافة</th>
                            <th>SSL</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($sites as $site)
                        @php
                            $sslMeta = is_array($site->website?->metadata) ? ($site->website->metadata['ssl'] ?? null) : null;
                            $sslOk = is_array($sslMeta) && !empty($sslMeta['success']);
                            $statusClass = match($site->status) {
                                'running' => 'success',
                                'provisioning' => 'warning',
                                'failed' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold" dir="ltr">{{ $site->domain }}</td>
                            <td><span class="badge bg-{{ $statusClass }}-transparent">{{ $site->status_label }}</span></td>
                            <td>{{ $site->website?->domain ?? '—' }}</td>
                            <td>
                                @if($sslOk)
                                    <span class="badge bg-success-transparent">مفعّل</span>
                                @else
                                    <span class="badge bg-secondary-transparent">غير مفعّل</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($site->website && ($configured ?? false) && ($supportsCloud ?? false))
                                    @if($site->status === 'provisioning')
                                        <form action="{{ route('admin.cyberpanel.wordpress-sites.refresh-status', $site) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">تحديث الحالة</button>
                                        </form>
                                    @endif
                                    @if(in_array($site->status, ['failed', 'provisioning'], true))
                                        <form action="{{ route('admin.cyberpanel.wordpress-sites.install-wordpress', $site) }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة تثبيت WordPress على {{ $site->domain }}؟');">
                                            @csrf
                                            <input type="hidden" name="admin_user" value="{{ $site->wp_user ?: 'admin' }}">
                                            <input type="hidden" name="admin_email" value="{{ $site->website->email }}">
                                            <input type="hidden" name="title" value="{{ $site->domain }}">
                                            <button type="submit" class="btn btn-sm btn-success">تثبيت WP</button>
                                        </form>
                                    @endif
                                    @if(!$sslOk)
                                        <form action="{{ route('admin.cyberpanel.wordpress-sites.issue-ssl', $site) }}" method="POST" class="d-inline" onsubmit="return confirm('إصدار شهادة SSL لـ {{ $site->domain }}؟');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">شهادة SSL</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.cyberpanel.wordpress-sites.install-wordpress-ssl', $site) }}" method="POST" class="d-inline" onsubmit="return confirm('تثبيت WordPress + شهادة SSL لـ {{ $site->domain }}؟');">
                                        @csrf
                                        <input type="hidden" name="admin_user" value="{{ $site->wp_user ?: 'admin' }}">
                                        <input type="hidden" name="admin_email" value="{{ $site->website->email }}">
                                        <input type="hidden" name="title" value="{{ $site->domain }}">
                                        <button type="submit" class="btn btn-sm btn-primary">WP + SSL</button>
                                    </form>
                                @endif
                                @if($site->website)
                                    <a href="{{ route('admin.cyberpanel.websites.show', $site->website) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                @endif
                                @if($site->status === 'running')
                                    <a href="{{ route('admin.cyberpanel.wordpress-sites.show', $site) }}" class="btn btn-sm btn-primary">
                                        <i class="fe fe-sliders me-1"></i> إدارة
                                    </a>
                                    <a href="{{ route('admin.cyberpanel.wordpress-sites.wp-login', $site) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">WP</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">لا مواقع WordPress مسجّلة — ثبّت WordPress من <a href="{{ route('admin.cyberpanel.websites.index') }}">صفحة المواقع</a>.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($sites->hasPages())<div class="card-footer">{{ $sites->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
