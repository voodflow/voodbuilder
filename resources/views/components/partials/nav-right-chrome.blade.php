@php
    $desktopChromeClass = $desktopChromeClass ?? 'vp:block';
    $desktopFlexClass = $desktopFlexClass ?? 'vp:flex';
    $mobileToggleClass = $mobileToggleClass ?? 'hidden max-vp:inline-flex';
    $showNotificationBell = (bool) ($showNotificationBell ?? true);
    $canvasPreview = (bool) ($canvasPreview ?? false);
    $showSearch = (bool) ($showSearch ?? true);
    $showProfileMenu = (bool) ($showProfileMenu ?? true);
    $hideExtraMenu = (bool) ($hideExtraMenu ?? false);
    $hideMobileToggle = (bool) ($hideMobileToggle ?? false);

    $chromeHidden = static fn (bool $visible): string => $visible ? '' : 'data-voodbuilder-chrome-hidden';
    $chromeWrapperClass = $canvasPreview ? $desktopChromeClass : 'hidden '.$desktopChromeClass;
@endphp

<div class="flex shrink-0 items-center justify-end gap-2 vp:gap-3">
    @unless ($hideExtraMenu)
        <x-voodbuilder::menu
            menu="header_extra"
            class="hidden shrink-0 items-center {{ $desktopFlexClass }}"
            link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
            data-voodbuilder-desktop-nav
            :canvas-preview="$canvasPreview"
        />
    @endunless

    @if ($showSearch || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="search"
            {!! $canvasPreview ? $chromeHidden($showSearch) : '' !!}
        >
            @if ($canvasPreview)
                <div class="flex items-center" data-voodbuilder-search data-gjs-type="default" data-gjs-selectable="false">
                    <button
                        type="button"
                        class="voodbuilder-header-icon-btn text-vp-text-2"
                        data-voodbuilder-search-open
                        data-gjs-type="voodbuilder-chrome-button"
                        data-gjs-selectable="false"
                        aria-label="{{ __('voodbuilder::search.button') }}"
                    >
                        {{-- Tabler outline: search (subset only) --}}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" data-vb-chrome-icon="search"><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                    </button>
                </div>
            @else
                <x-voodbuilder::search :canvas-preview="false" />
            @endif
        </div>
    @endif

    @if ((config('voodbuilder.notifications.enabled', true) && $showNotificationBell) || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="notifications"
            {!! $canvasPreview ? $chromeHidden($showNotificationBell) : '' !!}
        >
            @if ($canvasPreview)
                <button
                    type="button"
                    class="voodbuilder-header-icon-btn relative text-vp-text-2"
                    data-voodbuilder-notification-bell-preview
                    data-gjs-type="voodbuilder-chrome-button"
                    data-gjs-selectable="false"
                    title="{{ __('voodbuilder::pro.grapesjs.blocks.site_header_bell_preview') }}"
                    aria-label="{{ __('voodbuilder::notifications.bell_label') }}"
                >
                    {{-- Tabler outline: bell (subset only) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" data-vb-chrome-icon="bell"><path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                </button>
            @else
                @auth
                    <livewire:voodbuilder.site-notification-bell wire:key="nav-bell-desktop" />
                @endauth
            @endif
        </div>
    @endif

    @if ($showProfileMenu || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="profile"
            {!! $canvasPreview ? $chromeHidden($showProfileMenu) : '' !!}
        >
            <x-voodbuilder::nav-profile-menu :canvas-preview="$canvasPreview" />
        </div>
    @endif

    <button
        type="button"
        @class([
            'voodbuilder-header-icon-btn',
            $mobileToggleClass,
        ])
        data-mobile-nav-toggle
        data-gjs-type="voodbuilder-chrome-button"
        data-gjs-selectable="false"
        aria-controls="voodbuilder-mobile-nav"
        aria-expanded="false"
        aria-label="{{ __('Open menu') }}"
        @if ($hideMobileToggle) hidden @endif
    >
        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" data-vb-chrome-icon="menu-2"><path d="M4 6l16 0"/><path d="M4 12l16 0"/><path d="M4 18l16 0"/></svg>
    </button>
</div>
