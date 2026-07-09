@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'مقارنة المجموعات';
    $evoTitle = ($platformMode ?? '') === 'customers'
        ? 'مقارنة عملاء المنصة مع مجموعات WhatsApp'
        : 'مقارنة طلاب المنصة مع مجموعات WhatsApp';
    $evoSubtitle = ($platformMode ?? '') === 'customers'
        ? 'اكتشف العملاء غير المنضمين لمجموعة الواتساب ومراسلتهم للانضمام'
        : 'اكتشف الطلاب غير المنضمين لمجموعة الواتساب ومراسلتهم للانضمام';
    $evoBreadcrumb = 'مقارنة المجموعات';
    $activeTab = $filters['tab'] ?? 'missing';
    $rows = $result ? ($result[$activeTab] ?? []) : [];
@endphp

@section('evo-css')
<style>
    .evo-compare-stats .admin-stats-card { cursor: pointer; text-decoration: none; display: block; color: inherit; }
    .evo-compare-stats .admin-stats-card.is-active { outline: 3px solid rgba(255,255,255,.85); outline-offset: 2px; transform: translateY(-4px); }
    .evo-compare-tabs-wrap {
        background: var(--custom-white, #fff);
        border-radius: 16px;
        padding: .5rem;
        box-shadow: 0 4px 18px rgba(0,0,0,.06);
        border: 1px solid var(--default-border, rgba(0,0,0,.06));
    }
    .evo-compare-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin: 0; padding: 0; list-style: none; }
    .evo-compare-tabs__link {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .65rem 1.1rem; border-radius: 12px;
        font-weight: 600; font-size: .875rem;
        color: var(--default-text-color, #495057);
        background: var(--light, #f8f9fa);
        border: 1px solid transparent;
        transition: all .2s ease;
        text-decoration: none;
    }
    .evo-compare-tabs__link:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.08); color: inherit; }
    .evo-compare-tabs__link .evo-tab-count {
        min-width: 1.75rem; height: 1.75rem; padding: 0 .45rem;
        border-radius: 8px; font-size: .75rem; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .evo-compare-tabs__link--missing.is-active { background: linear-gradient(135deg, #e53935, #ef5350); color: #fff; box-shadow: 0 6px 16px rgba(229,57,53,.35); }
    .evo-compare-tabs__link--missing.is-active .evo-tab-count { background: rgba(255,255,255,.25); color: #fff; }
    .evo-compare-tabs__link--matched.is-active { background: linear-gradient(135deg, #00b09b, #96c93d); color: #fff; box-shadow: 0 6px 16px rgba(0,176,155,.35); }
    .evo-compare-tabs__link--matched.is-active .evo-tab-count { background: rgba(255,255,255,.25); color: #fff; }
    .evo-compare-tabs__link--wa_only.is-active { background: linear-gradient(135deg, #ff9a44, #ffc107); color: #1a1200; box-shadow: 0 6px 16px rgba(255,154,68,.35); }
    .evo-compare-tabs__link--wa_only.is-active .evo-tab-count { background: rgba(0,0,0,.12); color: #1a1200; }
    .evo-compare-tabs__link--no_phone.is-active { background: linear-gradient(135deg, #6b7280, #9ca3af); color: #fff; box-shadow: 0 6px 16px rgba(107,114,128,.35); }
    .evo-compare-tabs__link--no_phone.is-active .evo-tab-count { background: rgba(255,255,255,.25); color: #fff; }
    .evo-compare-tabs__link--missing:not(.is-active) .evo-tab-count { background: rgba(229,57,53,.12); color: #e53935; }
    .evo-compare-tabs__link--matched:not(.is-active) .evo-tab-count { background: rgba(0,176,155,.12); color: #00b09b; }
    .evo-compare-tabs__link--wa_only:not(.is-active) .evo-tab-count { background: rgba(255,193,7,.18); color: #c68400; }
    .evo-compare-tabs__link--no_phone:not(.is-active) .evo-tab-count { background: rgba(107,114,128,.12); color: #6b7280; }
    .evo-compare-context {
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(25,135,84,.06), rgba(13,110,253,.04));
        border: 1px solid rgba(25,135,84,.12);
        padding: 1rem 1.25rem;
    }
</style>
@endsection

@section('evo-content')
@if($waError)
    @include('admin.pages.evolution-api.partials.api-error', ['error' => $waError, 'errorHint' => 'تحقق من اتصال Evolution API.'])
@endif
@if($compareError)
    @include('admin.pages.evolution-api.partials.api-error', ['error' => $compareError])
@endif

<div class="card custom-card group-show-members-card border-0 shadow-sm mb-4">
    <div class="card-header border-0 pb-0">
        <div class="card-title mb-0"><i class="ri-git-merge-line me-2 text-success"></i>إعدادات المقارنة</div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.evolution-api.groups.compare') }}" id="evo-compare-form">
            <div class="row g-3">
                @if(($platformMode ?? '') !== 'customers' && ($courses ?? collect())->isNotEmpty())
                <div class="col-md-6">
                    <label class="form-label fw-semibold">مصدر طلاب المنصة</label>
                    <select name="scope" class="form-select" id="compare-scope">
                        <option value="group" @selected(($filters['scope'] ?? '') === 'group')>مجموعة المنصة فقط</option>
                        <option value="course" @selected(($filters['scope'] ?? '') === 'course')>كورس فقط</option>
                        <option value="both" @selected(($filters['scope'] ?? '') === 'both')>مجموعة + كورس (التقاطع)</option>
                        <option value="either" @selected(($filters['scope'] ?? '') === 'either')>مجموعة أو كورس (الاتحاد)</option>
                    </select>
                </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label fw-semibold">مجموعة WhatsApp <span class="text-danger">*</span></label>
                    <select name="whatsapp_jid" class="form-select" required>
                        <option value="">— اختر مجموعة واتساب —</option>
                        @foreach($whatsappGroups as $wg)
                            @php $wjid = $wg['id'] ?? $wg['jid'] ?? ''; @endphp
                            <option value="{{ $wjid }}" @selected(($filters['whatsapp_jid'] ?? '') === $wjid)>
                                {{ $wg['subject'] ?? $wg['name'] ?? $wjid }}
                                ({{ $wg['size'] ?? '?' }} عضو)
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(($platformMode ?? '') !== 'customers' && ($courses ?? collect())->isNotEmpty())
                <div class="col-md-6" id="field-platform-group">
                    <label class="form-label fw-semibold">مجموعة المنصة (CourseGroup)</label>
                    <select name="platform_group_id" class="form-select">
                        <option value="">— لا شيء —</option>
                        @foreach($platformGroups as $pg)
                            <option value="{{ $pg->id }}" @selected(($filters['platform_group_id'] ?? null) == $pg->id)>{{ $pg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6" id="field-course">
                    <label class="form-label fw-semibold">الكورس</label>
                    <select name="course_id" class="form-select">
                        <option value="">— لا شيء —</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(($filters['course_id'] ?? null) == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="active_only" value="1" id="active_only"
                               @checked($filters['active_only'] ?? true)>
                        <label class="form-check-label" for="active_only">
                            {{ ($platformMode ?? '') === 'customers' ? 'عملاء نشطون فقط (Active)' : 'طلاب بحالة تسجيل نشطة في الكورس فقط' }}
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-success">
                    <i class="ri-search-line me-1"></i> تشغيل المقارنة
                </button>
                @if($result)
                    <a href="{{ route('admin.evolution-api.groups.compare.export', array_merge($filters, ['tab' => $activeTab])) }}"
                       class="btn btn-outline-secondary">
                        <i class="ri-download-line me-1"></i> تصدير CSV
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($result && $waGroupInfo)
    @php
        $statCards = [
            ['key' => null, 'label' => ($platformMode ?? '') === 'customers' ? 'عملاء المنصة' : 'طلاب المنصة', 'value' => $result['stats']['platform_total'], 'variant' => 'blue', 'icon' => 'ri-graduation-cap-line', 'sub' => 'المحددون في المنصة'],
            ['key' => null, 'label' => 'أعضاء WA', 'value' => $result['stats']['wa_total'], 'variant' => 'cyan', 'icon' => 'ri-whatsapp-line', 'sub' => 'في مجموعة الواتساب'],
            ['key' => 'matched', 'label' => 'متطابقون', 'value' => $result['stats']['matched'], 'variant' => 'green', 'icon' => 'ri-checkbox-circle-line', 'sub' => 'في المنصة وWA'],
            ['key' => 'missing', 'label' => 'غير منضمين WA', 'value' => $result['stats']['missing'], 'variant' => 'red', 'icon' => 'ri-user-unfollow-line', 'sub' => 'يحتاجون دعوة'],
            ['key' => 'wa_only', 'label' => 'في WA فقط', 'value' => $result['stats']['wa_only'], 'variant' => 'orange', 'icon' => 'ri-group-line', 'sub' => 'ليسوا في اختيار المنصة'],
            ['key' => 'no_phone', 'label' => 'بدون رقم صالح', 'value' => $result['stats']['no_phone'], 'variant' => 'silver', 'icon' => 'ri-phone-lock-line', 'sub' => 'لا يمكن مطابقتهم'],
        ];
    @endphp
    <div class="row g-3 mb-4 evo-compare-stats dashboard-fade-in">
        @foreach($statCards as $index => $card)
            @php
                $cardHref = $card['key']
                    ? route('admin.evolution-api.groups.compare', array_merge($filters, ['tab' => $card['key']]))
                    : null;
                $tag = $cardHref ? 'a' : 'div';
            @endphp
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 dashboard-stagger-item" style="--stagger-delay: {{ $index * 60 }}ms">
                <{{ $tag }}
                    @if($cardHref) href="{{ $cardHref }}" @endif
                    class="card admin-stats-card admin-stats-card--{{ $card['variant'] }} h-100 {{ $card['key'] && $activeTab === $card['key'] ? 'is-active' : '' }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="admin-stats-card__icon-wrap">
                            <i class="{{ $card['icon'] }} admin-stats-card__icon"></i>
                        </div>
                        <div class="admin-stats-card__content flex-fill min-w-0">
                            <p class="admin-stats-card__label mb-1">{{ $card['label'] }}</p>
                            <h3 class="admin-stats-card__value mb-1">{{ $card['value'] }}</h3>
                            <p class="admin-stats-card__sub mb-0">{{ $card['sub'] }}</p>
                        </div>
                    </div>
                </{{ $tag }}>
            </div>
        @endforeach
    </div>

    <div class="evo-compare-context mb-4 small">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-success-transparent text-success"><i class="ri-whatsapp-line me-1"></i>{{ $waGroupInfo['name'] ?? '—' }}</span>
            <code class="small">{{ $waGroupInfo['jid'] ?? $filters['whatsapp_jid'] }}</code>
            @if($labels['platform_group'] ?? null)
                <span class="text-muted">·</span>
                <span><i class="ri-team-line me-1 text-primary"></i><strong>منصة:</strong> {{ $labels['platform_group'] }}</span>
            @endif
            @if($labels['course'] ?? null)
                <span class="text-muted">·</span>
                <span><i class="ri-book-open-line me-1 text-info"></i><strong>كورس:</strong> {{ $labels['course'] }}</span>
            @endif
        </div>
    </div>

    <div class="evo-compare-tabs-wrap mb-4">
        <ul class="evo-compare-tabs">
            @foreach([
                'missing' => ['label' => 'غير منضمين WA', 'count' => $result['stats']['missing'], 'icon' => 'ri-user-unfollow-line'],
                'matched' => ['label' => 'متطابقون', 'count' => $result['stats']['matched'], 'icon' => 'ri-checkbox-circle-line'],
                'wa_only' => ['label' => 'في WA فقط', 'count' => $result['stats']['wa_only'], 'icon' => 'ri-group-2-line'],
                'no_phone' => ['label' => 'بدون رقم', 'count' => $result['stats']['no_phone'], 'icon' => 'ri-phone-lock-line'],
            ] as $tabKey => $tab)
                <li>
                    <a class="evo-compare-tabs__link evo-compare-tabs__link--{{ $tabKey }} {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                       href="{{ route('admin.evolution-api.groups.compare', array_merge($filters, ['tab' => $tabKey])) }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="evo-tab-count">{{ $tab['count'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @if($activeTab === 'missing' && count($result['missing']) > 0)
        <form method="POST" action="{{ route('admin.evolution-api.groups.compare.message-missing') }}" class="card custom-card border-0 shadow-sm mb-3">
            @csrf
            <input type="hidden" name="scope" value="{{ $filters['scope'] ?? 'group' }}">
            <input type="hidden" name="course_id" value="{{ $filters['course_id'] ?? '' }}">
            <input type="hidden" name="platform_group_id" value="{{ $filters['platform_group_id'] ?? '' }}">
            <input type="hidden" name="whatsapp_jid" value="{{ $filters['whatsapp_jid'] ?? '' }}">
            <input type="hidden" name="whatsapp_group_name" value="{{ $waGroupInfo['name'] ?? '' }}">
            <input type="hidden" name="active_only" value="{{ ($filters['active_only'] ?? true) ? '1' : '0' }}">
            <div class="card-header border-0 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="card-title mb-0"><i class="ri-send-plane-line me-1 text-success"></i> رسالة جماعية للغير منضمين</div>
                <a href="{{ route('admin.evolution-api.groups.compare.campaigns') }}" class="btn btn-sm btn-outline-primary">
                    <i class="ri-file-list-3-line me-1"></i> تقارير الإرسال
                </a>
            </div>
            <div class="card-body">
                <textarea name="text" class="form-control mb-2" rows="3" required placeholder="مرحباً {student_name}، يرجى الانضمام لمجموعة الواتساب..."></textarea>
                <div class="alert alert-light border small mb-3 py-2">
                    <strong><i class="ri-time-line me-1"></i> الفواصل الزمنية:</strong>
                    {{ $delaySettings['delay_between_messages'] ?? 4 }} ث بين كل رسالة
                    @if(!empty($delaySettings['random_delay_enabled']))
                        + تفاوت عشوائي {{ $delaySettings['min_delay'] }}–{{ $delaySettings['max_delay'] }} ث
                    @else
                        (التفاوت العشوائي غير مفعّل)
                    @endif
                    · الرسالة الأولى فوراً والباقي عبر الطابور.
                    @if(($queuePendingCount ?? 0) > 0)
                        <span class="text-warning d-block mt-1"><i class="ri-alert-line"></i> يوجد {{ $queuePendingCount }} مهمة في الطابور — شغّل <code>php artisan queue:work</code></span>
                    @endif
                </div>
                <div class="form-text mb-3">
                    استخدم <code>{student_name}</code> · بعد الإرسال ستُوجَّه ل<strong>تقرير مفصّل</strong> يعرض حالة كل مستلم.
                    <a href="{{ route('admin.whatsapp-settings.index') }}#delay-settings">تعديل الفواصل</a>
                </div>
                <button type="submit" class="btn btn-success" id="evo-bulk-send-btn" disabled>
                    <i class="ri-send-plane-line me-1"></i> بدء الإرسال للمحددين (<span id="evo-selected-count">0</span>)
                </button>
            </div>
        </form>
    @endif

    <div class="card custom-card group-show-members-card border-0 shadow-sm">
        <div class="card-body p-0">
            @include('admin.pages.evolution-api.groups.partials.compare-results-table', [
                'rows' => $rows,
                'activeTab' => $activeTab,
            ])
        </div>
    </div>
@elseif($filters['whatsapp_jid'] ?? null)
    <div class="alert alert-info border-0 shadow-sm">لا توجد نتائج أو فشلت المقارنة.</div>
@else
    <div class="alert alert-light border text-center py-5">
        <i class="ri-git-merge-line fs-48 text-muted opacity-50 d-block mb-2"></i>
        <p class="text-muted mb-0">اختر مصدر الطلاب ومجموعة WhatsApp ثم اضغط «تشغيل المقارنة»</p>
    </div>
@endif
@endsection

@push('evo-scripts')
<script>
(function () {
    const scope = document.getElementById('compare-scope');
    const fieldGroup = document.getElementById('field-platform-group');
    const fieldCourse = document.getElementById('field-course');

    function toggleFields() {
        const v = scope?.value || 'group';
        if (fieldGroup) fieldGroup.style.display = ['group', 'both', 'either'].includes(v) ? '' : 'none';
        if (fieldCourse) fieldCourse.style.display = ['course', 'both', 'either'].includes(v) ? '' : 'none';
    }
    scope?.addEventListener('change', toggleFields);
    toggleFields();

    const bulkForm = document.querySelector('form[action*="compare/message-missing"]');
    const checkboxes = document.querySelectorAll('.evo-compare-check');
    const countEl = document.getElementById('evo-selected-count');
    const bulkBtn = document.getElementById('evo-bulk-send-btn');
    const selectAll = document.getElementById('evo-compare-select-all');

    function updateBulk() {
        const checked = document.querySelectorAll('.evo-compare-check:checked');
        if (countEl) countEl.textContent = checked.length;
        if (bulkBtn) bulkBtn.disabled = checked.length === 0;
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulk));
    selectAll?.addEventListener('change', function () {
        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
        updateBulk();
    });

    bulkForm?.addEventListener('submit', function () {
        bulkForm.querySelectorAll('input[name="user_ids[]"]').forEach(el => el.remove());
        document.querySelectorAll('.evo-compare-check:checked').forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = cb.value;
            bulkForm.appendChild(input);
        });
    });
})();
</script>
@endpush
