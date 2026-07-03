@php
    use Voodflow\Voodbuilder\Support\SubThemeResolver;

    $voodbuilderSubTheme = SubThemeResolver::forCurrentRoute();
@endphp
<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-voodbuilder-sub-theme="{{ $voodbuilderSubTheme }}"
    @class(['dark' => \Voodflow\Voodbuilder\Support\VoodbuilderTheme::serverInitialDark()])
>
<head>
    <x-voodbuilder::theme-script />
    <x-voodbuilder::theme-vars :sub-theme="$voodbuilderSubTheme" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('voodbuilder::admin.menu_preview.title') }}</title>
    @vite(config('voodbuilder.assets.vite', \Voodflow\Voodbuilder\Support\VoodbuilderPaths::defaultViteEntries()))
    @livewireStyles
    <style>
        .vb-menu-preview-badge {
            position: fixed;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 40;
            border-radius: 0.375rem;
            border: 1px dashed var(--vp-c-divider, #e2e8f0);
            background: var(--vp-c-bg, #fff);
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            color: var(--vp-c-text-2, #64748b);
        }
    </style>
</head>
<body class="min-h-screen bg-vp-bg text-vp-text-1">
    <div class="vb-menu-preview-badge">
        {{ $badge ?? '' }}
    </div>

    @yield('preview')

    @livewireScripts
    <x-voodbuilder::site-scripts />
</body>
</html>
