@php
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\NavigationMenuResolver;

    $items = Navigation::items($menuSlug);
@endphp

@if ($items->isNotEmpty())
    <ul class="list-none space-y-2">
        @include('voodbuilder::grapesjs.blocks.partials.footer-menu-list', ['items' => $items])
    </ul>
@elseif ($preview ?? false)
    <ul class="list-none space-y-2">
        @include('voodbuilder::grapesjs.blocks.partials.footer-menu-empty', ['menuSlug' => NavigationMenuResolver::placementLabel($menuSlug)])
    </ul>
@endif
