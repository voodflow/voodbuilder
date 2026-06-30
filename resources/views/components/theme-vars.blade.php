@props([
    'subTheme' => null,
])

@php
    use Voodflow\Voodbuilder\Support\SubThemeResolver;
    use Voodflow\Voodbuilder\Support\ThemePalette;

    $resolvedSubTheme = $subTheme ?? SubThemeResolver::forCurrentRoute();
    $css = ThemePalette::criticalDocumentCss($resolvedSubTheme);
@endphp

@if ($css !== '')
    <style id="voodbuilder-theme-critical">{!! $css !!}</style>
@endif
