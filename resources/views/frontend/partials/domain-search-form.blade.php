@php
    $pricing = $pricing ?? [];
    $selectedTlds = $selectedTlds ?? [];
    $popularTlds = ['com', 'net', 'org', 'io', 'sa', 'ae'];
    $tldList = ! empty($pricing) ? array_slice(array_keys($pricing), 0, 24) : [];
@endphp

<div class="domain-search-panel animate-on-scroll">
    <div class="domain-search-panel__glow" aria-hidden="true"></div>
    <div class="domain-search-panel__grid" aria-hidden="true"></div>

    <header class="domain-search-panel__head">
        <div class="domain-search-panel__icon" aria-hidden="true">
            <i class="fas fa-magnifying-glass"></i>
        </div>
        <div>
            <h2 class="domain-search-panel__title">ابحث عن نطاقك المثالي</h2>
            <p class="domain-search-panel__subtitle">أدخل الاسم واختر الامتدادات — نتحقق من التوفر ونعرض الأسعار فوراً</p>
        </div>
    </header>

    <form id="domain-search-form" action="{{ route('frontend.domain-search.post') }}" method="post" class="domain-search-form" novalidate>
        @csrf

        @if (session('error'))
            <div class="domain-search-alert domain-search-alert--warning" role="alert">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="domain-search-alert domain-search-alert--danger" role="alert">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="domain-search-field">
            <label for="domain" class="domain-search-field__label">
                <i class="fas fa-globe" aria-hidden="true"></i>
                اسم النطاق أو النطاق الكامل
            </label>
            <div class="domain-search-input-group">
                <span class="domain-search-input-prefix" aria-hidden="true">www.</span>
                <input type="text"
                    name="domain"
                    id="domain"
                    class="domain-search-input"
                    placeholder="mysite أو mysite.com"
                    value="{{ old('domain', $searchTerm ?? '') }}"
                    required
                    autocomplete="off"
                    spellcheck="false"
                    inputmode="url"
                    aria-describedby="domain-search-hint">
                <button type="submit" class="domain-search-submit" id="domain-search-submit" aria-label="بحث عن النطاق">
                    <span class="domain-search-submit__text">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span class="btn-text">بحث</span>
                    </span>
                    <span class="domain-search-submit__loading" aria-hidden="true">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
            <p id="domain-search-hint" class="domain-search-field__hint">
                <i class="fas fa-lightbulb" aria-hidden="true"></i>
                يمكنك كتابة الاسم فقط (مثل <kbd>cloudsoft</kbd>) أو النطاق كاملاً (مثل <kbd>cloudsoft.com</kbd>)
            </p>
        </div>

        @if (! empty($tldList))
            <div class="domain-tld-block">
                <div class="domain-tld-block__head">
                    <div>
                        <span class="domain-tld-block__label">امتدادات البحث</span>
                        <span class="domain-tld-block__note">اختياري — فارغ = أول 12 امتداداً</span>
                    </div>
                    <div class="domain-tld-block__actions">
                        <button type="button" class="domain-tld-action" data-tld-action="select-all">تحديد الكل</button>
                        <span class="domain-tld-action-sep" aria-hidden="true">|</span>
                        <button type="button" class="domain-tld-action" data-tld-action="clear">مسح</button>
                    </div>
                </div>
                <div class="domain-tld-chips" role="group" aria-label="اختيار امتدادات النطاق">
                    @foreach ($tldList as $tld)
                        @php
                            $isChecked = in_array($tld, $selectedTlds, true);
                            $isPopular = in_array($tld, $popularTlds, true);
                        @endphp
                        <label class="domain-tld-chip{{ $isPopular ? ' domain-tld-chip--popular' : '' }}{{ $isChecked ? ' is-selected' : '' }}">
                            <input class="domain-tld-chip__input"
                                type="checkbox"
                                name="tlds[]"
                                value="{{ $tld }}"
                                {{ $isChecked ? 'checked' : '' }}>
                            <span class="domain-tld-chip__dot" aria-hidden="true"></span>
                            <span class="domain-tld-chip__text">.{{ $tld }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </form>

    <ul class="domain-search-features" aria-label="مميزات البحث">
        <li><i class="fas fa-bolt" aria-hidden="true"></i> تحقق فوري</li>
        <li><i class="fas fa-tags" aria-hidden="true"></i> أسعار شفافة</li>
        <li><i class="fas fa-shield-halved" aria-hidden="true"></i> خصوصية WHOIS</li>
        <li><i class="fas fa-rotate" aria-hidden="true"></i> نقل وتجديد</li>
    </ul>
</div>
