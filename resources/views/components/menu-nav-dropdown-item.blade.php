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

@if ($hasChildren)
    <div
        class="relative"
        x-data="{ open: false }"
        @mouseenter="open = true"
        @mouseleave="open = false"
    >
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
            <span
                role="menuitem"
                @class([
                    $linkClass,
                    'cursor-default font-medium text-vp-text-1',
                ])
            >
                {{ __($item->label) }}
            </span>
        @endif

        <div
            x-show="open"
            x-cloak
            x-transition
            role="menu"
            @class([
                'absolute z-50 min-w-[14rem] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg',
                'top-0 left-full ml-1' => $depth > 0,
                'top-[calc(100%+0.25rem)] left-0' => $depth === 0,
            ])
        >
            @foreach ($item->children as $child)
                @if ($child->hasChildren())
                    <x-voodbuilder::menu-nav-dropdown-item :item="$child" :depth="$depth + 1" />
                @else
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
                @endif
            @endforeach
        </div>
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
