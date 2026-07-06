@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterConfig;
    use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;

    $redistribute = $redistribute ?? SiteFooterConfig::columnsRedistribute($config);
    $colStartClasses = [
        1 => 'md:col-start-1',
        2 => 'md:col-start-2',
        3 => 'md:col-start-3',
        4 => 'md:col-start-4',
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
            'basis-full md:mb-0 md:basis-0 md:flex-1' => $redistribute,
            $colStartClasses[$index] => ! $redistribute && $columnVisible,
            $columnChrome['class'],
        ])
        data-voodbuilder-footer-col="{{ $index }}"
        data-voodbuilder-chrome="footer-col-{{ $index }}"
        {!! $columnChrome['attr'] !!}
    >
        <h2 class="title-font font-medium text-vp-text-1 tracking-widest text-sm mb-3" data-voodbuilder-footer-title>
            {{ $columnTitle }}
        </h2>
        <nav class="list-none" data-voodbuilder-menu="{{ $menuSlug }}" aria-label="{{ $columnTitle }}"></nav>
    </div>
@endfor
