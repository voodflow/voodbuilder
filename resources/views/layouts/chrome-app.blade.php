@php
    use Voodflow\Voodbuilder\Models\SitePage;
    use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
    use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
    use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
    use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
    use Voodflow\Voodbuilder\Support\Editor\EditorHostChrome;
    use Voodflow\Voodbuilder\Support\SubThemeResolver;
    use Voodflow\Voodbuilder\Support\ThemePalette;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? SubThemeResolver::forCurrentRoute();
    $voodbuilderContentChannel = app(ContentChannelRegistry::class)->matchesCurrentRequest()?->id();
    $suppressHostChrome = EditorHostChrome::shouldSuppressHostRender($editorEditor ?? null);
    $voodbuilderViteEntries = \Voodflow\Voodbuilder\Support\Editor\EditorAssets::pageViteEntries(
        $editorEditor ?? false,
        $chromeLayoutEditor ?? false,
        $voodbuilderSubTheme,
    );
    $voodbuilderEditorAssetsReady = ! ($editorEditor ?? false) || \Voodflow\Voodbuilder\Support\Editor\EditorAssets::isBuilt();
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
    $isEditor = (bool) ($editorEditor ?? false)
        || (bool) ($chromeLayoutEditor ?? false)
        || request()->boolean('edit');
    // Host document must stay full-bleed while editing — content width applies inside the
    // canvas iframe / published page only. Otherwise landing.css max-width on site-shell
    // shrinks the entire Editor workspace.
    $pageContentWidth = $isEditor
        ? ['mode' => ChromeLayoutContentWidth::MODE_FULL, 'maxWidth' => null, 'customMaxWidth' => null]
        : (isset($page) && $page instanceof SitePage
            ? ChromeLayoutContentWidth::resolve($chromeLayout instanceof \Voodflow\Voodbuilder\Models\ChromeLayout ? $chromeLayout : null, $page)
            : ($chromeLayout instanceof \Voodflow\Voodbuilder\Models\ChromeLayout
                ? ChromeLayoutContentWidth::fromLayout($chromeLayout)
                : ['mode' => ChromeLayoutContentWidth::MODE_FULL, 'maxWidth' => null, 'customMaxWidth' => null]));
    $pageContentMaxWidth = ChromeLayoutContentWidth::cssMaxWidth($pageContentWidth);
    $elementContentMaxWidth = is_string($pageContentWidth['customMaxWidth'] ?? null)
        ? $pageContentWidth['customMaxWidth']
        : null;
    $chromeWidth = $isEditor
        ? ChromeLayoutContentWidth::CHROME_FULL
        : ChromeLayoutContentWidth::resolveChromeWidth(
            $chromeLayout instanceof \Voodflow\Voodbuilder\Models\ChromeLayout ? $chromeLayout : null,
        );
    // Full content width must force the layout token to 100% — otherwise theme.css
    // keeps --width-vp-layout at 80rem and .voodbuilder-editor-container stays boxed.
    $pageWidthStyleParts = $isEditor
        ? ['--width-vp-layout: 100% !important']
        : (filled($pageContentMaxWidth)
            ? [
                '--voodbuilder-page-content-max: '.$pageContentMaxWidth,
                '--width-vp-layout: '.$pageContentMaxWidth.' !important',
            ]
            : ['--width-vp-layout: 100% !important']);
    if (! $isEditor && filled($elementContentMaxWidth)) {
        $pageWidthStyleParts[] = '--voodbuilder-element-content-max: '.$elementContentMaxWidth;
    }
    $pageWidthStyle = implode('; ', $pageWidthStyleParts);
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-voodbuilder-sub-theme="{{ $voodbuilderSubTheme }}"
    data-voodbuilder-page-width="{{ $pageContentWidth['mode'] }}"
    data-voodbuilder-chrome-width="{{ $chromeWidth }}"
    @if (filled($voodbuilderContentChannel))
        data-voodbuilder-content-channel="{{ $voodbuilderContentChannel }}"
    @endif
    @if (filled($pageWidthStyle))
        style="{{ $pageWidthStyle }}"
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

    {{-- Page fonts before theme CSS so @font-face + woff2 preload start as early as possible. --}}
    @stack('fonts')

    @if ($voodbuilderEditorAssetsReady)
        @vite($voodbuilderViteEntries)
    @elseif ($editorEditor ?? false)
        <style>.voodbuilder-editor-frontend__notice{margin:1rem;padding:1rem;border:1px solid #f59e0b;border-radius:.5rem;background:#fffbeb;color:#92400e;font-size:.875rem}</style>
    @endif
    @if ($suppressHostChrome)
        <style id="voodbuilder-editor-host-chrome-critical">{!! EditorHostChrome::criticalHideCss() !!}</style>
    @endif
    {{-- Page JIT CSS before chrome CSS: page sheets often re-emit base utilities without md:
         variants. Chrome CSS is scoped to [data-voodbuilder-chrome-shell] so its .w-full (etc.)
         cannot override page responsive utilities such as lg:w-1/2. --}}
    {{-- Livewire assets auto-inject only when a component is on the page (inject_assets=true). --}}
    @stack('head')
    @if ($chromeRendered['css'] !== '')
        <style id="voodbuilder-chrome-layout-css">{!! $chromeRendered['css'] !!}</style>
    @endif
    @if ($chromeShellCss !== '')
        <style id="voodbuilder-chrome-shell-theme">{!! $chromeShellCss !!}</style>
    @endif
</head>
<body
    class="flex min-h-screen flex-col {{ trim(implode(' ', array_filter([trim((string) $__env->yieldContent('body_class')), trim((string) $__env->yieldContent('body_class_extra'))]))) }}"
    data-voodbuilder-page-width="{{ $pageContentWidth['mode'] }}"
    data-voodbuilder-chrome-width="{{ $chromeWidth }}"
    @if (filled($pageWidthStyle))
        style="{{ $pageWidthStyle }}"
    @endif
>
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

    @unless ($editorEditor ?? false)
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
