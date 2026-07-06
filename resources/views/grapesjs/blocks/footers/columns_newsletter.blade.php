<div class="container mx-auto px-5 py-24">
    <div
        class="mb-12 flex flex-col items-center gap-4 rounded-2xl border border-vp-divider bg-vp-bg-alt px-6 py-8 text-center md:flex-row md:justify-between md:text-left"
        data-voodbuilder-chrome="newsletter"
    >
        <div>
            <h2 class="text-base font-semibold text-vp-text-1" data-voodbuilder-footer-newsletter-title>
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_title') }}
            </h2>
            <p class="mt-1 text-sm text-vp-text-2" data-voodbuilder-footer-newsletter-text>
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_text') }}
            </p>
        </div>
        <form class="flex w-full max-w-md gap-2" action="#" method="post" onsubmit="return false;">
            <input type="email" class="min-w-0 flex-1 rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-sm text-vp-text-1" placeholder="{{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_placeholder') }}" />
            <button type="button" class="shrink-0 rounded-lg bg-vp-brand-1 px-4 py-2 text-sm font-semibold text-white">
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_button') }}
            </button>
        </form>
    </div>
    <div class="flex flex-wrap md:text-left text-center -mx-4 -mb-10">
        @include('voodbuilder::grapesjs.blocks.footers._columns', ['config' => $config])
    </div>
</div>
