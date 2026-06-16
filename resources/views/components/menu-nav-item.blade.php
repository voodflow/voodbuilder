@props([
    'item',
    'linkClass' => 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1',
    'mobile' => false,
])

@php
    use Voodflow\Vpress\Enums\MenuItemType;

    /** @var \Voodflow\Vpress\Models\NavigationMenuItem $item */
    $hasChildren = $item->hasChildren();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
@endphp

@if ($hasChildren)
    @if ($mobile)
        <li x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
            <button
                type="button"
                @class([
                    'vpress-mobile-nav__link w-full',
                    'is-active' => $isActive,
                ])
                @click="open = ! open"
                :aria-expanded="open"
            >
                <span>{{ __($item->label) }}</span>
                <svg
                    class="h-4 w-4 shrink-0 transition-transform duration-300 ease-out"
                    :class="{ 'rotate-180': open }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-collapse.duration.300ms x-cloak>
                <ul class="mt-1 space-y-1 pl-3">
                @if ($hasParentLink)
                    <li>
                        <a
                            href="{{ $item->resolveUrl() }}"
                            @class([
                                'vpress-mobile-nav__link vpress-mobile-nav__link--secondary',
                                'is-active' => $item->isSelfActive(),
                            ])
                            @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            data-mobile-nav-close
                        >
                            <span>{{ __($item->label) }}</span>
                        </a>
                    </li>
                @endif

                @foreach ($item->children as $child)
                    <li>
                        <a
                            href="{{ $child->resolveUrl() }}"
                            @class([
                                'vpress-mobile-nav__link vpress-mobile-nav__link--secondary',
                                'is-active' => $child->isActive(),
                            ])
                            @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                            data-mobile-nav-close
                        >
                            <span>{{ __($child->label) }}</span>
                            @if ($child->isExternal())
                                <x-vpress::external-link-icon />
                            @endif
                        </a>
                    </li>
                @endforeach
                </ul>
            </div>
        </li>
    @else
        <div
            class="relative"
            x-data="{ open: false }"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
        >
            <button
                type="button"
                @class([
                    $linkClass,
                    'text-vp-brand-1' => $isActive,
                ])
                aria-haspopup="menu"
                :aria-expanded="open"
                @click="open = ! open"
            >
                <span>{{ __($item->label) }}</span>
                <svg class="h-4 w-4 text-vp-text-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition
                role="menu"
                class="absolute top-[calc(100%+0.5rem)] left-0 z-50 min-w-[14rem] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
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

                @foreach ($item->children as $child)
                    <a
                        href="{{ $child->resolveUrl() }}"
                        role="menuitem"
                        @class([
                            'block px-3 py-2 text-sm transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
                            'font-medium text-vp-brand-1' => $child->isActive(),
                            'text-vp-text-2' => ! $child->isActive(),
                        ])
                        @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ __($child->label) }}
                    </a>
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
                    'vpress-mobile-nav__link',
                    'is-active' => $isActive,
                ])
                @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                data-mobile-nav-close
            >
                <span>{{ __($item->label) }}</span>
                @if ($item->isExternal())
                    <x-vpress::external-link-icon />
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
                <x-vpress::external-link-icon />
            @endif
        </a>
    @endif
@endif
