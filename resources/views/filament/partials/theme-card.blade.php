@php
    $selected = ($selectedId ?? null) === $card['id'];
    $isCustom = $card['is_app'] ?? false;
@endphp
<div
    class="voodbuilder-themes-ws__card @if ($selected) voodbuilder-themes-ws__card--selected @endif @if ($isCustom) voodbuilder-themes-ws__card--custom @else voodbuilder-themes-ws__card--bundled @endif"
    style="--vp-card-accent: {{ $card['preview'] }}; --vp-card-surface: {{ $card['surface'] }}"
    wire:click="selectTheme('{{ $card['id'] }}')"
    wire:keydown.enter="selectTheme('{{ $card['id'] }}')"
    role="button"
    tabindex="0"
    aria-label="{{ __('voodbuilder::settings.theme_workspace_edit_card', ['name' => $card['label']]) }}"
>
    <div class="voodbuilder-themes-ws__card-strip" aria-hidden="true">
        @foreach (array_slice($card['strip'], 0, 5) as $hex)
            <span style="background: {{ $hex }}"></span>
        @endforeach
    </div>
    <div class="voodbuilder-themes-ws__card-body">
        <div class="voodbuilder-themes-ws__card-topline">
            <span class="voodbuilder-themes-ws__card-name">{{ $card['label'] }}</span>
            <span class="voodbuilder-themes-ws__card-origin">
                {{ $isCustom
                    ? __('voodbuilder::settings.theme_origin_custom')
                    : __('voodbuilder::settings.theme_origin_base') }}
            </span>
        </div>
        <span class="voodbuilder-themes-ws__card-badge">
            @if (filled($card['description'] ?? null))
                {{ $card['description'] }}
            @elseif ($isCustom)
                {{ __('voodbuilder::settings.theme_workspace_custom_card_subtitle') }}
            @else
                {{ __('voodbuilder::settings.theme_origin_package') }}
            @endif
        </span>
    </div>

    @if ($isCustom)
        <span class="voodbuilder-themes-ws__card-icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
            </svg>
        </span>
    @else
        <button
            type="button"
            class="voodbuilder-themes-ws__card-icon voodbuilder-themes-ws__card-icon--action"
            wire:click.stop="openCloneModal('{{ $card['id'] }}')"
            aria-label="{{ __('voodbuilder::settings.clone_theme_card', ['name' => $card['label']]) }}"
            title="{{ __('voodbuilder::settings.clone_theme') }}"
        >
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V7.5m0 0H18a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 18 20.25H9.75A2.25 2.25 0 0 1 7.5 18v-1.5M8.25 7.5H6A2.25 2.25 0 0 0 3.75 9.75v8.25A2.25 2.25 0 0 0 6 20.25h2.25M8.25 7.5h7.5" />
            </svg>
        </button>
    @endif
</div>
