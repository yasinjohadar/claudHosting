@php
    $wrapperClass = $wrapperClass ?? 'nav-social';
@endphp

<div class="{{ $wrapperClass }}">
    @if (! empty($settings['social_facebook'] ?? null))
        <a href="{{ $settings['social_facebook'] }}" class="social-brand social-brand--facebook" target="_blank" rel="noopener noreferrer" title="فيسبوك" aria-label="فيسبوك"><i class="fab fa-facebook-f"></i></a>
    @endif
    @if (! empty($settings['social_youtube'] ?? null))
        <a href="{{ $settings['social_youtube'] }}" class="social-brand social-brand--youtube" target="_blank" rel="noopener noreferrer" title="يوتيوب" aria-label="يوتيوب"><i class="fab fa-youtube"></i></a>
    @endif
    @if (! empty($settings['social_instagram'] ?? null))
        <a href="{{ $settings['social_instagram'] }}" class="social-brand social-brand--instagram" target="_blank" rel="noopener noreferrer" title="انستغرام" aria-label="انستغرام"><i class="fab fa-instagram"></i></a>
    @endif
    @if (! empty($settings['social_linkedin'] ?? null))
        <a href="{{ $settings['social_linkedin'] }}" class="social-brand social-brand--linkedin" target="_blank" rel="noopener noreferrer" title="لينكد إن" aria-label="لينكد إن"><i class="fab fa-linkedin-in"></i></a>
    @endif
    @if (! empty($settings['social_github'] ?? null))
        <a href="{{ $settings['social_github'] }}" class="social-brand social-brand--github" target="_blank" rel="noopener noreferrer" title="جيت هاب" aria-label="جيت هاب"><i class="fab fa-github"></i></a>
    @endif
    @if (! empty($settings['social_telegram'] ?? null))
        <a href="{{ $settings['social_telegram'] }}" class="social-brand social-brand--telegram" target="_blank" rel="noopener noreferrer" title="تليجرام" aria-label="تليجرام"><i class="fab fa-telegram-plane"></i></a>
    @endif
</div>
