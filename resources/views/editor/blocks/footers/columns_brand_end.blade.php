<div class="voodbuilder-editor-container px-5 py-24">
    <div class="flex flex-col flex-wrap lg:flex-row lg:flex-nowrap lg:items-start">
        <div class="order-first flex grow flex-wrap lg:order-none lg:pr-12 lg:text-left text-center">
            @include('voodbuilder::editor.blocks.footers._columns', ['config' => $config])
        </div>
        <div class="mx-auto mt-10 w-64 shrink-0 px-4 text-center lg:mx-0 lg:mt-0 lg:text-left">
            <div data-voodbuilder-brand></div>
            @include('voodbuilder::editor.blocks.partials.footer-tagline', [
                'config' => $config,
                'preview' => $preview ?? false,
                'chromeKind' => 'footer-tagline',
                'taglineClass' => 'mt-3 text-sm text-vp-text-2',
            ])
        </div>
    </div>
</div>
