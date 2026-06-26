@php
    $selected = $selectedId === $card['id'];
    $editable = $card['can_edit_meta'] || $card['can_edit_colors'];
@endphp
<div
    class="vpress-themes-ws__card @if ($selected) vpress-themes-ws__card--selected @endif @if (! $editable) vpress-themes-ws__card--bundled @endif"
    style="--vp-card-accent: {{ $card['preview'] }}; background: {{ $card['surface'] }}"
    @if ($editable)
        wire:click="selectTheme('{{ $card['id'] }}')"
        role="button"
        tabindex="0"
    @endif
>
    <div class="vpress-themes-ws__card-strip" aria-hidden="true">
        @foreach (array_slice($card['strip'], 0, 5) as $hex)
            <span style="background: {{ $hex }}"></span>
        @endforeach
    </div>
    <div class="vpress-themes-ws__card-body">
        <span class="vpress-themes-ws__card-name">{{ $card['label'] }}</span>
        @if ($card['has_custom_colors'])
            <span class="vpress-themes-ws__card-badge">{{ __('vpress::settings.theme_custom_colors_badge') }}</span>
        @endif
    </div>
    <div class="vpress-themes-ws__card-actions" wire:click.stop>
        @if ($editable)
            <button type="button" class="vpress-themes-ws__icon-btn" title="{{ __('Edit') }}" wire:click="selectTheme('{{ $card['id'] }}')">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
            </button>
        @endif
        <button
            type="button"
            @class([
                'vpress-themes-ws__clone-btn' => ! $editable,
                'vpress-themes-ws__icon-btn' => $editable,
            ])
            title="{{ __('vpress::settings.clone_theme') }}"
            aria-label="{{ __('vpress::settings.clone_theme') }}"
            wire:click="openCloneModal('{{ $card['id'] }}')"
        >
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V7.5m0 0H18a2.25 2.25 0 0 1 2.25 2.25v9A2.25 2.25 0 0 1 18 20.25H9.75A2.25 2.25 0 0 1 7.5 18v-1.5M8.25 7.5H6A2.25 2.25 0 0 0 3.75 9.75v8.25A2.25 2.25 0 0 0 6 20.25h2.25M8.25 7.5h7.5" />
            </svg>
            @unless ($editable)
                <span>{{ __('vpress::settings.clone_theme_action') }}</span>
            @endunless
        </button>
        @if ($card['can_export'] && $editable)
            <button type="button" class="vpress-themes-ws__icon-btn" title="{{ __('vpress::settings.export_theme') }}" wire:click="exportTheme('{{ $card['id'] }}')">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            </button>
        @endif
        @if ($card['can_delete'])
            <button type="button" class="vpress-themes-ws__icon-btn" title="{{ __('vpress::settings.delete_theme') }}" wire:click="prepareDelete('{{ $card['id'] }}')">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            </button>
        @endif
    </div>
</div>
