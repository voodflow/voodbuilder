@php
    $iconBtn = 'voodbuilder-themes-ws__icon-btn';
@endphp
<div class="voodbuilder-themes-ws__icon-actions" role="toolbar" aria-label="{{ __('voodbuilder::settings.theme_tools') }}">
    <button
        type="button"
        class="{{ $iconBtn }}"
        wire:click="openCloneModal('{{ $selectedId }}')"
        title="{{ __('voodbuilder::settings.clone_theme') }}"
        aria-label="{{ __('voodbuilder::settings.clone_theme') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667l0 -8.666" />
            <path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" />
        </svg>
    </button>

    <button
        type="button"
        class="{{ $iconBtn }}"
        wire:click="exportTheme('{{ $selectedId }}')"
        title="{{ __('voodbuilder::settings.export_theme') }}"
        aria-label="{{ __('voodbuilder::settings.export_theme') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
            <path d="M7 11l5 5l5 -5" />
            <path d="M12 4l0 12" />
        </svg>
    </button>

    <label
        class="{{ $iconBtn }} voodbuilder-themes-ws__icon-btn--file"
        wire:loading.class="is-importing"
        wire:target="importArchive"
        title="{{ __('voodbuilder::settings.import_theme') }}"
        aria-label="{{ __('voodbuilder::settings.import_theme') }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
            <path d="M7 9l5 -5l5 5" />
            <path d="M12 4l0 12" />
        </svg>
        <input type="file" wire:model="importArchive" accept=".zip,application/zip" />
    </label>

    @if ($canEditColors)
        <button
            type="button"
            class="{{ $iconBtn }}"
            wire:click="copyColorScheme"
            title="{{ __('voodbuilder::settings.copy_color_scheme') }}"
            aria-label="{{ __('voodbuilder::settings.copy_color_scheme') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25" />
                <path d="M7.5 10.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                <path d="M11.5 7.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                <path d="M15.5 10.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
            </svg>
        </button>

        @if ($canPasteColorScheme)
            <button
                type="button"
                class="{{ $iconBtn }}"
                title="{{ __('voodbuilder::settings.paste_color_scheme') }}"
                aria-label="{{ __('voodbuilder::settings.paste_color_scheme') }}"
                x-data
                x-on:click="
                    if (navigator.clipboard?.readText) {
                        navigator.clipboard.readText()
                            .then((text) => $wire.pasteColorScheme(text))
                            .catch(() => $wire.pasteColorScheme())
                    } else {
                        $wire.pasteColorScheme()
                    }
                "
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                    <path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2" />
                    <path d="M10 14h4" />
                    <path d="M12 12v4" />
                </svg>
            </button>
        @endif
    @endif

    @if ($canDelete)
        <button
            type="button"
            class="{{ $iconBtn }} {{ $iconBtn }}--danger"
            wire:click="confirmDelete"
            title="{{ __('voodbuilder::settings.delete_theme') }}"
            aria-label="{{ __('voodbuilder::settings.delete_theme') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 7l16 0" />
                <path d="M10 11l0 6" />
                <path d="M14 11l0 6" />
                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
            </svg>
        </button>
    @endif
</div>
