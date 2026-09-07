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
    $voodbuilderHostViteReady = \Voodflow\Voodbuilder\Support\Editor\EditorAssets::hostViteReady();
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

    {{-- Page fonts before theme CSS so @font-face + woff2 preload start as early as possible. --}}
    @stack('fonts')

    @if ($voodbuilderHostViteReady && $voodbuilderEditorAssetsReady)
        @vite($voodbuilderViteEntries)
    @elseif (! $voodbuilderHostViteReady)
        <style>
            .voodbuilder-vite-missing{margin:1rem auto;max-width:40rem;padding:1rem 1.25rem;border-radius:.75rem;background:#fffbeb;color:#92400e;font:14px/1.5 system-ui,sans-serif}
            .voodbuilder-vite-missing code{font-size:.9em}
        </style>
    @elseif ($editorEditor ?? false)
        <style>.voodbuilder-editor-frontend__notice{margin:1rem;padding:1rem;border:1px solid #f59e0b;border-radius:.5rem;background:#fffbeb;color:#92400e;font-size:.875rem}</style>
    @endif
    @stack('head')
</head>
<body class="flex min-h-screen flex-col {{ trim(implode(' ', array_filter([trim((string) $__env->yieldContent('body_class')), trim((string) $__env->yieldContent('body_class_extra'))]))) }}">
    @unless ($voodbuilderHostViteReady)
        <div class="voodbuilder-vite-missing" role="status">
            Frontend assets are not built yet. From the Laravel app root run
            <code>php artisan voodbuilder:install</code>
            (builds when npm is available) or <code>npm run build</code> / <code>npm run dev</code>.
        </div>
    @endunless
    @unless ($hideSiteNav ?? false)
        <x-voodbuilder::nav
            :has-doc-sidebar="$voodbuilderHasDocSidebar"
            :show-reading-progress="$voodbuilderShowReadingProgress"
        />
    @endunless

    {{-- Content-driven height (matches editor). Opt-in sticky footer: body.voodbuilder-sticky-footer --}}
    <main>
        @yield('content')
    </main>

    @if(config('voodbuilder.footer.enabled', true) && ! ($hideSiteFooter ?? false))
        <x-voodbuilder::footer />
    @endif

    <x-voodbuilder::monitoring-scripts />
    <x-voodbuilder::popups-boot />

    @stack('scripts-before-livewire')
    {{-- Livewire JS/CSS auto-inject when a component is rendered; avoid shipping 500KB+ on static pages. --}}
    <x-voodbuilder::site-scripts />
    @stack('scripts')
    @stack('overlays')
</body>
</html>
