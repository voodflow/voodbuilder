@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $socialChrome = SiteFooterConfig::chromeAttributes($config, 'social', $preview);
    $socialClass = $socialClass ?? '';
@endphp

<div
    @class([$socialClass, $socialChrome['class']])
    data-voodbuilder-menu="social"
    data-voodbuilder-menu-variant="social"
    data-voodbuilder-footer-social
    data-voodbuilder-chrome="social"
    aria-label="{{ __('Social') }}"
    {!! $socialChrome['attr'] !!}
></div>
