@php
    use Voodflow\Voodbuilder\Support\SubThemeResolver;

    $voodbuilderSubTheme = SubThemeResolver::forCurrentRoute();
@endphp

<x-voodbuilder::theme-script />
<x-voodbuilder::theme-vars :sub-theme="$voodbuilderSubTheme" />
@vite(config('voodbuilder.assets.vite', \Voodflow\Voodbuilder\Support\VoodbuilderPaths::defaultViteEntries()))
