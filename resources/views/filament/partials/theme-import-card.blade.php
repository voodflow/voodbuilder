<label class="vpress-themes-ws__card vpress-themes-ws__card--import vpress-themes-ws__import-label" wire:loading.class="is-importing" wire:target="importArchive">
    <div class="vpress-themes-ws__card-strip vpress-themes-ws__card-strip--import" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="vpress-themes-ws__card-body">
        <span class="vpress-themes-ws__card-name">{{ __('vpress::settings.theme_workspace_import_card_title') }}</span>
        <span class="vpress-themes-ws__card-badge">{{ __('vpress::settings.theme_workspace_import_card_subtitle') }}</span>
        <span class="vpress-themes-ws__import-loading" wire:loading wire:target="importArchive">
            {{ __('vpress::settings.theme_workspace_importing') }}
        </span>
    </div>
    <span class="vpress-themes-ws__card-icon" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-7.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
        </svg>
    </span>
    <input type="file" wire:model="importArchive" accept=".zip,application/zip" />
</label>
