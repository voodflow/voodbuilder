@php
    use Voodflow\Vpress\Models\VpressSettings;

    $brandName = VpressSettings::brandName();
@endphp

<div class="container mx-auto px-5 py-24">
    <div class="flex flex-col flex-wrap md:flex-row md:flex-nowrap md:items-center lg:items-start">
        <div class="order-first flex grow flex-wrap md:order-none md:pr-12 md:text-left text-center">
            @include('vpress::grapesjs.blocks.footers._columns', ['config' => $config])
        </div>
        <div class="mx-auto mt-10 w-64 shrink-0 px-4 text-center md:mx-0 md:mt-0 md:text-left">
            <div data-vpress-brand></div>
            <p class="mt-3 text-sm text-vp-text-2" data-vpress-footer-tagline>
                {{ __('vpress::pro.grapesjs.blocks.footer_default_tagline') }}
            </p>
        </div>
    </div>
</div>
