@props([
    'item',
    'linkClass' => 'block px-3 py-2 text-sm transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
    'depth' => 0,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $hasChildren = $item->hasChildren();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
@endphp

@if ($hasChildren && $depth < 1)
    <div class="space-y-1">
        @if ($hasParentLink)
            <a
                href="{{ $item->resolveUrl() }}"
                role="menuitem"
                @class([
                    $linkClass,
                    'font-medium text-vp-brand-1' => $item->isSelfActive(),
                    'text-vp-text-1' => ! $item->isSelfActive(),
                ])
                @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ __($item->label) }}
            </a>
        @else
            <p class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-vp-text-3">
                {{ __($item->label) }}
            </p>
        @endif

        @foreach ($item->children as $child)
            <a
                href="{{ $child->resolveUrl() }}"
                role="menuitem"
                @class([
                    $linkClass,
                    'font-medium text-vp-brand-1' => $child->isActive(),
                    'text-vp-text-2' => ! $child->isActive(),
                ])
                @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ __($child->label) }}
            </a>
        @endforeach
    </div>
@else
    <a
        href="{{ $item->resolveUrl() }}"
        role="menuitem"
        @class([
            $linkClass,
            'font-medium text-vp-brand-1' => $isActive,
            'text-vp-text-2' => ! $isActive,
        ])
        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    >
        {{ __($item->label) }}
    </a>
@endif
