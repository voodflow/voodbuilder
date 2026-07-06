@php
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\NavigationMenuResolver;

    $items = Navigation::items($menuSlug);
    $display = Navigation::linkDisplay($menuSlug);
@endphp

@if ($items->isNotEmpty())
    <div class="inline-flex flex-wrap items-center justify-center gap-3 sm:justify-start">
        @foreach ($items as $item)
            <x-voodbuilder::social-menu-item :item="$item" :display="$display" />
        @endforeach
    </div>
@elseif ($preview ?? false)
    <div class="inline-flex flex-wrap items-center justify-center gap-3 text-xs text-vp-text-2 sm:justify-start">
        {{ __('voodbuilder::pro.grapesjs.blocks.site_footer_empty', ['menu' => NavigationMenuResolver::placementLabel($menuSlug)]) }}
    </div>
@endif
