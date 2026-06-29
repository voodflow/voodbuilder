@php
    $selected = ($selectedId ?? null) === $card['id'];
    $editable = $card['can_edit_meta'] || $card['can_edit_colors'];
    $isCustom = $card['is_app'] ?? false;
@endphp
<div
    class="vpress-themes-ws__card @if ($selected) vpress-themes-ws__card--selected @endif @if ($isCustom) vpress-themes-ws__card--custom @else vpress-themes-ws__card--bundled @endif"
    style="--vp-card-accent: {{ $card['preview'] }}; background: {{ $card['surface'] }}"
    @if ($isCustom)
        wire:click="selectTheme('{{ $card['id'] }}')"
        wire:keydown.enter="selectTheme('{{ $card['id'] }}')"
        role="button"
        tabindex="0"
        aria-label="{{ __('vpress::settings.theme_workspace_edit_card', ['name' => $card['label']]) }}"
    @else
        wire:click="openCloneModal('{{ $card['id'] }}')"
        wire:keydown.enter="openCloneModal('{{ $card['id'] }}')"
        role="button"
        tabindex="0"
        aria-label="{{ __('vpress::settings.clone_theme_card', ['name' => $card['label']]) }}"
    @endif
>
    <div class="vpress-themes-ws__card-strip" aria-hidden="true">
        @foreach (array_slice($card['strip'], 0, 5) as $hex)
            <span style="background: {{ $hex }}"></span>
        @endforeach
    </div>
    <div class="vpress-themes-ws__card-body">
        <span class="vpress-themes-ws__card-name">{{ $card['label'] }}</span>
        <span class="vpress-themes-ws__card-badge">
            @if ($isCustom && $card['has_custom_colors'])
                {{ __('vpress::settings.theme_custom_colors_badge') }}
            @elseif (filled($card['description'] ?? null))
                {{ $card['description'] }}
            @elseif ($isCustom)
                {{ __('vpress::settings.theme_workspace_custom_card_subtitle') }}
            @else
                {{ __('vpress::settings.theme_origin_package') }}
            @endif
        </span>
    </div>

    <span class="vpress-themes-ws__card-icon" aria-hidden="true">
        @if ($isCustom)
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
            </svg>
        @else
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V7.5m0 0H18a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 18 20.25H9.75A2.25 2.25 0 0 1 7.5 18v-1.5M8.25 7.5H6A2.25 2.25 0 0 0 3.75 9.75v8.25A2.25 2.25 0 0 0 6 20.25h2.25M8.25 7.5h7.5" />
            </svg>
        @endif
    </span>
</div>
