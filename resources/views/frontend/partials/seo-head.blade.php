@php
    $seo = $seo ?? null;
@endphp

@if (!empty($seo['enabled']))
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    @if (!empty($seo['meta_keywords']))
        <meta name="keywords" content="{{ $seo['meta_keywords'] }}">
    @endif

    <link rel="canonical" href="{{ $seo['canonical'] }}">

    @if (!empty($seo['prev_url']))
        <link rel="prev" href="{{ $seo['prev_url'] }}">
    @endif
    @if (!empty($seo['next_url']))
        <link rel="next" href="{{ $seo['next_url'] }}">
    @endif

    <meta property="og:type" content="{{ $seo['og']['type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $seo['og']['url'] ?? $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['og']['title'] ?? $seo['meta_title'] }}">
    <meta property="og:description" content="{{ $seo['og']['description'] ?? $seo['meta_description'] }}">
    <meta property="og:image" content="{{ $seo['og']['image'] }}">
    <meta property="og:locale" content="{{ $seo['og']['locale'] ?? 'ar_AR' }}">
    <meta property="og:site_name" content="{{ $seo['og']['site_name'] ?? app(\App\Services\GlobalSeoService::class)->organization()['name'] ?? 'استضافة كلاودسوفت' }}">

    @if (!empty($seo['article']))
        @if (!empty($seo['article']['published_time']))
            <meta property="article:published_time" content="{{ $seo['article']['published_time'] }}">
        @endif
        @if (!empty($seo['article']['modified_time']))
            <meta property="article:modified_time" content="{{ $seo['article']['modified_time'] }}">
        @endif
        @if (!empty($seo['article']['section']))
            <meta property="article:section" content="{{ $seo['article']['section'] }}">
        @endif
        @foreach ($seo['article']['tags'] ?? [] as $tagName)
            <meta property="article:tag" content="{{ $tagName }}">
        @endforeach
        @if (!empty($seo['article']['author']))
            <meta property="article:author" content="{{ $seo['article']['author'] }}">
        @endif
    @endif

    <meta name="twitter:card" content="{{ $seo['twitter']['card'] ?? 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ $seo['twitter']['title'] ?? $seo['og']['title'] }}">
    <meta name="twitter:description" content="{{ $seo['twitter']['description'] ?? $seo['og']['description'] }}">
    <meta name="twitter:image" content="{{ $seo['twitter']['image'] ?? $seo['og']['image'] }}">
    @if (!empty($seo['twitter']['site']))
        <meta name="twitter:site" content="{{ $seo['twitter']['site'] }}">
    @endif
    @if (!empty($seo['twitter']['creator']))
        <meta name="twitter:creator" content="{{ $seo['twitter']['creator'] }}">
    @endif

    @foreach ($seo['schemas'] ?? [] as $schema)
        <script type="application/ld+json">{!! json_encode(
            array_merge(['@context' => 'https://schema.org'], $schema),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) !!}</script>
    @endforeach
@endif
