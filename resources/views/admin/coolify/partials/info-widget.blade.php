{{-- label, desc, icon, accent, rows, highlight, copyText, footerUrl, footerLabel --}}
@php
    $accentClass = 'coolify-accent-' . ($accent ?? 'primary');
@endphp
<div class="coolify-info-widget {{ $accentClass }} {{ $class ?? '' }}">
    <div class="coolify-widget-accent"></div>
    <div class="coolify-widget-body">
        <div class="coolify-widget-top">
            <div>
                <p class="coolify-widget-label">{{ $label }}</p>
                @if(!empty($desc))
                    <p class="coolify-widget-desc">{{ $desc }}</p>
                @endif
            </div>
            <div class="coolify-widget-icon" aria-hidden="true">
                <i class="{{ $icon ?? 'fe fe-info' }}"></i>
            </div>
        </div>

        @if(!empty($highlight))
            <div class="coolify-widget-count" dir="ltr">{{ $highlight }}</div>
        @endif

        @if(!empty($rows))
            <div class="coolify-info-rows">
                @foreach($rows as $row)
                    <div class="coolify-info-row">
                        <span class="coolify-info-row-label">{{ $row['label'] ?? '' }}</span>
                        <span class="coolify-info-row-value {{ !empty($row['mono']) ? 'mono' : '' }}">
                            @if(!empty($row['badge']))
                                <span class="badge bg-{{ $row['badge'] }}-transparent text-{{ $row['badge'] }}">{{ $row['value'] ?? '—' }}</span>
                            @elseif(!empty($row['reachable']))
                                <span class="coolify-reach-pill {{ ($row['value'] === true || $row['value'] === 'نعم') ? 'text-success' : 'text-muted' }}">
                                    @if($row['value'] === true || $row['value'] === 'نعم')
                                        <span class="coolify-pulse"></span>
                                    @endif
                                    {{ is_bool($row['value']) ? ($row['value'] ? 'نعم' : 'لا') : ($row['value'] ?? '—') }}
                                </span>
                            @else
                                {{ $row['value'] ?? '—' }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        @if(!empty($copyText))
            <div class="d-flex align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
                <code class="small text-muted text-truncate flex-grow-1" dir="ltr" title="{{ $copyText }}">{{ $copyText }}</code>
                <button type="button" class="coolify-copy-btn" data-copy="{{ $copyText }}" title="نسخ">
                    <i class="fe fe-copy"></i>
                </button>
            </div>
        @endif

        @if(!empty($footerUrl))
            <a href="{{ $footerUrl }}" class="coolify-widget-foot text-decoration-none d-flex mt-2" style="opacity:1;transform:none;">
                <span>{{ $footerLabel ?? 'المزيد' }}</span>
                <i class="fe fe-arrow-left"></i>
            </a>
        @endif
    </div>
</div>

