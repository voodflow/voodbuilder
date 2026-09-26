@props([
    'item',
    'depth' => 0,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $children = $item->navigationChildren();
    $hasChildren = $children->isNotEmpty();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
    $paddingClass = match ($depth) {
        0 => '',
        1 => 'pl-3',
        default => 'pl-6',
    };
    $isRich = $item->hasRichPresentation();
@endphp

@if ($hasChildren && $depth < 2)
    <li data-voodbuilder-nav-mobile-item @class([$paddingClass, 'is-open' => $isActive])>
        <button
            type="button"
            data-voodbuilder-nav-mobile-toggle
            @class([
                'voodbuilder-mobile-nav__link w-full',
                'is-active' => $isActive,
            ])
            aria-expanded="{{ $isActive ? 'true' : 'false' }}"
        >
            <span class="voodbuilder-nav-menu-item__text">
                <span class="voodbuilder-nav-menu-item__label">{{ __($item->label) }}</span>
            </span>
            <svg
                data-voodbuilder-nav-mobile-chevron
                @class([
                    'h-4 w-4 shrink-0 transition-transform duration-300 ease-out',
                    'rotate-180' => $isActive,
                ])
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div
            data-voodbuilder-nav-mobile-panel
            @class(['is-open' => $isActive])
            aria-hidden="{{ $isActive ? 'false' : 'true' }}"
        >
            <ul class="voodbuilder-mobile-nav__sublinks">
                @if ($hasParentLink)
                    <li>
                        <a
                            href="{{ $item->resolveUrl() }}"
                            @class([
                                'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary',
                                'voodbuilder-mobile-nav__link--rich' => $item->hasRichPresentation(),
                                'is-active' => $item->isSelfActive(),
                            ])
                            @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            data-mobile-nav-close
                        >
                            <x-voodbuilder::menu-nav-item-content :item="$item" />
                        </a>
                    </li>
                @endif

                @foreach ($children as $child)
                    <x-voodbuilder::menu-nav-mobile-item :item="$child" :depth="$depth + 1" />
                @endforeach
            </ul>
        </div>
    </li>
@else
    <li @class([$paddingClass])>
        <a
            href="{{ $item->resolveUrl() }}"
            @class([
                'voodbuilder-mobile-nav__link',
                'voodbuilder-mobile-nav__link--secondary' => $depth > 0,
                'voodbuilder-mobile-nav__link--rich' => $isRich,
                'is-active' => $isActive,
            ])
            @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
            data-mobile-nav-close
        >
            <x-voodbuilder::menu-nav-item-content :item="$item" />
            @if ($item->isExternal())
                <x-voodbuilder::external-link-icon />
            @endif
        </a>
    </li>
@endif
