@php
    $contactEmail = $settings['contact_email'] ?? 'info@cloudsofthosting.com';
    $contactPhone = $settings['contact_phone'] ?? '+963 XXX XXX XXX';
    $contactWhatsapp = $settings['contact_whatsapp'] ?? $contactPhone;
    $contactAddress = $settings['contact_address'] ?? 'سوريا';
    $contactWorkHours = $settings['contact_work_hours'] ?? 'السبت - الخميس: 9:00 ص - 6:00 م';
    $phoneHref = 'tel:' . preg_replace('/\s+/', '', $contactPhone);
    $whatsappNumber = preg_replace('/[^0-9]/', '', $contactWhatsapp);
    $whatsappHref = 'https://wa.me/' . $whatsappNumber;

    $contactItems = [
        [
            'icon' => 'fas fa-envelope',
            'accent' => '#0057B8',
            'label' => 'البريد الإلكتروني',
            'value' => $contactEmail,
            'href' => 'mailto:' . $contactEmail,
        ],
        [
            'icon' => 'fas fa-phone-alt',
            'accent' => '#2E9AD0',
            'label' => 'رقم الهاتف',
            'value' => $contactPhone,
            'href' => $phoneHref,
            'ltr' => true,
        ],
        [
            'icon' => 'fab fa-whatsapp',
            'accent' => '#25D366',
            'label' => 'واتساب',
            'value' => $contactWhatsapp,
            'href' => $whatsappHref,
            'external' => true,
            'ltr' => true,
        ],
        [
            'icon' => 'fas fa-map-marker-alt',
            'accent' => '#6366f1',
            'label' => 'الموقع',
            'value' => $contactAddress,
            'href' => null,
        ],
        [
            'icon' => 'fas fa-clock',
            'accent' => '#f59e0b',
            'label' => 'ساعات العمل',
            'value' => $contactWorkHours,
            'href' => null,
        ],
    ];
@endphp

<section class="contact-page-section section-padding">
    <div class="contact-page-section__bg" aria-hidden="true"></div>
    <div class="container position-relative">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7 order-2 order-lg-1">
                <div class="contact-panel contact-panel--form animate-on-scroll">
                    <div class="contact-panel__glow" aria-hidden="true"></div>
                    <header class="contact-panel__head">
                        <div class="contact-panel__icon" aria-hidden="true">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div>
                            <h2 class="contact-panel__title">أرسل لنا رسالة</h2>
                            <p class="contact-panel__subtitle">املأ النموذج وسنرد عليك في أقرب وقت ممكن</p>
                        </div>
                    </header>

                    <form id="contactForm" class="contact-form"
                        action="{{ $settings['contact_form_action'] ?? 'https://formspree.io/f/YOUR_FORM_ID' }}"
                        method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="contact-field__label" for="contact-name">الاسم الكامل</label>
                                <div class="contact-field">
                                    <i class="fas fa-user contact-field__icon" aria-hidden="true"></i>
                                    <input type="text" name="name" id="contact-name" class="contact-field__input"
                                        placeholder="أدخل اسمك الكامل" required autocomplete="name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-field__label" for="contact-email">البريد الإلكتروني</label>
                                <div class="contact-field">
                                    <i class="fas fa-envelope contact-field__icon" aria-hidden="true"></i>
                                    <input type="email" name="_replyto" id="contact-email" class="contact-field__input"
                                        placeholder="example@email.com" required autocomplete="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-field__label" for="contact-phone">رقم الهاتف</label>
                                <div class="contact-field">
                                    <i class="fas fa-phone contact-field__icon" aria-hidden="true"></i>
                                    <input type="tel" name="phone" id="contact-phone" class="contact-field__input"
                                        placeholder="+963 XXX XXX XXX" autocomplete="tel">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="contact-field__label" for="contact-subject">الموضوع</label>
                                <div class="contact-field contact-field--select">
                                    <i class="fas fa-tag contact-field__icon" aria-hidden="true"></i>
                                    <select class="contact-field__input contact-field__select" name="subject" id="contact-subject" required>
                                        <option value="" disabled selected>اختر الموضوع</option>
                                        <option value="hosting">استفسار عن باقة استضافة</option>
                                        <option value="domain">بحث أو تسجيل نطاق</option>
                                        <option value="support">دعم فني</option>
                                        <option value="project">طلب مشروع أو موقع</option>
                                        <option value="collab">تعاون وشراكة</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="contact-field__label" for="contact-message">الرسالة</label>
                                <div class="contact-field contact-field--textarea">
                                    <i class="fas fa-comment-dots contact-field__icon" aria-hidden="true"></i>
                                    <textarea class="contact-field__input contact-field__textarea" name="message"
                                        id="contact-message" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="contact-submit">
                                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                    <span>إرسال الرسالة</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5 order-1 order-lg-2">
                <div class="contact-panel contact-panel--info animate-on-scroll">
                    <div class="contact-panel__glow contact-panel__glow--info" aria-hidden="true"></div>
                    <header class="contact-panel__head contact-panel__head--compact">
                        <div class="contact-panel__icon contact-panel__icon--info" aria-hidden="true">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h2 class="contact-panel__title">معلومات التواصل</h2>
                            <p class="contact-panel__subtitle">تواصل مباشرة عبر القنوات التالية</p>
                        </div>
                    </header>

                    <div class="contact-info-list">
                        @foreach ($contactItems as $item)
                            @if ($item['href'])
                                <a href="{{ $item['href'] }}"
                                    class="contact-info-card"
                                    style="--info-accent: {{ $item['accent'] }}"
                                    @if (! empty($item['external'])) target="_blank" rel="noopener noreferrer" @endif>
                            @else
                                <div class="contact-info-card" style="--info-accent: {{ $item['accent'] }}">
                            @endif
                                    <span class="contact-info-card__icon" aria-hidden="true">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </span>
                                    <span class="contact-info-card__body">
                                        <span class="contact-info-card__label">{{ $item['label'] }}</span>
                                        <span class="contact-info-card__value{{ ! empty($item['ltr']) ? ' contact-info-card__value--ltr' : '' }}">{{ $item['value'] }}</span>
                                    </span>
                                    @if ($item['href'])
                                        <i class="fas fa-arrow-left contact-info-card__arrow" aria-hidden="true"></i>
                                    @endif
                            @if ($item['href'])
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="contact-quick-actions">
                        <a href="{{ $whatsappHref }}" class="contact-quick-btn contact-quick-btn--whatsapp" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp" aria-hidden="true"></i>
                            <span>محادثة واتساب</span>
                        </a>
                        <a href="{{ route('frontend.consultation') }}" class="contact-quick-btn contact-quick-btn--book">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <span>حجز موعد</span>
                        </a>
                    </div>

                    @php
                        $hasSocial = ! empty($settings['social_facebook'] ?? null)
                            || ! empty($settings['social_youtube'] ?? null)
                            || ! empty($settings['social_instagram'] ?? null)
                            || ! empty($settings['social_linkedin'] ?? null)
                            || ! empty($settings['social_github'] ?? null)
                            || ! empty($settings['social_telegram'] ?? null);
                    @endphp
                    @if ($hasSocial)
                        <div class="contact-social-block">
                            <span class="contact-social-block__label">تابعنا على</span>
                            @include('frontend.partials.social-links', ['wrapperClass' => 'contact-social'])
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
