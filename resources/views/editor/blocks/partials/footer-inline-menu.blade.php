@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;

    $menuChrome = SiteFooterConfig::chromeAttributes($config, 'footer-menu', $preview);
    $menuClass = $menuClass ?? 'flex flex-wrap justify-center gap-x-6 gap-y-2';
@endphp

<nav
    @class([$menuClass, $menuChrome['class']])
    data-voodbuilder-menu="footer"
    data-voodbuilder-chrome="footer-menu"
    aria-label="{{ __('Footer') }}"
    {!! $menuChrome['attr'] !!}
></nav>
