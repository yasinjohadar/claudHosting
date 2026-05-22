<a href="{{ $url }}" class="quick-access-link text-decoration-none" aria-label="{{ $title }}">
    <div class="card overflow-hidden custom-card quick-access-card qa-accent-{{ $accent }} h-100">
        <div class="card-body quick-access-body">
            <div class="quick-access-icon-wrap flex-shrink-0">
                <i class="fe {{ $icon }} quick-access-icon-fe"></i>
            </div>
            <div class="quick-access-text flex-grow-1 min-w-0">
                <h6 class="quick-access-title mb-1">{{ $title }}</h6>
                <div class="quick-access-meta">
                    @if(!empty($meta_html))
                        {!! $meta !!}
                    @else
                        <span class="quick-access-stat">{{ $meta }}</span>
                    @endif
                </div>
            </div>
            <span class="quick-access-go flex-shrink-0" aria-hidden="true">
                <i class="fe fe-chevron-left"></i>
            </span>
        </div>
    </div>
</a>
