@php
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
    use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavSimpleBlock;
@endphp

@if ($placement === 'main' || $placement === 'header_extra')
    @php
        $headerConfig = array_merge(SiteNavSimpleBlock::defaultConfig(), [
            'show_profile_menu' => false,
            'show_notifications' => false,
        ]);
    @endphp
    {!! SiteNavSimpleBlock::toPreviewHtml($headerConfig, []) !!}
@elseif (str_starts_with($placement, 'footer_col_'))
    {!! SiteFooterColumnsSimpleBlock::toHtml(SiteFooterColumnsSimpleBlock::defaultConfig(), []) !!}
@endif
