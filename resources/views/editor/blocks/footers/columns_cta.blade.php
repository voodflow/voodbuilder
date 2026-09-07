<div class="voodbuilder-editor-container px-5 py-24">
    <div class="rounded-2xl border border-vp-divider bg-vp-bg-alt px-6 py-10 text-center md:px-12">
        <h2 class="text-lg font-semibold tracking-tight text-vp-text-1" data-voodbuilder-footer-cta-title>
            {{ __('voodbuilder::pro.editor.blocks.footer_cta_title') }}
        </h2>
        <p class="mx-auto mt-2 max-w-xl text-sm text-vp-text-2" data-voodbuilder-footer-cta-text>
            {{ __('voodbuilder::pro.editor.blocks.footer_cta_text') }}
        </p>
        <div class="mt-6 flex justify-center">
            <nav data-voodbuilder-menu="footer_col_1" aria-label="{{ __('voodbuilder::pro.editor.blocks.footer_col_1_title') }}"></nav>
        </div>
    </div>
    <div class="mt-12 flex flex-wrap md:text-left text-center -mx-4 -mb-10">
        @include('voodbuilder::editor.blocks.footers._columns', ['config' => $config])
    </div>
</div>
