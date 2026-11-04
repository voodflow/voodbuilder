<div class="voodbuilder-editor-container px-5 py-24">
    <div class="flex w-full flex-col flex-wrap lg:flex-row lg:flex-nowrap lg:items-start">
        @include('voodbuilder::editor.blocks.footers._brand_column', [
            'config' => $config,
            'preview' => $preview,
            'brandColumnClass' => 'mx-auto w-full max-w-sm shrink-0 px-4 text-center lg:mx-0 lg:w-80 lg:max-w-none lg:text-left',
        ])
        <div
            class="mt-10 grid w-full min-w-0 flex-1 grid-cols-1 gap-x-8 text-left lg:mt-0 lg:grid-cols-4 lg:pl-12"
            data-voodbuilder-footer-menu-cols
            data-voodbuilder-footer-columns-flow="1"
        >
            @include('voodbuilder::editor.blocks.footers._columns', [
                'config' => $config,
                'preview' => $preview,
                'redistribute' => false,
                'useColStart' => false,
            ])
            @include('voodbuilder::editor.blocks.footers._text_column', [
                'config' => $config,
                'column' => 'contact',
            ])
            @include('voodbuilder::editor.blocks.footers._text_column', [
                'config' => $config,
                'column' => 'details',
            ])
        </div>
    </div>
</div>
