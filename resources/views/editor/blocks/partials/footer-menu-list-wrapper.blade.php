@php
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\NavigationMenuResolver;

    $items = Navigation::items($menuSlug);
    $inline = $menuSlug === 'footer';
    $listClass = $inline
        ? 'list-none flex flex-wrap justify-center gap-x-6 gap-y-2'
        : 'list-none space-y-2';
@endphp

@if ($items->isNotEmpty())
    <ul class="{{ $listClass }}">
        @include('voodbuilder::editor.blocks.partials.footer-menu-list', ['items' => $items])
    </ul>
@elseif ($preview ?? false)
    <ul class="{{ $listClass }}">
        @include('voodbuilder::editor.blocks.partials.footer-menu-empty', ['menuSlug' => NavigationMenuResolver::placementLabel($menuSlug)])
    </ul>
@endif
