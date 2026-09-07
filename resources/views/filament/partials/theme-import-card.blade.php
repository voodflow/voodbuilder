<label
    class="voodbuilder-themes-ws__card voodbuilder-themes-ws__card--import voodbuilder-themes-ws__import-label"
    wire:loading.class="is-importing"
    wire:target="importArchive"
>
    <div class="voodbuilder-themes-ws__card-strip voodbuilder-themes-ws__card-strip--import" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="voodbuilder-themes-ws__card-body">
        <span class="voodbuilder-themes-ws__card-name" wire:loading.remove wire:target="importArchive">
            {{ __('voodbuilder::settings.theme_workspace_import_card_title') }}
        </span>
        <span class="voodbuilder-themes-ws__card-name voodbuilder-themes-ws__import-loading" wire:loading wire:target="importArchive">
            {{ __('voodbuilder::settings.theme_workspace_importing') }}
        </span>
        <span class="voodbuilder-themes-ws__card-badge" wire:loading.remove wire:target="importArchive">
            {{ __('voodbuilder::settings.theme_workspace_import_card_subtitle') }}
        </span>
    </div>
    <span class="voodbuilder-themes-ws__card-icon" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
    </span>
    <input type="file" wire:model="importArchive" accept=".zip,application/zip" />
</label>
