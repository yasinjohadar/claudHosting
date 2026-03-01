@extends('frontend.layouts.master')

@section('page-title')
تواصل معنا | استضافة كلاودسوفت
@endsection

@section('meta-description')
تواصل مع فريق استضافة كلاودسوفت — للاستفسارات، التسجيل في الباقات، أو طلب استشارة تقنية. نحن هنا لمساعدتك على اختيار الحل المناسب لمشروعك.
@endsection

@section('content')
    <!-- ============ PAGE BANNER (تواصل معنا) ============ -->
    <section class="page-banner page-banner-about">
        <div class="page-banner-overlay"></div>
        <div class="container position-relative">
            <div class="page-banner-content animate-on-scroll">
                <div class="page-banner-icon"><i class="fas fa-paper-plane"></i></div>
                <h1 class="page-banner-title">تواصل <span>معنا</span></h1>
                <p class="page-banner-desc">نحن هنا لمساعدتك — للاستفسارات أو التسجيل في الدورات أو طلب استشارة تقنية</p>
                <nav class="page-banner-breadcrumb" aria-label="breadcrumb">
                    <a href="{{ url('/') }}">الرئيسية</a>
                    <span class="page-banner-sep">/</span>
                    <span>تواصل معنا</span>
                </nav>
            </div>
        </div>
        <div class="page-banner-shape"></div>
    </section>

    <!-- ============ CONTACT SECTION ============ -->
    <section class="section-padding contact-page-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="glass-panel contact-form-wrapper animate-on-scroll">
                        <h4 style="font-weight:800; margin-bottom:8px;">أرسل لنا رسالة</h4>
                        <p style="color:var(--clr-text-secondary); margin-bottom:25px; font-size:0.95rem;">املأ النموذج أدناه وسنرد عليك في أقرب وقت ممكن</p>
                        <form id="contactForm" action="{{ $settings['contact_form_action'] ?? 'https://formspree.io/f/YOUR_FORM_ID' }}" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight:600; font-size:0.9rem;">الاسم الكامل</label>
                                    <input type="text" name="name" class="form-control" placeholder="أدخل اسمك الكامل" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight:600; font-size:0.9rem;">البريد الإلكتروني</label>
                                    <input type="email" name="_replyto" class="form-control" placeholder="example@email.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight:600; font-size:0.9rem;">رقم الهاتف</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+963 XXX XXX XXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" style="font-weight:600; font-size:0.9rem;">الموضوع</label>
                                    <select class="form-select" name="subject" required>
                                        <option value="" disabled selected>اختر الموضوع</option>
                                        <option value="course">استفسار عن دورة تدريبية</option>
                                        <option value="project">طلب مشروع برمجي</option>
                                        <option value="private">تدريب خاص</option>
                                        <option value="collab">تعاون وشراكة</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" style="font-weight:600; font-size:0.9rem;">الرسالة</label>
                                    <textarea class="form-control" name="message" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn-primary-custom w-100" style="justify-content:center;"><i class="fas fa-paper-plane"></i> إرسال الرسالة</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="glass-panel contact-info-card animate-on-scroll" style="margin-bottom:20px;">
                        <h4 style="font-weight:800; margin-bottom:20px;">معلومات التواصل</h4>
                        @php
                            $contactEmail = $settings['contact_email'] ?? 'info@cloudsofthosting.com';
                            $contactPhone = $settings['contact_phone'] ?? '+963 XXX XXX XXX';
                            $contactWhatsapp = $settings['contact_whatsapp'] ?? '+963 XXX XXX XXX';
                            $contactAddress = $settings['contact_address'] ?? 'سوريا';
                            $contactWorkHours = $settings['contact_work_hours'] ?? 'السبت - الخميس: 9:00 ص - 6:00 م';
                        @endphp
                        <div class="contact-info-item">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div><h6>البريد الإلكتروني</h6><p>{{ $contactEmail }}</p></div>
                        </div>
                        <div class="contact-info-item">
                            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                            <div><h6>رقم الهاتف</h6><p style="direction:ltr; text-align:right;">{{ $contactPhone }}</p></div>
                        </div>
                        <div class="contact-info-item">
                            <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                            <div><h6>واتساب</h6><p style="direction:ltr; text-align:right;">{{ $contactWhatsapp }}</p></div>
                        </div>
                        <div class="contact-info-item">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div><h6>الموقع</h6><p>{{ $contactAddress }}</p></div>
                        </div>
                        <div class="contact-info-item" style="margin-bottom:0;">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div><h6>ساعات العمل</h6><p>{{ $contactWorkHours }}</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
