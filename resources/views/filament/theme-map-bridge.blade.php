<div class="voodbuilder-theme-map-bridge">
    <style>
        .voodbuilder-theme-map-bridge {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .voodbuilder-theme-assignments {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            width: 100%;
        }

        .voodbuilder-theme-assignments__head,
        .voodbuilder-theme-assignments__row {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1.4fr);
            gap: 0.85rem;
            align-items: center;
        }

        .voodbuilder-theme-assignments__head {
            padding: 0 0.15rem 0.35rem;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-theme-assignments__head {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-assignments__row {
            padding: 0.55rem 0.6rem;
            border-radius: 0.55rem;
            background: rgb(248 250 252 / 0.7);
        }

        .dark .voodbuilder-theme-assignments__row {
            background: rgb(30 41 59 / 0.45);
        }

        .voodbuilder-theme-assignments__area {
            font-weight: 600;
            color: rgb(15 23 42);
            font-size: 0.8125rem;
            line-height: 1.3;
        }

        .dark .voodbuilder-theme-assignments__area {
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-assignments__meta {
            margin-top: 0.12rem;
            font-size: 0.68rem;
            line-height: 1.3;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-theme-assignments__meta {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-assignments select {
            width: 100%;
            border-radius: 0.45rem;
            border: 1px solid rgb(226 232 240);
            background: rgb(255 255 255);
            color: rgb(15 23 42);
            padding: 0.35rem 0.45rem;
            font: inherit;
            font-size: 0.75rem;
        }

        .dark .voodbuilder-theme-assignments select {
            border-color: rgb(51 65 85);
            background: rgb(15 23 42);
            color: rgb(248 250 252);
        }

        @media (max-width: 520px) {
            .voodbuilder-theme-assignments__head,
            .voodbuilder-theme-assignments__row {
                grid-template-columns: 1fr;
                gap: 0.35rem;
            }

            .voodbuilder-theme-assignments__head span:not(:first-child) {
                display: none;
            }
        }
    </style>

    @php
        $areas = is_array($payload['areas'] ?? null) ? $payload['areas'] : [];
        $themes = is_array($payload['themes'] ?? null) ? $payload['themes'] : [];
        $themeLabels = [];
        foreach ($themes as $theme) {
            if (is_array($theme) && isset($theme['id'], $theme['label'])) {
                $themeLabels[(string) $theme['id']] = (string) $theme['label'];
            }
        }
    @endphp

    <div class="voodbuilder-theme-assignments">
        <div class="voodbuilder-theme-assignments__head" aria-hidden="true">
            <span>{{ __('voodbuilder::settings.theme_assignments_col_area') }}</span>
            <span>{{ __('voodbuilder::settings.theme_assignments_col_layout') }}</span>
        </div>

        @foreach ($areas as $area)
            @php
                $areaId = (string) ($area['id'] ?? '');
                $allowed = is_array($area['allowed_theme_ids'] ?? null) ? $area['allowed_theme_ids'] : [];
                $current = (string) ($area['theme_id'] ?? '');
            @endphp
            <div class="voodbuilder-theme-assignments__row" wire:key="theme-assign-{{ $areaId }}">
                <div>
                    <div class="voodbuilder-theme-assignments__area">{{ $area['label'] ?? $areaId }}</div>
                    @if (! empty($area['description']))
                        <div class="voodbuilder-theme-assignments__meta">{{ $area['description'] }}</div>
                    @endif
                </div>
                <div>
                    <select wire:change="assignAreaTheme('{{ $areaId }}', $event.target.value)" aria-label="{{ __('voodbuilder::settings.theme_assignments_col_layout') }}">
                        @foreach ($allowed as $themeId)
                            <option value="{{ $themeId }}" @selected($themeId === $current)>
                                {{ $themeLabels[$themeId] ?? $themeId }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endforeach
    </div>
</div>
