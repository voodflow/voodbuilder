<div class="vpress-themes-ws">
    <style>
        .vpress-themes-ws {
            --vp-ws-radius: 0.75rem;
            --vp-ws-accent: #ea580c;
            --vp-ws-accent-hover: #c2410c;
            font-family: inherit;
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .vpress-themes-ws__catalog {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1.25rem 1.25rem;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 1100px) {
            .vpress-themes-ws__catalog {
                grid-template-columns: 1fr;
            }
        }

        .vpress-themes-ws__catalog-column {
            min-width: 0;
        }

        .vpress-themes-ws__catalog-note {
            grid-column: 1 / -1;
            margin: 0 0 0.25rem;
            font-size: 0.75rem;
            line-height: 1.4;
            color: rgb(100 116 139);
        }

        .dark .vpress-themes-ws__catalog-note {
            color: rgb(148 163 184);
        }

        .vpress-themes-ws__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 900px) {
            .vpress-themes-ws__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .vpress-themes-ws__grid {
                grid-template-columns: 1fr;
            }
        }

        .vpress-themes-ws__card {
            position: relative;
            width: 100%;
            flex-shrink: 0;
            border-radius: var(--vp-ws-radius);
            overflow: hidden;
            cursor: pointer;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
        }

        .vpress-themes-ws__intro {
            font-size: 0.8125rem;
            line-height: 1.45;
            color: rgb(100 116 139);
            margin: 0 0 1rem;
            max-width: 42rem;
        }

        .vpress-themes-ws__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .vpress-themes-ws__btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4375rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .vpress-themes-ws__btn--primary {
            background-color: var(--vp-ws-accent);
            color: #ffffff !important;
            border-color: var(--vp-ws-accent);
        }

        .vpress-themes-ws__btn--primary:hover {
            background-color: var(--vp-ws-accent-hover);
            border-color: var(--vp-ws-accent-hover);
        }

        .vpress-themes-ws__btn--ghost {
            background: #fff;
            color: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        .vpress-themes-ws__btn--ghost:hover {
            background: rgb(248 250 252);
            border-color: rgb(148 163 184);
        }

        .dark .vpress-themes-ws__btn--ghost {
            background: rgb(30 41 59);
            color: rgb(226 232 240);
            border-color: rgb(71 85 105);
        }

        .vpress-themes-ws__section-title {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0 0 0.5rem;
            color: rgb(100 116 139);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .vpress-themes-ws__card--selected {
            box-shadow: 0 0 0 2px var(--vp-card-accent, var(--vp-ws-accent));
        }

        .vpress-themes-ws__card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgb(15 23 42 / 0.08);
        }

        .vpress-themes-ws__card--bundled {
            cursor: default;
        }

        .vpress-themes-ws__card--bundled:hover {
            transform: none;
            box-shadow: none;
        }

        .vpress-themes-ws__card-strip {
            display: flex;
            height: 0.5rem;
        }

        .vpress-themes-ws__card-strip span {
            flex: 1;
            min-width: 0;
        }

        .vpress-themes-ws__card-body {
            padding: 0.625rem 0.75rem 0.375rem;
        }

        .vpress-themes-ws__card-name {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(30 41 59);
            line-height: 1.3;
            word-break: break-word;
        }

        .dark .vpress-themes-ws__card-name {
            color: rgb(241 245 249);
        }

        .vpress-themes-ws__card-badge {
            display: inline-block;
            margin-top: 0.25rem;
            font-size: 0.6875rem;
            font-weight: 500;
            color: rgb(100 116 139);
        }

        .vpress-themes-ws__card-actions {
            display: flex;
            gap: 0.125rem;
            justify-content: flex-end;
            padding: 0 0.5rem 0.5rem;
        }

        .vpress-themes-ws__icon-btn {
            width: 1.625rem;
            height: 1.625rem;
            border-radius: 0.375rem;
            border: none;
            background: transparent;
            color: rgb(100 116 139);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }

        .vpress-themes-ws__icon-btn:hover {
            background: rgb(15 23 42 / 0.06);
            color: rgb(30 41 59);
        }

        .vpress-themes-ws__clone-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            height: 1.625rem;
            padding: 0 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid rgb(203 213 225 / 0.9);
            background: rgb(255 255 255 / 0.85);
            color: rgb(51 65 85);
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .vpress-themes-ws__clone-btn:hover {
            background: rgb(248 250 252);
            border-color: rgb(148 163 184);
            color: rgb(15 23 42);
        }

        .dark .vpress-themes-ws__clone-btn {
            border-color: rgb(71 85 105 / 0.7);
            background: rgb(30 41 59 / 0.5);
            color: rgb(226 232 240);
        }

        .dark .vpress-themes-ws__clone-btn:hover {
            background: rgb(51 65 85 / 0.6);
            border-color: rgb(100 116 139);
            color: rgb(248 250 252);
        }

        .vpress-themes-ws__card--bundled .vpress-themes-ws__card-actions {
            padding-top: 0.125rem;
        }

        .dark .vpress-themes-ws__icon-btn:hover {
            background: rgb(255 255 255 / 0.08);
            color: rgb(248 250 252);
        }

        .vpress-themes-ws__editor {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(226 232 240 / 0.9);
        }

        .dark .vpress-themes-ws__editor {
            border-top-color: rgb(51 65 85 / 0.9);
        }

        .vpress-themes-ws__editor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.875rem;
        }

        .vpress-themes-ws__editor-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: rgb(15 23 42);
        }

        .dark .vpress-themes-ws__editor-title {
            color: rgb(248 250 252);
        }

        .vpress-themes-ws__editor-close {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgb(100 116 139);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .vpress-themes-ws__fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .vpress-themes-ws__field label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
            margin-bottom: 0.25rem;
        }

        .vpress-themes-ws__field input,
        .vpress-themes-ws__field textarea,
        .vpress-themes-ws__field select {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            padding: 0.4375rem 0.625rem;
            font-size: 0.875rem;
            background: #fff;
            color: rgb(15 23 42);
        }

        .vpress-themes-ws__field input:disabled {
            background: rgb(248 250 252);
            color: rgb(100 116 139);
        }

        .vpress-themes-ws__field-hint {
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: rgb(100 116 139);
        }

        .dark .vpress-themes-ws__field input,
        .dark .vpress-themes-ws__field textarea,
        .dark .vpress-themes-ws__field select {
            background: rgb(15 23 42);
            border-color: rgb(71 85 105);
            color: #fff;
        }

        .vpress-themes-ws__palette-block {
            margin-bottom: 0.875rem;
        }

        .vpress-themes-ws__palette-label {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            color: rgb(71 85 105);
        }

        .vpress-themes-ws__strip {
            display: flex;
            height: 1.75rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
        }

        .vpress-themes-ws__swatch {
            flex: 1;
            min-width: 0;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        .vpress-themes-ws__swatch:hover {
            opacity: 0.88;
        }

        .vpress-themes-ws__swatch--empty {
            background: repeating-linear-gradient(
                -45deg,
                rgb(241 245 249),
                rgb(241 245 249) 6px,
                rgb(248 250 252) 6px,
                rgb(248 250 252) 12px
            ) !important;
        }

        .vpress-themes-ws__palette-actions {
            margin-top: 0.375rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .vpress-themes-ws__link {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--vp-ws-accent);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .vpress-themes-ws__modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgb(15 23 42 / 0.45);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .vpress-themes-ws__modal {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.125rem;
            width: 100%;
            max-width: 20rem;
            border: 1px solid rgb(226 232 240);
            box-shadow: 0 16px 40px rgb(15 23 42 / 0.16);
        }

        .dark .vpress-themes-ws__modal {
            background: rgb(30 41 59);
            border-color: rgb(51 65 85);
        }

        .vpress-themes-ws__modal h3 {
            margin: 0 0 0.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgb(15 23 42);
        }

        .dark .vpress-themes-ws__modal h3 {
            color: rgb(248 250 252);
        }

        .vpress-themes-ws__modal-subtitle {
            margin: 0 0 0.875rem;
            font-size: 0.8125rem;
            color: rgb(100 116 139);
        }

        .vpress-themes-ws__color-input-row {
            display: flex;
            gap: 0.625rem;
            align-items: center;
        }

        .vpress-themes-ws__color-input-row input[type="color"] {
            width: 2.75rem;
            height: 2.75rem;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.5rem;
            padding: 0.125rem;
            cursor: pointer;
            flex-shrink: 0;
            background: #fff;
        }

        .vpress-themes-ws__color-input-row input[type="text"] {
            flex: 1;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            padding: 0.4375rem 0.625rem;
            font-size: 0.875rem;
            font-family: ui-monospace, monospace;
        }

        .vpress-themes-ws__seed-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .vpress-themes-ws__seed-row input[type="color"] {
            width: 2.25rem;
            height: 2.25rem;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.375rem;
            padding: 0.125rem;
            flex-shrink: 0;
        }

        .vpress-themes-ws__seed-row input[type="text"] {
            flex: 1;
        }

        .vpress-themes-ws__import-label {
            position: relative;
            overflow: hidden;
        }

        .vpress-themes-ws__import-label input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
    </style>

    <div class="vpress-themes-ws__header">
        <label class="vpress-themes-ws__btn vpress-themes-ws__btn--ghost vpress-themes-ws__import-label">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-7.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
            {{ __('vpress::settings.theme_workspace_import') }}
            <input type="file" wire:model="importArchive" accept=".zip,application/zip" />
        </label>
    </div>

    <p class="vpress-themes-ws__intro">{{ __('vpress::settings.themes_library_intro') }}</p>

    <div class="vpress-themes-ws__catalog">
        <p class="vpress-themes-ws__catalog-note">{{ __('vpress::settings.theme_workspace_bundled_note') }}</p>

        <div class="vpress-themes-ws__catalog-column">
            <h3 class="vpress-themes-ws__section-title">{{ __('vpress::settings.theme_workspace_plugin_themes') }}</h3>
            @if ($groups['plugin'] !== [])
                <div class="vpress-themes-ws__grid">
                    @foreach ($groups['plugin'] as $card)
                        @include('vpress::filament.partials.theme-card', ['card' => $card])
                    @endforeach
                </div>
            @endif
        </div>

        <div class="vpress-themes-ws__catalog-column">
            <h3 class="vpress-themes-ws__section-title">{{ __('vpress::settings.theme_workspace_your_themes') }}</h3>
            @if ($groups['custom'] !== [])
                <div class="vpress-themes-ws__grid">
                    @foreach ($groups['custom'] as $card)
                        @include('vpress::filament.partials.theme-card', ['card' => $card])
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('vpress::settings.theme_workspace_no_custom') }}</p>
            @endif
        </div>
    </div>

    @if ($selectedId)
        <div class="vpress-themes-ws__editor" wire:key="editor-{{ $selectedId }}">
            <div class="vpress-themes-ws__editor-header">
                <h2 class="vpress-themes-ws__editor-title">
                    {{ __('vpress::settings.theme_workspace_edit', ['name' => $label]) }}
                </h2>
                <button type="button" class="vpress-themes-ws__editor-close" wire:click="closeEditor">
                    {{ __('vpress::settings.theme_workspace_close') }}
                </button>
            </div>

            <div class="vpress-themes-ws__fields">
                <div class="vpress-themes-ws__field">
                    <label>{{ __('vpress::settings.theme_workspace_title') }}</label>
                    <input type="text" wire:model.blur="label" @disabled(! $metaEditable) />
                </div>
                <div class="vpress-themes-ws__field">
                    <label>{{ __('vpress::settings.theme_workspace_alias') }}</label>
                    <input
                        type="text"
                        wire:model.blur="themeSlug"
                        pattern="[a-z][a-z0-9-]*"
                        @disabled(! $metaEditable)
                    />
                    <p class="vpress-themes-ws__field-hint">{{ __('vpress::settings.theme_workspace_alias_help') }}</p>
                </div>
                <div class="vpress-themes-ws__field" style="grid-column: 1 / -1;">
                    <label>{{ __('vpress::settings.theme_workspace_description') }}</label>
                    <textarea wire:model.blur="description" rows="2" @disabled(! $metaEditable)></textarea>
                </div>
            </div>

            @if ($metaEditable)
                <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--ghost mb-3" wire:click="saveMetadata">
                    {{ __('vpress::settings.theme_workspace_save_details') }}
                </button>
            @endif

            @if ($canEditColors)
                @foreach (['light' => __('vpress::settings.theme_light_mode'), 'dark' => __('vpress::settings.theme_dark_mode')] as $mode => $modeLabel)
                @php
                    $palette = $mode === 'light' ? $light : $dark;
                @endphp
                <div class="vpress-themes-ws__palette-block">
                    <div class="vpress-themes-ws__palette-label">{{ $modeLabel }}</div>
                    <div class="vpress-themes-ws__strip">
                        @foreach ($colorKeys as $key)
                            @php
                                $hex = $palette[$key] ?? null;
                                $bg = $hex ?? '#e2e8f0';
                                $labelKey = $key === 'text' ? 'theme_body_text' : 'theme_'.$key;
                            @endphp
                            <button
                                type="button"
                                class="vpress-themes-ws__swatch @if (! $hex) vpress-themes-ws__swatch--empty @endif"
                                style="background: {{ $bg }}"
                                title="{{ __('vpress::settings.'.$labelKey) }}"
                                wire:click="openColor('{{ $mode }}', '{{ $key }}')"
                            ></button>
                        @endforeach
                    </div>
                    <div class="vpress-themes-ws__palette-actions">
                        @if ($mode === 'light')
                            <button type="button" class="vpress-themes-ws__link" wire:click="openGenerateModal">
                                {{ __('vpress::settings.theme_workspace_generate') }}
                            </button>
                        @else
                            <button type="button" class="vpress-themes-ws__link" wire:click="syncDarkFromLight">
                                {{ __('vpress::settings.sync_dark_theme_colors') }}
                            </button>
                        @endif
                        <button type="button" class="vpress-themes-ws__link" wire:click="resetColors">
                            {{ __('vpress::settings.reset_theme_colors') }}
                        </button>
                    </div>
                </div>
            @endforeach
            @else
                <div class="vpress-themes-ws__palette-block">
                    <p class="vpress-themes-ws__field-hint">{{ __('vpress::settings.theme_workspace_bundled_colors_help') }}</p>
                    <button type="button" class="vpress-themes-ws__link" wire:click="resetColors">
                        {{ __('vpress::settings.reset_theme_colors') }}
                    </button>
                </div>
            @endif
        </div>
    @endif

    @if ($showColorModal)
        <div class="vpress-themes-ws__modal-backdrop" wire:click.self="closeColorModal">
            <div class="vpress-themes-ws__modal" wire:click.stop>
                <h3>{{ __('vpress::settings.theme_workspace_pick_color_for', ['label' => $colorKeyLabel]) }}</h3>
                <p class="vpress-themes-ws__modal-subtitle">{{ ucfirst($colorMode) }} · {{ __('vpress::settings.theme_workspace_auto_apply') }}</p>
                <div class="vpress-themes-ws__color-input-row">
                    <input type="color" wire:model.live="colorValue" />
                    <input type="text" wire:model.live.debounce.300ms="colorValue" maxlength="7" />
                </div>
                <div class="flex gap-2 mt-4 justify-between">
                    <button type="button" class="vpress-themes-ws__link" wire:click="clearColor('{{ $colorMode }}', '{{ $colorKey }}')">
                        {{ __('vpress::settings.theme_workspace_clear_color') }}
                    </button>
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--primary" wire:click="closeColorModal">
                        {{ __('vpress::settings.theme_workspace_done') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showCloneModal)
        <div class="vpress-themes-ws__modal-backdrop" wire:click.self="$set('showCloneModal', false)">
            <div class="vpress-themes-ws__modal" wire:click.stop>
                <h3>{{ __('vpress::settings.clone_theme') }}</h3>
                <p class="vpress-themes-ws__modal-subtitle">{{ __('vpress::settings.clone_theme_help') }}</p>
                <div class="vpress-themes-ws__field mb-3">
                    <label>{{ __('vpress::settings.create_theme_id') }}</label>
                    <input type="text" wire:model="cloneTargetId" pattern="[a-z][a-z0-9-]*" />
                </div>
                <div class="vpress-themes-ws__field mb-4">
                    <label>{{ __('vpress::settings.create_theme_label') }}</label>
                    <input type="text" wire:model="cloneLabel" />
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--ghost" wire:click="$set('showCloneModal', false)">{{ __('Cancel') }}</button>
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--primary" wire:click="executeClone">{{ __('vpress::settings.clone_theme') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showGenerateModal)
        <div class="vpress-themes-ws__modal-backdrop" wire:click.self="$set('showGenerateModal', false)">
            <div class="vpress-themes-ws__modal" wire:click.stop style="max-width: 24rem;">
                <h3>{{ __('vpress::settings.generate_theme_palette') }}</h3>
                <p class="vpress-themes-ws__modal-subtitle">{{ __('vpress::settings.generate_theme_palette_help') }}</p>
                @foreach (['seedPrimary' => 'palette_seed_primary', 'seedSecondary' => 'palette_seed_secondary', 'seedHeaderBg' => 'palette_seed_header_bg'] as $prop => $langKey)
                    <div class="vpress-themes-ws__field mb-3">
                        <label>{{ __('vpress::settings.'.$langKey) }}</label>
                        <div class="vpress-themes-ws__seed-row">
                            <input type="color" wire:model.live="{{ $prop }}" />
                            <input type="text" wire:model.live.debounce.300ms="{{ $prop }}" placeholder="#3451b2" />
                        </div>
                    </div>
                @endforeach
                <div class="flex gap-2 justify-end">
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--ghost" wire:click="$set('showGenerateModal', false)">{{ __('Cancel') }}</button>
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--primary" wire:click="generatePalette">{{ __('vpress::settings.generate_theme_palette') }}</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="vpress-themes-ws__modal-backdrop" wire:click.self="$set('showDeleteModal', false)">
            <div class="vpress-themes-ws__modal" wire:click.stop>
                <h3>{{ __('vpress::settings.delete_theme') }}</h3>
                <p class="vpress-themes-ws__modal-subtitle">{{ __('vpress::settings.delete_theme_help') }}</p>
                <div class="vpress-themes-ws__field mb-4">
                    <label>{{ __('vpress::settings.delete_theme_fallback') }}</label>
                    <select wire:model="deleteFallbackId">
                        @foreach ($fallbackOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</button>
                    <button type="button" class="vpress-themes-ws__btn vpress-themes-ws__btn--primary" style="background:#dc2626;border-color:#dc2626" wire:click="deleteTheme">{{ __('vpress::settings.delete_theme') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
