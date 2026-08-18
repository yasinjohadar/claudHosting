@php
    /**
     * Diff between the cPanel zone and Cloudflare, rendered server-side by
     * WhmMailDnsController. Read-only markup — the apply button lives in the modal shell
     * so it survives this fragment being swapped.
     */
    $data = is_array($data ?? null) ? $data : [];
    $zone = $data['zone'] ?? null;
    $changes = $data['changes'] ?? [];
    $extras = $data['extras'] ?? [];
    $blockers = $data['blockers'] ?? [];
    $warnings = $data['warnings'] ?? [];
    $counts = $data['counts'] ?? [];
    $notes = $data['plan']['notes'] ?? [];
    $skipped = $data['plan']['skipped'] ?? [];
    $results = $data['results'] ?? [];

    $verdictMeta = [
        'create' => ['label' => 'سيُنشأ', 'badge' => 'bg-success-transparent', 'icon' => 'fe-plus-circle'],
        'update' => ['label' => 'سيُعدَّل', 'badge' => 'bg-warning-transparent', 'icon' => 'fe-edit-2'],
        'unchanged' => ['label' => 'مطابق', 'badge' => 'bg-secondary-transparent', 'icon' => 'fe-check'],
        'conflict' => ['label' => 'تعارض', 'badge' => 'bg-danger-transparent', 'icon' => 'fe-alert-octagon'],
        'manual' => ['label' => 'تركيب يدوي', 'badge' => 'bg-info-transparent', 'icon' => 'fe-clipboard'],
    ];
@endphp

@include('admin.whm.accounts.partials.whm-panel-styles')
@include('admin.whm.accounts.partials.copy-email-styles')

<div class="whm-section">
    <dl class="row small mb-0">
        <dt class="col-sm-4">النطاق</dt>
        <dd class="col-sm-8" dir="ltr">{{ $data['domain'] ?? '—' }}</dd>
        <dt class="col-sm-4">منطقة Cloudflare</dt>
        <dd class="col-sm-8" dir="ltr">
            @if($zone)
                {{ $zone['name'] }}
            @else
                <span class="text-danger">غير موجودة</span>
            @endif
        </dd>
    </dl>
</div>

@if($results !== [])
    <div class="whm-section">
        <div class="whm-section-title">نتيجة التطبيق</div>
        <ul class="list-unstyled small mb-0">
            @foreach($results as $result)
                <li class="mb-1">
                    @if($result['ok'])
                        <i class="fe fe-check text-success me-1"></i>
                    @else
                        <i class="fe fe-x text-danger me-1"></i>
                    @endif
                    <span class="badge bg-light text-dark">{{ $result['type'] }}</span>
                    <code dir="ltr">{{ $result['name'] }}</code>
                    @if(! $result['ok'] && ! empty($result['message']))
                        — <span class="text-danger">{{ $result['message'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

@foreach($blockers as $blocker)
    <div class="alert alert-danger py-2 px-3 small mb-2">
        <i class="fe fe-alert-octagon me-1"></i>{{ $blocker['message'] }}
    </div>
@endforeach

@foreach($warnings as $warning)
    <div class="alert alert-warning py-2 px-3 small mb-2">
        <i class="fe fe-alert-triangle me-1"></i>{{ $warning['message'] }}
    </div>
@endforeach

@foreach($notes as $note)
    <div class="text-muted small mb-1"><i class="fe fe-info me-1"></i>{{ $note }}</div>
@endforeach

@if($changes === [])
    <div class="text-center py-4">
        <i class="fe fe-inbox fs-2 text-muted opacity-50 d-block mb-2"></i>
        <p class="text-muted small mb-0">{{ $data['message'] ?? 'لا سجلات لعرضها' }}</p>
    </div>
@else
    <div class="whm-section">
        <div class="whm-section-title">
            التغييرات
            <span class="whm-meta-chip ms-1">{{ (int) ($counts['create'] ?? 0) }} إنشاء</span>
            <span class="whm-meta-chip">{{ (int) ($counts['update'] ?? 0) }} تعديل</span>
            <span class="whm-meta-chip">{{ (int) ($counts['unchanged'] ?? 0) }} مطابق</span>
            @if((int) ($counts['conflict'] ?? 0) > 0)
                <span class="whm-meta-chip text-danger">{{ (int) $counts['conflict'] }} تعارض</span>
            @endif
        </div>

        @foreach($changes as $change)
            @php $meta = $verdictMeta[$change['verdict']] ?? $verdictMeta['unchanged']; @endphp
            <div class="whm-mail-domain-card">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <span class="whm-mail-check-label">{{ $change['label'] }}</span>
                    <span class="whm-meta-chip">{{ $change['type'] }}</span>
                    <span class="badge {{ $meta['badge'] }}">
                        <i class="fe {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                    </span>
                    @if(($change['origin'] ?? '') === 'generated')
                        <span class="badge bg-info-transparent" title="لم يُنسخ من cPanel">مُولَّد محلياً</span>
                    @endif
                    @if(($change['priority'] ?? null) !== null)
                        <span class="text-muted small">الأولوية {{ $change['priority'] }}</span>
                    @endif
                </div>

                @if(! empty($change['reason']))
                    <div class="alert alert-danger py-2 px-3 small mb-2">{{ $change['reason'] }}</div>
                @endif

                <div class="whm-mail-field">
                    <span class="whm-mail-field-label">الاسم</span>
                    <div class="whm-mail-value-wrap">
                        <span class="whm-mail-value" dir="ltr">{{ $change['name'] }}</span>
                        <button type="button" class="btn btn-sm whm-copy-email"
                            data-copy="{{ $change['name'] }}"
                            data-copy-msg="تم نسخ اسم السجل إلى الحافظة"
                            title="نسخ" aria-label="نسخ اسم السجل">
                            <i class="fe fe-copy"></i>
                        </button>
                    </div>
                </div>

                @if($change['verdict'] === 'update' && ($change['old_content'] ?? null) !== null)
                    <div class="whm-mail-field">
                        <span class="whm-mail-field-label text-danger">القيمة الحالية (ستُستبدل)</span>
                        <div class="whm-mail-value-wrap">
                            <span class="whm-mail-value" dir="ltr">{{ $change['old_content'] }}</span>
                        </div>
                    </div>
                @endif

                <div class="whm-mail-field">
                    <span class="whm-mail-field-label">
                        {{ $change['verdict'] === 'update' ? 'القيمة الجديدة' : 'القيمة' }}
                    </span>
                    <div class="whm-mail-value-wrap">
                        <span class="whm-mail-value" dir="ltr">{{ $change['content'] }}</span>
                        <button type="button" class="btn btn-sm whm-copy-email"
                            data-copy="{{ $change['content'] }}"
                            data-copy-msg="تم نسخ قيمة {{ $change['label'] }} إلى الحافظة"
                            title="نسخ" aria-label="نسخ القيمة">
                            <i class="fe fe-copy"></i>
                        </button>
                    </div>
                </div>

                @if(($change['old_proxied'] ?? null) === true)
                    <p class="text-warning small mb-0">
                        <i class="fe fe-cloud me-1"></i>السجل حالياً عبر بروكسي Cloudflare — سيُطفأ لأن البروكسي لا يمرّر SMTP
                    </p>
                @endif

                @if(! empty($change['note']))
                    <p class="text-muted small mb-0">{{ $change['note'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif

@if($extras !== [])
    <div class="whm-section">
        <div class="whm-section-title">سجلات إضافية — لن تُحذف</div>
        <p class="text-muted small mb-2">لا نحذف أي سجل لم نخطّط له. راجع هذه يدوياً إن كانت قديمة.</p>
        <ul class="list-unstyled small mb-0">
            @foreach($extras as $extra)
                <li class="mb-1">
                    <span class="badge bg-light text-dark">{{ $extra['type'] }}</span>
                    <code dir="ltr">{{ $extra['name'] }}</code>
                    → <code dir="ltr">{{ $extra['content'] }}</code>
                    <span class="text-muted">— {{ $extra['reason'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if($skipped !== [])
    <div class="whm-section">
        <div class="whm-section-title">تُرِكت عن قصد</div>
        <ul class="list-unstyled small mb-0">
            @foreach($skipped as $row)
                <li class="mb-1 text-muted">
                    <span class="badge bg-light text-dark">{{ $row['type'] }}</span>
                    <code dir="ltr">{{ $row['name'] }}</code> — {{ $row['reason'] }}
                </li>
            @endforeach
        </ul>
    </div>
@endif
