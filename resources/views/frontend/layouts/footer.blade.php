    <!-- ============ NEWSLETTER SECTION ============ -->
    <section class="section-padding newsletter-section" id="newsletter">
        <div class="container">
            <div class="newsletter-card animate-on-scroll">
                <div class="newsletter-benefits">
                    <div class="newsletter-benefit-item">
                        <div class="newsletter-benefit-icon"><i class="fas fa-lightbulb"></i></div>
                        <span>نصائح الاستضافة</span>
                    </div>
                    <div class="newsletter-benefit-item">
                        <div class="newsletter-benefit-icon"><i class="fas fa-gift"></i></div>
                        <span>عروض خاصة</span>
                    </div>
                    <div class="newsletter-benefit-item">
                        <div class="newsletter-benefit-icon"><i class="fas fa-bell"></i></div>
                        <span>أخبار فورية</span>
                    </div>
                    <div class="newsletter-benefit-item">
                        <div class="newsletter-benefit-icon"><i class="fas fa-envelope"></i></div>
                        <span>رسائل حصرية</span>
                    </div>
                </div>
                <h2 class="newsletter-title">
                    <i class="fas fa-paper-plane newsletter-title-icon"></i>
                    اشترك في نشرتنا البريدية
                </h2>
                <p class="newsletter-desc">احصل على آخر أخبار الاستضافة، النصائح التقنية، العروض الحصرية والمقالات مباشرةً في بريدك.</p>
                <form class="newsletter-form" id="newsletterForm" novalidate>
                    <div class="newsletter-input-group">
                        <button type="submit" class="newsletter-btn">
                            <i class="fas fa-paper-plane"></i> اشترك الآن
                        </button>
                        <div class="newsletter-input-wrap">
                            <i class="fas fa-envelope newsletter-input-icon"></i>
                            <input type="email" name="email" id="newsletterEmail" class="newsletter-input" placeholder="أدخل بريدك الإلكتروني" required aria-label="البريد الإلكتروني">
                        </div>
                    </div>
                    <p class="newsletter-hint"><i class="fas fa-shield-alt"></i> تحترم خصوصيتك ولا نشارك بريدك مع أي جهة</p>
                </form>
            </div>
        </div>
    </section>

    <!-- ============ CTA SECTION ============ -->
    <section class="cta-section">
        <div class="container animate-on-scroll">
            <h2>هل أنت مستعد لنقل موقعك إلى استضافة أكثر استقراراً؟</h2>
            <p>اختر باقة استضافة كلاودسوفت المناسبة لموقعك أو متجرك الإلكتروني وتمتع بسرعة أعلى، أمان أفضل، ودعم فني متواصل.</p>
            <a href="{{ route('frontend.packages') }}" class="btn-light-custom">
                <i class="fas fa-rocket"></i> اختر باقتك الآن
            </a>
        </div>
    </section>

    <!-- ============ FOOTER ============ -->
    <footer class="main-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    @php
                        $siteName = $settings['site_name'] ?? 'ClaudSoft';
                        $footerDesc = $settings['footer_description'] ?? 'مدرب ومطور برمجيات شغوف بالتعليم ونقل المعرفة. أقدم دورات تدريبية عملية في مختلف مجالات البرمجة وتطوير الويب والموبايل.';
                    @endphp
                    <h5><img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار {{ $siteName }}" class="footer-logo-img"
                            style="width:35px; height:35px; border-radius:50%; margin-left:8px; border:2px solid var(--clr-primary);" width="35" height="35">
                        {{ $siteName }}</h5>
                    <p>{{ $footerDesc }}</p>
                    <div class="footer-social">
                        @if(!empty($settings['social_facebook'] ?? null))<a href="{{ $settings['social_facebook'] }}" target="_blank" rel="noopener noreferrer" title="فيسبوك" aria-label="فيسبوك"><i class="fab fa-facebook-f"></i></a>@endif
                        @if(!empty($settings['social_youtube'] ?? null))<a href="{{ $settings['social_youtube'] }}" target="_blank" rel="noopener noreferrer" title="يوتيوب" aria-label="يوتيوب"><i class="fab fa-youtube"></i></a>@endif
                        @if(!empty($settings['social_instagram'] ?? null))<a href="{{ $settings['social_instagram'] }}" target="_blank" rel="noopener noreferrer" title="انستغرام" aria-label="انستغرام"><i class="fab fa-instagram"></i></a>@endif
                        @if(!empty($settings['social_linkedin'] ?? null))<a href="{{ $settings['social_linkedin'] }}" target="_blank" rel="noopener noreferrer" title="لينكد إن" aria-label="لينكد إن"><i class="fab fa-linkedin-in"></i></a>@endif
                        @if(!empty($settings['social_github'] ?? null))<a href="{{ $settings['social_github'] }}" target="_blank" rel="noopener noreferrer" title="جيت هاب" aria-label="جيت هاب"><i class="fab fa-github"></i></a>@endif
                        @if(!empty($settings['social_telegram'] ?? null))<a href="{{ $settings['social_telegram'] }}" target="_blank" rel="noopener noreferrer" title="تليجرام" aria-label="تليجرام"><i class="fab fa-telegram-plane"></i></a>@endif
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5>روابط سريعة</h5>
                    <ul class="footer-links">
                        <li><a href="{{ url('/') }}"><i class="fas fa-chevron-left"></i> الرئيسية</a></li>
                        <li><a href="{{ route('frontend.about') }}"><i class="fas fa-chevron-left"></i> حول الشركة</a></li>
                        <li><a href="{{ route('frontend.packages') }}"><i class="fas fa-chevron-left"></i> الباقات</a></li>
                        <li><a href="{{ route('frontend.projects') }}"><i class="fas fa-chevron-left"></i> المشاريع</a></li>
                        <li><a href="{{ route('frontend.videos') }}"><i class="fas fa-chevron-left"></i> الفيديوهات</a></li>
                        <li><a href="{{ route('frontend.testimonials') }}"><i class="fas fa-chevron-left"></i> آراء الطلاب</a></li>
                        <li><a href="{{ route('frontend.contact') }}"><i class="fas fa-chevron-left"></i> تواصل معنا</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>أحدث الباقات</h5>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-left"></i> تطوير الويب الشامل</a></li>
                        <li><a href="#"><i class="fas fa-chevron-left"></i> بايثون للمبتدئين</a></li>
                        <li><a href="#"><i class="fas fa-chevron-left"></i> Flutter للموبايل</a></li>
                        <li><a href="#"><i class="fas fa-chevron-left"></i> WordPress المتقدم</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>تواصل معنا</h5>
                    <ul class="footer-links">
                        @php
                            $footerEmail = $settings['contact_email'] ?? 'info@cloudsofthosting.com';
                            $footerPhone = $settings['contact_phone'] ?? '+963 XXX XXX XXX';
                            $footerAddress = $settings['contact_address'] ?? 'سوريا';
                        @endphp
                        <li><i class="fas fa-envelope" style="color: var(--clr-primary); margin-left:8px;"></i>
                            {{ $footerEmail }}</li>
                        <li><i class="fas fa-phone" style="color: var(--clr-primary); margin-left:8px;"></i> {{ $footerPhone }}</li>
                        <li><i class="fas fa-map-marker-alt" style="color: var(--clr-primary); margin-left:8px;"></i>
                            {{ $footerAddress }}</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                {{ $settings['copyright_text'] ?? 'جميع الحقوق محفوظة' }} &copy; {{ date('Y') }} <span>{{ $settings['site_name'] ?? 'استضافة كلاودسوفت' }}</span> | صُنع بـ ❤️
            </div>
        </div>
    </footer>

    <!-- زر واتساب ثابت على اليمين للتواصل (نفس الرقم من الإعدادات) -->
    @php
        $whatsappNum = $settings['contact_whatsapp'] ?? $settings['contact_phone'] ?? null;
    @endphp
    @if(!empty($whatsappNum))
        @php $waNum = preg_replace('/[^0-9]/', '', $whatsappNum); @endphp
        <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener noreferrer" class="whatsapp-float" title="تواصل معنا عبر واتساب" aria-label="واتساب">
            <span class="whatsapp-float-icon"><i class="fab fa-whatsapp"></i></span>
        </a>
    @endif
