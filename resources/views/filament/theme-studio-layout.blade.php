{{-- Theme Studio desk: catalog | edit + live preview | assignments — 30/40/30, no nested scroll --}}
<div class="voodbuilder-theme-studio">
    <style>
        .voodbuilder-theme-studio {
            --vp-ts-accent: #ea580c;
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(0, 4fr) minmax(0, 3fr);
            gap: 1.35rem;
            width: 100%;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .voodbuilder-theme-studio {
                grid-template-columns: 1fr;
            }
        }

        .voodbuilder-theme-studio__panel {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            padding: 1.05rem 1.1rem 1.15rem;
            border-radius: 0.9rem;
            background: rgb(255 255 255 / 0.55);
            box-shadow: 0 0 0 1px rgb(15 23 42 / 0.05);
        }

        .dark .voodbuilder-theme-studio__panel {
            background: rgb(15 23 42 / 0.35);
            box-shadow: 0 0 0 1px rgb(148 163 184 / 0.12);
        }

        .voodbuilder-theme-studio__title {
            margin: 0;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-theme-studio__title {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-studio__catalog {
            overflow: visible;
            max-height: none;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__header {
            margin-bottom: 0.55rem;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__header-hint {
            display: none;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__catalog {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 0;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__catalog--tabs {
            display: block;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__grid {
            grid-template-columns: 1fr;
            gap: 0.55rem;
            margin: 0;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__card {
            min-height: 0;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__card-body {
            padding: 0.5rem 0.6rem 0.55rem;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__card-name {
            font-size: 0.8125rem;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__card-badge {
            font-size: 0.625rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            min-height: 0;
            margin-top: 0.2rem;
        }

        .voodbuilder-theme-studio__catalog .voodbuilder-themes-ws__section-title {
            font-size: 0.625rem;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor {
            margin: 0;
            padding: 0;
            border: 0;
            box-shadow: none;
            background: transparent;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-header {
            margin-bottom: 0.85rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid rgb(226 232 240 / 0.75);
        }

        .dark .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-header {
            border-bottom-color: rgb(51 65 85 / 0.75);
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-close {
            display: none;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-title {
            font-size: 0.95rem;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__fields {
            gap: 0.75rem;
            margin-bottom: 100px;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__preview-mode {
            margin-top: 0.35rem;
            margin-bottom: 0.85rem;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-header {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
        }

        .voodbuilder-theme-studio__editor .voodbuilder-themes-ws__editor-actions {
            display: none;
        }

        .voodbuilder-theme-studio__assign .voodbuilder-theme-map-bridge {
            margin: 0;
        }

        .voodbuilder-theme-studio__assign .voodbuilder-theme-assignments__head,
        .voodbuilder-theme-studio__assign .voodbuilder-theme-assignments__row {
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.4fr);
            gap: 0.85rem;
        }

        .voodbuilder-theme-studio__assign .voodbuilder-theme-assignments {
            gap: 0.5rem;
        }

        .voodbuilder-theme-studio__assign .voodbuilder-theme-assignments__row {
            padding: 0.7rem 0.75rem;
        }

        .voodbuilder-theme-studio__empty {
            padding: 1.25rem 0.25rem;
        }

        .voodbuilder-theme-studio__empty-title {
            margin: 0 0 0.35rem;
            font-size: 0.9375rem;
            font-weight: 650;
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-theme-studio__empty-title {
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-studio__empty-body {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-theme-studio__empty-body {
            color: rgb(148 163 184);
        }
    </style>

    <section class="voodbuilder-theme-studio__panel voodbuilder-theme-studio__catalog" aria-labelledby="theme-studio-themes-title">
        <h2 id="theme-studio-themes-title" class="voodbuilder-theme-studio__title">
            {{ __('voodbuilder::settings.theme_studio_themes_title') }}
        </h2>

        <livewire:voodbuilder.themes-workspace
            studio-role="catalog"
            :studio-desk="true"
            wire:key="theme-studio-catalog"
        />
    </section>

    <section class="voodbuilder-theme-studio__panel voodbuilder-theme-studio__editor" aria-labelledby="theme-studio-edit-title">
        <h2 id="theme-studio-edit-title" class="voodbuilder-theme-studio__title">
            {{ __('voodbuilder::settings.theme_studio_edit_title') }}
        </h2>

        <livewire:voodbuilder.themes-workspace
            studio-role="editor"
            :studio-desk="true"
            :initial-theme-id="$subTheme"
            wire:key="theme-studio-editor"
        />
    </section>

    <section class="voodbuilder-theme-studio__panel voodbuilder-theme-studio__assign" aria-labelledby="theme-studio-areas-title">
        <h2 id="theme-studio-areas-title" class="voodbuilder-theme-studio__title">
            {{ __('voodbuilder::settings.theme_studio_assignments_title') }}
        </h2>

        <livewire:voodbuilder.theme-map-bridge
            :sub-theme="$subTheme"
            :channel-themes="$channelThemes"
            wire:key="voodbuilder-theme-map"
        />
    </section>
</div>
