@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;

    $redistribute = SiteFooterConfig::columnsRedistribute($config);
    $visibleMenuColumns = SiteFooterConfig::visibleFooterColumnIndexes($config);
    $menuColumnsAlign = $redistribute && count($visibleMenuColumns) === 1 ? 'md:justify-end' : '';
@endphp

<div class="voodbuilder-gjs-container px-5 py-24">
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
