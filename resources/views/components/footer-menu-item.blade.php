@props(['item', 'depth' => 0])

@php
    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    $linkClass = match ($depth) {
        0 => 'text-sm text-vp-text-2 transition-colors hover:text-vp-brand-1',
        1 => 'text-sm text-vp-text-2 transition-colors hover:text-vp-brand-1',
        default => 'text-sm text-vp-text-3 transition-colors hover:text-vp-brand-1',
    };
@endphp

<li>
    <a
        href="{{ $item->resolveUrl() }}"
        class="{{ $linkClass }}"
        @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    >
        {{ __($item->label) }}
    </a>
    @if ($item->hasChildren())
        <ul @class([
            'mt-2 space-y-1 border-l border-vp-divider pl-3' => $depth === 0,
            'mt-1 space-y-1 pl-3' => $depth > 0,
        ])>
            @foreach ($item->children as $child)
                <x-voodbuilder::footer-menu-item :item="$child" :depth="$depth + 1" />
            @endforeach
        </ul>
    @endif
</li>
