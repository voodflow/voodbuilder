@php
    use Voodflow\Vpress\Support\SubThemeResolver;

    $vpressSubTheme = $vpressSubTheme ?? SubThemeResolver::forCurrentRoute();
    $vpressBodyClass = trim((string) $__env->yieldContent('body_class'));
    $vpressHasDocSidebar = str_contains($vpressBodyClass, 'vpress-has-doc-sidebar');
    $vpressShowReadingProgress = str_contains($vpressBodyClass, 'vpress-has-reading-progress');
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-vpress-sub-theme="{{ $vpressSubTheme }}"
    @class(['dark' => \Voodflow\Vpress\Support\VpressTheme::serverInitialDark()])
>
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

    @vite(config('vpress.assets.vite', \Voodflow\Vpress\Support\VpressPaths::defaultViteEntries()))
    <x-vpress::theme-vars />
    @livewireStyles
    @stack('head')
</head>
<body class="flex min-h-screen flex-col @yield('body_class')">
    <x-vpress::nav
        :has-doc-sidebar="$vpressHasDocSidebar"
        :show-reading-progress="$vpressShowReadingProgress"
    />

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
    <x-vpress::site-scripts />
    @stack('scripts')
</body>
</html>
