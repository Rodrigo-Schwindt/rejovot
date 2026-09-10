@php
    $seo = $seo ?? \App\Support\Seo::forCurrentRoute([
        'title' => $pageTitleOverride ?? $title ?? null,
        'description' => $metaDescriptionOverride ?? null,
        'keywords' => $metaKeywordsOverride ?? null,
        'robots' => $robots ?? null,
    ]);
@endphp

<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="keywords" content="{{ $seo['keywords'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

<meta property="og:locale" content="es_AR">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<meta name="theme-color" content="#0D2B5E">
