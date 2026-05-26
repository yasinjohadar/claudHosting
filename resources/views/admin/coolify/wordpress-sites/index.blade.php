@extends('admin.layouts.master')
@section('page-title') مواقع WordPress @stop

@push('styles')
    @include('admin.coolify.wordpress-sites.partials.index-styles')
@endpush

@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex justify-content-between align-items-center my-4 gap-3">
            <div>
                <h4 class="mb-1">مواقع WordPress</h4>
                <p class="text-muted small mb-0">إدارة المواقع المستضافة عبر Coolify</p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('admin.coolify.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-settings me-1"></i> إعدادات Coolify
                </a>
                @if ($readiness['ready'] ?? false)
                    <a href="{{ route('admin.coolify.wordpress-sites.create') }}" class="btn btn-primary btn-sm">
                        <i class="fe fe-plus me-1"></i> موقع جديد
                    </a>
                @endif
            </div>
        </div>

        @include('admin.coolify.partials.alerts')

        @if (! ($readiness['ready'] ?? false))
            <div class="alert alert-warning">
                اضبط <strong>النطاق الأساسي</strong> و<strong>السيرفر الافتراضي</strong> في
                <a href="{{ route('admin.coolify.settings.index') }}">إعدادات Coolify</a> قبل إنشاء المواقع.
            </div>
        @endif

        <div class="card custom-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 wp-sites-table align-middle">
                        <thead>
                            <tr>
                                <th>الموقع</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th>العميل</th>
                                <th>المشروع</th>
                                <th>التاريخ</th>
                                <th class="wp-sites-table__col-actions text-end">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($sites as $site)
                            @php
                                $st = $site->status;
                                $statusKey = match ($st) {
                                    'running' => 'running',
                                    'provisioning' => 'provisioning',
                                    'failed' => 'failed',
                                    default => 'default',
                                };
                                $projectLabel = $site->project_name;
                                $projectUuid = $site->project_uuid;
                            @endphp
                            <tr>
                                <td>
                                    <div class="wp-site-name">
                                        <span class="wp-site-name__icon" aria-hidden="true">
                                            <i class="fab fa-wordpress"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="wp-site-name__title text-truncate">{{ $site->display_name }}</div>
                                            <div class="wp-site-name__slug">
                                                @if ($site->isCustomDomain())
                                                    <span class="badge bg-info-subtle text-info me-1" style="font-size:0.65rem">مستقل</span>
                                                @endif
                                                {{ $site->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($site->public_url)
                                        <a href="{{ $site->public_url }}" target="_blank" rel="noopener noreferrer"
                                            class="wp-site-url text-primary text-decoration-none"
                                            title="{{ $site->public_url }}">
                                            <i class="fe fe-link"></i>
                                            {{ parse_url($site->public_url, PHP_URL_HOST) ?: $site->slug }}
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="wp-site-status wp-site-status--{{ $statusKey }}">
                                        {{ \App\Models\CoolifyWordpressSite::STATUSES[$st] ?? $st }}
                                    </span>
                                </td>
                                <td class="wp-site-client" id="wp-site-client-{{ $site->uuid }}">
                                    @include('admin.coolify.wordpress-sites.partials.client-cell', [
                                        'client' => $site->client,
                                        'customer' => $site->client?->customer,
                                    ])
                                </td>
                                <td>
                                    @if ($projectLabel)
                                        <span class="wp-site-project" title="{{ $projectLabel }}">{{ $projectLabel }}</span>
                                    @elseif ($projectUuid)
                                        <code class="wp-site-project-uuid" title="{{ $projectUuid }}">{{ Str::limit($projectUuid, 14) }}</code>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="wp-site-date">{{ $site->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    @include('admin.coolify.wordpress-sites.partials.index-row-actions', [
                                        'site' => $site,
                                        'clientUsers' => $clientUsers ?? [],
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr class="wp-sites-empty">
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fab fa-wordpress fa-2x mb-2 d-block opacity-25"></i>
                                    لا توجد مواقع بعد
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($sites->hasPages())
                <div class="card-footer border-top-0 bg-transparent">{{ $sites->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.whm.accounts.partials.whm-toast')
    @include('admin.partials.asset-client-assign-script')
@endpush
