@php
    $data = is_array($data ?? null) ? $data : [];
    $domains = $data['domains'] ?? [];
    $server = $data['server'] ?? ['hostname' => null, 'ip' => null, 'ptr' => null, 'ptr_state' => 'unknown'];
    $warnings = $data['warnings'] ?? [];
    $typeLabels = ['main' => 'رئيسي', 'addon' => 'إضافي', 'sub' => 'فرعي', 'parked' => 'موقوف'];
    $overallBadges = ['ok' => 'bg-success-transparent', 'problem' => 'bg-danger-transparent'];
    $overallLabels = ['ok' => 'سليم', 'problem' => 'يحتاج إصلاح'];
@endphp

@if(!($data['configured'] ?? false))
    <div class="alert alert-warning py-2 px-3 small mb-0">
        {{ $data['message'] ?? 'إعدادات WHM غير مكتملة' }}
    </div>
@elseif(empty($domains))
    <div class="text-center py-4">
        <i class="fe fe-mail fs-2 text-muted opacity-50 d-block mb-2"></i>
        <p class="text-muted small mb-0">{{ $data['message'] ?? 'لا بيانات بريد لعرضها' }}</p>
    </div>
@else
    @foreach($warnings as $warning)
        <div class="alert alert-warning py-2 px-3 small mb-2">{{ $warning }}</div>
    @endforeach

    <div class="whm-section">
        <div class="whm-section-title">إعدادات السيرفر</div>
        <div class="row g-2">
            @php
                $serverTiles = [
                    ['label' => 'اسم المضيف (Mail HELO)', 'value' => $server['hostname'], 'icon' => 'fe-server', 'color' => 'primary'],
                    ['label' => 'عنوان IP', 'value' => $server['ip'], 'icon' => 'fe-globe', 'color' => 'info'],
                    ['label' => 'السجل العكسي (PTR)', 'value' => $server['ptr'], 'icon' => 'fe-corner-down-left', 'color' => ($server['ptr_state'] ?? 'unknown') === 'ok' ? 'success' : (($server['ptr_state'] ?? '') === 'problem' ? 'danger' : 'secondary')],
                ];
            @endphp
            @foreach($serverTiles as $tile)
            <div class="col-md-4">
                <div class="whm-stat-tile">
                    <span class="whm-stat-icon bg-{{ $tile['color'] }}-transparent text-{{ $tile['color'] }}">
                        <i class="fe {{ $tile['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <span class="whm-stat-label">{{ $tile['label'] }}</span>
                        @if(!empty($tile['value']))
                        <div class="whm-mail-value-wrap">
                            <span class="whm-mail-value" dir="ltr">{{ $tile['value'] }}</span>
                            <button type="button" class="btn btn-sm whm-copy-email"
                                data-copy="{{ $tile['value'] }}"
                                data-copy-msg="تم نسخ {{ $tile['label'] }} إلى الحافظة"
                                title="نسخ" aria-label="نسخ {{ $tile['label'] }}">
                                <i class="fe fe-copy"></i>
                            </button>
                        </div>
                        @else
                            <span class="whm-stat-value text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="whm-section">
        <div class="whm-section-title">النطاقات ({{ count($domains) }})</div>
        @foreach($domains as $domain)
        <div class="whm-mail-domain-card">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                <code class="fw-semibold" dir="ltr">{{ $domain['domain'] }}</code>
                @if(isset($typeLabels[$domain['type']]))
                    <span class="whm-meta-chip">{{ $typeLabels[$domain['type']] }}</span>
                @endif
                @if(isset($overallLabels[$domain['overall']]))
                    <span class="badge {{ $overallBadges[$domain['overall']] }}">{{ $overallLabels[$domain['overall']] }}</span>
                @endif
                <button type="button" class="btn btn-sm whm-copy-email ms-auto"
                    data-copy="{{ $domain['domain'] }}"
                    data-copy-msg="تم نسخ النطاق إلى الحافظة"
                    title="نسخ النطاق" aria-label="نسخ النطاق">
                    <i class="fe fe-copy"></i>
                </button>
            </div>

            @if(!empty($domain['message']))
                <p class="text-muted small mb-2">{{ $domain['message'] }}</p>
            @endif

            @forelse($domain['checks'] as $check)
                @include('admin.whm.accounts.partials.email-deliverability-row', ['check' => $check])
            @empty
                <p class="text-muted small mb-0">لا فحوصات متاحة لهذا النطاق</p>
            @endforelse
        </div>
        @endforeach
    </div>
@endif
