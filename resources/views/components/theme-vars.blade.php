@php
    use Voodflow\Voodbuilder\Support\ThemePalette;

    $css = ThemePalette::css();
@endphp
@if ($css !== '')
<style id="voodbuilder-theme-palette">{!! $css !!}</style>
@endif
