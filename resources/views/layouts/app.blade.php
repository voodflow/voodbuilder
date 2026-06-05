<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => \Voodflow\Vpress\Support\VpressTheme::serverInitialDark()])>
<head>
    <x-vpress::theme-script />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        use Voodflow\Vpress\Models\VpressSettings;
    @endphp
    <title>{{ $title ?? VpressSettings::siteTitle() }}</title>

    {!! seo() !!}

    <x-vpress::geo-ai-meta />

    @include('cookie-consent::cookie-consent-head')

    @vite(config('vpress.assets.vite', ['packages/voodflow/vpress/resources/css/theme.css', 'resources/js/app.js']))
    @livewireStyles
    @stack('head')
</head>
<body class="vpress min-h-screen flex flex-col @yield('body_class')">
    <x-vpress::nav />

    <main class="flex-1">
        @yield('content')
    </main>

    @if(config('vpress.footer.enabled', true))
        <x-vpress::footer />
    @endif

    @include('cookie-consent::cookie-consent-body')
    <x-vpress::monitoring-scripts />

    @stack('scripts-before-livewire')
    @livewireScripts
    @stack('scripts')
</body>
</html>
