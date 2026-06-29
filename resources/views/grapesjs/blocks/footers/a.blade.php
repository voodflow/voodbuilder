@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $brandName = VoodbuilderSettings::brandName();
@endphp

<div class="container mx-auto px-5 py-24">
    <div class="flex flex-col flex-wrap md:flex-row md:flex-nowrap md:items-center lg:items-start">
        <div class="mx-auto w-64 shrink-0 px-4 text-center md:mx-0 md:text-left">
            <div data-voodbuilder-brand></div>
            <p class="mt-3 text-sm text-vp-text-2" data-voodbuilder-footer-tagline>
                {{ __('voodbuilder::pro.grapesjs.blocks.footer_default_tagline') }}
            </p>
        </div>
        <div class="mt-10 flex grow flex-wrap md:mt-0 md:pl-12 md:text-left text-center">
            @include('voodbuilder::grapesjs.blocks.footers._columns', ['config' => $config])
        </div>
    </div>
</div>
