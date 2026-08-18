{{--
    One collapsible card per WHM account: subscription summary in the always-visible
    header, deliverability panel lazy-fetched on show.bs.collapse. Replaces the two
    hand-written account tables (admin/customers/show + admin/pages/users/partials).

    account-summary is deliberately NOT rendered here: formatSslBadgeForDomain() makes
    a live WHM call, so N tiles would mean N synchronous API calls during page render.
    This accordion stays API-free until a card is opened.

    @param  iterable<\App\Models\WhmAccount> $accounts     required
    @param  bool             $configured   default false
    @param  string           $accordionId  default 'whm-accounts-accordion' (unique per page)
    @param  'plain'|'domain' $variant      default 'plain'
    @param  array            $billing      default [] (hides the default-amount row)
    @param  bool             $canRenew     default false
--}}
@php
    $accordionId = $accordionId ?? 'whm-accounts-accordion';
    $variant = ($variant ?? 'plain') === 'domain' ? 'domain' : 'plain';
    $configured = $configured ?? false;
    $billing = is_array($billing ?? null) ? $billing : [];
    $canRenew = $canRenew ?? false;

    $statusClass = fn (?string $s) => $variant === 'domain'
        ? 'domain-status-badge domain-status-badge--'.match ($s) {
            'active' => 'active',
            'suspended' => 'warning',
            'terminated' => 'expired',
            default => 'info',
        }
        : 'badge bg-'.match ($s) {
            'active' => 'success',
            'terminated' => 'secondary',
            default => 'warning',
        }.'-transparent';

    $btn = $variant === 'domain' ? 'domain-action-btn' : 'btn btn-sm btn-light';
    $btnCpanel = $variant === 'domain' ? 'domain-action-btn domain-action-btn--warning' : 'btn btn-sm btn-warning-transparent';
@endphp
@include('admin.whm.accounts.partials.whm-panel-styles')
<div class="accordion" id="{{ $accordionId }}">
@forelse($accounts as $acc)
    @php
        $itemId = $accordionId.'-'.$acc->id;
        $headerId = $itemId.'-heading';
        $days = $acc->subscription_days_remaining;
        $mailable = $configured && $acc->status !== 'terminated';
    @endphp
    <div class="accordion-item">
        <h2 class="accordion-header" id="{{ $headerId }}">
            <button class="accordion-button collapsed" type="button"
                data-bs-toggle="collapse" data-bs-target="#{{ $itemId }}"
                aria-expanded="false" aria-controls="{{ $itemId }}">
                <span class="d-flex flex-wrap align-items-center gap-2 w-100 pe-2">
                    <span class="fw-semibold" dir="ltr">{{ $acc->domain }}</span>
                    <code class="small text-muted" dir="ltr">{{ $acc->username }}</code>
                    <span class="{{ $statusClass($acc->status) }}">{{ $acc->status_label }}</span>
                    @if($acc->package)<span class="whm-meta-chip">{{ $acc->package }}</span>@endif
                    @if($acc->subscription_ends_at)
                        <span class="text-muted small">ينتهي {{ $acc->subscription_ends_at->format('Y-m-d') }}</span>
                        <span class="badge {{ $acc->subscription_status_badge }}">{{ $acc->subscription_status_label }}</span>
                        @if($days !== null && $days >= 0)
                            <span class="text-muted small">{{ $days }} يوم متبقي</span>
                        @endif
                    @else
                        <span class="text-warning small">لم يُضبط تاريخ نهاية الاشتراك</span>
                    @endif
                </span>
            </button>
        </h2>
        <div id="{{ $itemId }}" class="accordion-collapse collapse"
            aria-labelledby="{{ $headerId }}" data-bs-parent="#{{ $accordionId }}">
            <div class="accordion-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('admin.whm.accounts.show', $acc) }}" class="{{ $btn }}">
                        <i class="fe fe-eye"></i> عرض الحساب
                    </a>
                    @if($mailable)
                        <a href="{{ route('admin.whm.accounts.cpanel', $acc) }}" target="_blank" rel="noopener" class="{{ $btnCpanel }}">
                            <i class="fe fe-external-link"></i> cPanel
                        </a>
                        <a href="{{ route('admin.whm.accounts.wordpress.index', $acc) }}" class="{{ $btn }}">
                            <i class="fab fa-wordpress"></i> ووردبريس
                        </a>
                    @endif
                </div>

                @include('admin.whm.accounts.partials.subscription-panel', [
                    'account' => $acc,
                    'invoices' => $acc->relationLoaded('invoices') ? $acc->invoices->sortByDesc('date')->take(5) : collect(),
                    'invoiceRoute' => 'admin.invoices.show',
                    'billing' => $billing,
                    'canRenew' => $canRenew,
                ])

                @if($mailable)
                    @include('admin.whm.accounts.partials.email-deliverability-tab', [
                        'account' => $acc,
                        'embedded' => true,
                        'canWriteDns' => true,
                    ])
                @else
                    <div class="whm-section">
                        <div class="whm-section-title">قابلية تسليم البريد</div>
                        <p class="text-muted small mb-0">
                            {{ $acc->status === 'terminated' ? 'الحساب محذوف — لا بيانات بريد.' : 'إعدادات WHM غير مكتملة — اضبطها من إعدادات WHM.' }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-4">
        لا توجد حسابات مرتبطة —
        <a href="{{ route('admin.whm.accounts.index') }}">اربط حساباً من قائمة WHM</a>.
    </div>
@endforelse
</div>
