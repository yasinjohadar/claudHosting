@php
    $index = $index ?? 0;
    $stars = (float) ($stars ?? 5);
    $initial = mb_substr(trim($name ?? ''), 0, 1);
    $isFeatured = ($index % 3) === 1;
@endphp
<article class="testimonial-card glass-panel {{ $isFeatured ? 'testimonial-card--featured' : '' }} animate-on-scroll animate-delay-{{ ($index % 3) + 1 }}">
    <div class="testimonial-card-top">
        <span class="testimonial-quote-icon" aria-hidden="true"><i class="fas fa-quote-right"></i></span>
        <div class="testimonial-stars" aria-label="التقييم {{ $stars }} من 5">
            @for($i = 1; $i <= 5; $i++)
                @if($i <= floor($stars))
                    <i class="fas fa-star"></i>
                @elseif($i - 0.5 <= $stars)
                    <i class="fas fa-star-half-alt"></i>
                @else
                    <i class="far fa-star"></i>
                @endif
            @endfor
        </div>
    </div>
    <blockquote class="testimonial-quote">{{ $quote }}</blockquote>
    <footer class="testimonial-author">
        <div class="testimonial-avatar" aria-hidden="true">{{ $initial }}</div>
        <div class="testimonial-author-text">
            <div class="student-name">{{ $name }}</div>
            <div class="student-role">{{ $role }}</div>
        </div>
    </footer>
</article>
