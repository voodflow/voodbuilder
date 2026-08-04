{{-- Theme Studio desk: catalog | (map ↔ customize) --}}
@php
    $mode = $this->studioMode ?? 'map';
    $editingThemeId = $this->editingThemeId ?? null;
@endphp
<div class="voodbuilder-theme-studio" data-mode="{{ $mode }}">
    <style>
        .voodbuilder-theme-studio {
            --vp-ts-accent: #ea580c;
            display: grid;
            grid-template-columns: minmax(16.5rem, 20rem) minmax(0, 1fr);
            gap: 1.25rem 1.5rem;
            align-items: start;
            width: 100%;
        }

        @media (max-width: 1100px) {
            .voodbuilder-theme-studio {
                grid-template-columns: 1fr;
            }
        }

        .voodbuilder-theme-studio__sidebar {
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 8rem);
            overflow: auto;
            padding: 0.85rem;
            border-radius: 0.875rem;
            border: 1px solid rgb(226 232 240);
            background: rgb(255 255 255);
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
        }

        .dark .voodbuilder-theme-studio__sidebar {
            border-color: rgb(51 65 85);
            background: rgb(15 23 42 / 0.55);
            box-shadow: 0 8px 24px rgb(0 0 0 / 0.2);
        }

        .voodbuilder-theme-studio__stage {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.85rem 0.85rem 1rem;
            border-radius: 0.875rem;
            border: 1px solid rgb(226 232 240);
            background: rgb(255 255 255);
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
            min-height: min(72vh, 760px);
        }

        .dark .voodbuilder-theme-studio__stage {
            border-color: rgb(51 65 85);
            background: rgb(15 23 42 / 0.55);
            box-shadow: 0 8px 24px rgb(0 0 0 / 0.2);
        }

        .voodbuilder-theme-studio__tabs {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem;
            border-radius: 0.65rem;
            background: rgb(241 245 249);
            border: 1px solid rgb(226 232 240);
            align-self: flex-start;
        }

        .dark .voodbuilder-theme-studio__tabs {
            background: rgb(30 41 59);
            border-color: rgb(51 65 85);
        }

        .voodbuilder-theme-studio__tab {
            appearance: none;
            border: 0;
            background: transparent;
            border-radius: 0.5rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            color: rgb(71 85 105);
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .dark .voodbuilder-theme-studio__tab {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-studio__tab:hover {
            color: rgb(15 23 42);
            background: rgb(255 255 255 / 0.7);
        }

        .dark .voodbuilder-theme-studio__tab:hover {
            color: rgb(248 250 252);
            background: rgb(51 65 85 / 0.7);
        }

        .voodbuilder-theme-studio__tab.is-active {
            background: rgb(255 255 255);
            color: rgb(15 23 42);
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.06);
        }

        .dark .voodbuilder-theme-studio__tab.is-active {
            background: rgb(15 23 42);
            color: rgb(248 250 252);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.35);
        }

        .voodbuilder-theme-studio__stage-header {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .voodbuilder-theme-studio__stage-title {
            margin: 0;
            font-size: 0.9375rem;
            font-weight: 650;
            color: rgb(15 23 42);
            letter-spacing: -0.01em;
        }

        .dark .voodbuilder-theme-studio__stage-title {
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-studio__stage-help {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: rgb(100 116 139);
            max-width: 48rem;
        }

        .dark .voodbuilder-theme-studio__stage-help {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-studio__panel {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
            min-height: 0;
        }

        .voodbuilder-theme-studio__panel[hidden] {
            display: none !important;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__header {
            margin-bottom: 0.75rem;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__header-hint {
            font-size: 0.75rem;
            max-width: none;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__catalog {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
            margin-bottom: 0;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__grid {
            grid-template-columns: 1fr;
            gap: 0.55rem;
            margin-bottom: 0;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__card {
            min-height: 4.25rem;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__card-body {
            padding: 0.55rem 0.65rem 0.65rem;
            gap: 0.15rem;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__card-name {
            font-size: 0.8125rem;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__card-badge {
            font-size: 0.6875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
        }

        .voodbuilder-theme-studio__sidebar .voodbuilder-themes-ws__section-title {
            font-size: 0.6875rem;
            letter-spacing: 0.06em;
        }

        .voodbuilder-theme-studio__stage .voodbuilder-theme-map-bridge {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .voodbuilder-theme-studio__stage #voodbuilder-theme-map-root,
        .voodbuilder-theme-studio__stage .voodbuilder-tm__canvas,
        .voodbuilder-theme-studio__stage .voodbuilder-tm__canvas .react-flow {
            min-height: min(68vh, 720px);
        }

        .voodbuilder-theme-studio__stage .voodbuilder-tm__hint {
            display: none;
        }

        .voodbuilder-theme-studio__stage .voodbuilder-themes-ws__editor {
            margin-top: 0;
            border: 0;
            box-shadow: none;
            background: transparent;
            padding: 0;
        }

        .voodbuilder-theme-studio__stage .voodbuilder-themes-ws__editor-header {
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid rgb(226 232 240);
        }

        .dark .voodbuilder-theme-studio__stage .voodbuilder-themes-ws__editor-header {
            border-bottom-color: rgb(51 65 85);
        }

        .voodbuilder-theme-studio__stage .voodbuilder-themes-ws__swatches {
            grid-template-columns: repeat(auto-fill, minmax(7.5rem, 1fr));
        }

        .voodbuilder-theme-studio__empty {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: 0.75rem;
            min-height: 16rem;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px dashed rgb(203 213 225);
            background: rgb(248 250 252 / 0.55);
        }

        .dark .voodbuilder-theme-studio__empty {
            border-color: rgb(71 85 105);
            background: rgb(15 23 42 / 0.35);
        }

        .voodbuilder-theme-studio__empty-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 650;
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-theme-studio__empty-title {
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-studio__empty-body {
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
            color: rgb(100 116 139);
            max-width: 36rem;
        }

        .dark .voodbuilder-theme-studio__empty-body {
            color: rgb(148 163 184);
        }
    </style>

    <aside class="voodbuilder-theme-studio__sidebar" aria-label="{{ __('voodbuilder::settings.theme_workspace_plugin_themes') }}">
        <livewire:voodbuilder.themes-workspace studio-role="catalog" wire:key="theme-studio-catalog" />
    </aside>

    <section class="voodbuilder-theme-studio__stage" aria-label="{{ __('voodbuilder::admin.navigation.theme_studio') }}">
        <div class="voodbuilder-theme-studio__tabs" role="tablist">
            <button
                type="button"
                role="tab"
                class="voodbuilder-theme-studio__tab @if ($mode === 'map') is-active @endif"
                wire:click="$set('studioMode', 'map')"
                aria-selected="{{ $mode === 'map' ? 'true' : 'false' }}"
            >
                {{ __('voodbuilder::settings.theme_studio_tab_map') }}
            </button>
            <button
                type="button"
                role="tab"
                class="voodbuilder-theme-studio__tab @if ($mode === 'edit') is-active @endif"
                wire:click="$set('studioMode', 'edit')"
                aria-selected="{{ $mode === 'edit' ? 'true' : 'false' }}"
            >
                {{ __('voodbuilder::settings.theme_studio_tab_edit') }}
            </button>
        </div>

        {{-- Keep both panels mounted so Livewire events reach the editor while on the map. --}}
        <div class="voodbuilder-theme-studio__panel" @if ($mode !== 'map') hidden @endif>
            <header class="voodbuilder-theme-studio__stage-header">
                <h2 class="voodbuilder-theme-studio__stage-title">{{ __('voodbuilder::settings.area_themes_section') }}</h2>
                <p class="voodbuilder-theme-studio__stage-help">{{ __('voodbuilder::settings.area_themes_section_help_v2') }}</p>
            </header>

            <livewire:voodbuilder.theme-map-bridge
                :sub-theme="$subTheme"
                :channel-themes="$channelThemes"
                wire:key="voodbuilder-theme-map"
            />
        </div>

        <div class="voodbuilder-theme-studio__panel" @if ($mode !== 'edit') hidden @endif>
            <header class="voodbuilder-theme-studio__stage-header">
                <h2 class="voodbuilder-theme-studio__stage-title">{{ __('voodbuilder::settings.theme_studio_edit_title') }}</h2>
                <p class="voodbuilder-theme-studio__stage-help">{{ __('voodbuilder::settings.theme_studio_edit_help') }}</p>
            </header>

            <livewire:voodbuilder.themes-workspace
                studio-role="editor"
                wire:key="theme-studio-editor"
            />
        </div>
    </section>
</div>
