@php
    $aboutFacts = [
        [
            'icon' => 'fas fa-server',
            'value' => '+200',
            'label' => 'موقع مستضاف',
            'accent' => '#0057B8',
        ],
        [
            'icon' => 'fas fa-users',
            'value' => '+500',
            'label' => 'عميل نشط',
            'accent' => '#2E9AD0',
        ],
        [
            'icon' => 'fas fa-cloud',
            'value' => null,
            'label' => 'بنية سحابية موزعة',
            'accent' => '#6366f1',
        ],
        [
            'icon' => 'fas fa-shield-halved',
            'value' => null,
            'label' => 'حماية متقدمة ونسخ احتياطي',
            'accent' => '#10b981',
        ],
    ];
@endphp

<section class="about-intro section-padding" id="about-intro">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="about-visual animate-on-scroll">
                    <div class="about-visual__glow" aria-hidden="true"></div>
                    <div class="about-visual__ring" aria-hidden="true"></div>
                    <div class="about-visual__frame">
                        <div class="about-visual__picture" role="img"
                            aria-label="بنية سحابية لاستضافة المواقع — خوادم، سحابة، وأمان">
                            <img src="{{ asset('frontend/assets/images/hero-light.webp') }}"
                                alt=""
                                class="about-visual__img about-visual__img--theme-light"
                                width="520"
                                height="520"
                                loading="lazy"
                                decoding="async">
                            <img src="{{ asset('frontend/assets/images/hero-dark.webp') }}"
                                alt=""
                                class="about-visual__img about-visual__img--theme-dark"
                                width="520"
                                height="520"
                                loading="lazy"
                                decoding="async">
                        </div>
                    </div>
                    <div class="about-visual__float about-visual__float--ssl" aria-hidden="true">
                        <i class="fas fa-lock"></i>
                        <span>SSL مجاني</span>
                    </div>
                    <div class="about-visual__float about-visual__float--support" aria-hidden="true">
                        <i class="fas fa-headset"></i>
                        <span>دعم 24/7</span>
                    </div>
                    <div class="about-visual__float about-visual__float--uptime" aria-hidden="true">
                        <i class="fas fa-chart-line"></i>
                        <span>99.9% جاهزية</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="about-intro__content animate-on-scroll">
                    <span class="section-badge">من نحن؟</span>
                    <h2 class="about-intro__title">استضافة كلاودسوفت CloudSoft Hosting</h2>
                    <p class="about-intro__text">
                        استضافة كلاودسوفت هي منصة استضافة مواقع سحابية تم إطلاقها لتوفير بيئة آمنة وسريعة للمشاريع
                        العربية، مع تركيز خاص على الاستقرار وسهولة الإدارة. نعتمد بنية تحتية سحابية حديثة مع تقنيات
                        التكرار والنسخ الاحتياطي المستمر.
                    </p>
                    <p class="about-intro__text">
                        هدفنا أن نمنح أصحاب المواقع والمتاجر تجربة استضافة خالية من التعقيد؛ نعتني نحن بالخوادم،
                        الأمان، والنسخ الاحتياطي، لتتفرغ أنت لبناء مشروعك ونموّ عملك. نقدّم باقات مرنة تناسب
                        المشاريع الصغيرة والمتوسطة والشركات، مع إمكانية تخصيص الحلول عند الحاجة.
                    </p>

                    <div class="row g-3 about-facts">
                        @foreach ($aboutFacts as $i => $fact)
                            <div class="col-sm-6">
                                <article class="about-fact-card animate-on-scroll animate-delay-{{ ($i % 4) + 1 }}"
                                    style="--fact-accent: {{ $fact['accent'] }}"
                                    tabindex="0"
                                    role="group"
                                    aria-label="{{ $fact['label'] }}">
                                    <div class="about-fact-card__icon" aria-hidden="true">
                                        <i class="{{ $fact['icon'] }}"></i>
                                    </div>
                                    <div class="about-fact-card__body">
                                        @if (! empty($fact['value']))
                                            <strong class="about-fact-card__value">{{ $fact['value'] }}</strong>
                                        @endif
                                        <span class="about-fact-card__label">{{ $fact['label'] }}</span>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
