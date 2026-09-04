<div class="voodbuilder-theme-map-bridge">
    <style>
        .voodbuilder-theme-map-bridge {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .voodbuilder-theme-assignments {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .voodbuilder-theme-assignments th,
        .voodbuilder-theme-assignments td {
            text-align: left;
            padding: 0.75rem 0.65rem;
            border-bottom: 1px solid rgb(226 232 240);
            vertical-align: top;
        }

        .dark .voodbuilder-theme-assignments th,
        .dark .voodbuilder-theme-assignments td {
            border-bottom-color: rgb(51 65 85);
        }

        .voodbuilder-theme-assignments th {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
            font-weight: 600;
        }

        .voodbuilder-theme-assignments__area {
            font-weight: 600;
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-theme-assignments__area {
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-assignments__meta {
            margin-top: 0.2rem;
            font-size: 0.75rem;
            line-height: 1.4;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-theme-assignments__meta {
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-assignments select {
            width: 100%;
            max-width: 16rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            background: rgb(255 255 255);
            color: rgb(15 23 42);
            padding: 0.45rem 0.55rem;
            font: inherit;
        }

        .dark .voodbuilder-theme-assignments select {
            border-color: rgb(71 85 105);
            background: rgb(15 23 42);
            color: rgb(248 250 252);
        }

        .voodbuilder-theme-assignments__pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.7rem;
            border: 1px solid rgb(226 232 240);
            color: rgb(71 85 105);
            white-space: nowrap;
        }

        .dark .voodbuilder-theme-assignments__pill {
            border-color: rgb(51 65 85);
            color: rgb(148 163 184);
        }

        .voodbuilder-theme-map-bridge__toolbar {
            display: flex;
            justify-content: flex-end;
        }

        .voodbuilder-theme-map-bridge__toolbar button {
            appearance: none;
            border: 0;
            background: transparent;
            color: rgb(14 165 233);
            font: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
        }

        #voodbuilder-theme-map-root {
            min-height: 640px;
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

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="voodbuilder-theme-assignments">
            <thead>
                <tr>
                    <th>{{ __('voodbuilder::settings.theme_assignments_col_area') }}</th>
                    <th>{{ __('voodbuilder::settings.theme_assignments_col_layout') }}</th>
                    <th>{{ __('voodbuilder::settings.theme_assignments_col_requires') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($areas as $area)
                    @php
                        $areaId = (string) ($area['id'] ?? '');
                        $allowed = is_array($area['allowed_theme_ids'] ?? null) ? $area['allowed_theme_ids'] : [];
                        $current = (string) ($area['theme_id'] ?? '');
                    @endphp
                    <tr wire:key="theme-assign-{{ $areaId }}">
                        <td>
                            <div class="voodbuilder-theme-assignments__area">{{ $area['label'] ?? $areaId }}</div>
                            @if (! empty($area['description']))
                                <div class="voodbuilder-theme-assignments__meta">{{ $area['description'] }}</div>
                            @endif
                            @php
                                $chrome = $area['chrome_layout'] ?? null;
                                $chromeLabel = is_array($chrome)
                                    ? (string) ($chrome['name'] ?? '')
                                    : (is_string($chrome) ? $chrome : '');
                            @endphp
                            @if ($chromeLabel !== '')
                                <div class="voodbuilder-theme-assignments__meta">
                                    {{ __('voodbuilder::settings.theme_map_chrome_layout') }}: {{ $chromeLabel }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <select
                                wire:change="assignAreaTheme('{{ $areaId }}', $event.target.value)"
                            >
                                @foreach ($allowed as $themeId)
                                    <option value="{{ $themeId }}" @selected($themeId === $current)>
                                        {{ $themeLabels[$themeId] ?? $themeId }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <span class="voodbuilder-theme-assignments__pill">
                                {{ $area['required_capability_label'] ?? '' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="voodbuilder-theme-map-bridge__toolbar">
        <button type="button" wire:click="toggleGraph">
            {{ $showGraph
                ? __('voodbuilder::settings.theme_assignments_hide_map')
                : __('voodbuilder::settings.theme_assignments_show_map') }}
        </button>
    </div>

    @if ($showGraph)
        <script type="application/json" id="voodbuilder-theme-map-payload">@json($payload)</script>
        <div id="voodbuilder-theme-map-root" wire:ignore></div>

        @unless (\Voodflow\Voodbuilder\Support\ThemeMapAssets::isBuilt())
            <p class="text-sm text-warning-600 dark:text-warning-400">
                {{ __('voodbuilder::settings.theme_map_build_required') }}
            </p>
        @endunless
    @endif
</div>
