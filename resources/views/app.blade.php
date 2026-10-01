@php
    // Controllers may pass page details with ->withViewData('meta', [...]).
    // See App\Services\SeoService. Every value falls back to a site-wide default.
    $meta = $meta ?? [];
    $siteName = config('app.name', 'CulinaLog');
    $pageTitle = isset($meta['title']) ? $meta['title'].' - '.$siteName : $siteName;
    $description = $meta['description'] ?? 'CulinaLog is a community recipe book: keep your own recipes in one place and talk cooking with other home cooks.';
    // The canonical address has no query string, so filters and page numbers do not create duplicates.
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f6f5f1">

        <title inertia>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ $canonical }}">
        @if ($meta['noindex'] ?? false)
            <meta name="robots" content="noindex">
        @endif

        <!-- Link previews (Open Graph) -->
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="{{ $meta['type'] ?? 'website' }}">
        <meta property="og:title" content="{{ $meta['title'] ?? $siteName }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $canonical }}">
        @isset($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @endisset

        @isset($meta['jsonLd'])
            {{-- Structured data. The JSON_HEX flags turn < > & ' " into \u codes, so text
                 typed by users cannot close this script tag or inject markup. --}}
            <script type="application/ld+json" nonce="{{ Vite::cspNonce() }}">{!! json_encode($meta['jsonLd'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
        @endisset

        <link rel="icon" type="image/svg+xml" href="/favicon.svg">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=gabarito:500,600,700|instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes(nonce: Vite::cspNonce())
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
