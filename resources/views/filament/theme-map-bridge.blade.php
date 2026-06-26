<div class="vpress-theme-map-bridge">
    <style>
        .vpress-theme-map-bridge {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(226 232 240 / 0.9);
        }

        .dark .vpress-theme-map-bridge {
            border-top-color: rgb(51 65 85 / 0.9);
        }

        #vpress-theme-map-root {
            min-height: 320px;
        }
    </style>

    <script type="application/json" id="vpress-theme-map-payload">@json($payload)</script>

    <div id="vpress-theme-map-root" wire:ignore></div>

    @unless (\Voodflow\Vpress\Support\ThemeMapAssets::isBuilt())
        <p class="text-sm text-warning-600 dark:text-warning-400">
            {{ __('vpress::settings.theme_map_build_required') }}
        </p>
    @endunless
</div>
