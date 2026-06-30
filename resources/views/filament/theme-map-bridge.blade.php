<div class="voodbuilder-theme-map-bridge">
    <style>
        .voodbuilder-theme-map-bridge {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(226 232 240 / 0.9);
        }

        .dark .voodbuilder-theme-map-bridge {
            border-top-color: rgb(51 65 85 / 0.9);
        }

        #voodbuilder-theme-map-root {
            min-height: 520px;
        }
    </style>

    <script type="application/json" id="voodbuilder-theme-map-payload">@json($payload)</script>

    <div id="voodbuilder-theme-map-root" wire:ignore></div>

    @unless (\Voodflow\Voodbuilder\Support\ThemeMapAssets::isBuilt())
        <p class="text-sm text-warning-600 dark:text-warning-400">
            {{ __('voodbuilder::settings.theme_map_build_required') }}
        </p>
    @endunless
</div>
