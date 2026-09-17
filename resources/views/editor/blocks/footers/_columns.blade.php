@php
    use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;

    $redistribute = $redistribute ?? SiteFooterConfig::columnsRedistribute($config);
    $colStartClasses = [
        1 => 'lg:col-start-1',
        2 => 'lg:col-start-2',
        3 => 'lg:col-start-3',
        4 => 'lg:col-start-4',
    ];
@endphp

@for ($index = 1; $index <= 4; $index++)
    @php
        $menuSlug = 'footer_col_'.$index;
        $columnChrome = SiteFooterConfig::chromeAttributes($config, 'footer-col-'.$index, $preview);
        $columnTitle = SiteFooterColumnPlacements::columnTitle($index);
        $columnVisible = SiteFooterConfig::isFooterColumnVisible($config, $index);
    @endphp
    <div
        @class([
            'min-w-0 px-4 mb-10 w-full',
            'basis-full lg:mb-0 lg:basis-0 lg:flex-1' => $redistribute,
            $colStartClasses[$index] => ! $redistribute && $columnVisible,
            $columnChrome['class'],
        ])
        data-voodbuilder-footer-col="{{ $index }}"
        data-voodbuilder-chrome="footer-col-{{ $index }}"
        {!! $columnChrome['attr'] !!}
    >
        <h2 class="title-font font-medium text-vp-text-1 text-sm mb-3" data-voodbuilder-footer-title>
            {{ $columnTitle }}
        </h2>
        <nav class="list-none w-full" data-voodbuilder-menu="{{ $menuSlug }}" aria-label="{{ $columnTitle }}"></nav>
    </div>
@endfor
