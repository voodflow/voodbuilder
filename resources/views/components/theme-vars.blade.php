@php
    use Voodflow\Vpress\Support\ThemePalette;

    $css = ThemePalette::css();
@endphp
@if ($css !== '')
<style id="vpress-theme-palette">{!! $css !!}</style>
@endif
