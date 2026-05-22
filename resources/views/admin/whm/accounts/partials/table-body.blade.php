@forelse($accounts as $a)
    <tr class="whm-account-row">
        <td class="align-middle">
            <code class="whm-user-badge" dir="ltr">{{ $a->username }}</code>
        </td>
        <td class="align-middle">
            @include('admin.whm.accounts.partials.domain-link', ['account' => $a])
        </td>
        @include('admin.whm.accounts.partials.email-cell', ['account' => $a])
        <td class="text-center align-middle text-nowrap text-muted small">{{ $a->joined_at?->format('Y-m-d') ?? '—' }}</td>
        <td class="text-center align-middle text-nowrap">
            @if($a->subscription_ends_at)
                <span class="badge {{ $a->subscription_status_badge }}">{{ $a->subscription_ends_at->format('Y-m-d') }}</span>
                <div class="small text-muted">{{ $a->subscription_status_label }}</div>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="text-center align-middle">
            @if($a->package)
                <span class="badge bg-primary-transparent text-primary">{{ $a->package }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="align-middle whm-col-status">@include('admin.whm.accounts.partials.status-toggle', ['account' => $a])</td>
        <td class="align-middle whm-client-cell" data-account-id="{{ $a->id }}">
            @include('admin.whm.accounts.partials.client-cell', ['account' => $a])
        </td>
        <td class="align-middle text-center text-nowrap">
            <div class="d-inline-flex flex-wrap gap-1 justify-content-center">
                @include('admin.whm.accounts.partials.cpanel-link', ['account' => $a, 'configured' => $configured ?? true])
                <a href="{{ route('admin.whm.accounts.show', $a) }}" class="btn btn-sm btn-primary-light">
                    <i class="fe fe-edit-2 me-1"></i>عرض
                </a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-muted py-5">
            <i class="fe fe-inbox d-block mb-2 fs-2 opacity-50"></i>
            لا توجد نتائج — غيّر البحث أو زامن من WHM.
        </td>
    </tr>
@endforelse
