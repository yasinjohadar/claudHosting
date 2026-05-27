@extends('frontend.layouts.master')

@section('page-title')
حجز موعد واستشارة تقنية | استضافة كلاودسوفت
@endsection

@section('meta-description')
احجز جلستك الاستشارية مع فريق كلاودسوفت — نقاش مباشر حول مشروعك، الاستضافة، المسار المهني أو أي سؤال تقني. نرتب معك الموعد المناسب.
@endsection

@section('content')
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-calendar-check"></i></div>
                <h1 class="page-banner-title">حجز موعد <span>واستشارة تقنية</span></h1>
                <p class="page-banner-desc">احجز جلستك الاستشارية — نقاش مباشر حول مشروعك، مسارك المهني أو أي سؤال تقني</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <a href="{{ route('frontend.about') }}#specialties">الخدمات</a>
                    <span class="page-banner-sep">/</span>
                    <span>حجز موعد واستشارة</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <section class="section-padding security-intro-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="service-detail-intro animate-on-scroll">
                        <span class="section-badge">استشارة مخصصة</span>
                        <h2 class="service-detail-heading">استشارة تقنية واضحة ومباشرة لمشروعك</h2>
                        <p class="service-detail-lead">
                            جلسة واحدة أو أكثر (أونلاين أو حسب الاتفاق) نناقش فيها مشروعك، فكرتك، أو مسارك المهني في البرمجة وتطوير الويب والموبايل.
                            نساعدك في اختيار التقنيات المناسبة، مراجعة الكود، وضع خطة تنفيذ، أو الإجابة عن أي تحدٍ تقني.
                        </p>
                        <p class="service-detail-text">
                            المدة المعتادة بين 30 دقيقة وساعة حسب نوع الاستشارة. بعد إرسال النموذج سنتواصل معك لتأكيد الموعد والطريقة
                            (Google Meet، Zoom، Teams، أو واتساب) مع ملخص واضح لما سنغطيه في الجلسة.
                        </p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-panel service-detail-feature-list animate-on-scroll">
                        <h4 class="service-detail-feature-list-title"><i class="fas fa-check-circle"></i> ماذا يمكن أن نناقش؟</h4>
                        <ul class="service-detail-feature-list-ul">
                            <li><i class="fas fa-chevron-left"></i>اختيار التقنيات المناسبة لمشروعك</li>
                            <li><i class="fas fa-chevron-left"></i>مراجعة فكرة المشروع وخارطة التنفيذ</li>
                            <li><i class="fas fa-chevron-left"></i>تحسين الأداء أو حل مشاكل تقنية معقدة</li>
                            <li><i class="fas fa-chevron-left"></i>مراجعة كود وهيكلية المشروع</li>
                            <li><i class="fas fa-chevron-left"></i>تخطيط مسار تعلم عملي حسب هدفك</li>
                            <li><i class="fas fa-chevron-left"></i>استشارة تقنية للفِرق والشركات</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">أنواع الجلسات</span>
                <h2>اختر نوع الاستشارة المناسب</h2>
                <p>يمكنك في النموذج اختيار النوع الذي يناسبك أو كتابة طلب مخصص</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-type-card animate-on-scroll text-center">
                        <div class="consultation-type-icon mb-3"><i class="fas fa-bolt"></i></div>
                        <h6 class="mb-2">استشارة سريعة</h6>
                        <p>حوالي 30 دقيقة — سؤال محدد أو اختيار تقنية سريع</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-type-card animate-on-scroll text-center">
                        <div class="consultation-type-icon mb-3"><i class="fas fa-comments"></i></div>
                        <h6 class="mb-2">استشارة معمقة</h6>
                        <p>حوالي 60 دقيقة — نقاش مشروع أو مسار كامل</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-type-card animate-on-scroll text-center">
                        <div class="consultation-type-icon mb-3"><i class="fas fa-code"></i></div>
                        <h6 class="mb-2">مراجعة مشروع / كود</h6>
                        <p>مراجعة كود أو هيكل مشروع وتقديم توصيات عملية</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-type-card animate-on-scroll text-center">
                        <div class="consultation-type-icon mb-3"><i class="fas fa-road"></i></div>
                        <h6 class="mb-2">تخطيط مسار تعلم</h6>
                        <p>وضع خطة دراسية واضحة حسب هدفك ووقتك</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">كيف تحجز</span>
                <h2>خطوات الحجز</h2>
                <p>من النموذج حتى الجلسة — عملية بسيطة وواضحة</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-step animate-on-scroll text-center">
                        <span class="consultation-step-num">1</span>
                        <h6 class="mt-2 mb-1">املأ النموذج</h6>
                        <p>اختر نوع الاستشارة، التاريخ والوقت المناسبين واكتب ملخصاً لموضوعك</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-step animate-on-scroll text-center">
                        <span class="consultation-step-num">2</span>
                        <h6 class="mt-2 mb-1">مراجعة الطلب</h6>
                        <p>نراجع طلبك ونتواصل خلال 24-48 ساعة لتأكيد الموعد أو اقتراح بديل</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-step animate-on-scroll text-center">
                        <span class="consultation-step-num">3</span>
                        <h6 class="mt-2 mb-1">تأكيد الموعد</h6>
                        <p>نرسل لك رابط المكالمة أو نحدد طريقة التواصل المناسبة</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="glass-panel service-offer-card consultation-step animate-on-scroll text-center">
                        <span class="consultation-step-num">4</span>
                        <h6 class="mt-2 mb-1">الجلسة</h6>
                        <p>نلتقي في الموعد المحدد مع توصيات وخطوات واضحة بعد الجلسة</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" id="booking-form" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">احجز الآن</span>
                <h2>نموذج حجز الموعد</h2>
                <p>املأ البيانات وسنتواصل معك لتأكيد الموعد وتفاصيل الجلسة</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-panel consultation-form-wrapper animate-on-scroll p-4 p-lg-5">
                        <form id="consultationForm" action="https://formspree.io/f/YOUR_FORM_ID" method="POST">
                            <input type="hidden" name="_subject" value="طلب حجز موعد استشارة تقنية - استضافة كلاودسوفت">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="الاسم الكامل" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" name="_replyto" class="form-control" placeholder="example@email.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">رقم الهاتف / واتساب</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+963 XXX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">نوع الاستشارة <span class="text-danger">*</span></label>
                                    <select class="form-select" name="consultation_type" required>
                                        <option value="" disabled selected>اختر النوع</option>
                                        <option value="quick">استشارة سريعة (30 دقيقة)</option>
                                        <option value="deep">استشارة معمقة (60 دقيقة)</option>
                                        <option value="code_review">مراجعة مشروع / كود</option>
                                        <option value="learning_path">تخطيط مسار تعلم</option>
                                        <option value="other">أخرى (أوضح في الوصف)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">التاريخ المفضل</label>
                                    <input type="date" name="preferred_date" class="form-control" min="">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">الوقت المفضل</label>
                                    <select class="form-select" name="preferred_time">
                                        <option value="" selected>اختر فترة</option>
                                        <option value="morning">صباحاً (9 ص - 12 م)</option>
                                        <option value="afternoon">بعد الظهر (12 - 4 م)</option>
                                        <option value="evening">مساءً (4 - 8 م)</option>
                                        <option value="flexible">مرن حسب توفرك</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">موضوع الاستشارة / وصف مختصر <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="topic" rows="4" placeholder="اشرح باختصار ما تريد مناقشته أو السؤال عنه في الجلسة..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">ملاحظات إضافية</label>
                                    <textarea class="form-control" name="notes" rows="2" placeholder="أي تفاصيل إضافية أو طريقة تواصل مفضلة (زوم، تيمز، واتساب...)"></textarea>
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn-primary-custom w-100" style="justify-content:center;"><i class="fas fa-calendar-check"></i> إرسال طلب الحجز</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">أسئلة شائعة</span>
                <h2>كل ما تحتاج معرفته</h2>
                <p>إجابات سريعة على أهم الأسئلة قبل الحجز</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion consultation-accordion animate-on-scroll glass-panel p-3" id="consultationFaq">
                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">كيف تتم الجلسة — أونلاين أم حضورياً؟</button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#consultationFaq">
                                <div class="accordion-body text-secondary">غالباً تكون الجلسة أونلاين عبر مكالمة فيديو (زوم، Google Meet، تيمز أو واتساب). إن أردت لقاءً حضورياً يمكن ذكر ذلك في الملاحظات وسنرى إمكانية ترتيبه حسب الموقع.</div>
                            </div>
                        </div>
                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">ما مدة الانتظار حتى تأكيد الموعد؟</button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#consultationFaq">
                                <div class="accordion-body text-secondary">أحاول الرد على طلبات الحجز خلال 24–48 ساعة. إن كان الطلب في عطلة نهاية الأسبوع قد يطول قليلاً.</div>
                            </div>
                        </div>
                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">ماذا أجهز قبل الجلسة؟</button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#consultationFaq">
                                <div class="accordion-body text-secondary">حدد أسئلتك أو نقاط النقاش مسبقاً. إن كانت الاستشارة عن مشروع أو كود، أرسل رابط المستودع أو ملفات ذات صلة قبل الموعد إن أمكن. تأكد من اتصال إنترنت مستقر وبيئة هادئة.</div>
                            </div>
                        </div>
                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">هل يمكن إلغاء أو تأجيل الموعد؟</button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#consultationFaq">
                                <div class="accordion-body text-secondary">نعم. يُفضّل إبلاغي قبل الموعد بـ 24 ساعة على الأقل إن أردت الإلغاء أو التأجيل، وسنحدد موعداً بديلاً.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding service-related-section" style="background: var(--clr-bg-secondary);">
        <div class="container">
            <div class="section-header animate-on-scroll">
                <span class="section-badge">خدمات مرتبطة</span>
                <h2>مجالات تساعدك بعد الاستشارة</h2>
                <p>يمكننا تنفيذ التوصيات مباشرة عبر خدماتنا التقنية</p>
            </div>
            <div class="row g-4">
                @php
                    $relatedServices = [
                        [
                            'url' => route('frontend.service-detail-web'),
                            'icon' => 'fas fa-globe',
                            'title' => 'تطوير تطبيقات الويب',
                            'desc' => 'تحويل الفكرة إلى منصة جاهزة للإطلاق',
                            'accent' => '#0057B8',
                        ],
                        [
                            'url' => route('frontend.service-detail-mobile'),
                            'icon' => 'fas fa-mobile-alt',
                            'title' => 'تطبيقات الجوال',
                            'desc' => 'تطبيقات Android و iOS بمعايير حديثة',
                            'accent' => '#2E9AD0',
                        ],
                        [
                            'url' => route('frontend.service-detail-servers'),
                            'icon' => 'fas fa-server',
                            'title' => 'إدارة السيرفرات',
                            'desc' => 'تشغيل مستقر وآمن للبنية التحتية',
                            'accent' => '#10b981',
                        ],
                        [
                            'url' => route('frontend.service-detail-security'),
                            'icon' => 'fas fa-shield-alt',
                            'title' => 'أمن المعلومات',
                            'desc' => 'تقوية الحماية وتقليل المخاطر الأمنية',
                            'accent' => '#6366f1',
                        ],
                    ];
                @endphp
                @foreach ($relatedServices as $i => $service)
                    <div class="col-sm-6 col-lg-3">
                        <a href="{{ $service['url'] }}"
                            class="service-related-card animate-on-scroll animate-delay-{{ ($i % 4) + 1 }}"
                            style="--related-accent: {{ $service['accent'] }}">
                            <span class="service-related-card__icon" aria-hidden="true">
                                <i class="{{ $service['icon'] }}"></i>
                            </span>
                            <h6>{{ $service['title'] }}</h6>
                            <p>{{ $service['desc'] }}</p>
                            <span class="service-related-card__link">
                                اعرف المزيد <i class="fas fa-arrow-left"></i>
                            </span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>جاهز لحجز استشارتك؟</h2>
            <p>يمكنك تعبئة النموذج أو التواصل المباشر عبر صفحة التواصل لمناقشة التفاصيل بسرعة</p>
            <a href="#booking-form" class="btn-light-custom me-2"><i class="fas fa-calendar-check"></i> احجز الآن</a>
            <a href="{{ route('frontend.contact') }}" class="btn-primary-custom"><i class="fas fa-paper-plane"></i> تواصل معنا</a>
        </div>
    </section>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateInput = document.querySelector('input[name="preferred_date"]');
    if (dateInput) dateInput.setAttribute('min', new Date().toISOString().split('T')[0]);
    var form = document.getElementById('consultationForm');
    if (form) form.addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = this.querySelector('button[type="submit"]');
        var originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
        try {
            var res = await fetch(this.action, { method: 'POST', body: new FormData(this), headers: { Accept: 'application/json' } });
            var data = await res.json();
            if (data.ok) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> تم إرسال طلبك! سنتواصل قريباً';
                btn.style.background = '#28a745';
                this.reset();
                setTimeout(function() { btn.innerHTML = originalText; btn.disabled = false; btn.style.background = ''; }, 4000);
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> حدث خطأ، حاول لاحقاً';
                btn.style.background = '#dc3545';
                setTimeout(function() { btn.innerHTML = originalText; btn.disabled = false; btn.style.background = ''; }, 3000);
            }
        } catch (err) {
            btn.innerHTML = '<i class="fas fa-exclamation-circle"></i> خطأ في الاتصال';
            btn.style.background = '#dc3545';
            setTimeout(function() { btn.innerHTML = originalText; btn.disabled = false; btn.style.background = ''; }, 3000);
        }
    });
});
</script>
@endsection
