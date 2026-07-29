@props([
    'item',
    'linkClass' => '',
    'depth' => 0,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $children = $item->navigationChildren();
    $hasChildren = $children->isNotEmpty();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
    // Admin max depth is 2 (root + one nested level). Dynamic type children (e.g. docs
    // sections) are a logical 3rd level — expose them as a flyout, not flatten.
    $allowFlyout = $hasChildren && $depth < 1;
@endphp

@if ($allowFlyout)
    <div
        class="relative"
        data-voodbuilder-nav-dropdown
        data-voodbuilder-nav-dropdown-trigger="hover"
        data-voodbuilder-nav-dropdown-nested
    >
        <button
            type="button"
            data-voodbuilder-nav-dropdown-toggle
            role="menuitem"
            @class([
                $linkClass,
                'justify-between',
                'text-vp-brand-1' => $isActive,
            ])
            aria-haspopup="menu"
            aria-expanded="false"
        >
            <span>{{ __($item->label) }}</span>
            <svg class="h-3.5 w-3.5 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        <div
            data-voodbuilder-nav-dropdown-panel
            hidden
            role="menu"
            class="voodbuilder-dropdown-panel absolute top-0 left-[calc(100%-0.25rem)] z-50"
        >
            @if ($hasParentLink)
                <a
                    href="{{ $item->resolveUrl() }}"
                    role="menuitem"
                    @class([
                        $linkClass,
                        'text-vp-brand-1' => $item->isSelfActive(),
                    ])
                    @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ __($item->label) }}
                </a>

                <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
            @endif

            @foreach ($children as $child)
                <x-voodbuilder::menu-nav-dropdown-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </div>
    </div>
@else
    <a
        href="{{ $item->resolveUrl() }}"
        role="menuitem"
        @class([
            $linkClass,
            'text-vp-brand-1' => $isActive,
        ])
        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    >
        {{ __($item->label) }}
    </a>
@endif
