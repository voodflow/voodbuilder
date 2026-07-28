@props([
    'item',
    'linkClass' => 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1',
    'mobile' => false,
    'canvasPreview' => false,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $children = $item->navigationChildren();
    $hasChildren = $children->isNotEmpty();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
@endphp

@if ($hasChildren)
    @if ($mobile)
        <li data-voodbuilder-nav-mobile-item @class(['is-open' => $isActive])>
            <button
                type="button"
                data-voodbuilder-nav-mobile-toggle
                @if ($canvasPreview) data-gjs-type="voodbuilder-nav-menu-button" @endif
                @class([
                    'voodbuilder-mobile-nav__link w-full',
                    'is-active' => $isActive,
                ])
                aria-expanded="{{ $isActive ? 'true' : 'false' }}"
            >
                <span>{{ __($item->label) }}</span>
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

            <div data-voodbuilder-nav-mobile-panel @unless($isActive) hidden @endunless>
                <ul class="mt-1 space-y-1 pl-3">
                @if ($hasParentLink)
                    <li>
                        <a
                            href="{{ $item->resolveUrl() }}"
                            @class([
                                'voodbuilder-mobile-nav__link voodbuilder-mobile-nav__link--secondary',
                                'is-active' => $item->isSelfActive(),
                            ])
                            @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            data-mobile-nav-close
                        >
                            <span>{{ __($item->label) }}</span>
                        </a>
                    </li>
                @endif

                @foreach ($children as $child)
                    <x-voodbuilder::menu-nav-mobile-item :item="$child" :depth="1" />
                @endforeach
                </ul>
            </div>
        </li>
    @else
        <div
            class="relative"
            data-voodbuilder-nav-dropdown
            data-voodbuilder-nav-dropdown-trigger="click"
        >
            <button
                type="button"
                data-voodbuilder-nav-dropdown-toggle
                @if ($canvasPreview) data-gjs-type="voodbuilder-nav-menu-button" @endif
                @class([
                    $linkClass,
                    'text-vp-brand-1' => $isActive,
                ])
                aria-haspopup="menu"
                aria-expanded="false"
            >
                <span>{{ __($item->label) }}</span>
                <svg class="h-4 w-4 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                data-voodbuilder-nav-dropdown-panel
                hidden
                role="menu"
                class="absolute top-[calc(100%+0.5rem)] left-0 z-50 min-w-[14rem] overflow-visible rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
            >
                @if ($hasParentLink)
                    <a
                        href="{{ $item->resolveUrl() }}"
                        role="menuitem"
                        @class([
                            'block px-3 py-2 text-sm font-medium transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
                            'text-vp-brand-1' => $item->isSelfActive(),
                            'text-vp-text-1' => ! $item->isSelfActive(),
                        ])
                        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ __($item->label) }}
                    </a>

                    <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
                @endif

                @foreach ($children as $child)
                    <x-voodbuilder::menu-nav-dropdown-item :item="$child" :depth="0" />
                @endforeach
            </div>
        </div>
    @endif
@else
    @if ($mobile)
        <li>
            <a
                href="{{ $item->resolveUrl() }}"
                @class([
                    'voodbuilder-mobile-nav__link',
                    'is-active' => $isActive,
                ])
                @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                data-mobile-nav-close
            >
                <span>{{ __($item->label) }}</span>
                @if ($item->isExternal())
                    <x-voodbuilder::external-link-icon />
                @endif
            </a>
        </li>
    @else
        <a
            href="{{ $item->resolveUrl() }}"
            @class([
                $linkClass,
                'text-vp-brand-1' => $isActive,
            ])
            @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
        >
            <span>{{ __($item->label) }}</span>
            @if ($item->isExternal())
                <x-voodbuilder::external-link-icon />
            @endif
        </a>
    @endif
@endif
