@php
    use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
    use Voodflow\Voodbuilder\Support\SubThemeResolver;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? SubThemeResolver::forCurrentRoute();
    $voodbuilderContentChannel = app(ContentChannelRegistry::class)->matchesCurrentRequest()?->id();
    $voodbuilderBodyClass = trim((string) $__env->yieldContent('body_class'));
    $voodbuilderHasDocSidebar = str_contains($voodbuilderBodyClass, 'voodbuilder-has-doc-sidebar');
    $voodbuilderShowReadingProgress = str_contains($voodbuilderBodyClass, 'voodbuilder-has-reading-progress');
    $voodbuilderViteEntries = \Voodflow\Voodbuilder\Support\Editor\EditorAssets::pageViteEntries(
        $editorEditor ?? false,
        $chromeLayoutEditor ?? false,
        $voodbuilderSubTheme,
    );
    $voodbuilderEditorAssetsReady = ! ($editorEditor ?? false) || \Voodflow\Voodbuilder\Support\Editor\EditorAssets::isBuilt();
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-voodbuilder-sub-theme="{{ $voodbuilderSubTheme }}"
    @if (filled($voodbuilderContentChannel))
        data-voodbuilder-content-channel="{{ $voodbuilderContentChannel }}"
    @endif
    @class(['dark' => \Voodflow\Voodbuilder\Support\VoodbuilderTheme::serverInitialDark()])
>
<head>
    <x-voodbuilder::theme-script />
    <x-voodbuilder::theme-vars :sub-theme="$voodbuilderSubTheme" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    @endphp
    <title>{{ $title ?? VoodbuilderSettings::siteTitle() }}</title>

    {!! seo() !!}

    <x-voodbuilder::geo-ai-meta />

    @unless ($editorEditor ?? false)
        @include('cookie-consent::cookie-consent-head')
    @endunless

    @if ($voodbuilderEditorAssetsReady)
        @vite($voodbuilderViteEntries)
    @elseif ($editorEditor ?? false)
        <style>.voodbuilder-editor-frontend__notice{margin:1rem;padding:1rem;border:1px solid #f59e0b;border-radius:.5rem;background:#fffbeb;color:#92400e;font-size:.875rem}</style>
    @endif
    @stack('head')
</head>
<body class="flex min-h-screen flex-col {{ trim(implode(' ', array_filter([trim((string) $__env->yieldContent('body_class')), trim((string) $__env->yieldContent('body_class_extra'))]))) }}">
    @unless ($hideSiteNav ?? false)
        <x-voodbuilder::nav
            :has-doc-sidebar="$voodbuilderHasDocSidebar"
            :show-reading-progress="$voodbuilderShowReadingProgress"
        />
    @endunless

    <main class="flex-1">
        @yield('content')
    </main>

    @if(config('voodbuilder.footer.enabled', true) && ! ($hideSiteFooter ?? false))
        <x-voodbuilder::footer />
    @endif

    @unless ($editorEditor ?? false)
        @include('cookie-consent::cookie-consent-body')
    @endunless
    <x-voodbuilder::monitoring-scripts />
    <x-voodbuilder::popups-boot />

    @stack('scripts-before-livewire')
    {{-- Livewire JS/CSS auto-inject when a component is rendered; avoid shipping 500KB+ on static pages. --}}
    <x-voodbuilder::site-scripts />
    @stack('scripts')
    @stack('overlays')
</body>
</html>
