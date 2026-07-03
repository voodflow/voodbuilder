@props([
    'item',
    'depth' => 0,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuItemType;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $hasChildren = $item->hasChildren();
    $isActive = $item->isActive();
    $hasParentLink = $hasChildren && $item->type !== MenuItemType::Group && $item->hasResolvableLink();
    $paddingClass = match ($depth) {
        0 => '',
        1 => 'pl-3',
        2 => 'pl-6',
        default => 'pl-9',
    };
@endphp

@if ($hasChildren)
    <li x-data="{ open: {{ $isActive ? 'true' : 'false' }} }" @class([$paddingClass])>
        <button
            type="button"
            @class([
                'voodbuilder-mobile-nav__link w-full',
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

                @foreach ($item->children as $child)
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
                $depth > 0 ? 'voodbuilder-mobile-nav__link--secondary' : '',
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
@endif
