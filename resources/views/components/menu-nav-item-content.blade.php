@props([
    'item',
    'showDescription' => true,
])

@php
    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $icon = $item->resolvedIcon();
    $description = $showDescription ? $item->resolvedDescription() : null;
@endphp

@if ($icon)
    <span class="voodbuilder-nav-menu-item__icon" aria-hidden="true">
        <x-voodbuilder::tabler-icon :name="$icon" class="h-5 w-5" />
    </span>
@endif

<span class="voodbuilder-nav-menu-item__text">
    <span class="voodbuilder-nav-menu-item__label">{{ __($item->label) }}</span>
    @if ($description)
        <span class="voodbuilder-nav-menu-item__description">{{ __($description) }}</span>
    @endif
</span>
