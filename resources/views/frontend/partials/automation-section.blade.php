@php
    $autoFeatures = [
        [
            'icon' => 'fas fa-robot',
            'title' => 'وكلاء ذكاء اصطناعي',
            'desc' => 'اربط نماذج الذكاء الاصطناعي بمسارات عملك لتحليل الطلبات، تصنيف الرسائل، وصياغة الردود تلقائياً.',
        ],
        [
            'icon' => 'fas fa-plug',
            'title' => 'تكامل مع مئات الخدمات',
            'desc' => 'واتساب، البريد، قواعد البيانات، المتاجر، وأنظمة الفوترة — كلها في مسار واحد بلا كود.',
        ],
        [
            'icon' => 'fas fa-server',
            'title' => 'استضافة ذاتية على سيرفرك',
            'desc' => 'نشغّل n8n داخل حاوية على استضافتك، فتبقى بياناتك ومفاتيحك عندك بالكامل.',
        ],
        [
            'icon' => 'fas fa-clock-rotate-left',
            'title' => 'تشغيل ومراقبة مستمرة',
            'desc' => 'جدولة المهام، إعادة المحاولة عند الفشل، وسجل تنفيذ كامل لكل عملية.',
        ],
    ];
@endphp

<section class="section-padding automation-section" id="automation" aria-labelledby="automation-title">
    <span class="automation-section__glow" aria-hidden="true"></span>

    <div class="container position-relative">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 animate-on-scroll">
                <span class="automation-eyebrow">
                    <i class="fas fa-bolt" aria-hidden="true"></i> الأتمتة والذكاء الاصطناعي
                </span>

                <h2 class="automation-title" id="automation-title">
                    أتمِت أعمالك مع <span class="automation-title__brand">n8n</span> وقوّة الذكاء الاصطناعي
                </h2>

                <p class="automation-lead">
                    بدل المهام المتكررة اليدوية، نبني لك مسارات عمل تعمل وحدها على سيرفرك: تستقبل الطلب،
                    تحلّله بالذكاء الاصطناعي، ترد على العميل، وتحفظ النتيجة — على مدار الساعة وبلا تدخّل.
                </p>

                <ul class="automation-features">
                    @foreach ($autoFeatures as $feature)
                        <li class="automation-feature">
                            <span class="automation-feature__icon" aria-hidden="true">
                                <i class="{{ $feature['icon'] }}"></i>
                            </span>
                            <div>
                                <h3 class="automation-feature__title">{{ $feature['title'] }}</h3>
                                <p class="automation-feature__desc">{{ $feature['desc'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="automation-actions">
                    <a href="{{ route('frontend.contact') }}" class="btn-primary-custom">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i> اطلب أتمتة لعملك
                    </a>
                    <a href="{{ route('frontend.packages') }}" class="btn-outline-custom">
                        <i class="fas fa-server" aria-hidden="true"></i> استضافة تدعم n8n
                    </a>
                </div>
            </div>

            <div class="col-lg-6 animate-on-scroll">
                <div class="automation-visual">
                    <span class="automation-visual__halo" aria-hidden="true"></span>
                    <img src="{{ asset('frontend/assets/images/n8n-automation.svg') }}"
                        alt="مسار عمل في n8n يبدأ من مُشغِّل ويمر بوكيل ذكاء اصطناعي ثم يرسل إشعار واتساب ورسالة بريد ويحفظ البيانات"
                        width="1200" height="820" loading="lazy" decoding="async">
                </div>
            </div>
        </div>
    </div>
</section>
