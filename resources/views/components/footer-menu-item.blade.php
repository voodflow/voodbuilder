@props(['item', 'depth' => 0])

@php
    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $linkClass = match ($depth) {
        0 => 'text-sm text-vp-text-2 transition-colors hover:text-vp-brand-1',
        1 => 'text-sm text-vp-text-2 transition-colors hover:text-vp-brand-1',
        default => 'text-sm text-vp-text-3 transition-colors hover:text-vp-brand-1',
    };
    $icon = $item->resolvedIcon();
@endphp

<li>
    <a
        href="{{ $item->resolveUrl() }}"
        @class([
            'inline-flex items-center gap-2',
            $linkClass,
        ])
        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    >
        @if ($icon)
            <span class="voodbuilder-footer-menu-item__icon shrink-0 text-vp-brand-1" aria-hidden="true">
                <x-voodbuilder::tabler-icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
        <span>{{ __($item->label) }}</span>
    </a>
    @if ($item->hasChildren())
        <ul @class([
            'mt-2 space-y-1 border-l border-vp-divider pl-3' => $depth === 0,
            'mt-1 space-y-1 pl-3' => $depth > 0,
        ])>
            @foreach ($item->navigationChildren() as $child)
                <x-voodbuilder::footer-menu-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </ul>
    @endif
</li>
