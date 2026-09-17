@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;

    $redistribute = SiteFooterConfig::columnsRedistribute($config);
    $visibleMenuColumns = SiteFooterConfig::visibleFooterColumnIndexes($config);
    $menuColumnsAlign = $redistribute && count($visibleMenuColumns) === 1 ? 'lg:justify-end' : '';
@endphp

<div class="voodbuilder-editor-container px-5 py-24">
    <div class="flex w-full flex-col flex-wrap lg:flex-row lg:flex-nowrap lg:items-start">
        @include('voodbuilder::editor.blocks.footers._brand_column', ['config' => $config, 'preview' => $preview])
        <div @class([
            'mt-10 min-w-0 w-full flex-1 grow lg:mt-0 lg:pl-12 lg:text-left text-center',
            'flex flex-wrap lg:flex-nowrap' => $redistribute,
            'grid w-full grid-cols-1 lg:grid-cols-4' => ! $redistribute,
            $menuColumnsAlign,
        ]) data-voodbuilder-footer-menu-cols data-voodbuilder-footer-columns-redistribute="{{ $redistribute ? '1' : '0' }}">
            @include('voodbuilder::editor.blocks.footers._columns', [
                'config' => $config,
                'preview' => $preview,
                'redistribute' => $redistribute,
            ])
        </div>
    </div>
</div>
