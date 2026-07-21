@php
    use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
    use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
    use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
    use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHostChrome;
    use Voodflow\Voodbuilder\Support\SubThemeResolver;
    use Voodflow\Voodbuilder\Support\ThemePalette;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? $vpressSubTheme ?? SubThemeResolver::forCurrentRoute();
    $voodbuilderContentChannel = app(ContentChannelRegistry::class)->matchesCurrentRequest()?->id();
    $suppressHostChrome = GrapesJsHostChrome::shouldSuppressHostRender($grapesJsEditor ?? null);
    $voodbuilderViteEntries = \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets::pageViteEntries(
        $grapesJsEditor ?? false,
        $chromeLayoutEditor ?? false,
        $voodbuilderSubTheme,
    );
    $voodbuilderEditorAssetsReady = ! ($grapesJsEditor ?? false) || \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsAssets::isBuilt();
    $chromeLayout = $voodbuilderChromeLayout ?? ChromeLayoutResolver::activeLayout();
    $chromeRendered = $chromeLayout && ! $suppressHostChrome
        ? app(ChromeLayoutRenderer::class)->render($chromeLayout)
        : ['before' => '', 'after' => '', 'css' => '', 'js' => ''];
    $chromeShellSubTheme = $voodbuilderSubTheme;
    $chromeShellCss = $chromeRendered['before'] !== '' || $chromeRendered['after'] !== ''
        ? ThemePalette::criticalChromeShellCss($chromeShellSubTheme)
        : '';
    $voodbuilderBodyClass = trim((string) $__env->yieldContent('body_class'));
    $voodbuilderHasDocSidebar = str_contains($voodbuilderBodyClass, 'voodbuilder-has-doc-sidebar');
    $voodbuilderShowReadingProgress = str_contains($voodbuilderBodyClass, 'voodbuilder-has-reading-progress');
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

    @unless ($grapesJsEditor ?? false)
        @include('cookie-consent::cookie-consent-head')
    @endunless

    @if ($voodbuilderEditorAssetsReady)
        @vite($voodbuilderViteEntries)
    @elseif ($grapesJsEditor ?? false)
        <style>.voodbuilder-grapesjs-frontend__notice{margin:1rem;padding:1rem;border:1px solid #f59e0b;border-radius:.5rem;background:#fffbeb;color:#92400e;font-size:.875rem}</style>
    @endif
    @if ($suppressHostChrome)
        <style id="voodbuilder-grapesjs-host-chrome-critical">{!! GrapesJsHostChrome::criticalHideCss() !!}</style>
    @endif
    @if ($chromeRendered['css'] !== '')
        <style id="voodbuilder-chrome-layout-css">{!! $chromeRendered['css'] !!}</style>
    @endif
    @if ($chromeShellCss !== '')
        <style id="voodbuilder-chrome-shell-theme">{!! $chromeShellCss !!}</style>
    @endif
    {{-- Livewire assets auto-inject only when a component is on the page (inject_assets=true). --}}
    @stack('head')
</head>
<body class="flex min-h-screen flex-col {{ trim(implode(' ', array_filter([trim((string) $__env->yieldContent('body_class')), trim((string) $__env->yieldContent('body_class_extra'))]))) }}">
    @if ($chromeRendered['before'] !== '')
        <div data-voodbuilder-chrome-shell data-voodbuilder-sub-theme="{{ $chromeShellSubTheme }}">
            {!! $chromeRendered['before'] !!}
        </div>
    @endif

    <main class="flex flex-1 flex-col min-h-0">
        @yield('content')
    </main>

    @if ($chromeRendered['after'] !== '')
        <div data-voodbuilder-chrome-shell data-voodbuilder-sub-theme="{{ $chromeShellSubTheme }}">
            {!! $chromeRendered['after'] !!}
        </div>
    @endif

    @unless ($grapesJsEditor ?? false)
        @include('cookie-consent::cookie-consent-body')
    @endunless
    <x-voodbuilder::monitoring-scripts />
    <x-voodbuilder::popups-boot />

    @stack('scripts-before-livewire')
    {{-- Livewire JS/CSS auto-inject when a component is rendered; avoid shipping 500KB+ on static pages. --}}
    <x-voodbuilder::site-scripts />
    @if ($chromeRendered['js'] !== '')
        <script>{!! $chromeRendered['js'] !!}</script>
    @endif
    @stack('scripts')
    @stack('overlays')
</body>
</html>
