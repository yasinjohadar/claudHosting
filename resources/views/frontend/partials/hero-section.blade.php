@php
    $hero = $hero ?? ['enabled' => false];
@endphp

@if ($hero['enabled'] ?? false)
    @php
        $c = $hero['content'] ?? [];
        $heroStyle = '';
        if (! empty($hero['bg_light_css'])) {
            $heroStyle .= '--hero-bg-light: ' . $hero['bg_light_css'] . ';';
        }
        if (! empty($hero['bg_dark_css'])) {
            $heroStyle .= ' --hero-bg-dark: ' . $hero['bg_dark_css'] . ';';
        }
    @endphp
    <section class="hero-section" id="hero" @if ($heroStyle) style="{{ $heroStyle }}" @endif>
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-2 order-lg-1">
                    <div class="hero-content animate-on-scroll">
                        <h1>
                            {{ $c['title_prefix'] ?? '' }}
                            <span id="typingText"
                                data-texts="{{ $c['typing_texts_pipe'] ?? '' }}">{{ $c['typing_texts_initial'] ?? '' }}</span>
                            <span class="blinking-cursor"
                                style="animation: blink 0.8s infinite; color: var(--clr-primary);">|</span>
                        </h1>
                        <p class="subtitle">{{ $c['subtitle'] ?? '' }}</p>
                        @if (! empty($c['buttons']))
                        <div class="hero-btns">
                            @foreach ($c['buttons'] as $btn)
                                <a href="{{ $btn['url'] }}"
                                    class="{{ ($btn['style'] ?? 'primary') === 'outline' ? 'btn-outline-custom' : 'btn-primary-custom' }}">
                                    @if (! empty($btn['icon']))
                                        <i class="{{ $btn['icon'] }}"></i>
                                    @endif
                                    {{ $btn['label'] }}
                                </a>
                            @endforeach
                        </div>
                        @endif

                        @if (! empty($c['stats']))
                        <div class="hero-stats">
                            @foreach ($c['stats'] as $stat)
                                <div class="hero-stat-item">
                                    <span class="stat-num counter-num" data-count="{{ $stat['value'] }}">0{{ $stat['suffix'] ?? '+' }}</span>
                                    <span class="stat-label">{{ $stat['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 hero-visual-col">
                    <div class="hero-image-wrapper animate-on-scroll">
                        <div class="hero-ring" aria-hidden="true"></div>
                        <div class="hero-picture" role="img" aria-label="{{ $c['image_alt'] ?? '' }}">
                            <img src="{{ $hero['hero_image_light_url'] }}"
                                alt=""
                                class="hero-img hero-img--theme-light"
                                width="520"
                                height="520"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async">
                            <img src="{{ $hero['hero_image_dark_url'] }}"
                                alt=""
                                class="hero-img hero-img--theme-dark"
                                width="520"
                                height="520"
                                loading="eager"
                                decoding="async">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
