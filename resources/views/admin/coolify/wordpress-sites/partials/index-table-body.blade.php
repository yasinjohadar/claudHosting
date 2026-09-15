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
            @if (request()->anyFilled(['q', 'status', 'user_id', 'project_uuid', 'domain_type']))
                لا نتائج مطابقة للفلاتر الحالية
                <button type="button" id="wp-filter-reset-empty" class="btn btn-link btn-sm">مسح الفلاتر</button>
            @else
                لا توجد مواقع بعد
            @endif
        </td>
    </tr>
@endforelse
