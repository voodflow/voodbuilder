<div class="voodbuilder-gjs-container px-5 py-24">
    <div class="flex flex-col flex-wrap md:flex-row md:flex-nowrap md:items-center lg:items-start">
        <div class="order-first flex grow flex-wrap md:order-none md:pr-12 md:text-left text-center">
            @include('voodbuilder::grapesjs.blocks.footers._columns', ['config' => $config])
        </div>
        <div class="mx-auto mt-10 w-64 shrink-0 px-4 text-center md:mx-0 md:mt-0 md:text-left">
            <div data-voodbuilder-brand></div>
            @include('voodbuilder::grapesjs.blocks.partials.footer-tagline', [
                'config' => $config,
                'preview' => $preview ?? false,
                'chromeKind' => 'footer-tagline',
                'taglineClass' => 'mt-3 text-sm text-vp-text-2',
            ])
        </div>
    </div>
</div>
