@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $brandChrome = SiteFooterConfig::chromeAttributes($config, 'brand', $preview);
@endphp

<div class="container mx-auto px-5 py-16 text-center">
    <div class="mx-auto max-w-lg">
        <div @class([$brandChrome['class']]) data-voodbuilder-chrome="brand" {!! $brandChrome['attr'] !!}>
            <div data-voodbuilder-brand></div>
        </div>
        @include('voodbuilder::grapesjs.blocks.partials.footer-tagline', [
            'config' => $config,
            'preview' => $preview,
            'taglineClass' => 'mt-4 text-sm text-vp-text-2',
        ])
        @include('voodbuilder::grapesjs.blocks.partials.footer-inline-menu', [
            'config' => $config,
            'preview' => $preview,
            'menuClass' => 'mt-6 flex flex-wrap justify-center gap-x-6 gap-y-2',
        ])
        @include('voodbuilder::grapesjs.blocks.partials.footer-social', [
            'config' => $config,
            'preview' => $preview,
            'socialClass' => 'mt-6 flex justify-center',
        ])
        @include('voodbuilder::grapesjs.blocks.partials.footer-copyright', [
            'config' => $config,
            'preview' => $preview,
            'copyrightClass' => 'mt-8 text-xs text-vp-text-3',
        ])
    </div>
</div>
