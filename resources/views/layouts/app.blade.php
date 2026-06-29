@php
    use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
    use Voodflow\Voodbuilder\Support\SubThemeResolver;

    $vpressSubTheme = $vpressSubTheme ?? SubThemeResolver::forCurrentRoute();
    $vpressContentChannel = app(ContentChannelRegistry::class)->matchesCurrentRequest()?->id();
    $vpressBodyClass = trim((string) $__env->yieldContent('body_class'));
    $vpressHasDocSidebar = str_contains($vpressBodyClass, 'voodbuilder-has-doc-sidebar');
    $vpressShowReadingProgress = str_contains($vpressBodyClass, 'voodbuilder-has-reading-progress');
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-voodbuilder-sub-theme="{{ $vpressSubTheme }}"
    @if (filled($vpressContentChannel))
        data-voodbuilder-content-channel="{{ $vpressContentChannel }}"
    @endif
    @class(['dark' => \Voodflow\Voodbuilder\Support\VoodbuilderTheme::serverInitialDark()])
>
<head>
    <x-voodbuilder::theme-script />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    @endphp
    <title>{{ $title ?? VoodbuilderSettings::siteTitle() }}</title>

    {!! seo() !!}

    <x-voodbuilder::geo-ai-meta />

    @include('cookie-consent::cookie-consent-head')

    @vite(config('voodbuilder.assets.vite', \Voodflow\Voodbuilder\Support\VoodbuilderPaths::defaultViteEntries()))
    @livewireStyles
    @stack('head')
</head>
<body class="flex min-h-screen flex-col {{ trim(implode(' ', array_filter([trim((string) $__env->yieldContent('body_class')), trim((string) $__env->yieldContent('body_class_extra'))]))) }}">
    @unless ($hideSiteNav ?? false)
        <x-voodbuilder::nav
            :has-doc-sidebar="$vpressHasDocSidebar"
            :show-reading-progress="$vpressShowReadingProgress"
        />
    @endunless

    <main class="flex-1">
        @yield('content')
    </main>

    @if(config('voodbuilder.footer.enabled', true) && ! ($hideSiteFooter ?? false))
        <x-voodbuilder::footer />
    @endif

    @include('cookie-consent::cookie-consent-body')
    <x-voodbuilder::monitoring-scripts />

    @stack('scripts-before-livewire')
    @livewireScripts
    <x-voodbuilder::theme-vars />
    <x-voodbuilder::site-scripts />
    @stack('scripts')
    @stack('overlays')
</body>
</html>
