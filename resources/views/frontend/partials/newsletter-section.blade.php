@php
    // كمبوننت النشرة البريدية — كل القيم قابلة للتخصيص عند التضمين:
    // @include('frontend.partials.newsletter-section', ['nlTitle' => '...', 'nlDesc' => '...', 'nlBenefits' => [...]])
    $nlId = $nlId ?? 'newsletter-' . Str::random(6);
    $nlTitle = $nlTitle ?? 'اشترك في نشرتنا البريدية';
    $nlDesc =
        $nlDesc ??
        'احصل على آخر أخبار الاستضافة، النصائح التقنية، العروض الحصرية والمقالات مباشرةً في بريدك.';
    $nlEyebrow = $nlEyebrow ?? 'النشرة البريدية';
    $nlButton = $nlButton ?? 'اشترك الآن';
    $nlBenefits = $nlBenefits ?? [
        ['icon' => 'fas fa-lightbulb', 'label' => 'نصائح الاستضافة'],
        ['icon' => 'fas fa-gift', 'label' => 'عروض خاصة'],
        ['icon' => 'fas fa-bell', 'label' => 'أخبار فورية'],
        ['icon' => 'fas fa-envelope-open-text', 'label' => 'رسائل حصرية'],
    ];
@endphp

<section class="newsletter-section" id="newsletter" aria-labelledby="{{ $nlId }}-title">
    <div class="container">
        <div class="newsletter-card animate-on-scroll">
            <span class="newsletter-card__glow" aria-hidden="true"></span>
            <span class="newsletter-card__grid-bg" aria-hidden="true"></span>

            <div class="newsletter-card__inner">
                <div class="newsletter-intro">
                    <span class="newsletter-eyebrow">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> {{ $nlEyebrow }}
                    </span>
                    <h2 class="newsletter-title" id="{{ $nlId }}-title">{{ $nlTitle }}</h2>
                    <p class="newsletter-desc">{{ $nlDesc }}</p>

                    @if (!empty($nlBenefits))
                        <ul class="newsletter-benefits">
                            @foreach ($nlBenefits as $benefit)
                                <li class="newsletter-benefit">
                                    <i class="{{ $benefit['icon'] }}" aria-hidden="true"></i>
                                    {{ $benefit['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="newsletter-panel">
                    <form class="newsletter-form" novalidate>
                        <label class="newsletter-label" for="{{ $nlId }}-email">بريدك الإلكتروني</label>
                        <div class="newsletter-input-wrap">
                            <i class="fas fa-envelope newsletter-input-icon" aria-hidden="true"></i>
                            <input type="email" name="email" id="{{ $nlId }}-email" class="newsletter-input"
                                placeholder="أدخل بريدك الإلكتروني" autocomplete="email" required>
                        </div>
                        <button type="submit" class="newsletter-btn">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i> {{ $nlButton }}
                        </button>
                        <p class="newsletter-hint">
                            <i class="fas fa-shield-halved" aria-hidden="true"></i>
                            تحترم خصوصيتك ولا نشارك بريدك مع أي جهة
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
