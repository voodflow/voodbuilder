@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $brandChrome = SiteFooterConfig::chromeAttributes($config, 'brand', $preview);
    $brandColumnChrome = SiteFooterConfig::chromeAttributes($config, 'brand-column', $preview);
@endphp

<div
    @class([
        'mx-auto w-full max-w-xs shrink-0 px-4 text-center md:mx-0 md:w-64 md:max-w-none md:text-left',
        $brandColumnChrome['class'],
    ])
    data-voodbuilder-footer-brand-col
    data-voodbuilder-chrome="brand-column"
    {!! $brandColumnChrome['attr'] !!}
>
    <div @class([$brandChrome['class']]) data-voodbuilder-chrome="brand" {!! $brandChrome['attr'] !!}>
        <div data-voodbuilder-brand></div>
    </div>
    @include('voodbuilder::grapesjs.blocks.partials.footer-tagline', [
        'config' => $config,
        'preview' => $preview,
        'chromeKind' => 'footer-tagline',
        'taglineClass' => 'mt-3 text-sm text-vp-text-2',
    ])
    @include('voodbuilder::grapesjs.blocks.partials.footer-copyright', [
        'config' => $config,
        'preview' => $preview,
        'copyrightClass' => 'mt-4 text-xs text-vp-text-3',
    ])
    @include('voodbuilder::grapesjs.blocks.partials.footer-social', [
        'config' => $config,
        'preview' => $preview,
        'socialClass' => 'mt-4',
    ])
</div>
