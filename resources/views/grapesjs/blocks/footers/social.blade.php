@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $brandChrome = SiteFooterConfig::chromeAttributes($config, 'brand', $preview);
@endphp

<div class="container mx-auto px-5 py-8">
    <div class="flex flex-col items-center sm:flex-row sm:items-center">
        <div class="flex shrink-0 flex-col items-center sm:items-start">
            <div @class([$brandChrome['class']]) data-voodbuilder-chrome="brand" {!! $brandChrome['attr'] !!}>
                <div data-voodbuilder-brand></div>
            </div>
            @include('voodbuilder::grapesjs.blocks.partials.footer-tagline', [
                'config' => $config,
                'preview' => $preview,
                'taglineClass' => 'mt-1 max-w-xs text-center text-xs text-vp-text-2 sm:text-left',
            ])
        </div>
        @include('voodbuilder::grapesjs.blocks.partials.footer-copyright', [
            'config' => $config,
            'preview' => $preview,
            'copyrightClass' => 'mt-4 text-sm text-vp-text-2 sm:ml-4 sm:mt-0 sm:border-l sm:border-vp-divider sm:py-2 sm:pl-4',
        ])
        @include('voodbuilder::grapesjs.blocks.partials.footer-inline-menu', [
            'config' => $config,
            'preview' => $preview,
            'menuClass' => 'mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 sm:ml-6 sm:mt-0',
        ])
        @include('voodbuilder::grapesjs.blocks.partials.footer-social', [
            'config' => $config,
            'preview' => $preview,
            'socialClass' => 'mt-4 sm:ml-auto sm:mt-0',
        ])
    </div>
</div>
