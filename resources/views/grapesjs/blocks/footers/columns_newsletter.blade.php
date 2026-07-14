@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $newsletterChrome = SiteFooterConfig::chromeAttributes($config, 'newsletter', $preview);
    $redistribute = SiteFooterConfig::columnsRedistribute($config);
    $visibleMenuColumns = SiteFooterConfig::visibleFooterColumnIndexes($config);
    $menuColumnsAlign = $redistribute && count($visibleMenuColumns) === 1 ? 'md:justify-end' : '';
@endphp

<div class="voodbuilder-gjs-container px-5 py-24">
    <div
        @class([
            'mb-12 flex flex-col items-center gap-4 rounded-2xl border border-vp-divider bg-vp-bg-alt px-6 py-8 text-center md:flex-row md:justify-between md:text-left',
            $newsletterChrome['class'],
        ])
        data-voodbuilder-chrome="newsletter"
        {!! $newsletterChrome['attr'] !!}
    >
        <div>
            <h2 class="text-base font-semibold text-vp-text-1" data-voodbuilder-footer-newsletter-title>
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_title') }}
            </h2>
            <p class="mt-1 text-sm text-vp-text-2" data-voodbuilder-footer-newsletter-text>
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_text') }}
            </p>
        </div>
        <form
            @class([
                'flex w-full max-w-md gap-2 vb-gjs-form vb-gjs-newsletter-form',
            ])
            data-voodbuilder-form="newsletter"
            method="post"
            @if($preview) action="#" onsubmit="return false;" @endif
        >
            <input type="email" name="email" required class="min-w-0 flex-1 rounded-lg border border-vp-divider bg-vp-bg px-3 py-2 text-sm text-vp-text-1" placeholder="{{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_placeholder') }}" />
            <button type="{{ $preview ? 'button' : 'submit' }}" class="shrink-0 rounded-lg bg-vp-brand-1 px-4 py-2 text-sm font-semibold text-white">
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_newsletter_button') }}
            </button>
        </form>
    </div>
    <div class="flex flex-col flex-wrap md:flex-row md:flex-nowrap md:items-start lg:items-start">
        @include('voodbuilder::grapesjs.blocks.footers._brand_column', ['config' => $config, 'preview' => $preview])
        <div @class([
            'mt-10 w-full grow md:mt-0 md:pl-12 md:text-left text-center',
            'flex flex-wrap md:flex-nowrap' => $redistribute,
            'grid grid-cols-1 md:grid-cols-4' => ! $redistribute,
            $menuColumnsAlign,
        ]) data-voodbuilder-footer-menu-cols data-voodbuilder-footer-columns-redistribute="{{ $redistribute ? '1' : '0' }}">
            @include('voodbuilder::grapesjs.blocks.footers._columns', [
                'config' => $config,
                'preview' => $preview,
                'redistribute' => $redistribute,
            ])
        </div>
    </div>
</div>
