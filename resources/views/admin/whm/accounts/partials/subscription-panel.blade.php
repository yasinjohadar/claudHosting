{{--
    Subscription + invoices block. Read-only unless $canRenew.

    @param  \App\Models\WhmAccount              $account       required
    @param  \Illuminate\Support\Collection|null $invoices      pre-scoped, newest first
    @param  string|null $invoiceRoute  route NAME for one invoice; null → plain text, no links
    @param  array       $billing       ['renewal_amount'=>…, 'subscription_years'=>…]; [] → hide amount row
    @param  bool        $canRenew      false → no POST form at all (default false)
    @param  bool        $showTitle     default true
    @param  string|null $allInvoicesRoute  route NAME for the "كل الفواتير" link (no params)
--}}
@php
    $invoices = $invoices ?? ($account->relationLoaded('invoices') ? $account->invoices : collect());
    $invoiceRoute = $invoiceRoute ?? null;
    $allInvoicesRoute = $allInvoicesRoute ?? null;
    $billing = is_array($billing ?? null) ? $billing : [];
    $canRenew = ($canRenew ?? false) && $account->status !== 'terminated';
    $showTitle = $showTitle ?? true;
    $days = $account->subscription_days_remaining;

    // Derive the tone from the model's own badge so the colour can never disagree with
    // the label sitting next to it.
    $tone = str_replace(['bg-', '-transparent'], '', (string) $account->subscription_status_badge) ?: 'secondary';

    // Share of the paid period already consumed. Needs a real start date, so it is
    // skipped entirely rather than guessed when neither renewal nor join date is known.
    $periodStart = $account->last_renewed_at ?? $account->joined_at;
    $periodEnd = $account->subscription_ends_at;
    $progress = null;
    if ($periodStart && $periodEnd && $periodEnd->getTimestamp() > $periodStart->getTimestamp()) {
        $span = $periodEnd->getTimestamp() - $periodStart->getTimestamp();
        $used = now()->getTimestamp() - $periodStart->getTimestamp();
        $progress = (int) max(0, min(100, round($used / $span * 100)));
    }

    $paidTotal = $invoices->where('status', 'Paid')->sum('total');
@endphp
@include('admin.whm.accounts.partials.whm-panel-styles')
<div class="whm-section">
    @if($showTitle)<div class="whm-section-title">الاشتراك والفواتير</div>@endif

    <div class="whm-sub-grid">
        <div class="whm-sub-tile whm-sub-tile--{{ $tone }}">
            <span class="whm-sub-tile__icon"><i class="fe fe-calendar"></i></span>
            <div class="whm-sub-tile__body">
                <span class="whm-sub-tile__label">نهاية الاشتراك</span>
                <span class="whm-sub-tile__value" dir="ltr">{{ $account->subscription_ends_at?->format('Y-m-d') ?? '—' }}</span>
                @if($account->subscription_ends_at)
                    <span class="badge {{ $account->subscription_status_badge }} mt-1">{{ $account->subscription_status_label }}</span>
                @else
                    <span class="whm-sub-tile__hint">لم يُضبط تاريخ</span>
                @endif
            </div>
        </div>

        <div class="whm-sub-tile whm-sub-tile--{{ $tone }}">
            <span class="whm-sub-tile__icon"><i class="fe fe-clock"></i></span>
            <div class="whm-sub-tile__body">
                <span class="whm-sub-tile__label">المتبقي</span>
                @if($days === null)
                    <span class="whm-sub-tile__value">—</span>
                @elseif($days >= 0)
                    <span class="whm-sub-tile__value">{{ $days }} يوم</span>
                    <span class="whm-sub-tile__hint">حتى نهاية الاشتراك</span>
                @else
                    <span class="whm-sub-tile__value">{{ abs($days) }} يوم</span>
                    <span class="whm-sub-tile__hint">مضت على انتهائه</span>
                @endif
            </div>
        </div>

        <div class="whm-sub-tile whm-sub-tile--primary">
            <span class="whm-sub-tile__icon"><i class="fe fe-package"></i></span>
            <div class="whm-sub-tile__body">
                <span class="whm-sub-tile__label">الباقة</span>
                <span class="whm-sub-tile__value">{{ $account->package ?: '—' }}</span>
            </div>
        </div>

        <div class="whm-sub-tile whm-sub-tile--secondary">
            <span class="whm-sub-tile__icon"><i class="fe fe-rotate-cw"></i></span>
            <div class="whm-sub-tile__body">
                <span class="whm-sub-tile__label">آخر تجديد</span>
                <span class="whm-sub-tile__value" dir="ltr">{{ $account->last_renewed_at?->format('Y-m-d') ?? '—' }}</span>
                @if($account->last_renewed_at)
                    <span class="whm-sub-tile__hint">{{ $account->last_renewed_at->format('H:i') }}</span>
                @else
                    <span class="whm-sub-tile__hint">لم يُجدَّد بعد</span>
                @endif
            </div>
        </div>

        @if(array_key_exists('renewal_amount', $billing))
            <div class="whm-sub-tile whm-sub-tile--primary">
                <span class="whm-sub-tile__icon"><i class="fe fe-tag"></i></span>
                <div class="whm-sub-tile__body">
                    <span class="whm-sub-tile__label">مبلغ الفاتورة الافتراضي</span>
                    <span class="whm-sub-tile__value">{{ number_format($billing['renewal_amount'] ?? 0, 2) }} ر.س</span>
                </div>
            </div>
        @endif
    </div>

    @if($progress !== null)
        <div class="whm-sub-progress whm-sub-tile--{{ $tone }}">
            <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                <span>{{ $progress }}%</span>
                <span>
                    <span dir="ltr">{{ $periodStart->format('Y-m-d') }}</span>
                    <i class="fe fe-arrow-left mx-1"></i>
                    <span dir="ltr">{{ $periodEnd->format('Y-m-d') }}</span>
                </span>
            </div>
            <div class="whm-sub-progress__track" role="presentation">
                <div class="whm-sub-progress__bar" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    @endif

    @if($canRenew)
        <form action="{{ route('admin.whm.accounts.renew', $account) }}" method="POST" class="row g-2 align-items-end mt-3"
            onsubmit="return confirm('تجديد الاشتراك لمدة {{ $billing['subscription_years'] ?? 1 }} سنة وإنشاء فاتورة؟');">
            @csrf
            <div class="col-md-4">
                <label class="form-label small">مبلغ الفاتورة (اختياري)</label>
                <input type="number" name="amount" class="form-control form-control-sm" min="0" step="0.01"
                    placeholder="{{ $billing['renewal_amount'] ?? 0 }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fe fe-refresh-cw me-1"></i>تجديد الاشتراك
                </button>
            </div>
        </form>
    @endif

    @if($invoices->isNotEmpty())
        <div class="mt-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="small text-muted">آخر الفواتير</span>
                @if($paidTotal > 0)
                    <span class="whm-meta-chip">المدفوع {{ number_format($paidTotal, 2) }} ر.س</span>
                @endif
            </div>
            @foreach($invoices as $inv)
                <div class="whm-sub-invoice">
                    <i class="fe fe-file-text text-muted"></i>
                    @if($invoiceRoute)
                        <a href="{{ route($invoiceRoute, $inv) }}" class="fw-semibold" dir="ltr">{{ $inv->invoice_number }}</a>
                    @else
                        <span class="fw-semibold" dir="ltr">{{ $inv->invoice_number }}</span>
                    @endif
                    <span class="badge bg-{{ $inv->status === 'Paid' ? 'success' : 'warning' }}-transparent">{{ $inv->status_name }}</span>
                    <span class="whm-sub-invoice__amount" dir="ltr">{{ number_format($inv->total, 2) }} ر.س</span>
                </div>
            @endforeach
            @if($allInvoicesRoute)
                <a href="{{ route($allInvoicesRoute) }}" class="small d-inline-block mt-2">كل الفواتير</a>
            @endif
        </div>
    @endif
</div>
