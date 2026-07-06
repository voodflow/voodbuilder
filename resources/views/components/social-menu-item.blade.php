@props([
    'item',
    'display' => null,
])

@php
    use Voodflow\Voodbuilder\Enums\MenuLinkDisplay;
    use Voodflow\Voodbuilder\Support\MenuTablerIcons;

    /** @var \Voodflow\Voodbuilder\Models\NavigationMenuItem $item */
    /** @var MenuLinkDisplay|null $display */
    $display ??= MenuLinkDisplay::IconOnly;
    $icon = filled($item->icon) ? (string) $item->icon : null;
    $showIcon = $display->showsIcon() && filled($icon) && MenuTablerIcons::has($icon);
    $showLabel = $display->showsLabel();
    $ariaLabel = $showLabel ? null : __($item->label);
@endphp

<a
    href="{{ $item->resolveUrl() }}"
    @class([
        'inline-flex items-center gap-2 text-vp-text-2 transition-colors hover:text-vp-brand-1',
        'justify-center' => ! $showLabel,
    ])
    @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
>
    @if ($showIcon)
        <x-voodbuilder::tabler-icon :name="$icon" />
    @endif

    @if ($showLabel)
        <span>{{ __($item->label) }}</span>
    @endif
</a>
