@php
    $accentClass = 'coolify-accent-' . ($accent ?? 'primary');
    $featured = $featured ?? false;
    $stat = $stat ?? null;
    $statLabel = $statLabel ?? null;
    $tags = $tags ?? [];
    $actions = $actions ?? [];
@endphp
<div class="backup-hub-card {{ $accentClass }} {{ $featured ? 'backup-hub-card--featured' : '' }}">
    <div class="backup-hub-card-accent"></div>
    <div class="backup-hub-card-body">
        <div class="backup-hub-card-top">
            <div>
                <h5 class="backup-hub-card-title">{{ $title }}</h5>
                <p class="backup-hub-card-desc">{{ $desc }}</p>
            </div>
            <div class="backup-hub-card-icon" aria-hidden="true">
                <i class="{{ $icon ?? 'fe fe-box' }}"></i>
            </div>
        </div>
        @if($stat !== null)
            <div>
                <div class="backup-hub-card-stat">{{ is_numeric($stat) ? number_format((int) $stat) : $stat }}</div>
                @if($statLabel)
                    <div class="backup-hub-card-stat-label">{{ $statLabel }}</div>
                @endif
            </div>
        @endif
        @if(count($tags) > 0)
            <div class="backup-hub-card-tags">
                @foreach($tags as $tag)
                    <span class="backup-hub-tag">{{ $tag }}</span>
                @endforeach
            </div>
        @endif
        @if(count($actions) > 0)
            <div class="backup-hub-card-actions">
                @foreach($actions as $action)
                    <a href="{{ $action['href'] }}"
                       class="btn btn-sm {{ $action['class'] ?? 'btn-outline-primary' }}">
                        @if(!empty($action['icon']))
                            <i class="{{ $action['icon'] }} me-1"></i>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
