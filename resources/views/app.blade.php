<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Apply the browser preference before painting, avoiding a theme flash. --}}
        <script>
            (function() {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const system = systemDark ? 'dark' : 'light';
                let appearance = 'system';

                try {
                    const stored = localStorage.getItem('appearance');
                    const savedSystem = localStorage.getItem('appearance-system-theme');
                    if ((stored === 'light' || stored === 'dark') && (!savedSystem || savedSystem === system)) {
                        appearance = stored;
                    }
                } catch {}

                const dark = appearance === 'system' ? systemDark : appearance === 'dark';
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #0a0f1a;
            }
        </style>

        {{-- Favicons --}}
        <link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48">
        <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon-16.png" type="image/png" sizes="16x16">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#1C4C81" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0A0F1A" media="(prefers-color-scheme: dark)">

        {{-- Global SEO defaults. Specific pages override these via Inertia's
             <Head> component; what's here is what a non-JS crawler (or a
             social preview bot that doesn't render JS) sees. --}}
        @php
            $seoLocale = app()->getLocale();
            $isUrdu = $seoLocale === 'ur';
            $seoLocaleOg = $isUrdu ? 'ur_PK' : 'en_PK';
            $seoLocaleAltOg = $isUrdu ? 'en_PK' : 'ur_PK';
            $defaultDescription = $isUrdu
                ? 'ویمن چیمبر آف کامرس اینڈ انڈسٹری، کراچی میں نئی ممبرشپ کے لیے درخواست دیں یا موجودہ ممبرشپ کی تجدید کریں۔'
                : 'Apply for new membership or renew your existing membership at the Women Chamber of Commerce & Industry, Karachi. Online application, status tracking, bilingual.';
            $homeUrl = url('/');
            $defaultOgImage = url('/images/wccik-logo.png');
        @endphp
        <meta name="author" content="Women Chamber of Commerce & Industry, Karachi">
        <meta name="robots" content="index, follow, max-image-preview:large">
        <meta name="googlebot" content="index, follow">
        <meta name="description" content="{{ $defaultDescription }}">
        <link rel="canonical" href="{{ $homeUrl }}/">
        <link rel="alternate" hreflang="en-PK" href="{{ $homeUrl }}/">
        <link rel="alternate" hreflang="ur-PK" href="{{ $homeUrl }}/">
        <link rel="alternate" hreflang="x-default" href="{{ $homeUrl }}/">
        <meta property="og:site_name" content="WCCIK Membership Portal">
        <meta property="og:type" content="website">
        <meta property="og:locale" content="{{ $seoLocaleOg }}">
        <meta property="og:locale:alternate" content="{{ $seoLocaleAltOg }}">
        <meta property="og:title" content="{{ config('app.name', 'WCCIK Membership Portal') }}">
        <meta property="og:description" content="{{ $defaultDescription }}">
        <meta property="og:url" content="{{ $homeUrl }}/">
        <meta property="og:image" content="{{ $defaultOgImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:site" content="@WCCIK">
        <meta name="twitter:title" content="{{ config('app.name', 'WCCIK Membership Portal') }}">
        <meta name="twitter:description" content="{{ $defaultDescription }}">
        <meta name="twitter:image" content="{{ $defaultOgImage }}">
        <meta name="geo.region" content="PK-SD">
        <meta name="geo.placename" content="Karachi, Pakistan">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'WCCIK Membership Portal') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
