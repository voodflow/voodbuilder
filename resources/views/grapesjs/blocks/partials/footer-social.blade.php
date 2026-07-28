@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $config = is_array($config ?? null) ? $config : [];
    $normalized = SiteFooterConfig::normalize($config);
    $socialChrome = SiteFooterConfig::chromeAttributes($normalized, 'social', $preview);
    $socialClass = $socialClass ?? '';
    $socialAlign = SiteFooterConfig::normalizeSocialAlign($normalized['social_align'] ?? null);
    $socialJustify = SiteFooterConfig::socialJustifyClass($socialAlign);
@endphp

<div
    @class([
        $socialClass,
        $socialJustify,
        $socialChrome['class'],
        'flex',
        'flex-wrap',
    ])
    data-voodbuilder-menu="social"
    data-voodbuilder-menu-variant="social"
    data-voodbuilder-footer-social
    data-voodbuilder-social-align="{{ $socialAlign }}"
    data-voodbuilder-chrome="social"
    aria-label="{{ __('Social') }}"
    {!! $socialChrome['attr'] !!}
></div>
