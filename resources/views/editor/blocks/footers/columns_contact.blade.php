@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\MenuTablerIcons;

    $splitDividers = SiteFooterConfig::verticalDividers($config);
    $brandColumnClass = 'mx-auto w-full max-w-sm shrink-0 px-4 text-center lg:mx-0 lg:w-80 lg:max-w-none lg:text-left';

    if ($splitDividers) {
        $brandColumnClass .= ' lg:border-r lg:border-vp-divider lg:pr-10';
    }

    $detailsColumn = SiteFooterConfig::textColumn($config, 'details');
    $endCopyrightClass = 'text-xs leading-6 text-vp-text-3';
@endphp

<div class="voodbuilder-editor-container px-5 py-24">
    <div class="flex w-full flex-col flex-wrap lg:flex-row lg:flex-nowrap lg:items-start">
        @include('voodbuilder::editor.blocks.footers._brand_column', [
            'config' => $config,
            'preview' => $preview,
            'brandColumnClass' => $brandColumnClass,
            'hideCopyright' => $splitDividers,
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
            @if (! $splitDividers)
                @include('voodbuilder::editor.blocks.footers._text_column', [
                    'config' => $config,
                    'column' => 'details',
                ])
            @endif
            @if ($splitDividers)
                @php($copyrightChrome = SiteFooterConfig::chromeAttributes($config, 'copyright', $preview))
                <div
                    class="min-w-0 px-4 mb-10 w-full lg:mb-0 lg:flex-1 lg:border-l lg:border-vp-divider lg:pl-10"
                    data-voodbuilder-footer-copyright-col
                >
                    <p
                        @class([$endCopyrightClass, $copyrightChrome['class']])
                        data-voodbuilder-footer-copyright
                        data-voodbuilder-chrome="copyright"
                        {!! $copyrightChrome['attr'] !!}
                    >{{ SiteFooterConfig::resolveCopyright(
                        is_string($config['copyright'] ?? null) ? $config['copyright'] : null,
                        \Voodflow\Voodbuilder\Models\VoodbuilderSettings::brandName(),
                    ) }}</p>
                    @if ($detailsColumn['lines'] !== [])
                        <ul class="mt-2 list-none space-y-1 text-sm leading-6 text-vp-text-2" data-voodbuilder-footer-text-lines="details">
                            @include('voodbuilder::editor.blocks.footers._text_column_items', [
                                'config' => $config,
                                'column' => 'details',
                            ])
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
