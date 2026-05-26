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
    @php
        $siteName = $settings['site_name'] ?? 'ClaudSoft Hosting';
        $footerDesc = $settings['footer_description'] ?? 'استضافة كلاودسوفت — بنية سحابية موثوقة، باقات مرنة، ودعم فني مستمر لموقعك أو متجرك الإلكتروني.';
        $footerEmail = $settings['contact_email'] ?? 'info@cloudsofthosting.com';
        $footerPhone = $settings['contact_phone'] ?? '+963 XXX XXX XXX';
        $footerAddress = $settings['contact_address'] ?? 'سوريا';
        $copyrightText = $settings['copyright_text'] ?? 'جميع الحقوق محفوظة';
    @endphp
    <footer class="main-footer" id="site-footer">
        <div class="footer-bg" aria-hidden="true">
            <div class="footer-bg-grid"></div>
            <div class="footer-bg-glow footer-bg-glow--1"></div>
            <div class="footer-bg-glow footer-bg-glow--2"></div>
        </div>

        <div class="container position-relative">
            <div class="footer-top-bar">
                <div class="footer-top-bar-inner">
                    <span class="footer-top-pill"><i class="fas fa-server"></i> استضافة سحابية</span>
                    <span class="footer-top-pill"><i class="fas fa-shield-halved"></i> أمان وSSL</span>
                    <span class="footer-top-pill"><i class="fas fa-headset"></i> دعم 24/7</span>
                </div>
            </div>

            <div class="row g-4 g-lg-5 footer-main">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <a href="{{ url('/') }}" class="footer-brand-link">
                            <span class="footer-logo-wrap">
                                <img src="{{ asset('frontend/assets/images/logo.png') }}" alt="شعار {{ $siteName }}" class="footer-logo-img" width="48" height="48" loading="lazy">
                            </span>
                            <span class="footer-brand-name">{{ $siteName }}</span>
                        </a>
                        <p class="footer-brand-desc">{{ $footerDesc }}</p>
                        @include('frontend.partials.social-links', ['wrapperClass' => 'footer-social'])
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5 class="footer-heading"><i class="fas fa-link"></i> روابط سريعة</h5>
                    <ul class="footer-links">
                        <li><a href="{{ url('/') }}">الرئيسية</a></li>
                        <li><a href="{{ route('frontend.about') }}">حول الشركة</a></li>
                        <li><a href="{{ route('frontend.packages') }}">الباقات</a></li>
                        <li><a href="{{ route('frontend.domain-search') }}">بحث الدومينات</a></li>
                        <li><a href="{{ route('frontend.blog') }}">المدونة</a></li>
                        <li><a href="{{ route('frontend.contact') }}">تواصل معنا</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading"><i class="fas fa-box-open"></i> خدماتنا</h5>
                    <ul class="footer-links">
                        <li><a href="{{ route('frontend.packages') }}">استضافة المواقع</a></li>
                        <li><a href="{{ route('frontend.service-detail-web') }}">تطوير تطبيقات الويب</a></li>
                        <li><a href="{{ route('frontend.service-detail-mobile') }}">تطبيقات الجوال</a></li>
                        <li><a href="{{ route('frontend.service-detail-servers') }}">إدارة السيرفرات</a></li>
                        <li><a href="{{ route('frontend.service-detail-security') }}">الأمن السيبراني</a></li>
                        <li><a href="{{ route('frontend.clients') }}">عملاؤنا</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5 class="footer-heading"><i class="fas fa-envelope-open-text"></i> تواصل معنا</h5>
                    <ul class="footer-contact">
                        <li>
                            <a href="mailto:{{ $footerEmail }}" class="footer-contact-item">
                                <span class="footer-contact-icon"><i class="fas fa-envelope"></i></span>
                                <span class="footer-contact-text">{{ $footerEmail }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="tel:{{ preg_replace('/\s+/', '', $footerPhone) }}" class="footer-contact-item">
                                <span class="footer-contact-icon"><i class="fas fa-phone"></i></span>
                                <span class="footer-contact-text">{{ $footerPhone }}</span>
                            </a>
                        </li>
                        <li>
                            <span class="footer-contact-item footer-contact-item--static">
                                <span class="footer-contact-icon"><i class="fas fa-map-marker-alt"></i></span>
                                <span class="footer-contact-text">{{ $footerAddress }}</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-copyright mb-0">
                    {{ $copyrightText }} &copy; {{ date('Y') }}
                    <span class="footer-copyright-brand">{{ $siteName }}</span>
                </p>
                <a href="#top" class="footer-back-top" aria-label="العودة للأعلى">
                    <i class="fas fa-arrow-up"></i>
                </a>
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
