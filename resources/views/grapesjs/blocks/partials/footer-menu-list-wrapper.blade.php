@php
    use Voodflow\Vpress\Support\Navigation;
    use Voodflow\Vpress\Support\NavigationMenuResolver;

    $items = Navigation::items($menuSlug);
@endphp

@if ($items->isNotEmpty())
    <ul class="list-none space-y-2">
        @include('vpress::grapesjs.blocks.partials.footer-menu-list', ['items' => $items])
    </ul>
@elseif ($preview ?? false)
    <ul class="list-none space-y-2">
        @include('vpress::grapesjs.blocks.partials.footer-menu-empty', ['menuSlug' => NavigationMenuResolver::placementLabel($menuSlug)])
    </ul>
@endif
